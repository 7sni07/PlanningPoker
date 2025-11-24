<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'controllers/GameController.php';


$gameController = new GameController();


$action = $_GET['action'] ?? 'menu';

switch ($action) {
    case 'menu':
        $gameController->showMenu();
        break;
        
    case 'create_game':
        $gameController->createGame();
        break;
        
    case 'lobby':
        $gameController->showLobby();
        break;
    
    case 'invite_player':
        $gameController->invitePlayers();
        break;

    case 'join_game':
        $gameController->joinGame();
        break;
    
    case 'import_backlog':
        $gameController->importBacklog();
        break;
        
    default:
        $gameController->showMenu();
        break;
}