<?php


namespace Phi\Routing;



class RouteBuilderParameter implements \JsonSerializable
{

    private $name;


    private $acceptedTypes = [];


    public function __construct($name)
    {
        $this->name = $name;
    }


    public function addAcceptedType($type)
    {
        $this->acceptedTypes[] = $type;
        return $this;
    }


    public function jsonSerialize()
    {
        $data = array(
            'name' => $this->name,
            'acceptedTypes' => $this->acceptedTypes
        );


        return $data;
    }


}

