<?php
$app->group('/api', function () {

    $this->get('/login/{login}/{password}', function ($req, $res, $args) {
        $this->result = $this->controleur->login($args['login'], $args['password']);
    });

    $this->get('/uploadPicture/{login}/{password}', function ($req, $res, $args) {
        $this->result = $this->controleur->uploadFile($args['login'], $args['password']);
    });

})
->add(function ($request, $response, $next) {

    $response = $next($request, $response);
    $result = $this->result;
    $status = 200;

    if(empty($result)) {
        $status = 500;
    } else if($result['error']) {
        $status = 403;
    }

    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode($result));
});
