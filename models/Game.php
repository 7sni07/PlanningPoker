<?php

/** 
 *   @file Game.php
 *   @brief Manipulation des données dans la base de donnée.
 *   
 *   Fichier responsable a gérer la relation entre l'application et la base de donnée.
 *   

*/

require_once 'Database.php';


/** 
    *   @class Game
    *   @brief Manipulation des données dans la base de donnée.
    *   
    *   @todo 
    *
    */
class Game {

    
    /**
     * @var PDO $pdo Instance de la connexion à la base de données.
     */
    private $pdo;

    /**
     * @brief Constructeur de la classe Game.
     * 
     * * Initialise la connexion à la base de données en récupérant l'instance unique.
     * 
     */
    public function __construct() {
        
        $this->pdo = Database::getInstance()->getPdo();
    }


    /**
     * @brief Crée une nouvelle partie en base de données.
     *
     * @todo 
     *
     * @param int $ruleId L'identifiant de la règle de jeu choisie.
     * * @return int L'identifiant unique (ID) de la partie créée.
     *
     */
    public function createGame(int $ruleId): int {
        
        $sql = "INSERT INTO game (rule_id, status, started_at) 
                VALUES (:rule_id, 'LOBBY', NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        
        
        $stmt->bindParam(':rule_id', $ruleId, PDO::PARAM_INT);
        $stmt->execute();
        
        
        return (int)$this->pdo->lastInsertId();
    }


    /**
     * @brief Ajoute un joueur à une partie existante.
     *
     * @param string $pseudo Le nom d'affichage du joueur.
     * @param int $gameId L'identifiant de la partie à rejoindre.
     * * @return int L'identifiant unique (ID) du joueur créé.
     *
     */
    public function createPlayer(string $pseudo, int $gameId): int {
        
        $sql = "INSERT INTO player (pseudo, game_id) 
                VALUES (:pseudo, :game_id)";
        
        $stmt = $this->pdo->prepare($sql);
        
        
        $stmt->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
        $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmt->execute();
        
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @brief Récupère les informations d'une partie.
     * 
     * Cette méthode effectue deux actions :
     * 1. Récupère les infos de la partie.
     * 2. Récupère la liste des joueurs dde cette partie.
     * 
     * @param int $gameId L'identifiant de la partie recherchée.
     * @return array|null Retourne un tableau associatif contenant les données de la partie 
     * et une clé 'players' (tableau de joueurs), ou NULL si la partie n'existe pas.
     */
    public function getGameInfo(int $gameId): ?array {
        
        
        $sqlGame = "
            SELECT 
                g.game_id, g.status AS game_status, g.started_at, g.ended_at,
                r.name AS rule_name, r.description AS rule_description
            FROM 
                game g JOIN rule r
            ON g.rule_id = r.rule_id
            WHERE 
                g.game_id = :game_id
        ";

        $stmtGame = $this->pdo->prepare($sqlGame);
        $stmtGame->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtGame->execute();
        $gameData = $stmtGame->fetch();

        if (!$gameData) {
            return null;
        }

        
        $sqlPlayers = "
            SELECT player_id, pseudo
            FROM 
                player
            WHERE 
                game_id = :game_id
        ";

        $stmtPlayer = $this->pdo->prepare($sqlPlayers);
        $stmtPlayer->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtPlayer->execute();
        $playerData = $stmtPlayer->fetchAll();

        $gameData['players'] = $playerData;

        return $gameData;

    }

}