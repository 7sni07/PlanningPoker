<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning Poker - Menu</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1>🃏 Planning Poker App</h1>
    </header>

    <main>
        <h2>Créer une nouvelle session</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'PseudoRequis'): ?>
            <p style="color: red;">⚠️ Veuillez entrer un pseudo pour commencer la partie.</p>
        <?php endif; ?>
        
        <form action="index.php?action=create_game" method="POST">
            
            <label for="pseudo">Votre Pseudo (Hôte) :</label>
            <input type="text" id="pseudo" name="pseudo" required>
            
            <label for="num_players">Nombre de joueurs invité:</label>
            <input type="number" id="num_players" name="num_players" min="2" value="2" required>
            
            <label for="rule_id">Règles de Validation :</label>
            <select id="rule_id" name="rule_id" required>
                <?php foreach ($rules as $rule): ?>
                    <option value="<?php echo $rule['rule_id']; ?>">
                        <?php echo $rule['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Créer la Partie</button>
        </form>

        <hr>

        <h2>Rejoindre une session</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'PseudoRequis'): ?>
            <p style="color: red;">⚠️ Veuillez entrer votre pseudo et l'ID de la partie.</p>
        <?php endif; ?>
        
        <form action="index.php?action=join_game" method="POST">
            
            <label for="pseudo">Votre Pseudo :</label>
            <input type="text" id="pseudo" name="pseudo" required>
            
            <label for="gameID">ID de la Partie :</label>
            <input type="text" id="gameID" name="gameID" required>
            
            <button type="submit">Rejoindre la Partie</button>
        </form>

        <hr>

        <h2>Reprendre une partie en pause</h2>
        <form action="index.php?action=resume_game" method="POST" enctype="multipart/form-data">
            <label for="save_file">Fichier de sauvegarde (.json) :</label>
            <input type="file" id="save_file" name="save_file" accept=".json" required>
            <button type="submit">Charger la Partie</button>
        </form>
    </main>

</body>
</html>