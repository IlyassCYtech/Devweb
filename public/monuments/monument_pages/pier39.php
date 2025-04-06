<?php include('header.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pier 39 - San Francisco</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('../../../assets/images/backgrounddeux.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 30px;
            color: #2c3e50;
        }

        .content {
            max-width: 800px;
            margin: auto;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-size: 3rem;
            color: #e53935;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.3);
        }

        img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin: 20px 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        img:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
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
            transition: color 0.3s ease, transform 0.3s ease;
        }

        a.back:hover {
            color: #004d40;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="content">
        <h1>Pier 39</h1>
        <img src="pier39.jpg" alt="Pier 39">
        <p>Pier 39 est une destination touristique populaire à San Francisco. Ce quai animé est connu pour ses otaries, ses boutiques, ses restaurants et ses vues spectaculaires sur la baie.</p>
        <a href="../monument.php" class="back">← Retour à la liste des monuments</a>
    </div>
</body>
</html>
