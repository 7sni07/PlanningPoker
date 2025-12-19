<?php

/** 
 * @file GameController.php
 * @brief Contrôleur de l'application Planning Poker.
 *   
 * Ce fichier contient la logique métier centrale de l'application.
 * Il assure la liaison entre les requêtes de l'utilisateur
 * et la persistance des données.
 * Il gère le flux de la partie : création, lobby, vote, règles et fin de jeu.

*/


require_once 'models/Game.php';

   /** 
    *   @class GameController
    *   @brief Classe qui gère la logique de l'application.
    *   

    */
class GameController {

    /**
     * @var Game $gameModel Instance du model pour la gestion des requetes de la base de donées.
     */
    private Game $gameModel;


    /**
     * @brief Constructeur de la classe.
     * * Initialise l'instance du modèle Game pour les interactions avec la BDD.
     */
    public function __construct() {
        
        $this->gameModel = new Game();
    }


    /**
     * @brief Affiche le menu principal de l'application.
     * Récupère la liste des règles du jeu disponibles depuis le modèle
     * et inclut la vue 'views/menu.php'.
     * @return void
     */
    public function showMenu() {
        

        $rules = $this->gameModel->getRules();
        
        
        include 'views/menu.php';
    }

    

    /**
     * @brief Crée une nouvelle partie.
     * Traite le formulaire de création :
     * 1. Crée une nouvelle entrée 'game' en BDD.
     * 2. Crée le joueur 'Hôte' associé.
     * 3. Initialise les variables de session ($_SESSION).
     * 4. Redirige vers le Lobby.
     * * @return void Redirection.
     */
    public function createGame() {
        
        $pseudo = trim($_POST['pseudo'] ?? '');
        $rule_id = (int)($_POST['rule_id'] ?? 1);
        $nb_invited_players = (int)($_POST['num_players'] ?? 2); 

        if (empty($pseudo)) {
            header("Location: index.php?error=PseudoRequis");
            exit();
        }

        try {
            // Création de la partie
            $game_id = $this->gameModel->createGame($rule_id, $pseudo, $nb_invited_players);
            
            // Création de l'hôte
            $is_host = true;
            $player_id = $this->gameModel->createPlayer($pseudo, $game_id, $is_host);

            // Initialisation de la sesion
            session_start();
            $_SESSION['player_id'] = $player_id;
            $_SESSION['game_id'] = $game_id;
            $_SESSION['is_host'] = true;
            
            header("Location: index.php?action=lobby&game_id=" . $game_id);
            exit();

        } catch (Exception $e) {
            
            header("Location: index.php?error=ErreurBDD");
            exit();
        }
    }
    
    /**
     * @brief Affiche le salon d'attente (Lobby).
     * Cette page permet :
     * - De voir les joueurs connectés.
     * - D'importer le backlog (pour l'hôte).
     * - De lancer la partie.
     * * Si la partie est déjà lancée (IN_GAME), redirige automatiquement vers 'play'.
     * @return void Inclut la vue 'views/lobby.php'.
     */
    public function showLobby() {

        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        
        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $player_id = $_SESSION['player_id'] ?? null;
        $is_host = $_SESSION['is_host'] ?? false;


        // Le cas d'utilisateur non connecté
        if ($game_id === -1 || $player_id === null) {
            
            header("Location: index.php?action=menu&error=not_in_game");
            exit();
        }

        
        try {
            $gameData = $this->gameModel->getGameInfo($game_id);
            $backlogItems = $this->gameModel->getBacklogItems($game_id);
            
            // Partie n'esxiste pas
            if ($gameData === null) {
                session_destroy();
                header("Location: index.php?action=menu&error=game_not_found");
                exit();
            }
            
            // Si la partie est déja commençer.
            if ($gameData['game_status'] === 'IN_GAME') {
                header("Location: index.php?action=play");
                exit();
            }

            // Préparation des données pour la vue
            $gameData['backlog_items'] = $backlogItems;

            
            include 'views/lobby.php';

        } catch (Exception $e) {
            
            die("Erreur lobby : " . $e->getMessage());
        }
    }


    /**
     * @brief Invite des joueurs dans une partie existante.
     * Permet à l'hôte de réserver des places aux participants.
     * Action réservée à l'hôte depuis le Lobby.
     * @return void Redirection vers le Lobby.
     */
    public function invitePlayers() {        

        if (!empty($_POST['pseudo'])) {
            $pseudos = $_POST['pseudo'];
            $game_id = $_POST['gameID'];

            try {
            $this->gameModel->invitePlayers($pseudos,$game_id);
            header("Location: index.php?action=lobby&gameID=" . $game_id);

            } catch (Exception $e) {
            
                die("Erreur linvitation des joueurs : " . $e->getMessage());
            }
        }

    }


    /**
     * @brief Permet à un joueur de rejoindre une partie.
     * Vérifie si le joueur a été invité dans la partie spécifiée.
     * Si oui, initialise la session utilisateur et affiche le lobby.
     * @return void Redirection
     */
    public function joinGame() {        

        $pseudo = trim($_POST['pseudo']);
        $game_invite_id = trim($_POST['gameID'] ?? '');

        try {
            // Vérification des identifiants
            $playerInGame = $this->gameModel->getPlayerInGame($pseudo, $game_invite_id);

            if($playerInGame === null){
                header("Location: index.php?action=menu&error=game_not_found");
                exit();
            }

            $gameData = $this->gameModel->getGameInfo($playerInGame['game_id']);

            // Initialisation session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $game_id = (int)($playerInGame['game_id']);
            $player_id = $playerInGame['player_id'];
            $is_host = $playerInGame['is_host'];
            
            $_SESSION['player_id'] = $player_id;
            $_SESSION['game_id'] = $game_id;
            $_SESSION['is_host'] = $is_host;


            // Redirection selon l'état de la partie
            if ($gameData['game_status'] === 'IN_GAME') {
                header("Location: index.php?action=play");
            } else {
                header("Location: index.php?action=lobby&game_id=" . $game_id);
            }
            exit();

        } catch (Exception $e) {
            
            die("Erreur connexion : " . $e->getMessage());
        }

    }


    /**
     * @brief Importe un backlog depuis un fichier JSON.
     * * Action réservée à l'hôte.
     * Remplace le backlog actuel par le contenu du fichier uploadé.
     * @return void Redirection vers le Lobby
     */
    public function importBacklog() {

        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }

        $is_host = $_SESSION['is_host'] ?? false;
        $game_id = (int)($_SESSION['game_id'] ?? -1);

        if (!$is_host || $game_id === -1) {
            header("Location: index.php?action=lobby&error=unauthorized");
            exit();
        }

        if (isset($_FILES['backlog_file']) && $_FILES['backlog_file']['error'] === UPLOAD_ERR_OK) {

            $tmpName = $_FILES['backlog_file']['tmp_name'];
            $jsonContent = file_get_contents($tmpName);
            $items = json_decode($jsonContent, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($items)) {
                try {
                    // Nettoyage et insertion
                    $this->gameModel->dropBacklogItems($game_id);
                    $this->gameModel->addBacklogItems($game_id, $items);

                    header("Location: index.php?action=lobby&success=backlog_imported");
                    exit();

                } catch (Exception $e) {
                    die("Erreur lors de l'import du Backlog : " . $e->getMessage());
                }
            } else {
                // Erreur JSON invalide, on recharge le lobby
                $this->showLobby();
            }
        } else {
            // Erreur Upload
            $this->showLobby();
        }
    }

    /**
     * @brief Lance la partie.
     * * Vérifie les pré-requis (Backlog non vide, joueurs présents).
     * Gere aussi la reprise d'une partie mise en pause.
     * @return void Redirection vers l'écran de jeu (play).
     */
    public function startGame() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $is_host = $_SESSION['is_host'] ?? false;

        if (!$is_host || $game_id === -1) {
            header("Location: index.php?action=lobby&error=unauthorized");
            exit();
        }

        try {
            $gameData = $this->gameModel->getGameInfo($game_id);
            $backlogItems = $this->gameModel->getBacklogItems($game_id);

            // Vérifications Backlog
            if (empty($backlogItems)) {
                header("Location: index.php?action=lobby&error=empty_backlog");
                exit();
            }

            // Gestion de la sortie de PAUSE
            // On passe au round suivant après le relancement de la partie
            if ($gameData['game_status'] === 'PAUSE') {
                $currentTask = $this->gameModel->getNextPendingTask($game_id);
                if ($currentTask) {
                    $this->gameModel->prepareNextRound($currentTask['item_id']);
                }
            }

            // Changement de statut de la partie
            $this->gameModel->updateGameStatus($game_id, 'IN_GAME');

            header("Location: index.php?action=play");
            exit();

            } catch (Exception $e) {
            
                die("Erreur lancement de la partie : " . $e->getMessage());
        }

    }


    /**
     * @brief Contrôleur principal de l'écran de jeu (Table de Poker).
     * Cette méthode est appelée à chaque chargement de page ou rafraîchissement avec JS.
     * Elle :
     * 1. Vérifie le statut du jeu (Pause, Fini, En cours).
     * 2. Récupère la tâche actuelle.
     * 3. Vérifie si le tour de vote est terminé.
     * 4. Appelle l'arbitre (calculateVoteResult) pour déterminer l'issue (Succès/Conflit).
     * @return void Inclut la vue 'views/play.php' ou 'views/game_over.php'.
     */
public function play() {
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $player_id = $_SESSION['player_id'] ?? null;
        $is_host = $_SESSION['is_host'] ?? false;

        if ($game_id === -1 || $player_id === null) {
            header("Location: index.php?action=menu");
            exit();
        }

        try {
            $gameData = $this->gameModel->getGameInfo($game_id);


            if ($gameData['game_status'] === 'PAUSE') {
                include 'views/play.php';
                return;
            }

            if ($gameData['game_status'] === 'LOBBY') {
                header("Location: index.php?action=lobby&game_id=" . $game_id);
                exit();
            }

            $currentTask = $this->gameModel->getNextPendingTask($game_id);

            // Fin de partie, tous les tâches sont éstimées
            if (!$currentTask) {
                if ($gameData['game_status'] !== 'FINISHED') {
                    $this->gameModel->updateGameStatus($game_id, 'FINISHED');
                }
                $finalBacklog = $this->gameModel->getBacklogItems($game_id);
                include 'views/game_over.php';
                exit();
            }

            $round_number = (int)($currentTask['last_round_number'] ?? 1);
            $item_id = $currentTask['item_id'];

            // Vérification de l'avancement du vote
            $isRoundFinished = $this->gameModel->isRoundComplete($game_id, $item_id, $round_number);

            $showDebateMode = false;
            $votesDetails = [];
            $isSuccess = false;
            $suggestedValue = null;

            // Si tout le monde a voté, on analyse les résultats
            if ($isRoundFinished) {
                if ($currentTask['status'] !== 'VALIDATED') {
                    
                    $showDebateMode = true;
                    $votesDetails = $this->gameModel->getVotesForRound($item_id, $round_number);
                    $hasVoted = true; 

                    // Récupération de la règle du jeu
                    $ruleId = (int)($gameData['rule_id'] ?? 1); 

                    // Appel de la fonction qui décide
                    $voteResutlt = $this->calculateVoteResult($votesDetails, $ruleId, $round_number);

                    if ($voteResutlt['status'] === 'PAUSE') {
                        $this->gameModel->updateGameStatus($game_id, 'PAUSE');
                        header("Location: index.php?action=play&result=coffee_break");
                        exit();

                    } elseif ($voteResutlt['status'] === 'SUCCESS') {
                        $isSuccess = true;
                        $suggestedValue = $voteResutlt['value'];
                        
                    } else {
                        $isSuccess = false;
                    }
                }
            }

            $cards = [0, 1, 2, 3, 5, 8, 13, 20, 40, 100, '?', 'coffee'];

            if (!$showDebateMode) {
                $hasVoted = $this->gameModel->hasPlayerVoted($item_id, $player_id, $round_number);
            }

            include 'views/play.php';

        } catch (Exception $e) {
            die("Erreur play : " . $e->getMessage());
        }
    }

    /**
     * @brief Enregistre le vote d'un joueur.
     * Traite le formulaire POST envoyé depuis la table de jeu.
     * Si le vote complète le tour, déclenche la vérification de la "Pause Café".
     * @return void Redirige vers play().
     */
    public function submitVote() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $player_id = $_SESSION['player_id'] ?? null;
        $game_id = (int)($_SESSION['game_id'] ?? -1);

        if ($player_id === null || $game_id === -1) {
            header("Location: index.php?action=menu");
            exit();
        }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $item_id = (int)($_POST['item_id'] ?? -1);
                $vote_value = trim($_POST['vote_value'] ?? '?');
                
                if ($item_id >= 0) {
                    try {
                        $currentTask = $this->gameModel->getNextPendingTask($game_id);

                        if ($currentTask) {
                            $round_number = (int)($currentTask['last_round_number'] ?? 1);

                            $this->gameModel->submitVote($item_id, (int)$player_id, $vote_value, $round_number);

                            // Vérification fin de tour
                            if ($this->gameModel->isRoundComplete($game_id, $item_id, $round_number)) {
                                // On vérifie si c'est une pause café
                                $this->detectPauseGame($game_id, $item_id, $round_number);

                            } else {
                                header("Location: index.php?action=play&status=waiting_others");
                                exit();
                            }
                        }
                
                    } catch (Exception $e) {
            
                        die("Erreur lors du vote : " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @brief Analyse les votes selon la règle.
     * Analyse un tableau de votes et détermine le résultat selon la règle choisie (Moyenne, Médiane...).
     * @param array $votes Tableau des votes bruts.
     * @param int $ruleId ID de la règle active.
     * @param int $roundNumber Numéro du tour actuel (Round 1 est toujours mode strict).
     * @return array ['status' => 'SUCCESS'|'CONFLICT'|'PAUSE', 'value' => la valeur]
     */
    private function calculateVoteResult(array $votes, int $ruleId, int $roundNumber): array {
        
        if (empty($votes)){
            return ['status' => 'CONFLICT', 'value' => null];
        }

        $values = array_column($votes, 'value');
        
        // les '?' seront un probleme pour les calculs mathématiques.
        $numericValues = array_filter($values, function($v) {
            return is_numeric($v);
        });

        $uniqueValues = array_unique($values);

        
        if (count($uniqueValues) === 1 && reset($uniqueValues) === 'coffee') {
            return ['status' => 'PAUSE', 'value' => 'coffee'];
        }

        // ROUND 1 : TOUJOURS UNANIMITÉ (Peu importe la règle)
        if ($roundNumber === 1) {
            if (count($uniqueValues) === 1) {
                return ['status' => 'SUCCESS', 'value' => reset($uniqueValues)];
            }
            return ['status' => 'CONFLICT', 'value' => null];
        }

        // Règles spécifiques (Round > 1)
        switch ($ruleId) {
            case 1: // Strict (Unanimité)
                if (count($uniqueValues) === 1) {
                    return ['status' => 'SUCCESS', 'value' => reset($uniqueValues)];
                }
                break;

            case 2: // Moyenne Arrondie à l'entier le plus proche
                if (count($numericValues) > 0) {
                    $avg = array_sum($numericValues) / count($numericValues);
                    $final = round($avg); 
                    return ['status' => 'SUCCESS', 'value' => $final];
                }
                break;

            case 3: // Médiane
                if (count($numericValues) > 0) {
                    sort($numericValues);
                    $count = count($numericValues);
                    $middle = floor(($count - 1) / 2);
                    return ['status' => 'SUCCESS', 'value' => $numericValues[$middle]];
                }
                break;

            case 4: // Majorité Absolue (> 50%)
                $counts = array_count_values($values);
                $totalVotes = count($values);
                arsort($counts);
                $topValue = array_key_first($counts);
                $topCount = current($counts);

                if ($topCount > ($totalVotes / 2)) {
                    return ['status' => 'SUCCESS', 'value' => $topValue];
                }
                break; // Pas de majorité absolue = Conflit

            case 5: // Majorité Relative (Le plus grand nombre de votes)
                $counts = array_count_values($values);
                arsort($counts);
                $topValue = array_key_first($counts);
                $topCount = current($counts);
                
                // Vérifier s'il y a une égalité de vote pour quelque choix
                $keys = array_keys($counts, $topCount);
                if (count($keys) === 1) {
                    return ['status' => 'SUCCESS', 'value' => $topValue];
                }
                // Si égalité = Conflit
                break;
        }

        // Par défaut : Conflit
        return ['status' => 'CONFLICT', 'value' => null];
    }

    /**
     * @brief Traite le résultat après le dernier vote.
     * Appelé directement après un vote complet pour basculer le statut du jeu immédiatement
     * Sert à detecter si les joueurs veulent une pause
     */
    private function detectPauseGame(int $gameId, int $itemId, int $roundNumber) {
        
        $votes = $this->gameModel->getVotesForRound($itemId, $roundNumber);
        
        if (!empty($votes)) {
            $values = array_column($votes, 'value');
            $uniqueValues = array_unique($values);

            if (count($uniqueValues) === 1 && reset($uniqueValues) === 'coffee') {
                
                $this->gameModel->updateGameStatus($gameId, 'PAUSE');

                header("Location: index.php?action=play&result=coffee_break");
                exit();
            }
        }
        // Si pas de pause, retour au jeu normal
        header("Location: index.php?action=play");
        exit();
    }


    /**
     * @brief Permet de valider la valeur de l'estimation.
     * Action déclenchée par l'Hôte lorsqu'un résultat (succès) a été trouvé.
     * Enregistre la valeur finale, marque la tâche comme VALIDATED et passe à la suivante.
     * @return void
     */
    public function validateTask() {

        if (session_status() === PHP_SESSION_NONE){
            session_start();
        } 

        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $is_host = $_SESSION['is_host'] ?? false;

        if (!$is_host || $game_id === -1) { 
            header("Location: index.php"); 
            exit(); 
        }

        $currentTask = $this->gameModel->getNextPendingTask($game_id);
        $round = $currentTask['last_round_number'];
        $votes = $this->gameModel->getVotesForRound($currentTask['item_id'], $round);
        $gameData = $this->gameModel->getGameInfo($game_id);
        
        if (!empty($votes)) {

            $voteResult = $this->calculateVoteResult($votes, $gameData['rule_id'], $round);
            
            if ($voteResult['status'] === 'SUCCESS') {
                $finalValue = $voteResult['value'];
                $this->gameModel->validateTaskDifficulty($currentTask['item_id'], $finalValue);
                header("Location: index.php?action=play&result=success&val=".$finalValue);
                exit();
            }
        }
        header("Location: index.php?action=play");
    }

    /**
     * @brief Lance un nouveau tour de vote (Round N+1) pour la tâche en cours.
     * Action déclenchée par l'Hôte en cas de désaccord (Conflit).
     */
    public function nextRound() {
        
        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }
        
        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $is_host = $_SESSION['is_host'] ?? false;

        if ($game_id === -1 || !$is_host) {
            header("Location: index.php?action=play");
            exit();
        }

        $currentTask = $this->gameModel->getNextPendingTask($game_id);
        if ($currentTask) {
            $this->gameModel->prepareNextRound($currentTask['item_id']);
        }

        header("Location: index.php?action=play");
        exit();
    }

    /**
     * @brief Exporte l'état actuel de la partie au format JSON.
     */
    public function saveGame() {

        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }

        $game_id = (int)($_SESSION['game_id'] ?? -1);

        if ($game_id === -1) {
            header("Location: index.php?action=menu");
            exit();
        }

        $gameInfo = $this->gameModel->getGameInfo($game_id);
        $items = $this->gameModel->getBacklogItems($game_id);

        // Construction du JSON
        $gameInfo = [
            'game_id' => $gameInfo['game_id'],
            'game_status' => $gameInfo['game_status'],
            'started_at' => $gameInfo['started_at'],
            'ended_at' => $gameInfo['ended_at'],
            'invite_id' => $gameInfo['invite_id'],
            'nb_invited_players' => $gameInfo['nb_invited_players'],
            'rule_name' => $gameInfo['rule_name'],
            'rule_description' => $gameInfo['rule_description']
        ];

        $BacklogData = [];
        foreach ($items as $item) {
            $BacklogData[] = [
                'title' => $item['title'],
                'description' => $item['description'],
                'estimated_difficulty' => $item['estimated_difficulty'] ?? null,
                'status' => $item['estimated_difficulty'] ? 'VALIDATED' : 'PENDING'
            ];
        }

        $gameInfo['backlog_items'] = $BacklogData;

        // Envoi du fichier
        $filename = "planning_poker_save_" . date('Y-m-d_H-i') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($gameInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * @brief Relancer une partie mise en pause à travers le fichier JSON importé.
     * Vérifie la validité du fichier et reconnecte l'hôte à la partie existante.
     * Si la partie est marquée "FINISHED", refuse la reprise.
     */
    public function resumeGame() {
        
        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }


        if (!isset($_FILES['save_file']) || $_FILES['save_file']['error'] !== UPLOAD_ERR_OK) {
            header("Location: index.php?action=menu&error=upload_error");
            exit();
        }

        // Lecture JSON
        $jsonContent = file_get_contents($_FILES['save_file']['tmp_name']);
        $data = json_decode($jsonContent, true);

        // Validation Données
        if (!$data || !isset($data['game_id'])) {
            header("Location: index.php?action=menu&error=invalid_file");
            exit();
        }

        // Bloquer si la partie est fini
        if ($data['game_status'] === "FINISHED"){
            header("Location: index.php?action=menu&info=game_finished");
            exit();
        }

        $gameId = (int)$data['game_id'];

        try {
            $gameInfo = $this->gameModel->getGameInfo($gameId);

            if (!$gameInfo) {
                header("Location: index.php?action=menu&error=game_not_found_db");
                exit();
            }

            // Identification Hôte
            $hostPlayer = $this->gameModel->getHostPlayer($gameId);

            if (!$hostPlayer) {
                die("Erreur critique : Impossible de trouver l'hôte de cette partie.");
            }

            // Remplire la session de l'hôte
            $_SESSION['game_id'] = $gameId;
            $_SESSION['player_id'] = $hostPlayer['player_id'];
            $_SESSION['is_host'] = true;

            header("Location: index.php?action=lobby&game_id=" . $gameId);
            exit();

        } catch (Exception $e) {
            die("Erreur lors de la reprise : " . $e->getMessage());
        }
    }


    /**
     * @brief API : Renvoie le statut actuel de la partie en JSON.
     * Appelée en AJAX par les clients (JS) toutes les 2 secondes pour savoir
     * s'ils doivent changer de page (ex: Lobby -> Jeu).
     * @return void JSON {status: "IN_GAME" | "LOBBY" | ...}
     */
    public function apiCheckStatus() {
        
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }
        $game_id = (int)($_SESSION['game_id'] ?? -1);

        if ($game_id === -1) {
            echo json_encode(['status' => 'ERROR', 'message' => 'No session']);
            exit;
        }

        try {
            $gameData = $this->gameModel->getGameInfo($game_id);
            echo json_encode([
                'status' => $gameData['game_status']
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR']);
        }
        exit;
    }
}