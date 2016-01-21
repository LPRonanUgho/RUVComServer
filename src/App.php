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

  public function getUsers($idUser) {
    $response = array();

    if(!empty($idUser)) {
      $requete = $this->pdo->prepare("SELECT * FROM User WHERE id != ?;");
      $requete->execute([$idUser]);
    } else {
      $requete = $this->pdo->prepare("SELECT * FROM User;");
      $requete->execute();
    }

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

    $requete = $this->pdo->prepare("SELECT u.* FROM Message m INNER JOIN User u ON u.id = m.idUserReceiver WHERE m.idUserSender = ? GROUP BY u.id ORDER BY dateTime ASC;");
    $requete->execute([$idUser]);
    $users = $requete->fetchAll();

    $requete = $this->pdo->prepare("SELECT u.* FROM Message m INNER JOIN User u ON u.id = m.idUserSender WHERE m.idUserReceiver = ? GROUP BY u.id ORDER BY dateTime ASC;");
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

    if($result->idPhoto != null) {
      $requete = $this->pdo->prepare("SELECT * FROM Photo WHERE id = ?;");
      $requete->execute([$message->idPhoto]);
      $photo = $requete->fetch();

      $result->photo = $photo;
    } else {
      $result->photo = null;
    }

    if($result->isRead == 1) {
      $result->isRead = true;
    } else {
      $result->isRead = false;
    }

    unset($result->idPhoto);

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

      if($message->isRead == 1) {
        $result[$key]->isRead = true;
      } else {
        $result[$key]->isRead = false;
      }
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

      $idMessage = $this->pdo->lastInsertId();
      $response['data'] = $this->getMessage($idMessage);

      // Send push notification of message
      $this->sendPushNotification($idUserReceiver, $idMessage);
    } else {
      $response['error'] = true;
      $response['message'] = "Internal error !";
    }

    return $response;
  }

  public function setMessageRead($idUser, $idDistantUser, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    $requete = $this->pdo->prepare("UPDATE Message SET isRead = 1 WHERE idUserSender = ? AND idUserReceiver = ?;");
    $result = $requete->execute([$idDistantUser, $idUser]);

    if($result) {
      $response['error'] = false;
      $response['data']['message'] = "Update successfully finish";
    } else {
      $response['error'] = true;
      $response['message'] = "Internal error !";
    }

    return $response;
  }

  public function uploadFile($idUserSender, $idUserReceiver, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    if (isset($_FILES['image']['name'])) {
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
          $photoObj = $this->addPictureToDB($url, $this->settings['picture_path'], $name, $_FILES['image']['size']);

          //Register messag into DB
          $this->createMessage($idUserSender, $idUserReceiver, NULL, $photoObj->id, $token);

          //Return message
          $messageObj = $this->getMessage($this->pdo->lastInsertId());
          $messageObj->photo = $photoObj;
          unset($messageObj->idPhoto);
          $response['data'] = $messageObj;

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

    $lastPhotoInsertID = $this->pdo->lastInsertId();

    $requete = $this->pdo->prepare("SELECT * FROM Photo WHERE id = ?;");
    $requete->execute([$lastPhotoInsertID]);
    $photo = $requete->fetch();

    return $photo;
  }

  public function registerGCM($idUser, $idDevice, $token) {
    // array for final json response
    $response = array();

    if ($this->checkToken($token)){
      return $this->checkToken($token);
    }

    $requete = $this->pdo->prepare("SELECT * FROM GCM WHERE idUser = ? AND idDevice = ?;");
    $requete->execute([$idUser, $idDevice]);
    $exists = $requete->fetch();
    if(empty($exists)) {
      $requete = $this->pdo->prepare("INSERT INTO GCM VALUES(?, ?);");
      $result = $requete->execute([$idUser, $idDevice]);

      if($result) {
        $response['error'] = false;
        $response['data']['message'] = "Insert successfully finish !";
      } else {
        $response['error'] = true;
        $response['message'] = "Internal error !";
      }
    } else {
      $response['error'] = true;
      $response['message'] = "Already register !";
    }

    return $response;
  }

  private function sendPushNotification($idUser, $idMessage) {
    $requete = $this->pdo->prepare("SELECT idDevice FROM GCM WHERE idUser = ?;");
    $requete->execute([$idUser]);
    $ids_device = array();
    while($idDevice = $requete->fetch()) {
      $ids_device[] = $idDevice->idDevice;
    }

    $message = $this->getMessage($idMessage);

    foreach ($ids_device as $key => $idDevice) {
      // Set POST variables
      $url = 'https://gcm-http.googleapis.com/gcm/send';
      $fields = array(
        'to' => $idDevice,
        'data' => $message,
      );
      $headers = array(
        'Authorization: key=' . $this->settings['googleApiKey'],
        'Content-Type: application/json'
      );
      // Open connection
      $ch = curl_init();
      // Set the url, number of POST vars, POST data
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // Disabling SSL Certificate support temporarly
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

      $result = curl_exec($ch);

      curl_close($ch);
    }
  }
}
