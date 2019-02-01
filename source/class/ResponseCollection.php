<?php

namespace Phi\Routing;


use Phi\HTTP\Header;
use Phi\Traits\Collection;

class ResponseCollection
{

    use Collection;


    protected $executedResponses = array();



    public function execute()
    {

        foreach ($this->getResponses() as $response) {

            $returnValue = $response->execute();

            $this->executedResponses[] = $response;

            if(!$returnValue) {
                return false;
            }
        }

        return true;

    }


    public function isEmpty()
    {
        if(!count($this->getResponses())) {
            return true;
        }
        return false;
    }


    public function getExecutedResponses()
    {
        return $this->executedResponses;
    }



    /**
     * @param Response $response
     * @return $this
     */
    public function addResponse(Response $response)
    {
        $this->push($response);
        return $this;
    }

    /**
     * @return Response[]
     */
    public function getResponses()
    {
        return $this->getAll();
    }


    public function __toString()
    {
        $buffer = '';

        $responses = $this->getAll();

        foreach ($responses as $response) {
            $buffer .= $response->getContent();
        }
        return $buffer;
    }


    /**
     * @return Header[]
     */
    public function getHeaders()
    {

        /**
         * @var Header[] $headers
         */
        $headers = array();

        $responses = $this->getAll();

        foreach ($responses as $response) {

            if($response->isExecuted()) {
                if ($response->getRequest()->isHTTP()) {
                    $headers = array_merge($headers, $response->getHTTPResponse()->getHeaders());
                }
            }
        }

        return $headers;
    }


    /**
     * @return $this
     */
    public function send($flush = true)
    {
        $buffer = $this->__toString();
        $headers = $this->getHeaders();

        foreach ($headers as $header) {
            $header->send();
        }

        if($flush) {
            echo $buffer;
        }
        return $buffer;
    }


}