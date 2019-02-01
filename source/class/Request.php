<?php
namespace Phi\Routing;

use Phi\Routing\Interfaces\Request as IRequest;
use Phi\Traits\Collection;


class Request implements  IRequest
{


    use Collection;

    const SAPI_CLI = 'cli';

    protected static $mainInstance = null;

    protected $isHTTP;
    protected $uri = null;

    protected $protocol;

    /**
     * @var IRequest
     */
    protected $implementation;


    public static function getInstance()
    {

        if (static::$mainInstance === null) {
            static::$mainInstance = new static();
        }
        return static::$mainInstance;
    }


    public function __construct($isHTTP = null)
    {

        if ($isHTTP === null) {
            $this->isHTTP = $this->isHTTP();
        }

        if ($this->isHTTP()) {
            $this->implementation = new \Phi\HTTP\Request();
        }
        else {
            $this->implementation = new CliRequest();
        }
        if(!isset(static::$mainInstance)) {
            static::$mainInstance = $this;
        }

    }

    public function getVerb() {
        return $this->implementation->getVerb();
    }


    public function setImplementation(IRequest $implementation)
    {
        $this->implementation = $implementation;
        return $this;
    }

    /**
     * @return CliRequest|IRequest
     */
    public function getImplementation()
    {
        return $this->implementation;
    }


    public function getURI()
    {
        return $this->implementation->getURI();
    }


    public function getRequest()
    {
        return $this->implementation;
    }

    public function get($variableName = null)
    {
        return $this->implementation->get($variableName);
    }

    public function post($variableName = null)
    {
        return $this->implementation->post($variableName);
    }

    public function files($variableName = null)
    {
        return $this->implementation->files($variableName);
    }

    public function getSession()
    {
        return $this->implementation->getSession();
    }


    public function data($name = null)
    {

        $data = array();

        if(method_exists($this->implementation, 'get')) {
            $data = array_merge(
                $data,
                $this->implementation->get()
            );
        }

        if(method_exists($this->implementation, 'getBodyData')) {
            $data = array_merge(
                $data,
                $this->implementation->getBodyData()
            );
        }


        if(method_exists($this->implementation, 'post')) {
            $data = array_merge(
                $data,
                $this->implementation->post()
            );
        }

        if(method_exists($this->implementation, 'files')) {
            $data = array_merge(
                $data,
                $this->implementation->files()
            );
        }


        if(method_exists($this->implementation, 'cookies')) {
            $data = array_merge(
                $data,
                $this->implementation->cookies()
            );
        }

        if(method_exists($this->implementation, 'session')) {
            $data = array_merge(
                $data,
                $this->implementation->session()
            );
        }

        if($name !== null) {
            if(array_key_exists($name, $data)) {
                return $data[$name];
            }
        }

        return $data;
    }




    public function setURI($uri)
    {
        $this->implementation->setURI($uri);
        return $this;
    }


    public function isHTTP()
    {

        if($this->implementation) {
            return $this->implementation->isHTTP();
        }



        if ($this->isHTTP === null) {
            if (php_sapi_name() == static::SAPI_CLI) {
                $this->isHTTP = false;
            } else {
                $this->isHTTP = true;
            }
        }

        return $this->isHTTP;
    }

    public function getBody()
    {
        return $this->implementation->getBody();
    }

    public function getBodyData()
    {
        return $this->implementation->getBodyData();
    }


}