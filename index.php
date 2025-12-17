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
    
    case 'resume_game':
        $gameController->resumeGame();
        break;
    
    case 'import_backlog':
        $gameController->importBacklog();
        break;

    case 'start_game':
        $gameController->startGame();
        break;
    
    case 'play':
        $gameController->play();
        break;

    case 'submit_vote':
        $gameController->submitVote();
        break;

    case 'validate_task':
        $gameController->validateTask();
        break;

    case 'next_round':
        $gameController->nextRound();
        break;

    case 'save_game':
        $gameController->saveGame();
        break;
        
    case 'api_check_status':
        $gameController->apiCheckStatus();
        break;
        
    default:
        $gameController->showMenu();
        break;
}