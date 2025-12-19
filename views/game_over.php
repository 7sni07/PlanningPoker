<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Partie Terminée - Planning Poker</title>
    <link rel='stylesheet' type='text/css' href='./css/GameOverStyle.css'>

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
            <?php if ($is_host): ?>
            <a href="index.php?action=save_game" class="btn btn-download">
                📥 Exporter les résultats (JSON)
            </a>
            <?php endif; ?>

            <a href="index.php?action=menu" class="btn btn-home">
                🏠 Retour au Menu
            </a>
        </div>
    </div>

</body>
</html>