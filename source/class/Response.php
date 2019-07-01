<?php

namespace Phi\Routing;



use Phi\HTTP\Header;

class Response
{

    private $request;

    private $content = null;


    /**
     * @var Header[]
     */
    private $headers = [];

    public function __construct(Request $request = null)
    {
        if($request) {
            $this->request = $request;
        }
    }


    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
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


    public function getContent()
    {
        return $this->content;
    }


    public function addHeader($header, $headerValue = null)
    {
        if($header instanceof Header) {
            $this->headers[$header->getName()] = $header;
        }
        else if(is_string($header)){
            $this->header[$header] = $headerValue;
        }
        else {
            throw new Exception('Invalid header name');
        }
        return $this;
    }


    public function sendHeaders()
    {

        foreach ($this->headers as $header) {

            header($header->getName().': '.$header->getValue());
        }
        return $this;

    }


}

