<?php

namespace App;
/**
* Class Application
*/
class App
{
  private $pdo;
  private $settings;
  private $currentUserId;

  function __construct($settings) {
    $this->settings = $settings;

    $database = new Database();
    $this->pdo = $database->getPdo();
  }

  private function checkToken($token){
    $response = array();

    if($token === $this->settings['secretToken']) {
      return false;
    } else {
      $response['error'] = true;
      $response['message'] = "Token mismatch !";
    }

    return $response;
  }

  private function isValidUser($login, $password) {
    $requete = $this->pdo->prepare("SELECT id, login, firstname, lastname, email FROM User WHERE login = ? AND password = SHA1(?)");
    $requete->execute([$login, $password]);
    $result = $requete->fetch();

    if($result) {
      $this->currentUserId = $result->id;
      return true;
    } else {
      return false;
    }
  }

  public function userExists($googleID) {
    $response = array();

    $requete = $this->pdo->prepare("SELECT * FROM User WHERE googleID = ?;");
    $requete->execute([$googleID]);
    $result = $requete->fetch();

    if($result) {
      $response['error'] = false;
      $response['data'] = true;
    } else {
      $response['error'] = true;
      $response['data'] = 'No user found !';
    }

    return $response;
  }

  public function getUsers() {
    $response = array();

    $requete = $this->pdo->prepare("SELECT * FROM User;");
    $requete->execute();
    $result = $requete->fetchAll();

    if($result) {
      $response['error'] = false;
      $response['data'] = $result;
    } else {
      $response['error'] = true;
      $response['message'] = "No user found !";
    }

    return $response;
  }

  public function getConversations($idUser) {
    $response = array();

    $requete = $this->pdo->prepare("SELECT u.* FROM Message m INNER JOIN User u ON u.id = m.idUserReceiver WHERE m.idUserSender = ? OR m.idUserReceiver = ? GROUP BY m.idUserReceiver HAVING m.idUserReceiver != ?;");
    $requete->execute([$idUser, $idUser, $idUser]);
    $result = $requete->fetchAll();

    if($result) {
      $response['error'] = false;
      $response['data'] = $result;
    } else {
      $response['error'] = true;
      $response['message'] = "No conversation found !";
    }

    return $response;
  }

  private function getUser($id) {
    $response = array();

    $requete = $this->pdo->prepare("SELECT * FROM User WHERE id = ?;");
    $requete->execute([$id]);
    $result = $requete->fetch();

    if($result) {
      $response = $result;
    } else {
      $response['error'] = true;
      $response['message'] = "No user found !";
    }

    return $response;
  }

  public function createUser($googleID, $displayName, $email, $imageUrl, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    $requete = $this->pdo->prepare("INSERT INTO User VALUES (NULL, ?, ?, ?, ?)");
    $result = $requete->execute([$googleID, $displayName, $email, $imageUrl]);

    if($result) {
      $response['error'] = false;
      $response['data'] = $this->getUser($this->pdo->lastInsertId());
    } else {
      $response['error'] = true;
      $response['message'] = "Internal error !";
    }

    return $response;
  }

  public function updateUser($id, $googleID, $displayName, $email, $imageUrl, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    $requete = $this->pdo->prepare("UPDATE User SET googleID = ?, displayName = ?, email = ?, imageUrl = ? WHERE id = ?");
    $result = $requete->execute([$googleID, $displayName, $email, $imageUrl, $id]);

    if($result) {
      $response['error'] = false;
      $response['data'] = $this->getUser($id);
    } else {
      $response['error'] = true;
      $response['message'] = "Internal error !";
    }

    return $response;
  }

  public function uploadFile($login, $password) {
    // array for final json response
    $response = array();

    if(!$this->isValidUser($login, $password)) {
      $response['error'] = true;
      $response['message'] = "User invalid";
      return $response;
    }

    if (isset($_FILES['image']['name'])) {
      //$target_path = $this->settings['picture_path'] . basename($_FILES['image']['name']);
      //$extension = end( explode(".", $_FILES["file"]["name"]) );
      //$path = $_FILES['image']['name'];
      $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $name = "IMG_" . md5(uniqid(rand(), true)) . "." . $extension;
      $target_path = $this->settings['picture_path'] . $name;

      try {
        // Throws exception incase file is not being moved
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
          // make error flag true
          $response['error'] = true;
          $response['message'] = "Could not move the file : " . $_FILES['image']['tmp_name'] . " to : " . $target_path;
        } else {
          // File successfully uploaded
          $response['error'] = false;
          $url = $this->settings['absolute_picture_path'] . $name;
          $response['data']['id'] = $this->addPictureToDB($url, $this->settings['picture_path'], $name, $_FILES['image']['size']);
          $response['data']['url'] = $url;
        }
      } catch (Exception $e) {
        // Exception occurred. Make error flag true
        $response['error'] = true;
        $response['message'] = $e->getMessage();
      }

    } else {
      // File parameter is missing
      $response['error'] = true;
      $response['message'] = 'Not received any file!';
    }

    return $response;
  }

  private function addPictureToDB($url, $serverpath, $name, $filesize) {
    $requete = $this->pdo->prepare("INSERT INTO Photo (id, url, path, name, filesize) VALUES (NULL, ?, ?, ?, ?)");
    $requete->execute([$url, $serverpath, $name, $filesize]);

    return $this->pdo->lastInsertId();
  }
}
