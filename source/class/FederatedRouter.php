<?php

namespace Phi\Routing;


use Phi\Event\Traits\Listenable;
use Phi\HTTP\Header;

class FederatedRouter
{

    use Listenable;


    const DEFAULT_ROUTER = 'MAIN';


    const EVENT_RUN_BEFORE_ROUTING = 'EVENT_RUN_BEFORE_ROUTING';
    const EVENT_RUN_AFTER_ROUTING = 'EVENT_RUN_AFTER_ROUTING';

    const EVENT_RUN_BEFORE_ROUTE_EXECUTION = 'EVENT_RUN_BEFORE_ROUTE_EXECUTION';
    const EVENT_RUN_AFTER_ROUTE_EXECUTION = 'EVENT_RUN_AFTER_ROUTE_EXECUTION';


    const EVENT_NO_RESPONSE = 'EVENT_NO_RESPONSE';

    const EVENT_SUCCESS = 'EVENT_SUCCESS';

    /**
     * @var Request
     */
    protected $request;


    /**
     * @var Router[]
     */
    private $routers;

    /**
     * @var Header[]
     */
    private $headers;


    /**
     * @var ResponseCollection
     */
    private $responsesCollections;


    /**
     * @var string
     */
    private $output;


    /**
     * @var Route[]
     */
    protected $executedRoutes;


    public function __construct()
    {
        $this->addRouter(
            new Router(),
            self::DEFAULT_ROUTER
        );
    }

    /**
     * @param null $request
     * @param array $variables
     * @return Route[]
     */
    public function getValidatedRoutes($request = null, array $variables = array())
    {

        if ($request == null) {
            $request = Request::getInstance();
        }
        elseif (is_string($request)) {
            $uri = $request;
            $request = new Request();
            $request->setURI($uri);
        }


        $responsesCollections = array();
        $routes = array();

        foreach ($this->routers as $key => $router) {
            $collection = $router->route($request, $variables, $responsesCollections);

            if (!$collection->isEmpty()) {

                $responses = $collection->getResponses();
                foreach ($responses as $response) {
                    $routes[] = $response->getRoute();
                }
            }
        }

        return $routes;

    }


    /**
     * @return Route[]
     */
    public function getExecutedRoutes()
    {
        return $this->executedRoutes;
    }


    public function route($request = null, array $variables = array())
    {

        $this->headers = array();

        if ($request == null) {
            $request = Request::getInstance();
        }

        $this->responsesCollections = array();


        $routers = $this->routers;


        foreach ($routers as $router) {
            $collection = $router->route($request, $variables, $this->executedRoutes);
            if (!$collection->isEmpty()) {
                $this->responsesCollections[] = $collection;
            }
        }


        $this->fireEvent(
            static::EVENT_RUN_AFTER_ROUTING,
            array(
                'request' => $this->request,
                'application' => $this
            )
        );


        $this->fireEvent(
            static::EVENT_RUN_BEFORE_ROUTE_EXECUTION,
            array(
                'request' => $this->request,
                'application' => $this
            )
        );

        //=======================================================

        foreach ($this->responsesCollections as $collection) {
            $continue = $collection->execute();
            if ($continue !== true) {
                break;
            }
        }


        //=======================================================


        $this->fireEvent(
            static::EVENT_RUN_AFTER_ROUTE_EXECUTION,
            array(
                'request' => $this->request,
                'application' => $this
            )
        );

        $noResponse = true;
        foreach ($this->responsesCollections as $collection) {
            if (!empty($collection->getExecutedResponses())) {
                $noResponse = false;
                break;
            }
        }


        if ($noResponse) {

            $this->fireEvent(
                static::EVENT_NO_RESPONSE,
                array(
                    'request' => $this->request,
                    'application' => $this
                )
            );
        }
        else {
            $this->fireEvent(
                static::EVENT_SUCCESS,
                array(
                    'request' => $this->request,
                    'application' => $this
                )
            );
        }


        $output = '';

        foreach ($this->responsesCollections as $collection) {
            $this->headers = array_merge($this->headers, $collection->getHeaders());
            $output .= $collection->__toString($this->headers);
        }


        $this->output = $output;
    }


    public function getRouterByName($routerName)
    {
        if (array_key_exists($routerName, $this->routers)) {
            return $this->routers[$routerName];
        }
        else {
            throw new Exception('No router with name "' . $routerName . '" registered');
        }
    }




    /**
     * @param null $request
     * @param array $variables
     * @return bool
     */
    public function hasValidRoute($request = null, array $variables = array())
    {


        if ($request == null) {
            $request = Request::getInstance();
        }

        $this->responsesCollections = array();

        foreach ($this->routers as $router) {


            $collection = $router->route($request, $variables, $this->executedRoutes);

            if (!$collection->isEmpty()) {
                $this->responsesCollections[] = $collection;
            }
        }


        if(!empty($this->responsesCollections)) {
            return true;
        }

        return false;

    }


    /**
     * @return Header[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }


    /**
     * @return Router[]
     */
    public function getRouters()
    {
        return $this->routers;
    }


    public function addRouter(Router $router, $name = null)
    {
        if($name === null) {
            $name = get_class($router);
        }

        $this->routers[$name] = $router;

        return $this;

    }

    public function getResponsesCollections()
    {
        return $this->responsesCollections;
    }


    public function executeRoute($routerName, $routeId)
    {
        return $this->getRouterByName($routerName)->executeRoute($routeId);
    }

    public function getOutput()
    {
        return $this->output;
    }


}

