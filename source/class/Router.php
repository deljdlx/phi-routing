<?php

namespace Phi\Routing;

use Phi\Event\Traits\Listenable;
use Phi\HTTP\Header;
use Phi\Routing\Interfaces\Request as IRequest;


/**
 * Class Router
 * @package Phi
 * @param
 */
class Router implements \Phi\Routing\Interfaces\Router
{
    use Listenable;

    const EVENT_DEFAULT_REQUEST='EVENT_DEFAULT_REQUEST';


    protected $routes = array();
    protected $headers = array();


    public function addRoute(Route $route, $name)
    {
        $this->routes[$name] = $route;
        return $route;
    }


    public function getRouteByName($name)
    {
        if (isset($this->routes[$name])) {
            return $this->routes[$name];
        } else {
            throw new Exception('Route with name "' . $name . '" does not exist');
        }
    }


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
     * @return ResponseCollection
     */
    public function route(IRequest $request = null)
    {

        if ($request == null) {
            $request = $this->getDefaultRequest();
            $this->fireEvent(
                static::EVENT_DEFAULT_REQUEST,
                array(
                    'request'=>$request
                )
            );
        }


        $responseCollection = new ResponseCollection();

        foreach ($this->routes as $route) {
            /**
             * @var \Phi\Route $route
             */

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

                if ($returnValue) {
                    break;
                }
            }
        }

        return $responseCollection;

        /*
        if ($request->isHTTP()) {
            $this->sendHeaders();
        }
        echo $buffer;
        */
    }


    public function sendHeaders()
    {
        foreach ($this->headers as $header) {
            $header->send();
        }
        return $this;
    }

    public function build($routeName, $parameters)
    {
        $route = $this->getRouteByName($routeName);
        return $route->build($parameters);
    }
}





