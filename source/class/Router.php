<?php

namespace Phi\Routing;



use Phi\Routing\Exception\NotFound;

class Router
{

    /**
     * @var Route[]
     */
    private $routes = [];

    private $request;


    public function __construct()
    {

    }


    /**
     * @param $routeName
     * @param Route $route
     * @return $this
     * @throws Exception
     */
    public function addRoute($routeName, Route $route)
    {
        if(!is_string($routeName)) {
            throw new Exception('Route name must be a string');
        }

        if(array_key_exists($routeName, $this->routes)) {
            throw new Exception('A route name "'.$routeName.'" is already registered');
        }
        $this->routes[$routeName] = $route;
        return $this;
    }

    /**
     * @param $routeName
     * @param $validator
     * @param $callback
     * @return Route
     */
    public function get($routeName, $validator, $callback)
    {
        $route = new Route(\Phi\HTTP\Request::VERB_GET, $validator, $callback);
        $this->addRoute($routeName, $route);
        return $route;
    }

    public function post($routeName, $validator, $callback)
    {
        $route = new Route(\Phi\HTTP\Request::VERB_POST, $validator, $callback);
        $this->addRoute($routeName, $route);
        return $route;
    }

    public function delete($routeName, $validator, $callback)
    {
        $route = new Route(\Phi\HTTP\Request::VERB_DELETE, $validator, $callback);
        $this->addRoute($routeName, $route);
        return $route;
    }


    /**
     * @param Request $request
     * @return Response
     */
    public function route(Request $request)
    {
        $this->request = $request;


        foreach ($this->routes as $route) {

            if($route->validate($request)) {
                $route->execute($request);
                return $route->getResponse();
            }
        }

        throw new NotFound();

    }


}

