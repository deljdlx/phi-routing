<?php

namespace Phi\Routing;


use Phi\Core\Exception;
use Phi\Event\Traits\Listenable;
use Phi\HTTP\Header;
use Phi\Routing\Event\Match;
use Phi\Routing\Interfaces\Request as IRequest;
use Phi\Traits\Collection;
use Phi\Traits\HasDependency;

class Route implements \Phi\Routing\Interfaces\Route
{

    use Listenable;
    use Collection;

    protected $validator;


    protected $beforeHook;
    protected $afterHook;


    protected $callback;
    protected $verbs = array();
    protected $parameters = null;

    protected $isFinal = true;


    protected $output;
    protected $executionStatus = null;


    /** @var Header[] */
    protected $headers = array();
    protected $builders = array();
    protected $name = '';

    protected $matches = array();

    protected $parametersExtractor = null;

    /** @var IRequest */
    protected $request = null;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Router
     */
    protected $router;


    public function __construct($verbs = 'get', $validator = false, $callback = null, $headers = array(), $name = null)
    {

        $this->validator = $validator;
        $this->callback = $callback;
        $this->verbs = (array) $verbs;
        $this->headers = $headers;
        $this->name = $name;
    }


    public function getBuilders()
    {
        return $this->builders;
    }


    public function isFinal($value = null)
    {
        if($value == null) {
            $this->isFinal = $value;
            return $this;
        }
        else {
            return $this->isFinal();
        }
    }



    //regexp permettant de valider la fin d'une url se termine sois par "/", "?....." ou fin d'url ($)
    public static function getEndRouteRegexp()
    {
        return '(:?/|\?|$|&)';
    }


    /**
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }


    /**
     * @param Router $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }


    public function loadFromRoute(Route $route)
    {
        $this->validator = $route->validator;
        $this->callback = $route->callback;
        $this->verbs = $route->verbs;
        $this->headers = $route->headers;
        $this->name = $route->name;
    }



    public function doBefore($callable)
    {
        $this->beforeHook = $callable;
        return $this;
    }

    public function doAfter($callable)
    {
        $this->afterHook = $callable;
        return $this;
    }



    public function data($name = null)
    {
        return $this->request->data($name);
    }

    public function post($name = null)
    {
        return $this->request->post($name);
    }

    public function get($name = null)
    {
        return $this->request->get($name);
    }




    public function setRequest(IRequest $request)
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



    public function contentType($contentType)
    {
        $this->addHeader('Content-type', $contentType);
        return $this;
    }


    public function json()
    {
        $this->contentType('application/json');
        return $this;
    }

    public function text()
    {
        $this->contentType('text/plain');
        return $this;
    }



    public function html($charset = 'utf-8')
    {
        $this->contentType('text/html; charset="'.$charset.'"');
        return $this;
    }

    public function javascript($charset = 'utf-8')
    {
        $this->contentType('application/javascript; charset="'.$charset.'"');
        return $this;
    }



    public function redirect($url)
    {
        $this->addHeader('Location', $url);
        return $this;
    }

    public function error404()
    {
        $this->addHeader('HTTP/1.0 404 Not Found');
        return $this;
    }





    /**
     * @param $builder
     * @param null $name
     * @return $this
     */
    public function setBuilder($builder, $name = null, array $parametersDesriptors = null)
    {

        if ($name === null) {
            $name = 0;
        }

        $builderInstance = new RouteBuildder($this, $builder, $name);

        if(!empty($parametersDesriptors)) {
            $builderInstance->setParametersByDescriptor($parametersDesriptors);
        }


        if($name === null) {
            $this->builders[] = $builderInstance;
        }
        else {
            $this->builders[$name] = $builderInstance;
        }

        return $this;
    }

    public function buildURL($parameters = array(), $builderName = null)
    {

        if ($builderName === null) {
            $builderName = 0;
        }

        if (array_key_exists($builderName, $this->builders)) {
            $builder = $this->builders[$builderName];
            return $builder->getURL($parameters);
        }

        throw new \RuntimeException('No URL builder with name "' . $builderName . '" for route "' . $this->getName() . '" and no valid pattern for URL building');
    }


    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addHeader($header, $value = null)
    {

        if($header instanceof Header) {
            $this->headers[] = $header;
        }
        else {
            $this->headers[] = new Header($header, $value);
        }
        return $this;
    }

    public function getValidator()
    {
        return $this->validator;
    }


    /**
     * @param Request $request
     * @return bool
     */
    public function validate(IRequest $request = null, array $variables = array())
    {

        if($request) {
            $this->setRequest($request);
        }

        $callString = $this->request->getURI();


        if($this->request->isHTTP()) {
            $requestVerb = $this->request->getVerb();




                $verbValid = false;
                foreach ($this->verbs as $verb) {

                    if($verb === '*') {
                        $verbValid = true;
                        break;
                    }
                    if(strtoupper($verb) == strtoupper($requestVerb)) {
                        $verbValid = true;
                        break;
                    }
                }
                if(!$verbValid) {
                    return false;
                }

        }



        if (is_string($this->validator)) {
            $matches = array();

            if (preg_match_all($this->validator, $callString, $matches, PREG_SET_ORDER)) {

                array_shift($matches[0]);

                $this->matches = $matches[0];


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


        if (is_array($this->matches)) {
            $getParameters = $this->matches;
        }


        $extractedParameters = array();
        foreach ($getParameters as $key => $value) {
            $extractedParameters[$key] = urldecode($value);
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


    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }


    public function execute(array $parameters = null)
    {

        if($parameters !== null) {
            foreach ($parameters as $key => $value) {
                $this->setParameter($key, $value);
            }
        }

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
                throw new \Exception('Route callback missing parameter : [' . $parameter->name.'] for route ['.$this->name.']');
            }
        }


        $callback = $this->callback->bindTo($this, $this);

        $preHookValue = true;
        $returnValue = null;

        ob_start();


        if(is_callable($this->beforeHook)) {



            $preHookValue = call_user_func_array($this->beforeHook, array(
                $this
            ));
        }

        if($preHookValue) {
            $returnValue = call_user_func_array(
                array($callback, '__invoke'),
                $callParameters
            );
        }

        if(is_callable($this->afterHook)) {
            $closure = $this->afterHook->bindTo($this, $this);
            call_user_func_array($closure, array(
                $this
            ));
        }

        $this->output = ob_get_clean();

        $this->executionStatus = $returnValue;

        return $returnValue;
    }
    public function getStatus()
    {
        return $this->executionStatus;
    }



    public function getOutput()
    {
        return $this->output;
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
