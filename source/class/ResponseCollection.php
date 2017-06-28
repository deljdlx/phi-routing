<?php
namespace Phi\Routing;


use Phi\HTTP\Header;

class ResponseCollection
{

    /**
     * @var Response[]
     */
    protected $responses;


    /**
     * @param Response $response
     * @return $this
     */
    public function addResponse(Response $response)
    {
        $this->responses[] = $response;
        return $this;
    }

    /**
     * @return Response[]
     */
    public function getResponses()
    {
        return $this->responses;
    }


    public function __toString()
    {
        $buffer = '';
        foreach ($this->responses as $response) {
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

        foreach ($this->responses as $response) {

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