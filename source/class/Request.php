<?php
namespace Phi\Routing;

use Phi\Routing\Interfaces\Request as IRequest;


class Request implements  IRequest
{

    const SAPI_CLI = 'cli';

    protected static $mainInstance = null;

    protected $isHTTP;
    protected $uri = null;

    protected $protocol;

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
    }

    public function getVerb() {
        return $this->implementation->getVerb();
    }


    public function setImplementation(IRequest $implementation)
    {
        $this->implementation = $implementation;
        return $this;
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


    public function isHTTP()
    {


        if ($this->isHTTP === null) {
            if (php_sapi_name() == static::SAPI_CLI) {
                $this->isHTTP = false;
            } else {
                $this->isHTTP = true;
            }
        }

        return $this->isHTTP;


    }


}