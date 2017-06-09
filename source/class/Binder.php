<?php
namespace Phi\Routing;


class Binder
{

    protected $route;

    public function __contruct(Route $route) {
        $this->route=$route;
    }


    public function run($callable) {

    }



    public function getMethodParameters($userParameters, $controllerName, $method) {




        die('EXIT '.__FILE__.'@'.__LINE__);
        $reflector = new \ReflectionMethod($controllerName, $method);
        $parameters = $reflector->getParameters();
        $callParameters = array();
        foreach ($parameters as $parameter) {
            if (isset($userParameters[$parameter->name])) {
                $callParameters[] = $userParameters[$parameter->name];
            } else if ($parameter->isOptional()) {
                $callParameters[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception('Method ' . $controllerName . '::' . $method . ' missing parameter ' . $parameter->name);
            }
        }
        return $callParameters;
    }




}
