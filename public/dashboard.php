<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Vérifier si l'utilisateur est confirmé par email et par l'admin
if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
    header("Location: index.php");
    exit();
}

try {
    // Récupérer l'ID de l'utilisateur depuis la session
    $user_id = $_SESSION['user_id'];

    // Récupérer les informations de l'utilisateur
    $stmt = $conn->prepare("SELECT username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, is_confirmed  ,admin FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Vérifier si l'utilisateur existe
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("Utilisateur non trouvé.");
    }

    // Récupérer le nombre total d'objets
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ObjetConnecte");
    $total_objets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Récupérer le nombre d'objets disponibles
    $stmt = $conn->query("SELECT COUNT(*) as disponible FROM ObjetConnecte WHERE UtilisateurID IS NULL");
    $objets_disponibles = $stmt->fetch(PDO::FETCH_ASSOC)['disponible'];
    
    // Récupérer les objets en cours de l'utilisateur via l'historique
    $stmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Récupérer les objets empruntés par cet utilisateur
    $objets_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Mode clair */
        :root {
            --bg-primary: #f9fafb;
            --text-primary: #111827;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
        }

        /* Mode sombre */
        [data-theme="dark"] {
            --bg-primary: #111827;
            --text-primary: #f9fafb;
            --card-bg: #1f2937;
            --card-border: #374151;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .glass-nav {
            background: rgba(17, 24, 39, 0.8);
            border-bottom: 1px solid rgba(55, 65, 81, 0.5);
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, background-color 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .progress-bar {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 9999px;
            height: 0.5rem;
        }

        /* Dark mode toggle button */
        .theme-toggle {
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        [data-theme="dark"] .theme-toggle {
            background-color: #374151;
            color: #fbbf24;
        }

        .theme-toggle:hover {
            background-color: #e5e7eb;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: #4b5563;
        }

        [data-theme="dark"] .nav-link {
            color: #e5e7eb;
        }

        [data-theme="dark"] .nav-link:hover {
            color: #ffffff;
        }

        [data-theme="dark"] .stat-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
        }

        [data-theme="dark"] .text-gray-500 {
            color: #9ca3af;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #d1d5db;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #f9fafb;
        }

        [data-theme="dark"] .bg-gray-50 {
            background-color: #374151;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <!-- Toggle button for mobile -->
                <div class="sm:hidden flex items-center">
                    <button id="mobile-menu-toggle" class="text-gray-700 hover:text-blue-600 focus:outline-none">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>

                <div id="nav-links" class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="profil.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="dashboard.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="objets.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="recherche.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>

                <div class="hidden sm:flex items-center space-x-4">
                    <button id="theme-toggle" class="theme-toggle">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                    </button>
                    <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden sm:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="profil.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-base font-medium">Profil</a>
                <a href="dashboard.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-base font-medium">Accueil</a>
                <a href="objets.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-base font-medium">Objets</a>
                <?php if ($user['admin']) : ?>
                    <a href="../admin/admin.php" class="block text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-base font-medium">Admin</a>
                <?php endif; ?>
                <a href="recherche.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-base font-medium">🔍</a>
                <button id="theme-toggle-mobile" class="block w-full text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-base font-medium text-left">
                    Mode Jour/Nuit
                </button>
                <a href="logout.php" class="block text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-md text-base font-medium">Déconnexion</a>
            </div>
</div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-2">
                Bienvenue, <?php echo htmlspecialchars($user['username']); ?> !
            </h1>
            <p class="text-gray-600">Voici votre tableau de bord personnel</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Objects Card -->
            <div class="stat-card p-6">
                <h3 class="text-lg font-semibold mb-2">Total des Objets</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($total_objets, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Objets dans la base de données</p>
            </div>

            <!-- Available Objects Card -->
            <div class="stat-card p-6">
                <h3 class="text-lg font-semibold mb-2">Objets Disponibles</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo number_format($objets_disponibles, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Objets actuellement disponibles</p>
            </div>

            <!-- Player Level Card -->
            <div class="stat-card p-6">
                <h3 class="text-lg font-semibold mb-2">Niveau Joueur</h3>
                <p class="text-3xl font-bold text-purple-600"><?php echo htmlspecialchars($user['niveau']); ?></p>
                <div class="mt-2">
                    <div class="relative pt-1">
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-purple-100">
                            <div class="progress-bar bg-purple-500" style="width: <?php echo min(($user['points_experience'] % 1000) / 10, 100); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span><?php echo $user['points_experience'] % 1000; ?> XP</span>
                            <span>1000 XP</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Current Objects Section -->
            <div class="stat-card p-6">
                <h2 class="text-xl font-bold mb-4">Objets en cours</h2>
                <?php if (empty($objets_en_cours)) : ?>
                    <p class="text-gray-500">Aucun objet en cours.</p>
                <?php else : ?>
                    <div class="space-y-4">
                    <?php foreach ($objets_en_cours as $objet) : ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="font-medium"><?php echo htmlspecialchars($objet['Nom']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($objet['Description']); ?></p>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full">
                                    <?php echo htmlspecialchars($objet['Type']); ?>
                                </span>
                                <span class="mt-2 text-sm text-gray-500">
                                    Batterie: <?php echo $objet['EtatBatterie'] ? $objet['EtatBatterie'] . '%' : 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Section -->
            <div class="stat-card p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Profil</h2>
                    <a href="profil.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Modifier
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nom</p>
                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($user['nom']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Prénom</p>
                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($user['prenom']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date de naissance</p>
                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($user['date_naissance']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Sexe</p>
                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($user['sexe']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Points d'expérience</p>
                        <p class="mt-1 text-sm"><?php echo number_format($user['points_experience'], 0, ',', ' '); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Fonction pour définir le thème
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Mettre à jour les icônes
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            
            if (theme === 'dark') {
                darkIcon.classList.add('hidden');
                lightIcon.classList.remove('hidden');
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        }

        // Initialiser le thème
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);

        // Gestionnaire d'événements pour le bouton de basculement
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
            // Gestionnaire d'événements pour le bouton de basculement mobile
        document.getElementById('theme-toggle-mobile').addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Toggle mobile menu visibility
        document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>