<?php

/** 
 *   @file
 *   @brief Connexion à la base de donnée
 *   
 *   Fichier responsable a creer un object pour comuniquer avec la base de donnée

*/

class Database {
   /** 
    *   @class Database
    *   @brief Instance d'objet pdo
    *   
    *   Class qui retourne une seul instance de connexion de base de donnée

    */

    /**
     * @var string DB_HOST Hôte de la base de données.
     */
    private const DB_HOST = 'localhost';

    /**
     * @var string DB_NAME Nom de la base de données.
     */     
    private const DB_NAME = 'planningPoker';

    /**
     * @var string DB_USER Nom d'utilisateur de la base de données.
     */
    private const DB_USER = 'root';
    
    /**
     * @var string DB_PASS Mot de passe de la base de données.
     */
    private const DB_PASS = '';

    
    /**
     * @var PDO $pdo L'objet PDO contenant la connexion active.
     */
    private $pdo;

    /**
     * @var Database $instance L'instance unique de la classe (Singleton).
     */
    private static $instance = null;

    /**
     * @brief Constructeur de la class Database.
     *
     * Initialise la connexion PDO. Il est privé pour empêcher l'instanciation directe
     * et forcer l'utilisation de la méthode getInstance() pour récuperer l'unique objet.
     *
     * @throws PDOException Si la connexion échoue.
     */
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

    /**
     * @brief Récupère l'instance unique de la classe Database.
     *
     * Si l'instance n'existe pas encore, elle est créée. Sinon, l'instance existante est retournée.
     *
     * @return Database L'instance unique de la classe.
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * @brief Récupère l'objet PDO.
     *
     * Permet d'effectuer des requêtes SQL via l'objet PDO.
     *
     * @return PDO L'objet de connexion PDO.
     */
    public function getPdo() {
        return $this->pdo;
    }
}