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
     * @param string $pseudo L'identifiant du hôte de la partie.
     * * @return int L'identifiant unique (ID) de la partie créée.
     *
     */
    public function createGame(int $ruleId, string $pseudo, int $nbInvitedPlayers): int {
        
        $sql = "INSERT INTO game (rule_id, status, started_at, invite_id, nb_invited_players) 
                VALUES (:rule_id, 'LOBBY', NOW(), :invite_id, :nb_invited_players)";
        
        $stmt = $this->pdo->prepare($sql);
        
        
        $stmt->bindParam(':rule_id', $ruleId, PDO::PARAM_INT);
        $stmt->bindParam(':invite_id', uniqid($pseudo), PDO::PARAM_STR);
        $stmt->bindParam(':nb_invited_players', $nbInvitedPlayers, PDO::PARAM_INT);
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
    public function createPlayer(string $pseudo, int $gameId, bool $is_host): int {
        
        $sql = "
            INSERT INTO player (pseudo, game_id, is_host) 
            VALUES (:pseudo, :game_id, :is_host)
            ";
        
        $stmt = $this->pdo->prepare($sql);
        
        
        $stmt->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
        $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmt->bindParam(':is_host', $is_host, PDO::PARAM_BOOL);
        $stmt->execute();
        
        
        return (int)$this->pdo->lastInsertId();
    }

    public function invitePlayers(array $pseudos, int $gameId){
        

        foreach ($pseudos as $pseudo) {
            $sql = "INSERT INTO player (pseudo, game_id) 
                    VALUES (:pseudo, :game_id)";
            
            $stmt = $this->pdo->prepare($sql);
            
            
            $stmt->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
            $stmt->bindParam(':game_id', $gameId, PDO::PARAM_INT);
            $stmt->execute();
        
        }
        
        
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
                g.game_id, g.status AS game_status, g.started_at, g.ended_at, g.invite_id,g.nb_invited_players,
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
            SELECT player_id, pseudo, game_id, is_host
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

    public function getPlayerInfo(int $playerID): ?array {
        
        $sqlPlayer = "
            SELECT
                player_id, pseudo, game_id, is_host
            FROM
                player
            WHERE
                player_id = :player_id
        ";

        $stmtPlayer = $this->pdo->prepare($sqlPlayer);
        $stmtPlayer->bindParam(':player_id', $playerID, PDO::PARAM_INT);
        $stmtPlayer->execute();
        $playerData = $stmtPlayer->fetch();

        if (!$playerData) {
            return null;
        }

        return $playerData;

    }


    public function getRules(): ?array {
        
        $sqlRule = "
            SELECT * FROM rule
        ";

        $stmtRule = $this->pdo->prepare($sqlRule);
        $stmtRule->execute();
        $rulesData = $stmtRule->fetchAll();

        return $rulesData;
    }

    public function getPlayerInGame(string $playerPseudo, string $gameInviteId): ?array {
        
        $sqlPlayerInGame = "
            SELECT 
                p.player_id, p.pseudo, p.game_id, p.is_host
            FROM 
                player p JOIN game g
            ON p.game_id = g.game_id
            WHERE 
                p.pseudo = :pseudo AND g.invite_id = :invite_id
        ";

        $stmtgetPlayerInGame = $this->pdo->prepare($sqlPlayerInGame);
        $stmtgetPlayerInGame->bindParam(':pseudo', $playerPseudo, PDO::PARAM_STR);
        $stmtgetPlayerInGame->bindParam(':invite_id', $gameInviteId, PDO::PARAM_STR);
        $stmtgetPlayerInGame->execute();
        $playerInGame = $stmtgetPlayerInGame->fetch();


        if (!$playerInGame) {
            return null;
        }

        return $playerInGame;

    }


    /**
     * @brief Importe une liste de fonctionnalités (backlog) dans la BDD.
     * @param int $gameId L'ID de la partie.
     * @param array $items Tableau associatif décodé du JSON.
     */
    public function addBacklogItems(int $gameId, array $BacklogItems) {

        $sqlAddBacklogItems = "
            INSERT INTO backlog_item (game_id, title, description, status)
            VALUES (:game_id, :title, :description, 'EN COURS')
            ";
        
        $stmtAddBacklogItems = $this->pdo->prepare($sqlAddBacklogItems);

        foreach ($BacklogItems as $item) {
            $description = $item['description'] ?? '';
            $title = $item['title'] ?? 'Tâche sans titre';

            $stmtAddBacklogItems->bindParam(':game_id', $gameId, PDO::PARAM_INT);
            $stmtAddBacklogItems->bindParam(':title', $title, PDO::PARAM_STR);
            $stmtAddBacklogItems->bindParam(':description', $description, PDO::PARAM_STR);
            $stmtAddBacklogItems->execute();

    }


    }

    /**
     * @brief Récupère les items du backlog.
     * @param int $gameId L'ID de la partie.
     * @return array Liste des items.
     */
    public function getBacklogItems(int $gameId): array {
        $sqlgetBacklogItems = "SELECT 
                    item_id, title, description, estimated_difficulty, status 
                FROM 
                    backlog_item 
                WHERE 
                    game_id = :game_id";
        
        $stmtgetBacklogItems = $this->pdo->prepare($sqlgetBacklogItems);
        $stmtgetBacklogItems->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtgetBacklogItems->execute();
        
        return $stmtgetBacklogItems->fetchAll();
    }

    /**
     * @brief Supprimer le Backlog d'une partie
     * @param int $gameId L'ID de la partie.
     * @return void.
     */
    public function dropBacklogItems(int $gameId) {
        $sqldropBacklogItems = "
                DELETE  
                FROM 
                    backlog_item 
                WHERE 
                    game_id = :game_id";
        
        $stmtdropBacklogItems = $this->pdo->prepare($sqldropBacklogItems);
        $stmtdropBacklogItems->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtdropBacklogItems->execute();
    
    }


}