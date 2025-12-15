<?php

/** 
 * @file GameController.php
 * @brief Logique metier de l'application
 *   
 * Fichier responsable a gérer les régles de fonctionnement de l'application,
 * Il orchestre la relation entre le model et les vues.

*/


require_once 'models/Game.php';

   /** 
    *   @class GameController
    *   @brief Classe qui gère la logique metier de l'application
    *   

    */
class GameController {

    /**
     * @var Game $gameModel Instance du model pour la gestion des requetes de la base de donées.
     */
    private Game $gameModel;


    /**
     * @brief Constructeur de la classe.
     * * Initialise l'instance du modèle Game pour les interactions BDD.
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
     * Traite les données envoyées en POST (pseudo, règle, nombre de joueurs).
     * Crée la partie en BDD, inscrit le créateur comme hôte, initialise la session
     * et redirige vers le lobby.
     * * @return void Redirige vers index.php (lobby ou erreur).
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
            
            $game_id = $this->gameModel->createGame($rule_id, $pseudo, $nb_invited_players);
            

            $is_host = true;
            $player_id = $this->gameModel->createPlayer($pseudo, $game_id, $is_host);

            
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
     * @brief Affiche le lobby d'une partie en cours.
     * Vérifie si l'utilisateur est connecté via la session.
     * Récupère les informations de la partie et charge la vue 'views/lobby.php'.
     * @return void
     * @throws Exception Si une erreur survient lors de la récupération des infos du jeu.
     */
    public function showLobby() {

        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        
        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $player_id = $_SESSION['player_id'] ?? null;
        $is_host = $_SESSION['is_host'] ?? false;

        if ($game_id === -1 || $player_id === null) {
            
            header("Location: index.php?action=menu&error=not_in_game");
            exit();
        }

        
        try {
            
            $gameData = $this->gameModel->getGameInfo($game_id);

            $backlogItems = $this->gameModel->getBacklogItems($game_id);
            
            if ($gameData === null) {
                session_destroy();
                header("Location: index.php?action=menu&error=game_not_found");
                exit();
            }
            
            # Si la partie est déja commençer.
            if ($gameData['game_status'] === 'IN_GAME') {
                header("Location: index.php?action=play");
                exit();
            }

            $gameData['backlog_items'] = $backlogItems;

            
            include 'views/lobby.php';

        } catch (Exception $e) {
            
            die("Erreur lors de la récupération des informations du lobby : " . $e->getMessage());
        }
    }


    /**
     * @brief Invite des joueurs dans une partie existante.
     * Récupère une liste de pseudos et l'ID de la partie via POST inséré par le hôte de la partie,
     * les ajoute en base de données, puis redirige vers le lobby.
     * @return void
     */
    public function invitePlayers() {        

        if (!empty($_POST['pseudo'])) {

        $pseudos = $_POST['pseudo'];
        $game_id = $_POST['gameID'];

        try {
        
            $this->gameModel->invitePlayers($pseudos,$game_id);


            header("Location: index.php?action=lobby&gameID=" . $game_id);

        } catch (Exception $e) {
            
            die("Erreur lors de la récupération des informations du lobby : " . $e->getMessage());
        }
    }

    }


    /**
     * @brief Permet à un joueur de rejoindre une partie.
     * Vérifie si le joueur a été invité dans la partie spécifiée.
     * Si oui, initialise la session utilisateur et affiche le lobby.
     * @return void
     */
    public function joinGame() {        

        $pseudo = trim($_POST['pseudo']);
        $game_invite_id = trim($_POST['gameID'] ?? '');

        try {
        
            $playerInGame = $this->gameModel->getPlayerInGame($pseudo, $game_invite_id);

            if($playerInGame === null){
                header("Location: index.php?action=menu&error=game_not_found");
                exit();
            }

            $gameData = $this->gameModel->getGameInfo($playerInGame['game_id']);
            $backlogItems = $this->gameModel->getBacklogItems($gameData['game_id']);


            if (session_status() === PHP_SESSION_NONE) {
            session_start();
            }

            $game_id = (int)($playerInGame['game_id']);
            $player_id = $playerInGame['player_id'];
            $is_host = $playerInGame['is_host'];
            
            $_SESSION['player_id'] = $player_id;
            $_SESSION['game_id'] = $game_id;
            $_SESSION['is_host'] = $is_host;



            if ($gameData['game_status'] === 'IN_GAME') {
                
                header("Location: index.php?action=play");
            } else {
                
                header("Location: index.php?action=lobby&game_id=" . $game_id);
            }
            exit();

        } catch (Exception $e) {
            
            die("Erreur lors de la connexion à la partie : " . $e->getMessage());
        }

    }


    /**
     * @brief Gère l'importation du fichier JSON de backlog.
     * @return void
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
                    
                    $this->gameModel->dropBacklogItems($game_id);
                    $this->gameModel->addBacklogItems($game_id, $items);

                    header("Location: index.php?action=lobby&success=backlog_imported");
                    exit();

                } catch (Exception $e) {
                    die("Erreur lors de l'import du Backlog : " . $e->getMessage());
                }
            } else {
                // TODO : les gestions des erreurs à faire
                // die("Erreur : Le fichier n'est pas un JSON valide.");

                GameController::showLobby();
            }
        } else {
            // TODO : les gestions des erreurs à faire
            //die("Erreur lors du téléchargement du fichier.");

            GameController::showLobby();
        }
    }

    /**
     * @brief Lance la partie.
     * @return void
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

            if (empty($backlogItems)) {
                header("Location: index.php?action=lobby&error=empty_backlog");
                exit();
            }

            $currentPlayersCount = count($gameData['players']);
            $expectedPlayersCount = $gameData['nb_invited_players'];

            if ($currentPlayersCount < $expectedPlayersCount) {
                header("Location: index.php?action=lobby&error=missing_players");
                exit();
            }

            $this->gameModel->updateGameStatus($game_id, 'IN_GAME');

            header("Location: index.php?action=play");
            exit();

            } catch (Exception $e) {
            
                die("Erreur lors du lancement de la partie : " . $e->getMessage());
        }

    }


    /**
     * @brief Affiche la partie.
     * @return void
     */
    public function play() {
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $game_id = (int)($_SESSION['game_id'] ?? -1);
        $player_id = $_SESSION['player_id'] ?? null;
        $is_host = $_SESSION['is_host'] ?? false;

        if ($game_id === -1 || $player_id === null) {
            header("Location: index.php?action=menu");
            exit();
        }
        try{

            $gameData = $this->gameModel->getGameInfo($game_id);

            $isGamePaused = ($gameData['game_status'] === 'PAUSE');
        
            if ($gameData['game_status'] === 'LOBBY') {
                header("Location: index.php?action=lobby&game_id=" . $game_id);
                exit();
            }

            $currentTask = $this->gameModel->getNextPendingTask($game_id);

            if (!$currentTask) {
                // TODO: resultat des éstimations
                echo "<h1>🏆 Bravo ! La partie est terminée.</h1>";
                echo "<p>Toutes les tâches ont été estimées.</p>";
                exit();
            }

            $round_number = (int)($currentTask['last_round_number'] ?? 1);
            $item_id = $currentTask['item_id'];

            $isRoundFinished = $this->gameModel->isRoundComplete($game_id, $item_id, $round_number);

            $showDebateMode = false;
            $votesDetails = [];

            if ($isRoundFinished) {

                if ($currentTask['status'] !== 'VALIDATED') {

                    $showDebateMode = true;

                    $votesDetails = $this->gameModel->getVotesForRound($item_id, $round_number);
                    
                    $hasVoted = true; 
                }
            }

            $cards = [0, 1, 2, 3, 5, 8, 13, 20, 40, 100, '?', 'coffee'];

            
            if (!$showDebateMode) {
                    $hasVoted = $this->gameModel->hasPlayerVoted($item_id, $player_id, $round_number);
            }



            include 'views/play.php';


        } catch (Exception $e) {
            die("Erreur lors du chargement de la partie : " . $e->getMessage());
        }
        
    }

    /**
     * @brief Traite l'enregisrement des votes pour chaque joueurs.
     * @return void
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

                            if ($this->gameModel->isRoundComplete($game_id, $item_id, $round_number)) {

                                $this->processVotingResult($game_id, $item_id, $round_number);

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
     * @brief Logique du mode du jeu "Pour l'instant mode strict".
     * Cette fonction est appelée automatiquement dès que le dernier joueur a voté.
     * @return void
     */
    private function processVotingResult(int $gameId, int $itemId, int $roundNumber) {
        
        $votes = $this->gameModel->getVotesForRound($itemId, $roundNumber);
        
        $values = array_column($votes, 'value');

        $uniqueValues = array_unique($values);
        $isUnanimous = (count($uniqueValues) === 1);

        if ($isUnanimous) {
            
            $finalValue = $uniqueValues[0];

            if ($finalValue === 'coffee') {
                
                $this->gameModel->updateGameStatus($gameId, 'PAUSE');

                header("Location: index.php?action=play&result=coffee_break");
                exit();
            }
            
            $this->gameModel->validateTaskDifficulty($itemId, $finalValue);
            
            header("Location: index.php?action=play&result=success&val=" . $finalValue);

        } else {
            
            header("Location: index.php?action=play&result=debate");
        }
        exit();
    }

    /**
     * @brief Action déclenchée par l'hôte pour passer au tour suivant après un débat.
     */
    public function nextRound() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
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
     * @brief Génère le fichier JSON de sauvegarde de la partie "Pause Café".
     */
    public function saveGame() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $game_id = (int)($_SESSION['game_id'] ?? -1);

        if ($game_id === -1) {
            header("Location: index.php?action=menu");
            exit();
        }

        $gameInfo = $this->gameModel->getGameInfo($game_id);
        
        $items = $this->gameModel->getBacklogItems($game_id);

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

        // 3. Forcer le téléchargement
        $filename = "planning_poker_save_" . date('Y-m-d_H-i') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($gameInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }


    /**
     * @brief API : Renvoie le statut actuel de la partie en JSON.
     * Appelée par le Javascript du lobby toutes les X secondes.
     */
    public function apiCheckStatus() {
        // Pas d'affichage HTML, on veut du JSON
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();
        $game_id = (int)($_SESSION['game_id'] ?? -1);

        if ($game_id === -1) {
            echo json_encode(['status' => 'ERROR', 'message' => 'No session']);
            exit;
        }

        try {
            // On récupère juste les infos (tu pourrais optimiser le Modèle pour ne récupérer QUE le status)
            $gameData = $this->gameModel->getGameInfo($game_id);
            
            // On renvoie la réponse au format JSON
            echo json_encode([
                'status' => $gameData['game_status']
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR']);
        }
        exit; // Important : on arrête le script ici pour ne pas renvoyer le reste de la page
    }
}