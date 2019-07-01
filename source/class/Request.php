<?php

namespace Phi\Routing;



class Request extends \Phi\HTTP\Request
{

    private $parameters = [];

    public function __construct($autobuild = true)
    {
        parent::__construct($autobuild);
    }




    public function addParameter($parameterName, $value)
    {
        $this->parameters[$parameterName] = $value;
        return $this;
    }

    public function addParameters(array $parameters)
    {

        foreach ($parameters as $name => $value) {
            $this->parameters[$name] = $value;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter($name)
    {
        if(!array_key_exists($name, $this->parameters)) {
            throw new Exception('No parameter with name '.$name);
        }

        return $this->parameters[$name];
    }




}

