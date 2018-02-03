<?php
require(__DIR__.'/../vendor/autoload.php');


echo 'Test url pattern : /01-capture-parameter.php/parameter/{aValue}';

echo '<br/>';

$router = new \Phi\Routing\Router();
$router->get('captureParameter', '`/parameter/(.*)`', function($parameter) {
  echo 'Parameter captured : "'.$parameter.'"';
});

$router->route();