<?php
namespace Phi\Routing;


use Phi\HTTP\Request as PhiHTTPRequest;

class HTTPRequest extends PhiHTTPRequest implements \Phi\Routing\Interfaces\Request
{

    public function isHTTP() {
        return true;
    }


}