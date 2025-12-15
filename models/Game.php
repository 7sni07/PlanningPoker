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

        $stmtGetPlayerInGame = $this->pdo->prepare($sqlPlayerInGame);
        $stmtGetPlayerInGame->bindParam(':pseudo', $playerPseudo, PDO::PARAM_STR);
        $stmtGetPlayerInGame->bindParam(':invite_id', $gameInviteId, PDO::PARAM_STR);
        $stmtGetPlayerInGame->execute();
        $playerInGame = $stmtGetPlayerInGame->fetch();


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
            INSERT INTO backlog_item (game_id, title, description, last_round_number, status)
            VALUES (:game_id, :title, :description, 1,'EN COURS')
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
        $sqlGetBacklogItems = "SELECT 
                    item_id, title, description, estimated_difficulty, status 
                FROM 
                    backlog_item 
                WHERE 
                    game_id = :game_id";
        
        $stmtGetBacklogItems = $this->pdo->prepare($sqlGetBacklogItems);
        $stmtGetBacklogItems->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtGetBacklogItems->execute();
        
        return $stmtGetBacklogItems->fetchAll();
    }

    /**
     * @brief Supprimer le Backlog d'une partie
     * @param int $gameId L'ID de la partie.
     * @return void.
     */
    public function dropBacklogItems(int $gameId) {
        $sqlDropBacklogItems = "
                DELETE  
                FROM 
                    backlog_item 
                WHERE 
                    game_id = :game_id";
        
        $stmtDropBacklogItems = $this->pdo->prepare($sqlDropBacklogItems);
        $stmtDropBacklogItems->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtDropBacklogItems->execute();
    
    }

    /**
     * @brief Modification du statut d'une partie.
     * @param int $gameId L'ID de la partie.
     * @param string $status Le nouveau statut.
     * @return void
     */
    public function updateGameStatus(int $gameId, string $status) {
        $sqlupdateGameStatus = "UPDATE game SET status = :status WHERE game_id = :game_id";
        
        $stmtupdateGameStatus = $this->pdo->prepare($sqlupdateGameStatus);
        $stmtupdateGameStatus->bindParam(':status', $status, PDO::PARAM_STR);
        $stmtupdateGameStatus->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtupdateGameStatus->execute();
    }


    /**
     * @brief Récupère la prochaine tâche à estimer.
     * @param int $gameId
     * @return array|null La tâche ou null s'il n'y a plus rien à voter.
     */
    public function getNextPendingTask(int $gameId): ?array {
        
        $sqlGetNextPendingTask = "SELECT * FROM backlog_item 
                WHERE game_id = :game_id AND estimated_difficulty IS NULL 
                ORDER BY item_id ASC 
                LIMIT 1";
        
        $stmtGetNextPendingTask = $this->pdo->prepare($sqlGetNextPendingTask);
        $stmtGetNextPendingTask->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtGetNextPendingTask->execute();
        
        $task = $stmtGetNextPendingTask->fetch();

        return $task ?: null;
    }

    /**
     * @brief Enregistre les votes des joueurs dans la base de données.
     * @param int $gameId
     * @return void
     */
    public function submitVote(int $backlogItemId, int $playerId, string $value, int $roundNumber){
        
        $sqlSubmitVote = "
            INSERT INTO vote (player_id, item_id, value, round_number)
            VALUES (:player_id, :item_id, :value, :round_number)
            ";
        
        $stmtSubmitVote = $this->pdo->prepare($sqlSubmitVote);
        $stmtSubmitVote->bindParam(':player_id', $playerId, PDO::PARAM_INT);
        $stmtSubmitVote->bindParam(':item_id', $backlogItemId, PDO::PARAM_INT);
        $stmtSubmitVote->bindParam(':value', $value, PDO::PARAM_STR);
        $stmtSubmitVote->bindParam(':round_number', $roundNumber, PDO::PARAM_INT);
        $stmtSubmitVote->execute();
        
    }

    /**
     * @brief Vérifie si TOUS les joueurs ont voté pour un tour donnée
     * Coparaison entre le nombre de joueurs et le nombres des votes
     * @return bool
     */
    public function isRoundComplete(int $gameId, int $itemId, int $roundNumber): bool {

        $sqlCountPlayers = "SELECT COUNT(*) FROM player WHERE game_id = :game_id";
        $stmtCountPlayer = $this->pdo->prepare($sqlCountPlayers);
        $stmtCountPlayer->bindParam(':game_id', $gameId, PDO::PARAM_INT);
        $stmtCountPlayer->execute();
        $nbPlayers = (int)$stmtCountPlayer->fetchColumn();
        
        $sqlCountVotes = "SELECT COUNT(*) FROM vote 
                     WHERE item_id = :item_id AND round_number = :round_number";
        $stmtCountVotes = $this->pdo->prepare($sqlCountVotes);
        $stmtCountVotes->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        $stmtCountVotes->bindParam(':round_number', $roundNumber, PDO::PARAM_INT);
        $stmtCountVotes->execute();
        $nbVotes = (int)$stmtCountVotes->fetchColumn();

        return $nbVotes >= $nbPlayers;
    }

    /**
     * @brief Vérifie si un joueur spécifique a déjà voté pour le tour en cours.
     */
    public function hasPlayerVoted(int $itemId, int $playerId, int $roundNumber): bool {

        $sqlHasPlayerVoted = "SELECT COUNT(*) FROM vote 
                WHERE item_id = :item_id AND player_id = :player_id AND round_number = :round_number";

        $stmtHasPlayerVoted = $this->pdo->prepare($sqlHasPlayerVoted);

        $stmtHasPlayerVoted->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        $stmtHasPlayerVoted->bindParam(':player_id', $playerId, PDO::PARAM_INT);
        $stmtHasPlayerVoted->bindParam(':round_number', $roundNumber, PDO::PARAM_INT);

        $stmtHasPlayerVoted->execute();
        
        return $stmtHasPlayerVoted->fetchColumn() > 0;
    }

    /**
     * @brief Récupère la liste des votes pour un tour donné.
     * @return array
     */
    public function getVotesForRound(int $itemId, int $roundNumber): array {

        $sqlGetVotesForRound = "SELECT p.pseudo, v.value 
                FROM vote v
                JOIN player p ON v.player_id = p.player_id
                WHERE v.item_id = :item_id AND v.round_number = :round_number
                ORDER BY p.pseudo ASC";

        $stmtGetVotesForRound = $this->pdo->prepare($sqlGetVotesForRound);
        $stmtGetVotesForRound->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        $stmtGetVotesForRound->bindParam(':round_number', $roundNumber, PDO::PARAM_INT);
        $stmtGetVotesForRound->execute();
        return $stmtGetVotesForRound->fetchAll();
    }

    /**
     * @brief Enregistrement de la difficulté finale.
     * @return void
     */
    public function validateTaskDifficulty(int $itemId, string $difficulty) {

        $sqlValidateTaskDifficulty = "UPDATE backlog_item 
                SET estimated_difficulty = :difficulty, status = 'VALIDATED' 
                WHERE item_id = :item_id";

        $stmtValidateTaskDifficulty = $this->pdo->prepare($sqlValidateTaskDifficulty);
        $stmtValidateTaskDifficulty->bindParam(':difficulty', $difficulty, PDO::PARAM_STR);
        $stmtValidateTaskDifficulty->bindParam(':item_id', $itemId, PDO::PARAM_INT);

        $stmtValidateTaskDifficulty->execute();
    }

    /**
     * @brief Prépare la même tâche pour un nouveau tour de vote.
     * retourn void
     */
    public function prepareNextRound(int $itemId) {

        $sqlPrepareNextRound = "UPDATE backlog_item 
                SET last_round_number = last_round_number + 1
                WHERE item_id = :item_id";

        $stmtPrepareNextRound = $this->pdo->prepare($sqlPrepareNextRound);
        $stmtPrepareNextRound->bindParam(':item_id', $itemId, PDO::PARAM_INT);
        $stmtPrepareNextRound->execute();
    }




}