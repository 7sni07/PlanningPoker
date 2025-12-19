<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lobby - Partie #<?php echo ($gameData['game_id']); ?> (Planning Poker)</title>
    <style>
        /* Styles de base pour la lisibilité */
        body { font-family: 'Segoe UI', sans-serif; margin: 30px; line-height: 1.6; background-color: #f4f6f8; color: #333; }
        .lobby-container { display: flex; gap: 40px; margin-top: 20px; }
        .game-info, .player-list { background: white; border: 1px solid #ddd; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .game-info { flex: 1; }
        .player-list { flex: 1; }
        .player-list ul { list-style-type: none; padding-left: 0; }
        .player-list li { margin-bottom: 8px; padding: 8px; border-bottom: 1px solid #eee; }
        
        .host-actions { margin-top: 30px; border: 2px solid #007bff; padding: 25px; border-radius: 8px; background: white; }
        .game-id-display { font-size: 2em; color: #007bff; font-weight: bold; margin-top: 5px; background: #e6f7ff; padding: 10px; display: inline-block; border-radius: 5px; }

        /* Nouveaux styles pour le tableau */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: 600; color: #555; }
        
        /* Badges de statut */
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; text-transform: uppercase; }
        .badge-pending { background-color: #e9ecef; color: #495057; }
        .badge-validated { background-color: #d4edda; color: #155724; }
        .badge-current { background-color: #cce5ff; color: #004085; }
        
        button { cursor: pointer; padding: 10px 20px; border-radius: 5px; border: none; font-size: 1em; }
        button[type="submit"] { background-color: #28a745; color: white; transition: background 0.3s; }
        button[type="submit"]:hover { background-color: #218838; }
    </style>
</head>
<body>

    <header>
        <h1>🃏 Salon d'Attente de la Partie</h1>
        <p>
            Règles de validation : <strong><?php echo htmlspecialchars($gameData['rule_name']); ?></strong> 
            (Statut actuel : <?php echo htmlspecialchars($gameData['game_status']); ?>)
        </p>
    </header>

    <div class="lobby-container">

        <section class="game-info">
            <h2>🔗 ID de la Partie</h2>
            <?php if ($is_host): ?>
            <p>Donnez cet ID aux autres joueurs pour qu'ils rejoignent :</p>
             <?php endif; ?>
            <div class="game-id-display">
                <?php echo htmlspecialchars($gameData['invite_id']); ?>
            </div>
        </section>

        <section class="player-list">
            <h2>Participants :</h2>
            <ul>
                <?php foreach ($gameData['players'] as $player): ?>
                    <li>
                        <?php echo htmlspecialchars($player['pseudo']); ?>
                        <?php if ($player['player_id'] === $player_id): ?>
                            <strong>(Vous)</strong>
                        <?php endif; ?>

                        <?php if ($player['is_host']): ?>
                            👑 (Hôte)
                        <?php else:?>
                            👤 (Invité)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if(count($gameData['players']) - ($gameData['nb_invited_players'] + 1) != 0):?>
                <hr>
                <p><strong>Inviter des participants manquants :</strong></p>
                <form action="index.php?action=invite_player" method="POST">
                    <?php for($i=0; $i<$gameData['nb_invited_players']; $i++): ?>
                        <div style="margin-bottom: 5px;">
                            <label for="pseudo_<?= $i ?>">Pseudo :</label>
                            <input type="text" id="pseudo_<?= $i ?>" name="pseudo[]" required>
                            <input type=hidden name="gameID" value="<?php echo $gameData['game_id']; ?>" >
                        </div>
                    <?php endfor; ?>
                    <button type="submit" style="background-color: #17a2b8; margin-top: 10px;">Inviter</button>
                </form>
            <?php endif; ?>
        </section>

    </div>

    <?php if ($is_host): ?>
        <section class="host-actions">
            <h2>⚙️ Configuration du Jeu (Hôte Uniquement)</h2>

            <?php if (empty($gameData['backlog_items'])): ?>
                
                <h3>Importer le Backlog (User Stories)</h3>
                <p>Chargez le fichier JSON contenant la liste des fonctionnalités à estimer.</p>
                
                <form action="index.php?action=import_backlog" method="POST" enctype="multipart/form-data">
                    <label for="backlog_file">Fichier JSON :</label>
                    <input type="file" id="backlog_file" name="backlog_file" accept=".json" required>
                    <br><br>
                    <button type="submit" style="background-color: #007bff;">Charger le Backlog</button>
                </form>

            <?php else: ?>
                
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;">
                    ✅ <strong>Backlog chargé !</strong> Le fichier contient <?php echo count($gameData['backlog_items']); ?> tâches.
                </div>

            <?php endif; ?>

            <hr>

            <h3>Aperçu des User Stories</h3>
            
            <?php if (!empty($gameData['backlog_items'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Statut</th> <th>Difficulté</th> </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gameData['backlog_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                
                                <td>
                                    <?php 
                                        $statusClass = 'badge-pending';
                                        $statusText = 'À faire';

                                        if ($item['status'] === 'VALIDATED') {
                                            $statusClass = 'badge-validated';
                                            $statusText = 'Terminé';
                                        } elseif ($item['status'] === 'EN COURS') { // Ou le statut actuel
                                            $statusClass = 'badge-current';
                                            $statusText = 'En cours';
                                        }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>

                                <td style="font-weight: bold; text-align: center;">
                                    <?php 
                                        // Si null, on affiche un tiret, sinon la valeur
                                        echo ($item['estimated_difficulty'] !== null) ? htmlspecialchars($item['estimated_difficulty']) : '-'; 
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="font-style: italic; color: #666;">Aucune tâche importée pour le moment.</p>
            <?php endif; ?>

            <hr>

            <h3>Lancer la partie</h3>

            <?php if (isset($_GET['error'])): ?>
                <div style="background-color: #ffcccc; color: red; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <?php 
                        if ($_GET['error'] == 'empty_backlog') {
                            echo "⚠️ Erreur : Vous devez importer un backlog avant de commencer.";
                        } elseif ($_GET['error'] == 'missing_players') {
                            echo "⚠️ Erreur : Veuillez inviter les autres joueurs.";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="index.php?action=start_game" method="POST">
                <button type="submit" style="width: 100%; padding: 15px; font-size: 1.2em;">
                    🚀 Démarrer la Session Planning Poker
                </button>
            </form>

        </section>
    <?php endif; ?>


    <script>
        // Fonction qui interroge le serveur
        function checkGameStatus() {
            fetch('index.php?action=api_check_status')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'IN_GAME') {
                        window.location.href = 'index.php?action=play';
                    }
                })
                .catch(error => console.error('Erreur:', error));
        }

        setInterval(checkGameStatus, 2000);
    </script>
</body>
</html>