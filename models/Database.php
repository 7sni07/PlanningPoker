"""
@file
@brief Connexion à la base de donnée

Fichier responsable a creer un object pour comuniquer avec la base de donnée
"""

<?php

class Database {
   """
   @class Database
   @brief

   Class qui retourne une seul instance de connexion de base de donnée
   """
    
    private const DB_HOST = 'localhost';      
    private const DB_NAME = 'planningPoker'; 
    private const DB_USER = 'root';           
    private const DB_PASS = '';

    private $pdo;
    private static $instance = null;

    private function __construct() {

        try {
            
            $dsn = 'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=utf8';

            
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS);

            
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
                }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }
}