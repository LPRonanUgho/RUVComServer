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
      $response['data'] = $result;
    } else {
      $response['error'] = true;
      $response['message'] = 'No user found !';
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

    $requete = $this->pdo->prepare("SELECT u.* FROM Message m INNER JOIN User u ON u.id = m.idUserReceiver WHERE m.idUserSender = ? GROUP BY u.id;");
    $requete->execute([$idUser]);
    $users = $requete->fetchAll();

    $requete = $this->pdo->prepare("SELECT u.* FROM Message m INNER JOIN User u ON u.id = m.idUserSender WHERE m.idUserReceiver = ? GROUP BY u.id;");
    $requete->execute([$idUser]);
    while($user = $requete->fetch()) {
      $users[] = $user;
    }

    $users = array_unique($users, SORT_REGULAR);

    $result = array();
    foreach ($users as $key => $user) {
      $requete = $this->pdo->prepare("SELECT isRead FROM Message WHERE idUserSender = ? AND idUserReceiver = ? ORDER BY dateTime DESC LIMIT 1;");
      $requete->execute([$user->id, $idUser]);
      $lastMessageRead = $requete->fetch();

      $notification = (!empty($lastMessageRead) && $lastMessageRead->isRead == 0) ? true : false;

      $requete = $this->pdo->prepare("SELECT dateTime FROM Message WHERE (idUserSender = ? AND idUserReceiver = ?) OR (idUserSender = ? AND idUserReceiver = ?) ORDER BY dateTime DESC LIMIT 1;");
      $requete->execute([$user->id, $idUser, $idUser, $user->id]);
      $lastMessage = $requete->fetch();

      $result[] = array("user" => $user, "notification" => $notification, "lastMessage" => $lastMessage->dateTime);
    }

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

  public function getMessage($id) {
    $response = array();

    $requete = $this->pdo->prepare("SELECT * FROM Message WHERE id = ?;");
    $requete->execute([$id]);
    $result = $requete->fetch();

    if($result) {
      $response['error'] = false;
      $response = $result;
    } else {
      $response['error'] = true;
      $response['message'] = "No message found !";
    }

    return $response;
  }

  public function getMessages($idUser, $idDistantUser) {
    $response = array();

    $requete = $this->pdo->prepare("SELECT * FROM Message WHERE (idUserSender = ? AND idUserReceiver = ?) OR (idUserSender = ? AND idUserReceiver = ?) ORDER BY dateTime ASC;");
    $requete->execute([$idUser, $idDistantUser, $idDistantUser, $idUser]);
    $result = $requete->fetchAll();

    foreach ($result as $key => $message) {
      if($message->idPhoto != null) {
        $requete = $this->pdo->prepare("SELECT * FROM Photo WHERE id = ?;");
        $requete->execute([$message->idPhoto]);
        $photo = $requete->fetch();

        $result[$key]->photo = $photo;
      } else {
        $result[$key]->photo = null;
      }

      unset($result[$key]->idPhoto);
    }

    if($result) {
      $response['error'] = false;
      $response['data'] = $result;
    } else {
      $response['error'] = true;
      $response['message'] = "No message found !";
    }

    return $response;
  }

  public function createUser($googleID, $displayName, $email, $imageUrl, $coverImageUrl, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    $requete = $this->pdo->prepare("INSERT INTO User VALUES (NULL, ?, ?, ?, ?, ?);");
    $result = $requete->execute([$googleID, $displayName, $email, $imageUrl, $coverImageUrl]);

    if($result) {
      $response['error'] = false;
      $response['data'] = $this->getUser($this->pdo->lastInsertId());
    } else {
      $response['error'] = true;
      $response['message'] = "Internal error !";
    }

    return $response;
  }

  public function updateUser($id, $googleID, $displayName, $email, $imageUrl, $coverImageUrl, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    $requete = $this->pdo->prepare("UPDATE User SET googleID = ?, displayName = ?, email = ?, imageUrl = ?, coverImageUrl = ? WHERE id = ?;");
    $result = $requete->execute([$googleID, $displayName, $email, $imageUrl, $coverImageUrl, $id]);

    if($result) {
      $response['error'] = false;
      $response['data'] = $this->getUser($id);
    } else {
      $response['error'] = true;
      $response['message'] = "Internal error !";
    }

    return $response;
  }

  public function createMessage($idUserSender, $idUserReceiver, $message, $idPhoto, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    if (empty($message) && empty($idPhoto)) {
        $response['message'] = "One parameter is require (between message or photo) !";
        return $response;
    }

    $requete = $this->pdo->prepare("INSERT INTO Message VALUES (NULL, ?, ?, ?, ?, 0, NOW());");
    $result = $requete->execute([$idUserSender, $idUserReceiver, trim(utf8_encode($message)), $idPhoto]);

    if($result) {
      $response['error'] = false;
      $response['data'] = $this->getMessage($this->pdo->lastInsertId());
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
