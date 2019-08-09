<?php

namespace Phi\Routing;



use Phi\Core\Regexp;

class Validator
{



    private $validator = false;

    private $parameters = [];


    /**
     * Validator constructor.
     * @param bool $test
     */
    public function __construct($test = false)
    {
        $this->setValidator($test);
    }


    /**
     * @param $test
     * @return $this
     */
    public function setValidator($test)
    {
        $this->validator = $test;
        return $this;
    }


    /**
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request)
    {
        if($this->validator instanceof Regexp) {
            return $this->validateByRegexp($request);
        }
        else if(is_callable($this->validator)) {
            return $this->validateByCallable($request);
        }
        else if(is_string($this->validator)) {
            return $this->validateByString($request);
        }
        else if(is_bool($this->validator)) {

            return $this->validateByBoolean($request);
        }

        return false;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }


    //=======================================================

    /**
     * @param Request $request
     * @return mixed
     */
    private function validateByCallable(Request $request)
    {
        $returnValue = call_user_func_array($this->validator, array($request));

        return $returnValue;
    }


    /**
     * @param Request $request
     * @return bool
     */
    private function validateByRegexp(Request $request)
    {
        if($this->validator->match($request->getURI())) {
            $matches = $this->validator->getMatches();
            foreach ($matches as $parameterName => $data) {
                if(array_key_exists(0, $data)) {
                    $this->parameters[$parameterName] = $data[0];
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function validateByString(Request $request)
    {
        if(preg_match('`'.$this->validator.'`', $request->getURI())) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function validateByBoolean()
    {
        return $this->validator;
    }

}


