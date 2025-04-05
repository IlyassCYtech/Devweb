<?php include('header.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lombard Street - San Francisco</title>
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
        <h1>Lombard Street</h1>
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/LombardStreet_SanFrancisco.jpg" alt="Lombard Street">
        <p>Lombard Street est célèbre pour sa portion très sinueuse, souvent appelée la rue la plus tortueuse du monde. Elle est bordée de fleurs et attire chaque année des milliers de touristes.</p>
        <a href="../monument.php" class="back">← Retour à la liste des monuments</a>
    </div>
</body>
</html>
