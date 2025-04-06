<?php
// Fichier: generate_report_button.php
session_start();
require('../includes/db_connect.php');


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    log_error("Accès refusé : utilisateur non connecté.");
    header("Location: ../public/index.php");
    exit();
}
if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT admin FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['admin'] != 1) {
    header("Location: ../public/index.php");
    exit();
}

// Pour avoir une idée rapide des statistiques avant de générer le rapport
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM ObjetConnecte");
$total_objects = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM Historique_Actions");
$total_actions = $stmt->fetch()['total'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération de Rapport - Smart City San Francisco</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Garder la nav telle quelle */
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.3);
        }
        
        /* Style pour le modal d'historique */
        .history-modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .history-modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 70%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            animation: fadeInModal 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar inchangée -->
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="../public/profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="../public/dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="../public/objets.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="../public/recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>
                <a href="../public/logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 pt-20 pb-8">
        <!-- Report container -->
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Tableau de bord des statistiques</h2>
            <p class="text-gray-600 mb-6">
                Bienvenue dans votre espace de génération de rapports. En tant qu'administrateur, vous pouvez accéder à une analyse complète des données de notre plateforme Smart City.
            </p>
            
            <!-- Stats cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
                    <h4 class="text-xl font-semibold text-blue-800"><?php echo $total_users; ?></h4>
                    <p class="text-blue-600">Utilisateurs enregistrés</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
                    <h4 class="text-xl font-semibold text-blue-800"><?php echo $total_objects; ?></h4>
                    <p class="text-blue-600">Objets connectés</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
                    <h4 class="text-xl font-semibold text-blue-800"><?php echo $total_actions; ?></h4>
                    <p class="text-blue-600">Actions enregistrées</p>
                </div>
            </div>
            
            <!-- Generate button -->
            <div class="text-center mb-8">
                <p class="text-gray-600 mb-4">Générez un rapport complet au format PDF contenant des statistiques détaillées sur les utilisateurs, les objets connectés et leurs interactions.</p>
                <a href="generate_report.php" class="inline-flex items-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-medium rounded-full transform transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
                    <i class="fas fa-file-pdf mr-2"></i> Générer le rapport
                </a>
            </div>
            
            <!-- Report content -->
            <div class="mt-8">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Contenu du rapport</h4>
                <ul class="space-y-2 pl-5 list-disc text-gray-600">
                    <li>Statistiques détaillées sur les utilisateurs (nombre, types, niveaux)</li>
                    <li>Analyse de l'utilisateur le plus actif</li>
                    <li>Répartition des objets connectés par type et état</li>
                    <li>Identification de l'objet et du type d'objet les plus utilisés</li>
                    <li>Analyse de l'objet utilisé le plus récemment</li>
                    <li>Statistiques d'activité et tendances</li>
                    <li>Informations géographiques sur les objets connectés à San Francisco</li>
                    <li>Recommandations pour améliorer l'utilisation de la plateforme</li>
                </ul>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6 text-gray-500 text-sm">
            <p>Les données sont mises à jour en temps réel. Dernière mise à jour: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</body>
</html>