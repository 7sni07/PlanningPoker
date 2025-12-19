<!DOCTYPE html>

<html lang="fr" dir="ltr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel='stylesheet' type='text/css' href='./css/MenuStyle.css'>

    <title>Planning Poker</title>

</head>

<body>

    <div class="container">

        <h1>♠️ Planning Poker</h1>

        <div class="tabs">

            <button class="tab-button active" onclick="showForm('join-form')">Rejoindre une Session</button>

            <button class="tab-button" onclick="showForm('create-form')">Nouvelle Session</button>

            <button class="tab-button" onclick="showForm('resume-form')">Reprendre une Session</button>

        </div>

        <div id="join-form" class="form-content active">

            <form action="index.php?action=join_game" method="POST">

                <div class="form-group">

                    <label for="pseudo">Votre Pseudo:</label>

                    <input type="text" id="pseudo" name="pseudo" placeholder="Votre Pseudo dans la session" required>

                </div>

                <div class="form-group">

                    <label for="gameID">ID de la Partie:</label>
                    <input type="text" id="gameID" name="gameID" placeholder="Code de la session" required>

                </div>

                <button type="submit" class="btn-primary">Rejoindre la Session</button>

            </form>

        </div>

        <div id="create-form" class="form-content">

            <form action="index.php?action=create_game" method="POST">

                <div class="form-group">

                    <label for="pseudo">Pseudo de l'Hôte:</label>

                    <input type="text" id="pseudo" name="pseudo" placeholder="Entrer votre Pseudo" required>

                </div>

                <div class="form-group">

                    <label for="num_players">Nombre de Membres invités:</label>

                    <input type="number" id="num_players" name="num_players" min="2" value="2" required>

                </div>

                <div class="form-group">

                    <label for="rule_id">Mode de jeu:</label>

                    <select id="rule_id" name="rule_id" required>

                        <?php foreach ($rules as $rule): ?>
                            
                            <option value="<?php echo $rule['rule_id']; ?>">
                        
                            <?php echo $rule['name']; ?>
                    
                            </option>
                        
                        <?php endforeach; ?>

                    </select>

                </div>

            
                <button type="submit" class="btn-primary">Créer et Démarrer la Session</button>

            </form>

        </div>

        <div id="resume-form" class="form-content">

            <form action="index.php?action=resume_game" method="POST" enctype="multipart/form-data">

                <div class="form-group">

                    <label for="save_file">Fichier de sauvegarde (.json) :</label>

                    <input type="file" id="save_file" name="save_file" accept=".json" required>

                </div>

            
                <button type="submit" class="btn-primary">Charger la Partie</button>

            </form>

        </div>

    </div>



    <script>

        function showForm(formId) {

            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

            document.querySelectorAll('.form-content').forEach(form => form.classList.remove('active'));



            document.querySelector(`.tab-button[onclick*="${formId}"]`).classList.add('active');

            document.getElementById(formId).classList.add('active');

        }

    </script>



</body>

</html>