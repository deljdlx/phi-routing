<?php
require(__DIR__.'/_autoload.php');

ini_set('display_errors', 'on');

echo '<pre id="' . __FILE__ . '-' . __LINE__ . '" style="border: solid 1px rgb(255,0,0); background-color:rgb(255,255,255)">';
echo '<div style="background-color:rgba(100,100,100,1); color: rgba(255,255,255,1)">' . __FILE__ . '@' . __LINE__ . '</div>';
print_r('Testing federated router');
echo '</pre>';

$router = new \Phi\Routing\FederatedRouter();


echo '<pre id="' . __FILE__ . '-' . __LINE__ . '" style="border: solid 1px rgb(255,0,0); background-color:rgb(255,255,255)">';
echo '<div style="background-color:rgba(100,100,100,1); color: rgba(255,255,255,1)">' . __FILE__ . '@' . __LINE__ . '</div>';
print_r('Add match all routing rule');
echo '</pre>';

$router->getRouterByName(\Phi\Routing\FederatedRouter::DEFAULT_ROUTER)->get('exemple00', '`.*`', function() {
    echo 'Match all route';
});

$router->route();

echo $router->getOutput();