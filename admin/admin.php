<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
$pdo = require_once('../includes/db_connect.php');

// Fonction de log pour erreurs
function log_error($message) {
    $logFile = '../logs/admin.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

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

try {
    // Vérifier dans la base si l'utilisateur est admin
    $stmt = $pdo->prepare("SELECT admin FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['admin'] != 1) {
        log_error("Accès refusé : utilisateur ID $user_id tenté d'accéder à l'admin.");
        header("Location: ../public/index.php");
        exit();
    }

    // Récupérer tous les utilisateurs
    $stmtUsers = $pdo->query("SELECT * FROM users ORDER BY id ASC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer tous les objets connectés
    $stmtObjects = $pdo->query("SELECT * FROM ObjetConnecte ORDER BY ID ASC");
    $objects = $stmtObjects->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les utilisateurs en attente de validation par l'admin (is_confirmed_by_ad = 0)
    $stmtPendingUsers = $pdo->query("SELECT * FROM users WHERE is_confirmed_by_ad = 0 ORDER BY id ASC");
    $pendingUsers = $stmtPendingUsers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    log_error("Erreur lors de la récupération des données: " . $e->getMessage());
    die("Erreur interne, veuillez réessayer plus tard.");
}

try {
    // Fetch delete requests
    $requestsStmt = $pdo->prepare("
        SELECT dr.id, dr.request_date, u.username, o.Nom AS object_name
        FROM DeleteRequests dr
        JOIN users u ON dr.user_id = u.id
        JOIN ObjetConnecte o ON dr.object_id = o.ID
        ORDER BY dr.request_date DESC
    ");
    $requestsStmt->execute();
    $deleteRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    log_error("Erreur lors de la récupération des demandes de suppression: " . $e->getMessage());
    die("Erreur interne, veuillez réessayer plus tard.");
}

try {
    // Récupérer l'historique des connexions/inscriptions
    $historyStmt = $pdo->prepare("
        SELECT uh.action_date, uh.action_type, u.username, u.photo_profil 
        FROM UserHistory uh
        JOIN users u ON uh.user_id = u.id
        ORDER BY uh.action_date DESC
    ");
    $historyStmt->execute();
    $userHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    log_error("Erreur lors de la récupération de l'historique des utilisateurs: " . $e->getMessage());
    die("Erreur interne, veuillez réessayer plus tard.");
}

// Fonction pour récupérer l'historique d'un utilisateur (pour AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_history') {
    try {
        $userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
            exit;
        }
        
        // Récupérer l'historique des actions de l'utilisateur
        $stmtHistory = $pdo->prepare("
        SELECT h.*, o.Nom as nom_objet 
        FROM Historique_Actions h 
        LEFT JOIN ObjetConnecte o ON h.id_objet_connecte = o.ID 
        WHERE h.id_utilisateur = :user_id 
        ORDER BY h.date_heure DESC
    ");
    $stmtHistory->execute([':user_id' => $userId]);
        $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    } catch (PDOException $e) {
        log_error("Erreur lors de la récupération de l'historique: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Utilisateurs & Objets</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.3);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-hover {
            transition: all 0.2s ease;
        }

        .btn-hover:hover {
            transform: translateY(-1px);
        }

        .edit-form {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        @keyframes fadeInModal {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close-modal:hover {
            color: #555;
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-8">
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
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            
            <div class="glass-card rounded-2xl p-6 mb-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">Panel Administrateur</h1>
                    <div class="flex space-x-4">
                        <a href="backup_db.php" class="btn-hover inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Sauvegarder la Base de Données
                        </a>
                        <a href="admin_objet.php" class="btn-hover inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            Gérer les Objets
                        </a>
                    </div>
                </div>
            </div>

            <!-- Users Section -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Liste des Utilisateurs</h2>
                <div class="table-container bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prénom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">XP</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user) : ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-id"><?= htmlspecialchars($user['id']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 user-username"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-nom"><?= htmlspecialchars($user['nom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-prenom"><?= htmlspecialchars($user['prenom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-email"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-type_membre"><?= htmlspecialchars($user['type_membre']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-niveau"><?= htmlspecialchars($user['niveau']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-experience"><?= htmlspecialchars($user['points_experience']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-admin" data-value="<?= $user['admin'] ?>">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['admin'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= $user['admin'] ? '✅' : '❌' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="btn-hover btn-history mr-2 inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-id="<?= $user['id'] ?>" data-username="<?= htmlspecialchars($user['username']) ?>">
                                            Historique
                                        </button>
                                        <button class="btn-hover btn-edit mr-2 inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-id="<?= $user['id'] ?>">
                                            Modifier
                                        </button>
                                        <button class="btn-hover btn-delete inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" data-id="<?= $user['id'] ?>">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section des utilisateurs en attente de validation par l'admin -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Utilisateurs en attente de validation par l'admin</h2>
                <div class="table-container bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom d'utilisateur</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prénom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validation Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pendingUsers as $user) : ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['id']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['nom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['prenom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $user['is_confirmed'] == 1 ? '✅ Validé' : '❌ Non validé' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="btn-approve btn-hover inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" data-id="<?= $user['id'] ?>">
                                            Approuver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bouton pour restaurer la base de données -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Restaurer la Base de Données</h2>
                <form action="restore_db.php" method="POST">
                    <label for="sqlFile" class="block text-sm font-medium text-gray-700">Choisir un fichier .sql :</label>
                    <select name="sqlFile" id="sqlFile" class="mt-2 mb-4 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <?php
                        $backupDir = '../backups/';
                        if (is_dir($backupDir)) {
                            $files = array_filter(scandir($backupDir), function ($file) use ($backupDir) {
                                return is_file($backupDir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'sql';
                            });
                            foreach ($files as $file) {
                                echo "<option value=\"$file\">" . htmlspecialchars($file) . "</option>";
                            }
                        } else {
                            echo "<option disabled>Aucun fichier trouvé</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn-hover inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        Restaurer la Base de Données
                    </button>
                </form>
            </div>

            <!-- Objects Section -->
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Liste des Objets Connectés</h2>
                <div class="table-container bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marque</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">État</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connectivité</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Énergie</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière Interaction</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($objects as $obj) : ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['ID']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($obj['Nom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['Type']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['Description']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['Marque']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $obj['Etat'] === 'Actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= htmlspecialchars($obj['Etat']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['Connectivite']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['EnergieUtilisee']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($obj['DerniereInteraction']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Delete Requests Section -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Demandes de suppression</h2>
                <div class="table-container bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objet</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de demande</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($deleteRequests as $request): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($request['username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($request['object_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($request['request_date']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" action="handle_request.php" class="inline">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-500 text-white rounded">Approuver</button>
                                        </form>
                                        <form method="POST" action="handle_request.php" class="inline">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" name="action" value="reject" class="px-4 py-2 bg-red-500 text-white rounded">Rejeter</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Historique des Connexions/Inscription</h2>
                <div class="table-container bg-white">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($userHistory as $history): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img src="../uploads/<?= htmlspecialchars($history['photo_profil']) ?>" alt="Photo de profil" class="h-10 w-10 rounded-full">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($history['username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($history['action_type']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($history['action_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

    <!-- Modal pour afficher l'historique des actions -->
    <div id="historyModal" class="history-modal">
        <div class="history-modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="historyTitle" class="text-2xl font-bold mb-4 text-gray-900"></h2>
            <div id="historyContent" class="mt-4">
                <div class="flex justify-center">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p class="text-center text-gray-600 mt-2">Chargement de l'historique...</p>
            </div>
        </div>
    </div>


    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Animation for page load
        document.querySelectorAll('.glass-card').forEach(card => {
            card.classList.add('fade-in');
        });

        // Edit user functionality
        document.querySelectorAll(".btn-edit").forEach(button => {
            button.addEventListener("click", function () {
                let row = this.closest("tr");
                let userId = row.querySelector(".user-id").innerText;
                let username = row.querySelector(".user-username").innerText;
                let nom = row.querySelector(".user-nom").innerText;
                let prenom = row.querySelector(".user-prenom").innerText;
                let email = row.querySelector(".user-email").innerText;
                let typeMembre = row.querySelector(".user-type_membre").innerText;
                let niveau = row.querySelector(".user-niveau").innerText;
                let experience = row.querySelector(".user-experience").innerText;
                let admin = row.querySelector(".user-admin").dataset.value;

                let editForm = `
                    <tr id="editRow" class="edit-form bg-blue-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${userId}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="text" id="editUsername" value="${username}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="text" id="editNom" value="${nom}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="text" id="editPrenom" value="${prenom}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="email" id="editEmail" value="${email}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select id="editTypeMembre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="élève" ${typeMembre === 'élève' ? 'selected' : ''}>Élève</option>
                                <option value="parent" ${typeMembre === 'parent' ? 'selected' : ''}>Parent</option>
                                <option value="développeur" ${typeMembre === 'développeur' ? 'selected' : ''}>Développeur</option>
                                <option value="autre" ${typeMembre === 'autre' ? 'selected' : ''}>Autre</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select id="editNiveau" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="Débutant" ${niveau === 'Débutant' ? 'selected' : ''}>Débutant</option>
                                <option value="Intermédiaire" ${niveau === 'Intermédiaire' ? 'selected' : ''}>Intermédiaire</option>
                                <option value="Avancé" ${niveau === 'Avancé' ? 'selected' : ''}>Avancé</option>
                                <option value="Expert" ${niveau === 'Expert' ? 'selected' : ''}>Expert</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="number" id="editExperience" value="${experience}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <select id="editAdmin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="0" ${admin == 0 ? 'selected' : ''}>❌</option>
                                <option value="1" ${admin == 1 ? 'selected' : ''}>✅</option>
                            </select>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="btn-save btn-hover inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-2" data-id="${userId}">
                                Enregistrer
                            </button>
                            <button class="btn-cancel btn-hover inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Annuler
                            </button>
                        </td>
                    </tr>
                `;

                row.insertAdjacentHTML("afterend", editForm);
                row.style.display = "none";

                // Cancel button handler
                document.querySelector(".btn-cancel").addEventListener("click", function () {
                    document.getElementById("editRow").remove();
                    row.style.display = "table-row";
                });

                // Save button handler
                document.querySelector(".btn-save").addEventListener("click", function () {
                    let userId = this.dataset.id;
                    let newUsername = document.getElementById("editUsername").value;
                    let newNom = document.getElementById("editNom").value;
                    let newPrenom = document.getElementById("editPrenom").value;
                    let newEmail = document.getElementById("editEmail").value;
                    let newTypeMembre = document.getElementById("editTypeMembre").value;
                    let newNiveau = document.getElementById("editNiveau").value;
                    let newExperience = document.getElementById("editExperience").value;
                    let newAdmin = document.getElementById("editAdmin").value;

                    fetch("edit_user.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id=${userId}&username=${newUsername}&nom=${newNom}&prenom=${newPrenom}&email=${newEmail}&type_membre=${newTypeMembre}&niveau=${newNiveau}&points_experience=${newExperience}&admin=${newAdmin}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
                    })
                    .catch(error => console.error("Erreur:", error));
                });
            });
        });

        // Delete user functionality
        document.querySelectorAll(".btn-delete").forEach(button => {
            button.addEventListener("click", function () {
                let userId = this.dataset.id;
                
                if (confirm("Voulez-vous vraiment supprimer cet utilisateur ?")) {
                    fetch("delete_user.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id=${userId}`
                    })
                    .then(response => response.text())
                    .then(data => {
                        location.reload();
                    })
                    .catch(error => console.error("Erreur:", error));
                }
            });
        });

        // Approve user functionality
        document.querySelectorAll(".btn-approve").forEach(button => {
            button.addEventListener("click", function () {
                const userId = this.dataset.id;

                if (confirm("Êtes-vous sûr de vouloir approuver cet utilisateur ?")) {
                    fetch("approve_user.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id=${userId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload(); // Recharger la page pour mettre à jour la liste
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Erreur:", error);
                        alert("Une erreur est survenue lors de l'approbation de l'utilisateur.");
                    });
                }
            });
        });
    });
    </script>
      <script>
document.addEventListener("DOMContentLoaded", function() {
    // Référence au modal
    const historyModal = document.getElementById("historyModal");
    const closeBtn = document.querySelector(".close-modal");
    
    // Fonction pour fermer le modal
    function closeModal() {
        historyModal.style.display = "none";
    }
    
    // Fermer le modal en cliquant sur le X
    closeBtn.addEventListener("click", closeModal);
    
    // Fermer le modal en cliquant en dehors du contenu
    window.addEventListener("click", function(event) {
        if (event.target === historyModal) {
            closeModal();
        }
    });
    
    // Afficher l'historique d'un utilisateur
    document.querySelectorAll(".btn-history").forEach(button => {
        button.addEventListener("click", function() {
            const userId = this.dataset.id;
            const username = this.dataset.username;
            
            // Afficher le modal
            historyModal.style.display = "block";
            document.getElementById("historyTitle").textContent = `Historique des actions de ${username}`;
            
            // Indiquer le chargement
            const historyContent = document.getElementById("historyContent");
            historyContent.innerHTML = `
                <div class="flex justify-center">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p class="text-center text-gray-600 mt-2">Chargement de l'historique...</p>
            `;
            
            // Charger l'historique via AJAX
            fetch("admin.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `action=get_history&id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.history && data.history.length > 0) {
                    // Créer un tableau pour afficher l'historique
                    let tableHTML = `
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type d'action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objet concerné</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                    `;
                    
                    data.history.forEach(item => {
                        tableHTML += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.date_heure}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.type_action}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.nom_objet || 'Aucun objet'}</td>
                            </tr>
                        `;
                    });
                    
                    tableHTML += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    historyContent.innerHTML = tableHTML;
                } else {
                    historyContent.innerHTML = `<p class="text-center text-gray-600">Aucun historique disponible pour cet utilisateur.</p>`;
                }
            })
            .catch(error => {
                console.error("Erreur:", error);
                historyContent.innerHTML = `<p class="text-center text-red-600">Erreur lors du chargement de l'historique. Consultez la console pour plus de détails.</p>`;
            });
        });
    });
});
</script>

</body>
</html>