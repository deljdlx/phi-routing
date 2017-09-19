<?php

namespace Phi\Routing;

use Phi\Event\Traits\Listenable;
use Phi\Routing\Interfaces\Request as IRequest;
use Phi\Event\Interfaces\Listenable as IListenable;
use Phi\Routing\Interfaces\Router as IRouter;

/**
 * Class Router
 * @package Phi
 * @param
 */
class Router implements IRouter, IListenable
{
    use Listenable;

    const EVENT_DEFAULT_REQUEST = 'EVENT_DEFAULT_REQUEST';

    /** @var Route[] */
    protected $routes = array();

    protected $headers = array();


    /**
     * @param Route $route
     * @param $name
     * @return $this
     */
    public function addRoute(Route $route, $name)
    {
        $route->addParentListenable($this);
        $this->routes[$name] = $route;
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
            new Route($name, 'get', $validator, $callback, $headers),
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
        return new HTTPRequest();
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

                if (!$outputBuffering) {
                    echo $buffer;
                }


                if ($returnValue) {
                    break;
                }
            }
        }

        return $responseCollection;
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





