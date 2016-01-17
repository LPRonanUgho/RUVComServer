<?php
/**
* Routes with middleware
*/
$app->group('/api', function () {

    $this->post('/login/{login}/{password}', function ($req, $res, $args) {
        $this->result = $this->controleur->checkConnexion($args['login'], $args['password']);
    });

})->add(function ($request, $response, $next) {
    $response = $next($request, $response);
    $result = $this->result;
    $status = 200;

    if($result['error']) {$status = 403;}

    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode($result));
});
