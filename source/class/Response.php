<?php
namespace Phi\Routing;


use Phi\Routing\Interfaces\Request as PhiRequest;
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

    /**
     * @var array
     */
    protected $extraData = array();


    //protected $



    protected $executed = false;

    public function __construct($content = null)
    {
        $this->content = $content;
    }

    public function execute()
    {

        $returnValue = $this->route->execute();
        $buffer = $this->route->getOutput();
        $this->setContent($buffer);


        $this->executed = true;
        return $returnValue;
    }

    public function isExecuted()
    {
        return $this->executed;
    }


    public function addExtraData($key, $value)
    {
        $this->extraData[$key] = $value;
        return $this;
    }

    public function getExtraData($key = null) {
        if($key === null) {
            return $this->extraData;
        }
        else if(array_key_exists($key, $this->extraData)) {
            return $this->extraData[$key];
        }
        else {
            return null;
        }
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
    public function setRequest(PhiRequest $request)
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