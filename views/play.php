<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>En Jeu - Partie #<?php echo htmlspecialchars($gameData['game_id']); ?></title>
    
    <link rel='stylesheet' type='text/css' href='./css/PlayStyle.css'>

</head>
<body>

    <aside class="sidebar">
        <h2>Joueurs (<?php echo count($gameData['players']); ?>)</h2>
        <?php foreach ($gameData['players'] as $p): ?>
            <div class="player-item <?php echo ($p['player_id'] === $player_id) ? 'is-me' : ''; ?>">
                <?php echo htmlspecialchars($p['pseudo']); ?>
                <?php if ($p['is_host']) echo '👑'; ?>
            </div>
        <?php endforeach; ?>
    </aside>

    <main class="main-content">

        <?php if ((isset($_GET['result']) && $_GET['result'] === 'coffee_break') || $gameData['game_status'] === 'PAUSE'): ?>

            <div class="coffee-break-container">
                <span class="coffee-icon">☕</span>
                <h1 style="color: #2c3e50;">Pause Café !</h1>
                <p style="color: #7f8c8d; font-size: 1.1em; margin-bottom: 30px;">
                    Les joueurs ont demandé une pause. <br>L'état du backlog a été préservé.
                </p>

                <?php if ($is_host): ?>
                    <a href="index.php?action=save_game" class="btn-action btn-download">
                        📥 Télécharger le fichier JSON
                    </a>
                <?php endif; ?>

                <a href="index.php?action=menu" class="btn-action btn-menu">
                    🚪 Retour au Menu Principal
                </a>
            </div>

        <?php else: ?>

            <section class="task-card">
                <div class="round-badge">
                    Round #<?php echo htmlspecialchars($currentTask['last_round_number'] ?? 1); ?>
                </div>
                <h3 style="color: #999; font-size: 0.9em; text-transform: uppercase;">Tâche en cours d'estimation</h3>
                <h1 class="task-title"><?php echo htmlspecialchars($currentTask['title']); ?></h1>
                <p class="task-desc"><?php echo htmlspecialchars($currentTask['description']); ?></p>
            </section>

            <?php if (isset($showDebateMode) && $showDebateMode === true): ?>
                
                <?php 
                    
                    $titleColor = $isSuccess ? '#27ae60' : '#e67e22'; 
                    
                    if ($isSuccess) {
                        if ($round_number === 1) {
                            $titleText = '🎉 Unanimité !';
                            $subText = 'Tout le monde est d\'accord.';
                        } else {
                            $titleText = '✅ Résultat du vote';
                            $subText = 'Valeur retenue : <strong>' . $suggestedValue . '</strong>';
                        }
                    } else {
                        $titleText = '⚡ Désaccord';
                        $subText = ($round_number === 1) ? 'Round 1 : Pas d\'nanimité.' : 'La règle du jeu choisi ne dégage pas de résultat.';
                    }
                ?>

                <div class="debate-box" style="text-align: center; width: 100%; max-width: 600px;">
                    
                    <h2 style="color: <?php echo $titleColor; ?>;"><?php echo $titleText; ?></h2>
                    <h2 style="color: <?php echo $titleColor; ?>;"><?php echo $subText; ?></h2>
                    <p>Voici les votes de l'équipe :</p>

                    <table style="width: 100%; margin: 20px 0; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <tr style="background: #ecf0f1;">
                            <th style="padding: 10px;">Joueur</th>
                            <th style="padding: 10px;">Vote</th>
                        </tr>
                        <?php foreach ($votesDetails as $vote): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($vote['pseudo']); ?></td>
                                <td style="padding: 10px; font-weight: bold; color: #2980b9;">
                                    <?php echo htmlspecialchars($vote['value']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <?php if ($is_host): ?>
                        <div style="margin-top: 20px; padding: 15px; background: <?php echo $isSuccess ? '#d4edda' : '#fff3cd'; ?>; border: 1px solid <?php echo $isSuccess ? '#c3e6cb' : '#ffeeba'; ?>; border-radius: 5px;">
                            
                            <?php if ($isSuccess): ?>
                                <a href="index.php?action=validate_task" class="btn-vote" style="text-decoration: none; display: inline-block; background-color: #28a745;">
                                    ✅ Valider et passer à la suite
                                </a>
                            <?php else: ?>
                                <p style="color: #856404;">Débattez des différences puis relancez le vote.</p>
                                <a href="index.php?action=next_round" class="btn-vote" style="text-decoration: none; display: inline-block; background-color: #ffc107; color: #333;">
                                    🔄 Relancer le tour
                                </a>
                            <?php endif; ?>

                        </div>
                    <?php else: ?>
                        <div class="waiting-box">
                            <?php if ($isSuccess): ?>
                                <p style="color: #27ae60; font-weight: bold;">En attente de validation par l'hôte...</p>
                            <?php else: ?>
                                <p>Débat en cours... En attente de l'hôte.</p>
                            <?php endif; ?>
                            <div class="loader"></div>
                            <script>setInterval(() => { location.reload(); }, 3000);</script>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($hasVoted): ?>

                <div class="waiting-box">
                    <h2 style="color: #27ae60;">Vote enregistré ! ✅</h2>
                    <p>En attente des autres joueurs...</p>
                    <div class="loader"></div>
                </div>
                <script>setInterval(() => { location.reload(); }, 3000);</script>

            <?php else: ?>

                <div class="timer-box" id="timer">45</div>

                <form id="voteForm" action="index.php?action=submit_vote" method="POST">
                    <input type="hidden" name="item_id" value="<?php echo $currentTask['item_id']; ?>">
                    <input type="hidden" name="player_id" value="<?php echo $player_id; ?>">
                    
                    <div class="cards-container">
                        <?php foreach ($cards as $val): ?>
        
                            <?php 
                                $imageName = ($val === '?') ? 'interro' : $val; 
                                
                                $imagePath = "public/img/cards/" . $imageName . ".svg";
                            ?>

                            <input type="radio" name="vote_value" id="card_<?php echo $val; ?>" value="<?php echo $val; ?>" class="card-option" required>
                            
                            <label for="card_<?php echo $val; ?>" class="card-label">
                                <img src="<?php echo $imagePath; ?>" alt="Carte <?php echo $val; ?>">
                            </label>

                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn-vote">Valider mon vote</button>
                </form>

                <script>
                    let timeLeft = 45;
                    const timerElement = document.getElementById('timer');
                    const voteForm = document.getElementById('voteForm');
                    const countdown = setInterval(() => {
                        timeLeft--;
                        timerElement.textContent = timeLeft;
                        if (timeLeft < 10) { timerElement.style.color = 'red'; timerElement.style.borderColor = 'red'; }
                        if (timeLeft <= 0) {
                            clearInterval(countdown);
                            alert("Temps écoulé !");
                            voteForm.submit();
                        }
                    }, 1000);
                </script>

            <?php endif; ?> 

        <?php endif; ?> </main>
</body>
</html>