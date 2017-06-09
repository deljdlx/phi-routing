<?php
namespace Phi\Routing;


use Phi\HTTP\Header;
use Phi\Routing\Interfaces\Request;

class Route implements \Phi\Routing\Interfaces\Route
{


    protected $validator;
    protected $callback;
    protected $verbs = array();
    protected $parameters = array();
    protected $headers = array();
    protected $builders = array();
    protected $name = '';

    protected $matches = array();


    public function __construct($name, $verbs, $validator, $callback, $headers = array())
    {
        $this->validator = $validator;
        $this->callback = $callback;
        $this->verbs = array($verbs);
        $this->name = $name;
        $this->addHeaders($headers);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setBuilder($builder, $name = null)
    {

        if ($name === null) {
            $name = 0;
        }
        $this->builders[$name] = $builder;
        return $this;
    }

    public function build($parameters, $builderName = null)
    {

        if ($builderName === null) {
            $builderName = 0;
        }

        if (isset($this->builders[$builderName])) {
            $builder = $this->builders[$builderName];

            if (is_callable($builder)) {
                return call_user_func_array($builder, $parameters);
            } elseif (is_string($builder)) {

                $url = preg_replace_callback('`\{(.*?)\}`', function ($match) use ($parameters) {
                    $value = $parameters[$match[1]];
                    return $value;
                }, $builder);

                return $url;
            } elseif (is_string($this->validator)) {
                return $this->validator;
            }
        }

        throw new \RuntimeException('No URL builder with name "' . $builderName . '" for route "' . $this->getName() . '" and no valid pattern for URL building');
    }

    public function addHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addHeader($name, $value)
    {
        $this->headers[] = new Header($name, $value);
        return $this;
    }


    public function validate(Request $request)
    {
        $callString = $request->getURI();
        if (is_string($this->validator)) {
            $matches = array();
            if (preg_match_all($this->validator, $callString, $matches)) {

                $this->matches = $matches;

                if (!empty($matches)) {
                    array_shift($matches);
                    foreach ($matches as $key => $match) {
                        $this->parameters[$key] = $match[0];
                    }
                }
                return true;
            }
        } else if (is_closure($this->validator)) {
            $parameters = array();
            $closure = $this->validator->bindTo($this, $this);
            $validate = call_user_func_array(
                array($closure, '__invoke'),
                $parameters
            );
            if ($validate) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function getBindedParametersWithMethod($userParameters, $controllerName, $method)
    {
        $reflector = new \ReflectionMethod($controllerName, $method);
        $parameters = $reflector->getParameters();
        $callParameters = array();
        foreach ($parameters as $parameter) {
            if (isset($userParameters[$parameter->name])) {
                $callParameters[] = $userParameters[$parameter->name];
            } else if ($parameter->isOptional()) {
                $callParameters[] = $parameter->getDefaultValue();
            } else {
                throw new Exception('Method ' . $controllerName . '::' . $method . ' missing parameter ' . $parameter->name);
            }
        }
        return $callParameters;
    }

    public function execute()
    {
        $reflector = new \ReflectionFunction($this->callback);
        $parameters = $reflector->getParameters();
        $callParameters = array();
        foreach ($parameters as $index => $parameter) {
            if (isset($this->parameters[$parameter->name])) {
                $callParameters[] = $this->parameters[$parameter->name];
            } else if (isset($this->parameters[$index])) {
                $callParameters[] = $this->parameters[$index];
            } else if ($parameter->isOptional()) {
                $callParameters[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception('Route callback missing parameter : ' . $parameter->name);
            }
        }
        $callback = $this->callback->bindTo($this, $this);
        return call_user_func_array(
            array($callback, '__invoke'),
            $callParameters
        );
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getMatches() {
        return $this->matches;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}