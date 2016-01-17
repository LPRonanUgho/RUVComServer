<?php

namespace App;
/**
* Class Application
*/
class App
{
    private $pdo;

    function __construct() {
        $database = new Database();
        $this->pdo = $database->getPdo();
    }

    public function checkConnexion($login, $password) {
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
}
