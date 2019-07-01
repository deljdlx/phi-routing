<?php


require(__DIR__.'/_autoload.php');


$request = new \Phi\Routing\Request();

$router = new \Phi\Routing\Router();



$router->get('test', true, function(\Phi\Routing\Response $response) {
    $response->setContent(json_encode(array(
        'test1' => 'hello',
        'test2' => 'world',
        'test3' => '€uro',

    )));
})->plainText('utf-8');



try {
    $response = $router->route($request);

    $response->sendHeaders();
    echo $response->getContent();



} catch (\Phi\Routing\Exception $exception) {
    echo '<pre id="' . __FILE__ . '-' . __LINE__ . '" style="border: solid 1px rgb(255,0,0); background-color:rgb(255,255,255)">';
    echo '<div style="background-color:rgba(100,100,100,1); color: rgba(255,255,255,1)">' . __FILE__ . '@' . __LINE__ . '</div>';
    print_r('Routing exception');
    echo '</pre>';
}



