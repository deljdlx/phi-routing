<?php
namespace Phi\Routing;


use Phi\HTTP\Header;

class ResponseCollection
{

    /**
     * @var Response[]
     */
    protected $responses;


    public function addResponse(Response $response)
    {
        $this->responses[] = $response;
        return $this;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function send()
    {
        $buffer = '';

        /**
         * @var Header[] $headers
         */
        $headers = array();

        foreach ($this->responses as $response) {
            $buffer .= $response->getContent();

            if ($response->getReques()->isHTTP()) {
                $headers = array_merge($headers, $response->getHTTPResponse()->getHeaders());
            }
        }

        foreach ($headers as $header) {
            $header->send();
        }

        echo $buffer;


    }


}