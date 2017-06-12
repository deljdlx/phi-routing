<?php
namespace Phi\Routing;


use Phi\Routing\Interfaces\Request;
use \Phi\HTTP\Response as PhiResponse;

class Response
{

    /**
     * @var string
     */
    protected $content;
    /**
     * @var Route
     */
    protected $route;

    /**
     * @var Request
     */
    protected $request;

    public function __construct($content = null)
    {
        $this->content = $content;
    }

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }


    /**
     * @param Route $route
     * @return $this
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;
        return $this;
    }


    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return PhiResponse
     */
    public function getHTTPResponse()
    {
        $response = new PhiResponse($this->content, $this->route->getHeaders());
        return $response;
    }


}