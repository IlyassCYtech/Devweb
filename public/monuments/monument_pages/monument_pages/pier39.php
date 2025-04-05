<?php include('header.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pier 39 - San Francisco</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fefefe;
            margin: 0;
            padding: 30px;
            color: #2c3e50;
        }

        .content {
            max-width: 800px;
            margin: auto;
            text-align: center;
        }

        h1 {
            font-size: 36px;
            color: #e53935;
        }

        img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin: 20px 0;
        }

        p {
            font-size: 17px;
            line-height: 1.6;
            text-align: justify;
        }

        a.back {
            display: inline-block;
            margin-top: 30px;
            text-decoration: none;
            color: #00695c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1>Pier 39</h1>
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Pier_39_Sea_Lions_San_Francisco.jpg" alt="Pier 39">
        <p>Pier 39 est un lieu emblématique du front de mer avec des restaurants, boutiques et surtout des otaries célèbres qui se prélassent au soleil. C’est un endroit idéal pour une promenade en famille.</p>
        <a href="../monument.php" class="back">← Retour à la liste des monuments</a>
    </div>
</body>
</html>
