<?php

namespace Phi\Routing;

use Phi\Event\Traits\Listenable;
use Phi\Routing\Interfaces\Request as IRequest;
use Phi\Event\Interfaces\Listenable as IListenable;
use Phi\Routing\Interfaces\Router as IRouter;
use Phi\Routing\Request\HTTP;

/**
 * Class Router
 * @package Phi
 * @param
 */
class Router implements IRouter, IListenable
{
    use Listenable;

    const EVENT_DEFAULT_REQUEST = 'EVENT_DEFAULT_REQUEST';

    const STATUS_SUCCESS  = 0;
    const STATUS_FAIL  = 1;

    const SUBROUTER_PATTERN_KEY = 'pattern';
    const SUBROUTER_ROUTER_KEY = 'router';



    /** @var Route[] */
    protected $routes = array();

    protected $headers = array();

    protected $subRouters = array();

    protected $status = self::STATUS_FAIL;


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
            $this->routes[$name] = $route;
        }

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



    //regexp permettant de valider la fin d'une url se termine sois par "/", "?....." ou fin d'url ($)
    public function getEndRouteRegexp()
    {
        return '(:?/|\?|$)';
    }


    protected function getDefaultRequest()
    {
        return new HTTP();
    }


    /**
     * @param IRequest|null $request
     * @param bool outputBuffering
     * @return ResponseCollection
     */
    public function route(IRequest $request = null, $outputBuffering = false)
    {

        if ($request == null) {
            $request = $this->getDefaultRequest();
            $this->fireEvent(
                static::EVENT_DEFAULT_REQUEST,
                array(
                    'request' => $request
                )
            );
        }


        $responseCollection = new ResponseCollection();

        $subRouterResponses = $this->routeSubRouter($request);

        if(!empty($subRouterResponses)) {
            foreach ($subRouterResponses as $response) {
                $responseCollection->addResponse($response);
            }
            $this->status = self::STATUS_SUCCESS;
        }


        if($this->status !== self::STATUS_SUCCESS) {
            foreach ($this->routes as $route) {

                $route->setRequest($request);
                if ($route->validate($request)) {

                    $response = new Response();
                    $response
                        ->setRequest($request)
                        ->setRoute($route);

                    $responseCollection->addResponse($response);

                    ob_start();
                    $returnValue = $route->execute();
                    $buffer = ob_get_clean();

                    $response->setContent($buffer);

                    $this->status = self::STATUS_SUCCESS;

                    if (!$returnValue) {
                        break;
                    }
                }
            }
        }



        if(!$outputBuffering) {
            $responseCollection->send();
        }

        return $responseCollection;
    }


    protected function routeSubRouter($request)
    {

        foreach ($this->subRouters as $name => $subRouterDescriptor) {

            $pattern = $subRouterDescriptor[self::SUBROUTER_PATTERN_KEY];
            $subRouter =  $subRouterDescriptor[self::SUBROUTER_ROUTER_KEY];


            if(preg_match($pattern, $request->getURI())) {

                $subRequest = clone $request;

                $uri = $request->getURI();

                $subURI = preg_replace($pattern, '', $uri);


                $subRequest->setURI($subURI);

                $responses = $subRouter->route($subRequest);

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
     * @return $this
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $header) {
            $header->send();
        }
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
        return $route->build($parameters);
    }
}





