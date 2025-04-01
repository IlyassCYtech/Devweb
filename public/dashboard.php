<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../public/index.php"); // Corrige la redirection
    }
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
    if ($_SESSION['is_confirmed'] != 1) {
        // Si l'utilisateur n'est pas confirmé, le rediriger vers la page de confirmation
        header("Location: confirm.php");
        exit();
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
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .progress-bar {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 9999px;
            height: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">

                    <a href="profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="objets.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                        <?php endif; ?>
                    <a href="recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>
                <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <!-- Stats Grid -->

        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Bienvenue, <?php echo htmlspecialchars($user['username']); ?> !
            </h1>
            <p class="text-gray-600">Voici votre tableau de bord personnel</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Objects Card -->
            <div class="stat-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Total des Objets</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($total_objets, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Objets dans la base de données</p>
            </div>

            <!-- Available Objects Card -->
            <div class="stat-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Objets Disponibles</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo number_format($objets_disponibles, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Objets actuellement disponibles</p>
            </div>

            <!-- Player Level Card -->
            <div class="stat-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Niveau Joueur</h3>
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
                <h2 class="text-xl font-bold text-gray-900 mb-4">Objets en cours</h2>
                <?php if (empty($objets_en_cours)) : ?>
                    <p class="text-gray-500">Aucun objet en cours.</p>
                <?php else : ?>
                    <div class="space-y-4">
                    <?php foreach ($objets_en_cours as $objet) : ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($objet['Nom']); ?></h3>
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
                    <h2 class="text-xl font-bold text-gray-900">Profil</h2>
                    <a href="profil.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Modifier
                    </a>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Nom</p>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['nom']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Prénom</p>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['prenom']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Date de naissance</p>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['date_naissance']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Sexe</p>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['sexe']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Points d'expérience</p>
                            <p class="mt-1 text-sm text-gray-900"><?php echo number_format($user['points_experience'], 0, ',', ' '); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>