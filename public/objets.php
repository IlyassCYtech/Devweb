<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    log_error("Accès refusé : utilisateur non connecté.");
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT id, username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../public/index.php");
    die("Utilisateur non trouvé.");
}

// Récupérer le statut admin
$isAdmin = $user['admin'] ?? 0;

// Récupérer les objets utilisés par l'utilisateur
$usedStmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID = :id");
$usedStmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$usedStmt->execute();

// Récupérer les objets disponibles (non associés)
$availableStmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID IS NULL");
$availableStmt->execute();

// Récupérer les types d'objets disponibles
$typeStmt = $conn->prepare("SELECT * FROM TypeObjet");
$typeStmt->execute();
$types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes Objets | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .section-header {
            position: relative;
            margin-bottom: 2rem;
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .object-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .object-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="glass-nav fixed w-full z-50 top-0 glass-effect">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Profil</a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Accueil</a>
                    <a href="objets.php" class="text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Admin</a>
                    <?php endif; ?>
                    <a href="recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">🔍</a>
                </div>
                <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Gestion des Objets Connectés</h1>
            <a href="ajouter_objet.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white gradient-bg hover:opacity-90 transition-opacity duration-200">
                + Ajouter un objet
            </a>
        </div>

        <!-- Section Objets Utilisés -->
        <div class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 section-header">Objets Utilisés</h2>
            <div id="usedObjects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                <?php while ($object = $usedStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                    <div class="bg-white p-6 rounded-lg shadow-md relative card-hover glass-effect object-card"
                         onclick="window.location.href='modifier_objet.php?id=<?= $object['ID'] ?>'">
                        <button class="absolute top-4 right-4 text-red-600 hover:text-red-800 transition-colors duration-200" 
                                onclick="event.stopPropagation(); returnObject(<?= $object['ID'] ?>, <?= $user['id'] ?>)">
                            <span class="text-2xl">🔄</span>
                        </button>
                        <h3 class="font-semibold text-xl text-gray-800 mb-3"><?= htmlspecialchars($object['Nom']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($object['Description']) ?></p>
                        <div class="space-y-2">
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Marque:</span> 
                                <?= htmlspecialchars($object['Marque']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Connectivité:</span>
                                <?= htmlspecialchars($object['Connectivite']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">État:</span>
                                <span class="px-2 py-1 rounded-full text-xs <?= $object['Etat'] === 'Actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars($object['Etat']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Section Objets Disponibles -->
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 section-header">Objets Disponibles</h2>
            <div id="availableObjects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                <?php while ($object = $availableStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                    <div class="bg-white p-6 rounded-lg shadow-md relative card-hover glass-effect object-card"
                         onclick="window.location.href='modifier_objet.php?id=<?= $object['ID'] ?>'">
                        <button class="absolute top-4 right-4 text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                onclick="event.stopPropagation(); assignObject(<?= $object['ID'] ?>, <?= $user['id'] ?>)">
                            <span class="text-2xl">➕</span>
                        </button>
                        <h3 class="font-semibold text-xl text-gray-800 mb-3"><?= htmlspecialchars($object['Nom']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($object['Description']) ?></p>
                        <div class="space-y-2">
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Marque:</span>
                                <?= htmlspecialchars($object['Marque']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Connectivité:</span>
                                <?= htmlspecialchars($object['Connectivite']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">État:</span>
                                <span class="px-2 py-1 rounded-full text-xs <?= $object['Etat'] === 'Actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars($object['Etat']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/objet.js"></script>
</body>
</html>