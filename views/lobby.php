<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <link rel='stylesheet' type='text/css' href='./css/LobbyStyle.css'>

    <title>Lobby – Partie #<?php echo $gameData['game_id']; ?> (Planning Poker)</title>
    

</head>

<body>

<div class="lobby-wrapper">

    <div class="card-box">
        <h1>🃏 Salon d'attente</h1>

        <div class="header-meta">
            <strong>Règles :</strong> <?= $gameData['rule_name']; ?>
            <span style="margin: 0 10px;">|</span>
            <strong>Statut :</strong> <?= $gameData['game_status']; ?>
        </div>
    </div>

    <div class="split-layout">

        <!-- Colonne gauche : infos joueurs -->
        <div class="col-left">

            <div class="card-box" style="margin-bottom: 22px;">
                <h2>🔗 Identifiant de la partie</h2>

                <?php if ($is_host): ?>
                    <p>Partagez ce code avec l’équipe :</p>
                <?php endif; ?>

                <div class="invite-code-box">
                    <?php echo $gameData['invite_id']; ?>
                </div>
            </div>

            <div class="card-box">
                <h2>👥 Participants</h2>

                <ul class="player-list-ul">
                    <?php foreach ($gameData['players'] as $p): ?>
                        <li class="player-item">
                            <span><?= htmlspecialchars($p['pseudo']); ?></span>
                            <div>
                                <?php if ($p['is_host']): ?>
                                    <span class="badge badge-host">Hôte</span>
                                <?php endif; ?>

                                <?php if ($p['player_id'] === $player_id): ?>
                                    <span class="badge badge-you">Moi</span>
                                <?php endif; ?>

                                <?php if (!$p['is_host'] && $p['player_id'] !== $player_id): ?>
                                    <span class="badge badge-guest">Invité</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php 
                    if ((count($gameData['players']) - ($gameData['nb_invited_players'] + 1)) != 0): 
                ?>
                <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 12px;">
                    
                    <h3>Inviter d'autres joueurs</h3>
                    <form action="index.php?action=invite_player" method="POST">
                        
                        <?php for ($i = 0; $i < $gameData['nb_invited_players']; $i++): ?>
                            <div style="margin-bottom: 10px;">
                                <label for="pseudo_<?= $i ?>">Pseudo invité #<?= $i+1 ?></label>
                                <input type="text" id="pseudo_<?= $i ?>" 
                                       name="pseudo[]" placeholder="Nom du joueur..." required>
                                <input type="hidden" name="gameID" 
                                       value="<?= $gameData['game_id']; ?>">
                            </div>
                        <?php endfor; ?>

                        <button class="btn-main btn-small">Envoyer les invitations</button>
                    </form>
                </div>
                <?php endif; ?>

            </div>
        </div>


        <!-- Colonne droite : actions hôte -->
        <?php if ($is_host): ?>
        <div class="col-right">

            <div class="card-box" style="border: 2px solid #3498db;">
                <h2 style="color: #3498db;">⚙️ Tableau de bord de l’hôte</h2>
            <?php if ($gameData['game_status'] != "PAUSE"): ?>
                <div style="margin-bottom: 28px;">
                    <h3>📂 Importer le Backlog</h3>
                    <p>Ajoutez un fichier JSON contenant vos user stories.</p>

                    <form action="index.php?action=import_backlog" method="POST"
                          enctype="multipart/form-data" 
                          style="background: #fafafa; padding: 14px; border-radius: 8px;">
                        
                        <label for="backlog_file">Fichier JSON :</label>
                        <input type="file" id="backlog_file" name="backlog_file" accept=".json" required>

                        <button class="btn-main btn-small">Charger le fichier</button>
                    </form>
                </div>
            <?php endif; ?>
                <div style="margin-bottom: 28px;">
                    <h3>📋 Aperçu des tâches</h3>

                    <?php if (!empty($gameData['backlog_items'])): ?>

                        <div style="overflow-x: auto;">
                            <table class="backlog-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Titre</th>
                                <th style="width: 40%;">Description</th>
                                <th style="width: 15%;">Statut</th>
                                <th style="width: 15%; text-align: center;">Difficulté</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gameData['backlog_items'] as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                    <td style="color: #666; font-size: 0.9em;"><?php echo htmlspecialchars($item['description']); ?></td>
                                    
                                    <td>
                                        <?php 
                                            $statusClass = 'badge-pending';
                                            $statusText = 'À faire';

                                            if ($item['status'] === 'VALIDATED') {
                                                $statusClass = 'badge-validated';
                                                $statusText = 'Terminé';
                                            } elseif ($item['status'] === 'EN COURS') { 
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
                                            echo ($item['estimated_difficulty'] !== null) 
                                                ? htmlspecialchars($item['estimated_difficulty']) 
                                                : '<span style="color:#ccc;">-</span>'; 
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                        </div>

                    <?php else: ?>

                        <div style="text-align:center; padding:20px; background:#fafafa; border-radius:6px;">
                            Aucune tâche importée pour le moment.
                        </div>

                    <?php endif; ?>
                </div>

                <div>
                    <hr style="border: 0; border-top: 1px solid #ccc; margin: 20px 0;">
                    <h3>🚀 Lancer la session</h3>
                    <form action="index.php?action=start_game" method="POST">
                        <button class="btn-main">Démarrer le Planning Poker</button>
                    </form>
                </div>

            </div>
        </div>
        <?php endif; ?>

    </div>
</div>







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