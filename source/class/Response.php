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
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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
            $this->headers[$header] = new Header($header, $headerValue);
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


    /**
     * @param string $charset
     * @return $this
     */
    public function json($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'application/json; charset='.$charset);

        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function html($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'text/html; charset='.$charset);
        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function plainText($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'text/plain; charset='.$charset);
        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function javascript($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'application/javascript; charset='.$charset);
        return $this;
    }

    public function stylesheet($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'text/css; charset='.$charset);
        return $this;
    }



}

