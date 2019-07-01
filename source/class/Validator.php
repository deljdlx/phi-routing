<?php

namespace Phi\Routing;



use Phi\Core\Regexp;

class Validator
{



    private $validator = false;

    private $parameters = [];


    public function __construct($test = false)
    {
        $this->setValidator($test);
    }


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

    public function getParameters()
    {
        return $this->parameters;
    }


    //=======================================================

    private function validateByCallable(Request $request)
    {
        $returnValue = call_user_func_array($this->validator, array($request));

        return $returnValue;
    }



    private function validateByRegexp(Request $request)
    {
        if($this->validator->match($request->getURI())) {
            $this->parameters = $this->validator->getMatches();
            return true;
        }
        return false;
    }

    private function validateByString(Request $request)
    {
        if(preg_match('`'.$this->validator.'`', $request->getURI())) {
            return true;
        }
        return false;
    }

    private function validateByBoolean(Request $request)
    {
        return $this->validator;
    }

}


