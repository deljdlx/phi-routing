<?php

namespace Phi\Routing;


use Phi\HTTP\Header;
use Phi\Traits\Collection;

class ResponseCollection
{

    use Collection;



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

            if ($response->getRequest()->isHTTP()) {
                $headers = array_merge($headers, $response->getHTTPResponse()->getHeaders());
            }
        }
        return $headers;
    }


    /**
     * @return $this
     */
    public function send()
    {
        $buffer = $this->__toString();
        $headers = $this->getHeaders();

        foreach ($headers as $header) {
            $header->send();
        }

        echo $buffer;
        return $this;
    }


}