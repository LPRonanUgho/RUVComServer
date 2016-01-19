<?php
$app->group('/api', function () {
    $this->post('/uploadPicture/{login}/{password}', function ($req, $res, $args) {
        $this->result = $this->controleur->uploadFile($args['login'], $args['password']);
    });

    $this->post('/createUser/{googleID}/{displayName}/{email}/{imageUrl}', function ($req, $res, $args) {
        $this->result = $this->controleur->createUser($args['googleID'], $args['displayName'], $args['email'], $args['imageUrl'], $_POST['token']);
    });

    $this->post('/updateUser/{id}/{googleID}/{displayName}/{email}/{imageUrl}', function ($req, $res, $args) {
        $this->result = $this->controleur->updateUser($args['id'], $args['googleID'], $args['displayName'], $args['email'], $args['imageUrl'], $_POST['token']);
    });

    $this->get('/userExists/{googleID}', function ($req, $res, $args) {
        $this->result = $this->controleur->userExists($args['googleID']);
    });

    $this->get('/getUsers', function ($req, $res, $args) {
        $this->result = $this->controleur->getUsers();
    });

    $this->get('/getConversations/{idUser}', function ($req, $res, $args) {
        $this->result = $this->controleur->getConversations($args['idUser']);
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
