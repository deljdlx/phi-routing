<?php
require(__DIR__.'/../vendor/autoload.php');
ini_set('display_errors', 'on');
$router = new \Phi\Routing\Router();
$router->get('exemple00', '`.*`', function() {
  echo 'Match all route';
});

$router->route();