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


    /**
     * @param bool $flush
     * @return $this
     */
    public function send($flush=true)
    {
        $buffer = '';

        /**
         * @var Header[] $headers
         */
        $headers = array();

        foreach ($this->responses as $response) {
            $buffer .= $response->getContent();

            if ($response->getRequest()->isHTTP()) {
                $headers = array_merge($headers, $response->getHTTPResponse()->getHeaders());
            }
        }

        foreach ($headers as $header) {
            $header->send();
        }

        if($flush) {
            echo $buffer;
        }

        return $this;

    }


}