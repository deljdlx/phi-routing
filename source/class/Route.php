<?php

namespace Phi\Routing;


use Phi\Core\Exception;
use Phi\Event\Traits\Listenable;
use Phi\HTTP\Header;
use Phi\Routing\Event\Match;
use Phi\Routing\Interfaces\Request;

class Route implements \Phi\Routing\Interfaces\Route
{

    use Listenable;

    protected $validator;
    protected $callback;
    protected $verbs = array();
    protected $parameters = null;

    /** @var Header[] */
    protected $headers = array();
    protected $builders = array();
    protected $name = '';

    protected $matches = array();

    protected $parametersExtractor = null;

    /** @var Request */
    protected $request = null;


    public function __construct($verbs, $validator, $callback, $headers = array(), $name = null)
    {
        $this->validator = $validator;
        $this->callback = $callback;
        $this->verbs = (array) $verbs;
        $this->headers = $headers;
        $this->name = $name;
    }

    public function setRequest(Request $request)
    {
      $this->request = $request;
      return $this;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @param $builder
     * @param null $name
     * @return $this
     */
    public function setBuilder($builder, $name = null)
    {

        if ($name === null) {
            $name = 0;
        }

        if($name === null) {
            $this->builders[] = $builder;
        }
        else {
            $this->builders[$name] = $builder;
        }

        return $this;
    }

    public function buildURL($parameters, $builderName = null)
    {

        if ($builderName === null) {
            $builderName = 0;
        }

        if (isset($this->builders[$builderName])) {
            $builder = $this->builders[$builderName];

            if (is_callable($builder)) {
                return call_user_func_array($builder, $parameters);
            }
            elseif (is_string($builder)) {

                $url = preg_replace_callback('`\{(.*?)\}`', function ($match) use ($parameters) {
                    $value = $parameters[$match[1]];
                    return $value;
                }, $builder);

                return $url;
            }
            elseif (is_string($this->validator)) {
                return $this->validator;
            }
        }

        throw new \RuntimeException('No URL builder with name "' . $builderName . '" for route "' . $this->getName() . '" and no valid pattern for URL building');
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


    /**
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request = null)
    {

        if($request) {
            $this->setRequest($request);
        }

        $callString = $this->request->getURI();


        if($this->request->isHTTP()) {
            $requestVerb = $this->request->getVerb();

            if(!in_array($requestVerb, $this->verbs) && $requestVerb !=='*') {
                return false;
            }
        }

        if (is_string($this->validator)) {
            $matches = array();

            if (preg_match_all($this->validator, $callString, $matches)) {
                $this->matches = $matches;
                $this->parameters = $this->extractParameters($this->request);
                $this->fireEvent(new Match($this));
                return true;
            }
        }
        else if (isClosure($this->validator)) {
            $parameters = array();
            $closure = $this->validator->bindTo($this, $this);
            $validate = call_user_func_array(
                array($closure, '__invoke'),
                $parameters
            );
            if ($validate) {
                $this->parameters = $this->extractParameters($this->request);
                $this->fireEvent(new Match($this));
                return true;
            }
            else {
                return false;
            }
        }
        else if(is_bool($this->validator)) {
            return $this->validator;
        }
        return false;
    }

    public function extractParameters($request)
    {

        if ($this->parametersExtractor) {
            if(is_callable($this->parametersExtractor)) {
                return call_user_func_array(
                    $this->parametersExtractor,
                    array($request)
                );
            }
            throw new Exception('Route parameters extractor is set but is not a callable');
        }


        $reflector = new \ReflectionFunction($this->callback);
        $callbackParameters = $reflector->getParameters();

        $getParameters = array();
        if (is_array($this->matches) && array_key_exists(1, $this->matches)) {
            $getParameters = $this->matches[1];
        }


        $extractedParameters = array();
        foreach ($getParameters as $key => $value) {
            if (is_array($value) && array_key_exists(0, $value)) {
                $extractedParameters[$key] = $value[0];
            }
            else {
                $extractedParameters[$key] = $value;
            }
        }

        $realParameters = array();
        foreach ($callbackParameters as $index => $parameter) {
            if (array_key_exists($parameter->getName(), $extractedParameters)) {
                $realParameters[$parameter->getName()] = $extractedParameters[$parameter->getName()];
            }
            elseif (array_key_exists($index, $extractedParameters)) {
                $realParameters[$parameter->getName()] = $extractedParameters[$index];
            }
            elseif ($parameter->isOptional()) {
                $realParameters[$parameter->getName()] = $parameter->getDefaultValue();
            }
            else {
                $realParameters[$parameter->getName()] = null;
            }
        }
        return $realParameters;
    }

    public function setParameterExtractor($callable)
    {
        if(!is_callable($callable)) {
            throw new Exception('Parameters extrator must be a callable');
        }

        $this->parametersExtractor = $callable;
        return $this;
    }


    public function execute()
    {

        $reflector = new \ReflectionFunction($this->callback);
        $parameters = $reflector->getParameters();


        $callParameters = array();
        foreach ($parameters as $index => $parameter) {
            if (isset($this->parameters[$parameter->name])) {
                $callParameters[] = $this->parameters[$parameter->name];
            }
            else if (isset($this->parameters[$index])) {
                $callParameters[] = $this->parameters[$index];
            }
            else if ($parameter->isOptional()) {
                $callParameters[] = $parameter->getDefaultValue();
            }
            else {
                throw new \Exception('Route callback missing parameter : ' . $parameter->name);
            }
        }
        $callback = $this->callback->bindTo($this, $this);
        return call_user_func_array(
            array($callback, '__invoke'),
            $callParameters
        );
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        if ($this->parameters === null) {
            $this->parameters = $this->extractParameters();
        }
        return $this->parameters;
    }


    /**
     * @return array
     */
    public function getMatches()
    {
        return $this->matches;
    }


    /**
     * @return Header[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
