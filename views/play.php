<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>En Jeu - Partie #<?php echo htmlspecialchars($gameData['game_id']); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; height: 100vh; background-color: #f0f2f5; }
        
        /* Sidebar gauche (Joueurs) */
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 1.2em; border-bottom: 1px solid #34495e; padding-bottom: 10px; }
        .player-item { padding: 10px; border-bottom: 1px solid #34495e; }
        .player-item.is-me { background-color: #34495e; font-weight: bold; border-left: 4px solid #3498db; }
        
        /* Contenu principal */
        .main-content { flex: 1; padding: 30px; display: flex; flex-direction: column; align-items: center; overflow-y: auto; }
        
        /* --- STYLES STANDARDS (Jeu) --- */
        .task-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 600px; width: 100%; margin-bottom: 30px; border-top: 5px solid #007bff; }
        .task-title { font-size: 1.8em; margin: 0 0 10px 0; color: #333; }
        .task-desc { color: #666; font-size: 1.1em; }
        .timer-box { font-size: 2em; font-weight: bold; color: #e74c3c; margin-bottom: 20px; border: 2px solid #e74c3c; padding: 10px 20px; border-radius: 50px; }
        .cards-container { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }
        .round-badge { background-color: #f1c40f; color: #2c3e50; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9em; display: inline-block; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-transform: uppercase; letter-spacing: 1px; }
        
        /* Cartes & Votes */
        .card-option { display: none; }
        .card-label { display: flex; justify-content: center; align-items: center; width: 60px; height: 90px; background: white; border: 2px solid #ccc; border-radius: 8px; font-size: 1.5em; font-weight: bold; color: #333; cursor: pointer; transition: transform 0.2s, border-color 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card-label:hover { transform: translateY(-5px); border-color: #3498db; }
        .card-option:checked + .card-label { background-color: #3498db; color: white; border-color: #3498db; transform: translateY(-10px); box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4); }

        /* --- STYLES PAUSE CAFÉ --- */
        .coffee-break-container {
            text-align: center; margin-top: 50px; 
            background: white; padding: 40px; border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px;
        }
        .coffee-icon { font-size: 80px; margin-bottom: 20px; display: block; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }

        .btn-action { display: block; width: 100%; padding: 15px; margin: 10px 0; border-radius: 8px; text-decoration: none; font-weight: bold; text-align: center; transition: background 0.3s; }
        .btn-download { background-color: #3498db; color: white; }
        .btn-download:hover { background-color: #2980b9; }
        .btn-menu { background-color: #e74c3c; color: white; }
        .btn-menu:hover { background-color: #c0392b; }

        .btn-vote { margin-top: 30px; padding: 15px 40px; font-size: 1.2em; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .waiting-box, .debate-box { text-align: center; animation: fadeIn 0.5s; }
        .loader { border: 8px solid #f3f3f3; border-top: 8px solid #3498db; border-radius: 50%; width: 60px; height: 60px; animation: spin 2s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
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
                            <input type="radio" name="vote_value" id="card_<?php echo $val; ?>" value="<?php echo $val; ?>" class="card-option" required>
                            <label for="card_<?php echo $val; ?>" class="card-label">
                                <?php echo ($val === 'coffee') ? '☕' : $val; ?>
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