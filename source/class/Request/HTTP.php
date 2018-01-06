<?php
namespace Phi\Routing\Request;


use Phi\HTTP\Request as PhiHTTPRequest;

class HTTP extends PhiHTTPRequest implements \Phi\Routing\Interfaces\Request
{

    public function isHTTP() {
        return true;
    }

}