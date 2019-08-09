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


    /**
     * Route constructor.
     * @param $verbs
     * @param $validator
     * @param $callback
     */
    public function __construct($verbs, $validator, $callback)
    {
        $this->response = new Response();


        $this->setValidator($validator);
        $this->setVerbs($verbs);


        $this->callback = $callback;
    }


    /**
     * @param $verbs
     * @return $this
     * @throws Exception
     */
    public function setVerbs($verbs): Route
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


    /**
     * @param $validator
     * @return $this
     */
    public function setValidator($validator): Route
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
    public function execute(Request $request): Response
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


    /**
     * @param $callback
     * @return $this
     */
    public function setResponseContent($callback): Route
    {
        if(is_string($callback))
        {
            $this->response->setContent($callback);
        }
        else if(is_callable($callback)) {
            ob_start();
            call_user_func_array($callback, array($this->response));
            $content = ob_get_clean();
            if(is_string($content)) {
                $this->response->setContent($content);
            }
        }

        return $this;
    }


    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param $verb
     * @return bool
     */
    public function validateVerb($verb): bool
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
    public function validate(Request $request): bool
    {
        if(!$this->validateVerb($request->getVerb())) {
            return false;
        }
        return $this->validator->validate($request);
    }


    /**
     * @param $header
     * @param null $headerValue
     * @return $this
     * @throws Exception
     */
    public function addHeader($header, $headerValue = null): Route
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

    /**
     * @param string $charset
     * @return $this
     */
    public function json($charset = 'utf-8'): Route
    {
        $this->addHeader('Content-type', 'application/json; charset='.$charset);

        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function html($charset = 'utf-8'): Route
    {
        $this->addHeader('Content-type', 'text/html; charset='.$charset);
        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function plainText($charset = 'utf-8'): Route
    {
        $this->addHeader('Content-type', 'text/plain; charset='.$charset);
        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    public function javascript($charset = 'utf-8'): Route
    {
        $this->addHeader('Content-type', 'text/javascript; charset='.$charset);
        return $this;
    }

}

