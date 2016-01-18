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

    public function login($login, $password) {
        // array for final json response
        $response = array();

        // Other
        $requete = $this->pdo->prepare("SELECT id, login, firstname, lastname, email FROM User WHERE login = ? AND password = SHA1(?)");
        $requete->execute([$login, $password]);
        $result = $requete->fetch();

        if($result) {
            $response['error'] = false;
            $response['data'] = $result;
        } else {
            $response['error'] = true;
            $response['message'] = "No user found !";
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

            $target_path = $this->settings['picture_path'] . basename($_FILES['image']['name']);

            try {
                // Throws exception incase file is not being moved
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    // make error flag true
                    $response['error'] = true;
                    $response['message'] = "Could not move the file : " . $_FILES['image']['tmp_name'] . " to : " . $target_path;
                } else {
                    // File successfully uploaded
                    $response['error'] = false;
                    $url = $this->settings['absolute_picture_path'] . basename($_FILES['image']['name']);
                    $response['data']['url'] = $url;
                    $response['data']['id'] = $this->addPictureToDB($url, $target_path, $_FILES['image']['size']);
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

    private function addPictureToDB($url, $serverpath, $filesize) {
        $requete = $this->pdo->prepare("INSERT INTO Photo (id, url, serverPath, filesize) VALUES (NULL, ?, ?, ?)");
        $requete->execute([$url, $serverpath, $filesize]);

        return $this->pdo->lastInsertId();
    }
}
