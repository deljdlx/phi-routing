<?php

namespace Phi\Routing;

use Phi\Core\Exception;
use Phi\Event\Traits\Listenable;
use Phi\HTTP\Header;
use Phi\Routing\Interfaces\Request as IRequest;
use Phi\Event\Interfaces\Listenable as IListenable;
use Phi\Routing\Interfaces\Router as IRouter;
use Phi\Routing\Request\HTTP;
use Phi\Traits\Collection;
use Phi\Traits\HasDependency;

/**
 * Class Router
 * @package Phi
 * @param
 */
class Router implements IRouter, IListenable
{
    use Listenable;
    use Collection;

    const EVENT_DEFAULT_REQUEST = 'EVENT_DEFAULT_REQUEST';
    const EVENT_ROUTING_START = 'EVENT_ROUTING_START';

    const STATUS_SUCCESS  = 0;
    const STATUS_FAIL  = 1;

    const SUBROUTER_PATTERN_KEY = 'pattern';
    const SUBROUTER_ROUTER_KEY = 'router';



    /** @var Route[] */
    protected $routes = array();


    /**
     * @var Header[]
     */
    protected $headers = array();

    protected $subRouters = array();

    protected $status = self::STATUS_FAIL;


    protected $validators = array();



    public function __construct()
    {
        $this->registerRoutes();
    }

    public function registerRoutes()
    {
        return $this;
    }

    public function addValidator($validator)
    {
        $this->validators[] = $validator;
        return $this;
    }


    /**
     * @param Route $route
     * @param $name
     * @return $this
     */
    public function addRoute(Route $route, $name = null)
    {

        $route->addParentListenable($this);

        if ($name === null) {
            $this->routes[] = $route;
        }
        else {
            if(array_key_exists($name, $this->routes)) {
                throw new Exception('An route with name '.$name.' already exists');
            }
            $this->routes[$name] = $route;
        }

        $route->setRouter($this);

        return $route;
    }


    public function addRouter(IRouter $router, $name, $pattern = null)
    {
        $this->subRouters[$name] = array(
            self::SUBROUTER_PATTERN_KEY => $pattern,
            self::SUBROUTER_ROUTER_KEY => $router
        );
        return $this;
    }




    /**
     * @param $name
     * @return Route
     */
    public function getRouteByName($name)
    {
        if (isset($this->routes[$name])) {
            return $this->routes[$name];
        }
        else {
            throw new Exception('Route with name "' . $name . '" does not exist');
        }
    }

    /**
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }





    /**
     * @param $name
     * @param $validator
     * @param $callback
     * @param array $headers
     * @return Router
     */
    public function all($name, $validator, $callback, $headers = array())
    {
        return $this->addRoute(
            new Route('*', $validator, $callback, $headers, $name),
            $name
        );
    }


    /**
     * @param $name
     * @param $validator
     * @param $callback
     * @param array $headers
     * @return Router
     */
    public function get($name, $validator, $callback, $headers = array())
    {
        return $this->addRoute(
            new Route('get', $validator, $callback, $headers, $name),
            $name
        );
    }

    /**
     * @param $name
     * @param $validator
     * @param $callback
     * @param array $headers
     * @return Router
     */
    public function post($name, $validator, $callback, $headers = array())
    {
        return $this->addRoute(
            new Route('post', $validator, $callback, $headers, $name),
            $name
        );
    }


    /**
     * @param $name
     * @param $validator
     * @param $callback
     * @param array $headers
     * @return Router
     */
    public function delete($name, $validator, $callback, $headers = array())
    {
        return $this->addRoute(
            new Route('delete', $validator, $callback, $headers, $name),
            $name
        );
    }





    protected function getDefaultRequest()
    {
        return new HTTP();
    }


    /**
     * @param $routeId
     * @return bool|Route
     */
    public function executeRoute($routeId)
    {
        foreach ($this->routes as $route) {

            if ($route->getName() == $routeId) {
                $route->execute();
                $this->status = self::STATUS_SUCCESS;
                return $route;
            }
        }
        return false;
    }

    /**
     * @param IRequest|null $request
     * @param bool outputBuffering
     * @return ResponseCollection
     */
    public function route(IRequest $request = null, array $variables = array(),  &$executedRoutes = null)
    {

        if ($request === null) {
            $request = $this->getDefaultRequest();
            $this->fireEvent(
                static::EVENT_DEFAULT_REQUEST,
                array(
                    'request' => $request
                )
            );
        }


        $variables = array_merge($this->getVariables(), $variables);


        $this->fireEvent(
            static::EVENT_ROUTING_START,
            array(
                'request' => $request
            )
        );

        $responseCollection = new ResponseCollection();

        foreach ($this->validators as $validator) {
            if(is_string($validator)) {
                if(!preg_match_all($validator, $request->getURI())) {
                    return $responseCollection;
                }
            }
            else if(is_callable($validator)) {
                if($this->isClosure($validator)) {

                    $validator = $validator->bindTo($this, $this);
                }

                if(!call_user_func_array($validator, array($request))) {
                    return $responseCollection;
                }
            }
        }




        $subRouterResponses = $this->routeSubRouter($request, $variables);

        if(!empty($subRouterResponses)) {
            foreach ($subRouterResponses as $response) {
                $responseCollection->addResponse($response);
            }
            $this->status = self::STATUS_SUCCESS;
        }



        if($this->status !== self::STATUS_SUCCESS) {
            foreach ($this->routes as $route) {


                $route->setVariables(
                    $variables
                );

                $route->setRequest($request);

                if ($route->validate($request)) {

                    $executedRoutes[] = $route;

                    $response = new Response();
                    $response
                        ->setRequest($request)
                        ->setRoute($route);

                    $route->setResponse($response);

                    $responseCollection->addResponse($response);

                    if($route->isFinal()) {
                        break;
                    }

                }
            }
        }


        return $responseCollection;
    }


    protected function routeSubRouter($request, array $variables = array())
    {

        foreach ($this->subRouters as $name => $subRouterDescriptor) {

            $pattern = $subRouterDescriptor[self::SUBROUTER_PATTERN_KEY];
            $subRouter =  $subRouterDescriptor[self::SUBROUTER_ROUTER_KEY];


            if(preg_match($pattern, $request->getURI())) {

                $subRequest = clone $request;

                $uri = $request->getURI();

                $subURI = preg_replace($pattern, '', $uri);


                $subRequest->setURI($subURI);

                $responses = $subRouter->route($subRequest, $variables);

                if($subRouter->getStatus() === self::STATUS_SUCCESS) {
                    return $responses;
                }
            }
        }
        return false;
    }



    public function getStatus()
    {
        return $this->status;
    }



    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addHeader($name, $value = null)
    {
        $this->headers[] = new Header($name, $value);
        return $this;
    }



    /**
     * @return $this
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $header) {
            $header->send();
        }

        return $this;
    }

    public function error404()
    {
        $this->addHeader('HTTP/1.0 404 Not Found');
        return $this;
    }

    public function redirect($url)
    {
        $this->addHeader('Location', $url);
        return $this;
    }



    /**
     * @param $routeName
     * @param $parameters
     * @return string
     */
    public function build($routeName, $parameters)
    {
        $route = $this->getRouteByName($routeName);
        return $route->buildURL($parameters);
    }



    protected function isClosure($variable)
    {
        return is_object($variable) && ($variable instanceof Closure);
    }





}





