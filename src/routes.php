<?php
$app->group('/api', function () {
  $this->post('/uploadPicture/{idUserSender}/{idUserReceiver}/{geoLat}/{geoLong}', function ($req, $res, $args) {
    $this->result = $this->controleur->uploadFile($args['idUserSender'], $args['idUserReceiver'], $_POST['token'], $args['geoLat'], $args['geoLong']);
  });

  $this->post('/uploadPicture/{idUserSender}/{idUserReceiver}', function ($req, $res, $args) {
    $this->result = $this->controleur->uploadFile($args['idUserSender'], $args['idUserReceiver'], $_POST['token']);
  });

  $this->post('/createUser/{googleID}/{displayName}/{email}', function ($req, $res, $args) {
    $this->result = $this->controleur->createUser($args['googleID'], $args['displayName'], $args['email'], $_POST['imageUrl'], $_POST['coverImageUrl'], $_POST['token']);
  });

  $this->post('/updateUser/{id}/{googleID}/{displayName}/{email}', function ($req, $res, $args) {
    $this->result = $this->controleur->updateUser($args['id'], $args['googleID'], $args['displayName'], $args['email'], $_POST['imageUrl'], $_POST['coverImageUrl'], $_POST['token']);
  });

  $this->post('/createMessage/{idUserSender}/{idUserReceiver}', function ($req, $res, $args) {
    $this->result = $this->controleur->createMessage($args['idUserSender'], $args['idUserReceiver'], $_POST['message'], $_POST['idPhoto'], $_POST['token']);
  });

  $this->post('/setMessageRead/{idUser}/{idDistantUser}', function ($req, $res, $args) {
    $this->result = $this->controleur->setMessageRead($args['idUser'], $args['idDistantUser'], $_POST['token']);
  });

  $this->get('/userExists/{googleID}', function ($req, $res, $args) {
    $this->result = $this->controleur->userExists($args['googleID']);
  });

  $this->post('/registerGCM/{idUser}', function ($req, $res, $args) {
    $this->result = $this->controleur->registerGCM($args['idUser'], $_POST['idDevice'], $_POST['token']);
  });

  $this->get('/getMessages/{userID}/{idDistantUser}', function ($req, $res, $args) {
    $this->result = $this->controleur->getMessages($args['userID'], $args['idDistantUser']);
  });

  $this->get('/getUsers', function ($req, $res, $args) {
    $this->result = $this->controleur->getUsers();
  });

  $this->get('/getUsers/{idUser}', function ($req, $res, $args) {
    $this->result = $this->controleur->getUsers($args['idUser']);
  });

  $this->get('/getConversations/{idUser}', function ($req, $res, $args) {
    $this->result = $this->controleur->getConversations($args['idUser']);
  });

  $this->post('/deleteGCM/{idUser}', function ($req, $res, $args) {
    $this->result = $this->controleur->deleteGCM($args['idUser'], $_POST['token']);
  });

  $this->get('/locatesPictures/{idUser}', function($req, $res, $args) {
    $this->result = $this->controleur->locatesPictures($args['idUser']);
  });

})
->add(function ($request, $response, $next) {

  $response = $next($request, $response);
  $result = $this->result;
  $status = 200;

  if(empty($result)) {
    $status = 500;
  } else if($result['error']) {
    $status = 202;
  }

  return $response
  ->withStatus($status)
  ->withHeader('Content-Type', 'application/json')
  ->write(json_encode($result));
});
