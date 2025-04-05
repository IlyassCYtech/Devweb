<?php include('header.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Monuments à San Francisco</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #e0f7fa, #fce4ec);
            color: #2c3e50;
        }
        h1 {
            text-align: center;
            font-size: 42px;
            margin-top: 40px;
            margin-bottom: 10px;
            color: #1a237e;
        }
        p.subtitle {
            text-align: center;
            font-size: 18px;
            color: #4a4a4a;
            margin-bottom: 40px;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            padding: 0 40px 60px;
        }
        .monument {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 320px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
        }
        .monument:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }
        .monument img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .monument-content {
            padding: 20px;
        }
        .monument-content h2 {
            font-size: 22px;
            color: #00695c;
            margin-bottom: 10px;
        }
        .monument-content p {
            font-size: 15px;
            line-height: 1.6;
        }
        .back-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #1a237e;
            color: white;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #3949ab;
        }
        footer {
            text-align: center;
            padding: 30px 10px;
            background: #f1f8e9;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>

    <h1>🌉 Monuments à découvrir à San Francisco</h1>
    <p class="subtitle">Explore les lieux emblématiques de la ville entre mer, collines et légendes.</p>

    <a href="../index.php" class="back-button">Retour à l'accueil</a>

    <div class="container">
        <div class="monument">
            <a href="monument_pages/golden_gate.php">
                <img src="monument_pages/golden_gate.jpg" alt="Golden Gate Bridge">
                <div class="monument-content">
                    <h2>Golden Gate Bridge</h2>
                    <p>Le célèbre pont rouge emblématique reliant San Francisco à Marin County. Un incontournable !</p>
                </div>
            </a>
        </div>
        <div class="monument">
            <a href="monument_pages/alcatraz.php">
                <img src="monument_pages/alcatraz.jpg" alt="Alcatraz">
                <div class="monument-content">
                    <h2>Alcatraz</h2>
                    <p>Ancienne prison fédérale sur une île, connue pour ses évasions légendaires et son histoire captivante.</p>
                </div>
            </a>
        </div>
        <div class="monument">
            <a href="monument_pages/painted_ladies.php">
                <img src="monument_pages/painted_ladies.jpg" alt="Painted Ladies">
                <div class="monument-content">
                    <h2>Painted Ladies</h2>
                    <p>Des maisons victoriennes colorées face au skyline moderne de SF. Un contraste charmant !</p>
                </div>
            </a>
        </div>
        <div class="monument">
            <a href="monument_pages/lombard_street.php">
                <img src="monument_pages/lombard_street.jpg" alt="Lombard Street">
                <div class="monument-content">
                    <h2>Lombard Street</h2>
                    <p>La rue la plus sinueuse du monde, célèbre pour ses virages serrés et son décor floral enchanteur.</p>
                </div>
            </a>
        </div>
        <div class="monument">
            <a href="monument_pages/coit_tower.php">
                <img src="monument_pages/coit_tower.jpg" alt="Coit Tower">
                <div class="monument-content">
                    <h2>Coit Tower</h2>
                    <p>Un monument historique offrant une vue panoramique spectaculaire sur toute la baie.</p>
                </div>
            </a>
        </div>
        <div class="monument">
            <a href="monument_pages/pier39.php">
                <img src="monument_pages/pier39.jpg" alt="Pier 39">
                <div class="monument-content">
                    <h2>Pier 39</h2>
                    <p>Un quai animé où tu peux voir des otaries, manger des fruits de mer, ou simplement flâner au bord de l'eau.</p>
                </div>
            </a>
        </div>
    </div>

    <footer>
        © 2025 Ville Intelligente - San Francisco | Projet Web ING1
    </footer>

</body>
</html>
