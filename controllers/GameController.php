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

        
        $game_id = (int)($_SESSION['game_id'] ?? 0);
        $player_id = $_SESSION['player_id'] ?? null;
        $is_host = $_SESSION['is_host'] ?? false;

        if ($game_id === null || $player_id === null) {
            
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
        $game_id = (int) trim($_POST['gameID'] ?? 0);

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

            $gameData['backlog_items'] = $backlogItems;

            if (session_status() === PHP_SESSION_NONE) {
            session_start();
            }

            $game_id = (int)($playerInGame['game_id']);
            $player_id = $playerInGame['player_id'];
            $is_host = $playerInGame['is_host'];
            
            $_SESSION['player_id'] = $player_id;
            $_SESSION['game_id'] = $game_id;
            $_SESSION['is_host'] = $is_host;

            include 'views/lobby.php';

        } catch (Exception $e) {
            
            die("Erreur lors de la récupération des informations du lobby : " . $e->getMessage());
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
        $game_id = $_SESSION['game_id'] ?? 0;

        if (!$is_host || $game_id === 0) {
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
}