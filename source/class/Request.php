<?php
namespace Phi\Routing;


class Request implements \Phi\Routing\Interfaces\Request
{


    protected static $mainInstance = null;

    protected $isHTTP;
    protected $uri = null;


    protected $protocol;


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
            if ($this->isHTTP()) {
                $this->URI = $_SERVER['REQUEST_URI'];
                $this->protocol = $_SERVER['SERVER_PROTOCOL'];
            }
        } else {
            $this->isHTTP = $isHTTP;
        }
    }


    public function getURI()
    {
        return $this->URI;
    }

    public function setURI($uri) {
        $this->URI=$uri;
    }


    public function isHTTP()
    {


        if ($this->isHTTP === null) {
            if (php_sapi_name() == "cli") {
                $this->isHTTP = false;
            } else {
                $this->isHTTP = true;
            }
        }

        return $this->isHTTP;


    }


}