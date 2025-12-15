<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lobby - Partie #<?php echo ($gameData['game_id']); ?> (Planning Poker)</title>
    <style>
        /* Styles de base pour la lisibilité */
        body { font-family: sans-serif; margin: 30px; line-height: 1.6; }
        .lobby-container { display: flex; gap: 40px; }
        .game-info, .player-list { border: 1px solid #ddd; padding: 15px; border-radius: 6px; }
        .game-info { background: #f9f9f9; }
        .player-list ul { list-style-type: disc; padding-left: 20px; }
        .player-list li { margin-bottom: 5px; }
        .host-actions { margin-top: 30px; border: 2px solid #007bff; padding: 20px; border-radius: 8px; background: #e6f7ff; }
        .game-id-display { font-size: 1.5em; color: #007bff; font-weight: bold; margin-top: 5px; }
    </style>
</head>
<body>

    <header>
        <h1>🃏 Salon d'Attente de la Partie</h1>
        <p>
            Règles de validation : <strong><?php echo $gameData['rule_name']; ?></strong> 
            (Statut actuel : <?php echo $gameData['game_status']; ?>)
        </p>
    </header>

    <div class="lobby-container">

        <section class="game-info">
            <h2>🔗 ID de la Partie</h2>
            <?php if ($is_host): ?>
            <p>Donnez cet ID aux autres joueurs pour qu'ils rejoignent :</p>
             <?php endif; ?>
            <div class="game-id-display">
                <?php echo $gameData['invite_id']; ?>
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
                            (Hôte)
                        <?php else:?>
                            (Invité)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if(count($gameData['players'])- ($gameData['nb_invited_players'] + 1)!= 0):?>

            <p>Inviter les autres participants...</p>

            <ul>

                <form action="index.php?action=invite_player" method="POST">

                <?php for($i=0; $i<$gameData['nb_invited_players']; $i++): ?>

                    <li>

                        <label for="pseudo_<?= $i ?>">Pseudo :</label>

                        <input type="text" id="pseudo_<?= $i ?>" name="pseudo[]" required>

                        <input type=hidden name="gameID" value="<?php echo $gameData['game_id']; ?>" >

                    </li>


                <?php endfor; ?>

                <button type="submit">Inviter</button>

                </form>

            </ul>

            <?php endif; ?>
        </section>

    </div>

    <?php if ($is_host): ?>
        <section class="host-actions">
            <h2>⚙️ Configuration du Jeu (Hôte Uniquement)</h2>

            <h3>Importer le Backlog (User Stories)</h3>
            <p>Chargez le fichier JSON contenant la liste des fonctionnalités à estimer.</p>
            
            <form action="index.php?action=import_backlog" method="POST" enctype="multipart/form-data">
                <label for="backlog_file">Fichier JSON :</label>
                <input type="file" id="backlog_file" name="backlog_file" accept=".json" required>
                <br><br>
                <button type="submit">Charger le Backlog</button>
            </form>

            <hr>

            <h3>Aperçu des User Stories importées</h3>
            
            <?php if (!empty($gameData['backlog_items'])): ?>
                <table border="1" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #ddd;">
                            <th>Titre</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gameData['backlog_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
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
                            echo "⚠️ Erreur : Veuillez inviter les autres joueur.";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="index.php?action=start_game" method="POST">
                <button type="submit">Démarrer la Session Planning Poker</button>
            </form>

        </section>
    <?php endif; ?>





    <script>
        // Fonction qui interroge le serveur
        function checkGameStatus() {
            // On appelle l'URL de l'API créée à l'étape 1
            fetch('index.php?action=api_check_status')
                .then(response => response.json()) // On convertit la réponse en objet JS
                .then(data => {
                    console.log("Statut reçu :", data.status); // Pour le débogage (F12)

                    // SI LE JEU A COMMENCÉ
                    if (data.status === 'IN_GAME') {
                        // Redirection automatique !
                        window.location.href = 'index.php?action=play';
                    }
                    
                })
                .catch(error => console.error('Erreur:', error));
        }

        // On lance cette vérification toutes les 2000 millisecondes (2 secondes)
        setInterval(checkGameStatus, 2000);
    </script>
</body>
</html>