<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Partie Terminée - Planning Poker</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background-color: #f0f2f5; color: #333; }
        
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 800px; width: 90%; text-align: center; }
        
        h1 { color: #27ae60; margin-bottom: 10px; font-size: 2.5em; }
        p.subtitle { color: #7f8c8d; font-size: 1.2em; margin-bottom: 30px; }
        
        /* Tableau des résultats */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; text-align: left; }
        th { background-color: #f8f9fa; color: #555; padding: 12px; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background-color: #f9f9f9; }
        
        .score-badge { 
            display: inline-block; 
            background-color: #3498db; 
            color: white; 
            font-weight: bold; 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 1.1em;
        }

        /* Boutons */
        .actions { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1em; transition: transform 0.2s; display: flex; align-items: center; gap: 10px; }
        .btn:hover { transform: translateY(-3px); }
        
        .btn-download { background-color: #2c3e50; color: white; }
        .btn-home { background-color: #ecf0f1; color: #333; border: 1px solid #ccc; }
        
        /* Confettis (optionnel, simple CSS) */
        .confetti { font-size: 50px; }
    </style>
</head>
<body>

    <div class="container">
        <div class="confetti">🏆</div>
        <h1>Félicitations !</h1>
        <p class="subtitle">Toutes les tâches du backlog ont été estimées.</p>

        <table>
            <thead>
                <tr>
                    <th>Tâche</th>
                    <th>Description</th>
                    <th style="text-align: center;">Difficulté Retenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($finalBacklog as $item): ?>
                    <tr>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($item['title']); ?></td>
                        <td style="color: #666; font-size: 0.9em;"><?php echo htmlspecialchars($item['description']); ?></td>
                        <td style="text-align: center;">
                            <span class="score-badge">
                                <?php echo htmlspecialchars($item['estimated_difficulty']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="actions">
            <a href="index.php?action=save_game" class="btn btn-download">
                📥 Exporter les résultats (JSON)
            </a>

            <a href="index.php?action=menu" class="btn btn-home">
                🏠 Retour au Menu
            </a>
        </div>
    </div>

</body>
</html>