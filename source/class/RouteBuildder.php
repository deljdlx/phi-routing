<?php


namespace Phi\Routing;



function someFunction(int $param, $param2) {}


class RouteBuildder implements \JsonSerializable
{


    /**
     * @var Route
     */
    private $route;

    private $action;

    private $name;

    /**
     * @var RouteBuilderParameter[]
     */
    private $parameters = [];


    public function __construct(Route $route, $action, $name = null)
    {
        $this->route = $route;
        $this->action = $action;
        $this->name = $name;
    }


    public function getURL($parameters)
    {

        if (is_callable($this->action)) {
            return call_user_func_array($this->action, $parameters);
        }
        elseif (is_string($this->action)) {

            $url = preg_replace_callback('`\{(.*?)\}`', function ($match) use ($parameters) {
                $value = $parameters[$match[1]];
                return $value;
            }, $this->action);

            return $url;
        }
        elseif (is_string($this->route->getValidator())) {
            return $this->route->getValidator();
        }
    }


    public function setParametersByDescriptor(array $descriptors)
    {
        foreach ($descriptors as $parameterName => $descriptor) {
            $parameter = new RouteBuilderParameter($parameterName);
            if(array_key_exists('accept', $descriptor)) {
                foreach ($descriptor['accept'] as $acceptedType) {
                    $parameter->addAcceptedType($acceptedType);
                }
            }



            $this->parameters[$parameterName] = $parameter;
        }

        return $this;
    }


    public function getParameters()
    {
        return $this->parameters;
    }


    public function jsonSerialize()
    {
        $descriptor = [];
        if(is_string($this->action)) {
            $descriptor['type'] = 'string';
            $descriptor['value'] = $this->action;
        }
        else if($this->action instanceof \Closure) {
            $descriptor['type'] = 'closure';


            $methodReflector = new \ReflectionFunction($this->action);


            $parameterDescriptors = [];

            foreach ($methodReflector->getParameters() as $parameter) {

                if($type = $parameter->getType()) {

                    if($type instanceof \ReflectionNamedType) {
                        $type = $type->getName();
                    }
                    else {
                        $type = $type->__toString();
                    }
                }

                $parameterDescriptors[$parameter->getName()] = array(
                   'name' => $parameter->getName(),
                    'class' => $parameter->getClass(),
                    'isOptionnal' =>  $parameter->isOptional(),
                    'type' => $type,
                    'defaultValue' => null
                );
                if($parameter->isDefaultValueAvailable()) {
                    $parameterDescriptors[$parameter->getName()]['defaultValue'] = $parameter->getDefaultValue();
                }
            }
            $descriptor['parameters'] = $parameterDescriptors;

            //$descriptor['parameters'] = $this->getParameters();

        }

        return $descriptor;
    }


}

