<?php
require(__DIR__.'/../vendor/autoload.php');

$router = new \Phi\Routing\Router();
$router->get('exemple00', '`.*`', function() {
  echo 'Match all route';
});

$router->route();