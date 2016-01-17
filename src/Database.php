<?php

namespace App;

use \PDO;
/**
 *
 */
class Database
{
    /**
    * VARIABLES
    */
    private $pdo;
    private $host;
    private $database;
    private $user;
    private $password;
    private $errorDatabase = "Erreur de connexion à la base de données.";

    /**
    * Constructeur
    */
    public function __construct(){
        if(!isset($_SESSION['lang'])) {
            // Si la variable de session lang n'est pas défini, défault en francais
            $_SESSION['lang'] = 'fr';
            // Sinon récupération en session
        }

        $this->lang = $_SESSION['lang'];

        // Si PROD chargements du bon Pdo
        if($_SERVER['SERVER_ADDR'] == '151.80.158.49') {
            $this->loadProd();
        } else { // Sinon chargement du pdo de DEV
            $this->loadDev();
        }

        // Enfin chargement de PDO
        $this->loadPdo();
    }

    public function getPdo() {
        return $this->pdo;
    }

    private function loadPdo() {
        try {
            // Instanciation de pdo
            $this->pdo = new PDO('mysql:host='.$this->host.';dbname='.$this->database.';charset=utf8', $this->user, $this->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        }
        catch( PDOException $Exception ) {
            // Afficher l'erreur pdo si localhost
            if($_SERVER['SERVER_ADDR'] != '151.80.158.49') {
                die('code : '.(int)$Exception->getCode().', Message : '.$Exception->getMessage());
            } else {
                //Sinon afficher erreur standard
                die($this->errorDatabase);
            }
        }
    }

    private function loadProd() {
        // Fichier database sur le server
        $filename = "/var/www/databases.json";
        // Si le fichier existe
        if (file_exists($filename)) {
            // Open and read file
            $handle = fopen($filename, "rb");
            // Read the JSON file
            $content = fread($handle, filesize($filename));
            // Close the file
            fclose($handle);
            // Decode the JSON into an associative array
            $json = json_decode($content, true);
            // Récupération des données pour pdo
            $this->host       = $json['RUVCom']['host'];
            $this->database   = $json['RUVCom']['database'];
            $this->user       = $json['RUVCom']['user'];
            $this->password   = $json['RUVCom']['password'];
        } else {
            die($this->errorDatabase);
        }
    }

    private function loadDev() {
        $this->host       = "localhost";
        $this->database   = "RUVCom";
        $this->user       = "RUVCom";
        $this->password   = "RUVCom";
    }
}
?>
