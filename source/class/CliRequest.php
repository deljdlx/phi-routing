<?php
namespace Phi\Routing;


class CliRequest implements \Phi\Routing\Interfaces\Request
{

    protected $parameters;

    public function isHTTP()
    {
        return false;
    }


    public function __construct(array $parameters = null)
    {
        if($parameters===null) {
            global $argv;
            $this->parameters=$argv;
        }
    }

    public function getURI()
    {
        return getcwd() . '://' . implode('/', $this->parameters);
    }


}