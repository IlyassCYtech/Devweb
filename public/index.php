<?php

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

try {
    // Récupérer les statistiques principales
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ObjetConnecte");
    $total_objets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as disponible FROM ObjetConnecte WHERE UtilisateurID IS NULL");
    $objets_disponibles = $stmt->fetch(PDO::FETCH_ASSOC)['disponible'];

    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Récupérer les types d'objets
    $typeStmt = $conn->prepare("SELECT * FROM TypeObjet");
    $typeStmt->execute();
    $types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger text-center">
        <?php 
        echo htmlspecialchars($_SESSION['error_message']);
        unset($_SESSION['error_message']); // Supprimer le message après l'affichage
        ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        html, body {
            scroll-behavior: smooth;
            overflow-x: hidden;
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: row;
            height: 100%;
            overflow-x: auto; /* Permet le défilement horizontal */
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.5); /* Transparence */
            border: 2px solid black; /* Bord noir */
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card h3 {
            font-size: 1.5rem; /* Agrandir le titre */
        }
        .stat-card p {
            font-size: 2rem; /* Agrandir les chiffres */
        }
        p {
            color: white; /* Tous les paragraphes en blanc */
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.5); /* Transparence */
            border: 2px solid black; /* Bord noir */
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
            padding: 1.5rem; /* Ajout de padding pour éviter que le texte touche les bords */
            overflow: hidden; /* Empêche le contenu de dépasser */
            text-align: center; /* Centre le texte */
        }

        .stat-card h3 {
            font-size: 1.25rem; /* Taille ajustée pour les titres */
            overflow-wrap: break-word; /* Permet de couper les mots longs */
            word-break: break-word; /* Coupe les mots longs si nécessaire */
            margin-bottom: 0.5rem; /* Espacement sous le titre */
        }

        .stat-card p {
            font-size: 1.5rem; /* Taille ajustée pour les chiffres */
            overflow-wrap: break-word; /* Permet de couper les mots longs */
            word-break: break-word; /* Coupe les mots longs si nécessaire */
            margin: 0.5rem 0; /* Espacement vertical */
        }

        .stat-card:hover {
            transform: translateY(-2px); /* Effet de survol */
        }

        .grid {
            gap: 1.5rem; /* Augmente l'espacement entre les cartes */
        }

        .section {
            min-width: 100vw; /* Chaque section occupe toute la largeur de la fenêtre */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }

        #section-1 {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 50px 20px;
        }

        #section-1 h1 {
            margin-top: -155px; /* Positionne le titre plus haut */
            font-size: 4rem; /* Taille du titre pour un effet PowerPoint */
            font-weight: bold;
        }

        #section-1 h2 {
            margin-top: 20px;
            font-size: 1.8rem; /* Taille du sous-titre */
            font-weight: 500;
        }

        #section-1 .grid {
            margin-top: 150px; /* Espacement entre le titre et les cartes */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Grille responsive */
            gap: 30px; /* Espacement entre les cartes */
            width: 100%;
            max-width: 1200px; /* Limite la largeur totale */
        }

        #section-1 .stat-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.7); /* Fond noir transparent */
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        #section-1 .stat-card:hover {
            transform: scale(1.05); /* Zoom léger au survol */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        #section-1 .stat-card i {
            font-size: 3rem; /* Icône plus grande */
            margin-bottom: 15px;
        }

        #section-1 .stat-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        #section-1 .stat-card p {
            font-size: 1.2rem;
        }

        #section-2 .col-md-6:first-child {
            background: rgba(0, 0, 0, 0.6);
        }

        #section-2 .col-md-6:last-child {
            background: rgba(255, 255, 255, 0.8);
        }

        #section-3 .col-md-6:first-child {
            background: rgba(0, 0, 0, 0.6);
        }

        #section-3 .col-md-6:last-child {
            background: rgba(255, 255, 255, 0.8);
        }

        #section-3 .col-md-6:last-child .d-flex {
            justify-content: center; /* Centre les boutons horizontalement */
        }
        #section-3 .col-md-6:last-child .d-flex .btn {
            margin: 0 10px; /* Ajoute un espacement entre les boutons */
        }

        .hidden-footer-content {
            display: none;
        }

        #card {
            display: none;
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 400px;
            width: 100%;
        }

        #overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #closeFooterContent {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            font-size: 1.5rem;
            background: none;
            border: none;
            color: #333;
            cursor: pointer;
        }

        #closeFooterContent:hover {
            color: red;
        }

        #hiddenFooterContent {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 80%;
            overflow-y: auto;
        }

        #hiddenFooterContent::-webkit-scrollbar {
            width: 8px;
        }

        #hiddenFooterContent::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #hiddenFooterContent::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .arrow {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            font-size: 3rem;
            color: white;
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .arrow:hover {
            background-color: rgba(0, 0, 0, 0.8);
            transform: translateY(-50%) scale(1.1);
        }

        .arrow-left {
            left: 20px;
        }

        .arrow-right {
            right: 20px;
        }

        .arrow i {
            font-size: 2rem;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const sections = document.querySelectorAll(".section");
            const arrowLeft = document.getElementById("arrow-left");
            const arrowRight = document.getElementById("arrow-right");
            let currentSectionIndex = 0;
            let isScrolling = false; // Verrouillage pour empêcher les défilements multiples

            function scrollToSection(index) {
                if (index < 0) {
                    index = sections.length - 1; // Aller à la dernière section
                } else if (index >= sections.length) {
                    index = 0; // Retourner à la première section
                }
                sections[index].scrollIntoView({ behavior: "smooth", inline: "start" });
                currentSectionIndex = index;
            }

            arrowLeft.addEventListener("click", () => {
                if (!isScrolling) {
                    isScrolling = true;
                    scrollToSection(currentSectionIndex - 1);
                    setTimeout(() => isScrolling = false, 800); // Délai pour éviter les défilements multiples
                }
            });

            arrowRight.addEventListener("click", () => {
                if (!isScrolling) {
                    isScrolling = true;
                    scrollToSection(currentSectionIndex + 1);
                    setTimeout(() => isScrolling = false, 800); // Délai pour éviter les défilements multiples
                }
            });

            // Gestion du défilement vertical pour naviguer horizontalement
            window.addEventListener("wheel", (event) => {
                if (isScrolling) return; // Empêche les défilements multiples rapides
                isScrolling = true;

                if (event.deltaY > 0) {
                    // Scroll vers le bas
                    scrollToSection(currentSectionIndex + 1);
                } else if (event.deltaY < 0) {
                    // Scroll vers le haut
                    scrollToSection(currentSectionIndex - 1);
                }

                setTimeout(() => {
                    isScrolling = false; // Réinitialise le verrouillage après un court délai
                }, 800); // Délai pour éviter les défilements rapides
            });

            // Navbar links scroll behavior
            const navLinks = document.querySelectorAll(".glass-nav a");
            navLinks.forEach(link => {
                link.addEventListener("click", (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute("href").substring(1);
                    const targetSection = document.getElementById(targetId);
                    if (targetSection) {
                        targetSection.scrollIntoView({ behavior: "smooth", inline: "start" });
                    }
                });
            });
        });
    </script>
</head>
<body class="bg-gray-50">

<!-- Navbar -->
<nav class="glass-nav fixed w-full z-50 top-0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex">
                <a href="Travail.php">
                    <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                </a>
            </div>
            <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                <a href="#section-1" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">📊 Statistique</a>
                <a href="#section-2" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🏙️ Découverte de la ville</a>
                <a href="#section-3" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔌 Objets connectés</a>
            </div>
            <div class="hidden sm:flex sm:items-center sm:justify-end space-x-4">
                <button id="showRegister" class="btn btn-success py-2 px-4 text-white bg-green-500 hover:bg-green-600 rounded-lg">S'inscrire</button>
                <button id="showLogin" class="btn btn-primary py-2 px-4 text-white bg-blue-500 hover:bg-blue-600 rounded-lg">Se connecter</button>
            </div>
        </div>
    </div>
</nav>

<!-- Flèches directionnelles -->
<div class="arrow arrow-left" id="arrow-left">
    <i class="fas fa-chevron-left"></i>
</div>
<div class="arrow arrow-right" id="arrow-right">
    <i class="fas fa-chevron-right"></i>
</div>

<!-- Section principale -->
<div id="section-1" class="section section-1" style="background-image: url('../assets/images/background.png');">
    <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center">
        <div class="text-center mb-4">
            <h1 class="text-6xl font-bold text-white mb-4" style="margin-top: -215px; font-family: 'Roboto Slab', serif; text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.5);">
                Bienvenue sur le site de San Francisco!
            </h1>
            <h2 class="text-2xl font-bold text-white mb-4" style="font-family: 'Roboto Slab', serif; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">
                Découvrez votre ville sous différents angles!
            </h2> 
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
            <!-- Carte: Nombre d'utilisateurs -->
            <div class="stat-card p-6 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="bg-gray-200 text-black-600 p-4 rounded-full">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Nombre d'Utilisateurs</h3>
                        <p class="text-4xl font-bold text-gray-900"><?php echo number_format($total_users, 0, ',', ' '); ?></p>
                        <p class="text-sm text-gray-500 mt-1">Utilisateurs enregistrés</p>
                    </div>
                </div>
            </div>

            <!-- Carte: Total des objets -->
            <div class="stat-card p-6 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="bg-blue-100 text-blue-600 p-4 rounded-full">
                        <i class="fas fa-database text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Total des Objets</h3>
                        <p class="text-4xl font-bold text-blue-600"><?php echo number_format($total_objets, 0, ',', ' '); ?></p>
                        <p class="text-sm text-gray-500 mt-1">Objets dans la base de données</p>
                    </div>
                </div>
            </div>

            <!-- Carte: Objets disponibles -->
            <div class="stat-card p-6 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center">
                    <div class="bg-green-100 text-green-600 p-4 rounded-full">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Objets Disponibles</h3>
                        <p class="text-4xl font-bold text-green-600"><?php echo number_format($objets_disponibles, 0, ',', ' '); ?></p>
                        <p class="text-sm text-gray-500 mt-1">Objets actuellement disponibles</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Découverte ville -->
<div id="section-2" class="section section-2" style="background-image: url('../assets/images/backgrounddeux.png');">
    <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center">
        <div class="row w-100 h-100">
            <!-- Partie gauche avec l'image -->
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <div class="text-center text-white p-5">
                    <h1 class="text-4xl font-bold mb-4" style="font-family: 'Poppins', sans-serif; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);">
                        Quelle temps fait-il?
                    </h1>
                    <p class="text-lg mb-4" style="font-family: 'Poppins', sans-serif; line-height: 1.8;">
                        Observer la météo de San Francisco en direct!
                    </p>
                    <a href="meteo.php" class="btn py-2 px-4 mb-5" style="font-size: 1.2rem; font-weight: bold; background-color: #4CAF50; color: white; border: none; border-radius: 5px;">
                        Aller sur la page
                    </a>
                </div>
            </div>
            <!-- Partie droite avec un fond transparent -->
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <div class="text-center p-5">
                    <h1 class="text-4xl font-bold mb-4" style="font-family: 'Poppins', sans-serif; color: #333;">
                        Découvrez les recoins de San Francisco
                    </h1>
                    <p class="text-lg mb-4" style="font-family: 'Poppins', sans-serif; line-height: 1.8; color: #555;">
                        Venez découvrir les recoins de San Francisco grâce à ce guide vous expliquant la richesse culturelle de cette ville.
                    </p>
                    <a href="monuments/monument.php" class="btn py-2 px-4" style="font-size: 1.2rem; font-weight: bold; background-color: #4CAF50; color: white; border: none; border-radius: 5px;">
                        Aller sur la page
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<div id="section-3" class="section section-3" style="background-image: url('../assets/images/backgroundtrois.png');">
    <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center">
        <div class="row w-100 h-100">
            <!-- Première case -->
            <div class="col-md-6 d-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.6);">
                <div class="text-center text-white p-5">
                    <h1 class="text-4xl font-bold mb-4" style="font-family: 'Poppins', sans-serif; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);">
                        Découvrez San Francisco avec ses objets connectés !
                    </h1>
                    <p class="text-lg mb-4" style="font-family: 'Poppins', sans-serif; line-height: 1.8;">
                        Venez découvrir San Francisco grâce à ses objets connectés et ses utilisateurs! Vous pouvez chercher les informations sur ses objets et les utilisateurs!
                    </p>
                    <div class="d-flex justify-content-center">
                        <button id="revealFooter" class="btn py-2 px-4" style="font-size: 1.2rem; font-weight: bold; background-color: #4CAF50; color: white; border: none; border-radius: 5px;">
                            Voir plus
                        </button>
                    </div>
                </div>
            </div>
            <!-- Deuxième case -->
            <div class="col-md-6 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.8);">
                <div class="text-center p-5">
                    <h1 class="text-4xl font-bold mb-4" style="font-family: 'Poppins', sans-serif; color: #333;">
                        Et bien plus encore...
                    </h1>
                    <p class="text-lg mb-4" style="font-family: 'Poppins', sans-serif; line-height: 1.8; color: #555;">
                        Inscrivez-vous ou connectez-vous pour pouvoir réserver vos objets ou les rendre!
                    </p>
                    <div class="d-flex">
                        <button id="footerShowRegister" class="btn btn-success py-2 px-4 text-white bg-green-500 hover:bg-green-600 rounded-lg">S'inscrire</button>
                        <button id="footerShowLogin" class="btn btn-primary py-2 px-4 text-white bg-blue-500 hover:bg-blue-600 rounded-lg">Se connecter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay pour pop-up -->
<div id="overlay"></div>

<!-- Contenu caché -->
<div id="hiddenFooterContent" class="hidden-footer-content">
    <button id="closeFooterContent" class="btn btn-close position-absolute top-0 end-0 m-4" style="font-size: 1.5rem;">✖</button>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Recherche</h1>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="mb-4">
            <label for="searchType" class="block text-sm font-medium text-gray-700">Rechercher par :</label>
            <select id="searchType" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="objet">Objet</option>
                <option value="utilisateur">Utilisateur</option>
            </select>
        </div>

        <div id="searchBar" class="mb-4 hidden">
            <label for="searchInput" class="block text-sm font-medium text-gray-700">Rechercher un utilisateur :</label>
            <input type="text" id="searchInput" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Nom d'utilisateur...">
        </div>

        <div id="filters" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="typeObjet" class="block text-sm font-medium text-gray-700">Type d'objet :</label>
                <select id="typeObjet" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= htmlspecialchars($type['Nom']) ?>">
                            <?= htmlspecialchars($type['Nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="etatObjet" class="block text-sm font-medium text-gray-700">État :</label>
                <select id="etatObjet" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    <option value="Actif">Actif</option>
                    <option value="Inactif">Inactif</option>
                </select>
            </div>
            <div>
                <label for="batterieMin" class="block text-sm font-medium text-gray-700">Batterie min. (%) :</label>
                <input type="number" id="batterieMin" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" min="0" max="100" placeholder="Ex: 20">
            </div>
            <div>
                <label for="disponibiliteObjet" class="block text-sm font-medium text-gray-700">Disponibilité :</label>
                <select id="disponibiliteObjet" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Toutes</option>
                    <option value="1">Disponible</option>
                    <option value="0">Indisponible</option>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button id="searchButton" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                Rechercher
            </button>
        </div>
    </div>

    <div id="results" class="mt-6">
        <p class="text-gray-500">Les résultats apparaîtront ici...</p>
    </div>
</div>

<!-- Formulaire de connexion et d'inscription -->
<div id="card" class="card">
    <button id="closeCard" class="btn btn-close position-absolute top-0 end-0 m-4" style="font-size: 1.5rem;"></button>
    <div id="loginForm" class="form-section" style="display: none;">
        <h2 class="text-center mb-4 text-primary">Connexion</h2>
        <form id="login-form" action="process_login.php" method="POST">
            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control border-primary" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control border-primary" required>
            </div>
            <div id="error-message" class="alert alert-danger" style="display: none;" role="alert"></div>
            <button type="submit" class="btn btn-primary w-100 py-2">Se connecter</button>
        </form>
    </div>

    <div id="registerForm" class="form-section" style="display: none;">
        <h2 class="text-center mb-3 text-success">Inscription</h2>
        <form id="register-form" action="register.php" method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">Pseudonyme</label>
                    <input type="text" name="username" id="username" class="form-control border-success" required>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password-register" class="form-control border-success" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control border-success" required>
            </div>
            <div class="mb-3">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" name="prenom" id="prenom" class="form-control border-success" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance" id="date_naissance" class="form-control border-success" required>
                </div>
                <div class="col-md-6">
                    <label for="age" class="form-label">Âge</label>
                    <input type="number" name="age" id="age" class="form-control border-success" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="sexe" class="form-label">Sexe/Genre</label>
                <select name="sexe" id="sexe" class="form-select border-success" required>
                    <option value="Homme">Homme</option>
                    <option value="Femme">Femme</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="type_membre" class="form-label">Type de membre</label>
                <select name="type_membre" id="type_membre" class="form-select border-success" required>
                    <option value="élève">Élève</option>
                    <option value="parent">Parent</option>
                    <option value="développeur">Développeur</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email-register" class="form-control border-success" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="niveau" class="form-label">Niveau</label>
                    <select name="niveau" id="niveau" class="form-select border-success" required>
                        <option value="Débutant">Débutant</option>
                        <option value="Intermédiaire">Intermédiaire</option>
                        <option value="Avancé">Avancé</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="points_experience" class="form-label">Points d'expérience</label>
                    <input type="number" name="points_experience" id="points_experience" class="form-control border-success" required>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="admin" id="admin" value="1">
                <label for="admin" class="form-check-label">Administrateur</label>
            </div>
            <div id="error-message-register" class="alert alert-danger" style="display: none;" role="alert"></div>
            <button type="submit" class="btn btn-success w-100 py-2">S'inscrire</button>
        </form>
    </div>
</div>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="../assets/js/login.js"></script>
<script src="../assets/js/register.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const showLoginButton = document.getElementById('showLogin');
        const showRegisterButton = document.getElementById('showRegister');
        const closeCardButton = document.getElementById('closeCard');
        const card = document.getElementById('card');
        const overlay = document.getElementById('overlay');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Afficher le formulaire de connexion
        showLoginButton.addEventListener('click', () => {
            overlay.style.display = 'block';
            card.style.display = 'block';
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        });

        // Afficher le formulaire d'inscription
        showRegisterButton.addEventListener('click', () => {
            overlay.style.display = 'block';
            card.style.display = 'block';
            registerForm.style.display = 'block';
            loginForm.style.display = 'none';
        });

        // Fermer la carte
        closeCardButton.addEventListener('click', () => {
            overlay.style.display = 'none';
            card.style.display = 'none';
            loginForm.style.display = 'none';
            registerForm.style.display = 'none';
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const searchType = document.getElementById("searchType");
        const searchBar = document.getElementById("searchBar");
        const filters = document.getElementById("filters");
        const searchInput = document.getElementById("searchInput");
        const searchButton = document.getElementById("searchButton");
        const resultsContainer = document.getElementById("results");

        // Affichage conditionnel des filtres
        searchType.addEventListener("change", function () {
            if (searchType.value === "utilisateur") {
                searchBar.classList.remove("hidden");
                filters.classList.add("hidden");
            } else {
                searchBar.classList.add("hidden");
                filters.classList.remove("hidden");
            }
        });

        // Gestion du bouton de recherche
        searchButton.addEventListener("click", function () {
            if (searchType.value === "utilisateur") {
                searchUser();
            } else {
                searchObject();
            }
        });

        function searchUser() {
            const query = searchInput.value.trim();

            // Afficher un message de chargement
            resultsContainer.innerHTML = "<p class='text-blue-600'>🔍 Recherche en cours...</p>";

            // Effectuer une requête à `search_user.php` avec le contenu de la barre de recherche
            fetch(`../freetourphp/search_user.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    // Effacer les résultats précédents
                    resultsContainer.innerHTML = "";

                    // Si aucun utilisateur n'est trouvé
                    if (data.length === 0) {
                        resultsContainer.innerHTML = "<p class='text-gray-500'>Aucun utilisateur trouvé.</p>";
                        return;
                    }

                    // Afficher les utilisateurs trouvés
                    data.forEach(user => {
                        const userCard = document.createElement("div");
                        userCard.className = "flex items-center bg-white p-4 shadow rounded-lg mb-4";

                        // Utilisation du chemin de la photo de profil de chaque utilisateur
                        const profilePic = user.profile_pic_path;

                        userCard.innerHTML = `
                            <img src="${profilePic}" alt="Photo de profil" class="w-12 h-12 rounded-full mr-4">
                            <div>
                                <p class="text-lg font-semibold">${user.username}</p>
                                <p class="text-gray-600">${user.first_name} ${user.last_name}</p>
                            </div>
                        `;
                        resultsContainer.appendChild(userCard);
                    });
                })
                .catch(error => {
                    // Afficher un message d'erreur en cas de problème
                    resultsContainer.innerHTML = "<p class='text-red-500'>Erreur lors de la recherche.</p>";
                });
        }

        function searchObject() {
            const typeObjet = document.getElementById("typeObjet").value;
            const etatObjet = document.getElementById("etatObjet").value;
            const batterieMin = document.getElementById("batterieMin").value;
            const disponibiliteObjet = document.getElementById("disponibiliteObjet").value;

            resultsContainer.innerHTML = "<p class='text-blue-600'>🔍 Recherche en cours...</p>";

            fetch(`../freetourphp/search_object.php?type=${encodeURIComponent(typeObjet)}&etat=${encodeURIComponent(etatObjet)}&batterieMin=${encodeURIComponent(batterieMin)}&disponibilite=${encodeURIComponent(disponibiliteObjet)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = "";
                    if (data.length === 0) {
                        resultsContainer.innerHTML = "<p class='text-gray-500'>Aucun objet trouvé.</p>";
                        return;
                    }

                    const gridContainer = document.createElement("div");
                    gridContainer.className = "grid grid-cols-2 gap-4";

                    data.forEach(objet => {
                        const objetCard = document.createElement("div");
                        objetCard.className = "relative flex items-center bg-white p-4 shadow rounded-lg";

                        objetCard.innerHTML = `
                            <div>
                                <h2 class="text-lg font-semibold">${objet.Nom}</h2>
                                <p class="text-gray-600">Type : ${objet.Type}</p>
                                <p class="text-gray-600">État : ${objet.Etat}</p>
                                <p class="text-gray-600">Disponibilité : ${objet.UtilisateurID === null ? "✅ Disponible" : "❌ Indisponible"}</p>
                            </div>
                        `;
                        gridContainer.appendChild(objetCard);
                    });

                    resultsContainer.appendChild(gridContainer);
                })
                .catch(error => {
                    resultsContainer.innerHTML = "<p class='text-red-500'>Erreur lors de la recherche.</p>";
                });
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        const revealFooterButton = document.getElementById('revealFooter');
        const hiddenFooterContent = document.getElementById('hiddenFooterContent');
        const closeFooterContentButton = document.getElementById('closeFooterContent');
        const footerShowLoginButton = document.getElementById('footerShowLogin');
        const footerShowRegisterButton = document.getElementById('footerShowRegister');
        const card = document.getElementById('card');
        const overlay = document.getElementById('overlay');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Afficher le contenu caché du footer lorsqu'on clique sur "Voir plus"
        revealFooterButton.addEventListener('click', () => {
            overlay.style.display = 'block';
            hiddenFooterContent.style.display = 'block';
        });

        // Masquer le contenu caché et réafficher le bouton "Voir plus"
        closeFooterContentButton.addEventListener('click', () => {
            overlay.style.display = 'none';
            hiddenFooterContent.style.display = 'none';
        });

        // Afficher le formulaire de connexion depuis le footer
        footerShowLoginButton.addEventListener('click', () => {
            overlay.style.display = 'block';
            card.style.display = 'block';
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        });

        // Afficher le formulaire d'inscription depuis le footer
        footerShowRegisterButton.addEventListener('click', () => {
            overlay.style.display = 'block';
            card.style.display = 'block';
            registerForm.style.display = 'block';
            loginForm.style.display = 'none';
        });

        // Fermer le formulaire pop-up
        closeCardButton.addEventListener('click', () => {
            overlay.style.display = 'none';
            card.style.display = 'none';
        });

        // Fermer la barre de recherche
        closeFooterContentButton.addEventListener("click", () => {
            overlay.style.display = "none";
            hiddenFooterContent.style.display = "none";
        });

        // Fermer la barre de recherche en cliquant sur l'overlay
        overlay.addEventListener("click", () => {
            overlay.style.display = "none";
            hiddenFooterContent.style.display = "none";
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const sections = document.querySelectorAll(".section");
        const arrowLeft = document.getElementById("arrow-left");
        const arrowRight = document.getElementById("arrow-right");
        let currentSectionIndex = 0;

        function scrollToSection(index) {
            if (index < 0) {
                index = sections.length - 1; // Aller à la dernière section
            } else if (index >= sections.length) {
                index = 0; // Retourner à la première section
            }
            sections[index].scrollIntoView({ behavior: "smooth", inline: "start" });
            currentSectionIndex = index;
        }

        arrowLeft.addEventListener("click", () => {
            scrollToSection(currentSectionIndex - 1);
        });

        arrowRight.addEventListener("click", () => {
            scrollToSection(currentSectionIndex + 1);
        });

        window.addEventListener("scroll", () => {
            sections.forEach((section, index) => {
                const rect = section.getBoundingClientRect();
                if (rect.left >= 0 && rect.left < window.innerWidth / 2) {
                    currentSectionIndex = index;
                }
            });
        });

        // Navbar links scroll behavior
        const navLinks = document.querySelectorAll(".glass-nav a");
        navLinks.forEach(link => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                const targetId = link.getAttribute("href").substring(1);
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: "smooth", inline: "start" });
                }
            });
        });
    });
</script>
</body>
</html>