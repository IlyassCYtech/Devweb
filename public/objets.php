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
$stmt = $conn->prepare("SELECT username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../public/index.php");
    die("Utilisateur non trouvé.");
}

// Récupérer le statut admin
$isAdmin = $user['admin'] ?? 0;  // Vérifie directement le statut admin

// Récupérer les objets utilisés par l'utilisateur
$usedStmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID = :id");
$usedStmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$usedStmt->execute();

// Récupérer les objets disponibles (non associés)
$availableStmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID IS NULL");
$availableStmt->execute();
?>


<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes Objets | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
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
                </div>
                <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>


    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-6">Mes Objets Connectés</h1>

        <!-- Section Objets Occupés -->
        <div class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Objets Utilisés</h2>
            <div id="usedObjects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($object = $usedStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                    <div class="bg-white p-4 rounded-lg shadow-md relative">
                        <!-- Nouveau bouton "Rendre" -->
                        <button class="absolute top-2 right-2 text-red-600 hover:text-red-800" onclick="returnObject(<?= $object['ID'] ?>)">
                            <span class="text-3xl">🚫</span>
                        </button>
                        <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($object['Nom']) ?></h3>
                        <p class="text-gray-600"><?= htmlspecialchars($object['Description']) ?></p>
                        <p class="text-gray-500">Marque: <?= htmlspecialchars($object['Marque']) ?></p>
                        <p class="text-gray-500">Connectivité: <?= htmlspecialchars($object['Connectivite']) ?></p>
                        <p class="text-gray-500">État: <?= htmlspecialchars($object['Etat']) ?></p>
                    </div>

                <?php endwhile; ?>
            </div>
        </div>

        <!-- Section Objets Disponibles -->
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Objets Disponibles</h2>
            <div id="availableObjects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($object = $availableStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                    <div class="bg-white p-4 rounded-lg shadow-md relative">
                        <button class="absolute top-2 right-2 text-blue-600 hover:text-blue-800" onclick="assignObject(<?= $object['ID'] ?>)">
                            <span class="text-3xl">+</span>
                        </button>    
                        <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($object['Nom']) ?></h3>
                        <p class="text-gray-600"><?= htmlspecialchars($object['Description']) ?></p>
                        <p class="text-gray-500">Marque: <?= htmlspecialchars($object['Marque']) ?></p>
                        <p class="text-gray-500">Connectivité: <?= htmlspecialchars($object['Connectivite']) ?></p>
                        <p class="text-gray-500">État: <?= htmlspecialchars($object['Etat']) ?></p>
                    </div>

                <?php endwhile; ?>
            </div>
        </div>

    </main>

    
    <script>
    function assignObject(objectId) {
        // Crée un objet de données à envoyer
        const userId = <?= $user_id ?>;  // UtilisateurID de la session

        // Envoi des données via AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'assign_object.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        // Paramètres à envoyer : ID de l'objet et ID de l'utilisateur
        const params = 'objectId=' + objectId + '&userId=' + userId;

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Lorsque la requête est terminée avec succès, recharge la page
                window.location.reload();
            }
        };

        xhr.send(params);
    }

    function returnObject(objectID) {
    const userId = <?= $user_id ?>;  // L'utilisateur connecté récupère son ID à partir de PHP

    // Demander la confirmation avant de rendre l'objet
    if (confirm("Êtes-vous sûr de vouloir rendre cet objet ?")) {
        // Envoyer la requête AJAX pour mettre à jour l'objet dans la base de données
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "return_object.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        const params = 'objectID=' + objectID + '&userID=' + userId;  // Utilisation de objectID et userID

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Si tout se passe bien, recharger la page pour mettre à jour les objets
                location.reload(); // Recharger la page pour afficher les mises à jour
            }
        };
        xhr.send(params);
    }
}



</script>

</body>
</html>
