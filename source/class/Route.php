<?php

namespace Phi\Routing;



use Phi\HTTP\Header;

class Route
{


    /**
     * @var Validator
     */
    private $validator;

    private $callback;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request;
     */
    private $request;


    /**
     * @var array
     */
    private $verbs = [];


    /**
     * @var Header
     */
    private $headers = [];



    public function __construct($verbs, $validator, $callback)
    {
        $this->response = new Response();


        $this->setValidator($validator);
        $this->setVerbs($verbs);


        $this->callback = $callback;
    }


    public function setVerbs($verbs)
    {


        if(is_array($verbs)) {
            $this->verbs = $verbs;
        }
        else if(is_string($verbs)){
            $this->verbs = (array) $verbs;
        }
        else {
            throw new Exception('Validator verb must be an array of strings or a string');
        }

        return $this;
    }


    public function setValidator($validator)
    {
        if($validator instanceof Validator) {
            $validatorInstance = $validator;
        }
        else {
            $validatorInstance = new Validator($validator);
        }


        $this->validator = $validatorInstance;
        return $this;
    }


    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function execute(Request $request)
    {

        if($this->validate($request)) {


            $request->addParameters($this->validator->getParameters());


            $this->request = $request;
            $this->response->setRequest($request);

            $this->setResponseContent($this->callback);


            foreach ($this->headers as $header) {
                $this->response->addHeader($header);
            }

            return $this->response;
        }
        throw new Exception('Invalid request for current route');
    }


    public function setResponseContent($callback)
    {
        if(is_string($callback))
        {
            $this->response->setContent($callback);
        }
        else if(is_callable($callback)) {
            $content = call_user_func_array($callback, array($this->response));
            if(is_string($content)) {
                $this->response->setContent($content);
            }
        }

        return $this;
    }


    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param $verb
     * @return bool
     */
    public function validateVerb($verb)
    {

        foreach ($this->verbs as $validVerb) {
            if($validVerb === $verb) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request)
    {
        if(!$this->validateVerb($request->getVerb())) {
            return false;
        }
        return $this->validator->validate($request);
    }


    public function addHeader($header, $headerValue = null)
    {
        if($header instanceof Header) {
            $this->headers[$header->getName()] = $header;
        }
        else if(is_string($header)){
            $this->headers[$header] = new Header($header, $headerValue);
        }
        else {
            throw new Exception('Invalid header name');
        }
        return $this;
    }



    //=======================================================

    public function json($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'application/json; charset='.$charset);

        return $this;
    }

    public function html($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'text/html; charset='.$charset);
        return $this;
    }

    public function plainText($charset = 'utf-8')
    {
        $this->addHeader('Content-type', 'text/plain; charset='.$charset);
        return $this;
    }

}

