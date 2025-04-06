<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Vérifier si l'utilisateur est admin dans la base de données
    $stmt = $conn->prepare("SELECT admin FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['admin'] != 1) {
        // Rediriger si l'utilisateur n'est pas admin
        header("Location: ../public/index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur interne : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_objet = trim($_POST['type_objet'] ?? '');

    if (empty($type_objet)) {
        $_SESSION['error_message'] = "Le nom du type d'objet est requis.";
    } else {
        try {
            // Vérifier si une image a été uploadée
            if (isset($_FILES['type_image']) && $_FILES['type_image']['error'] === UPLOAD_ERR_OK) {
                $image = $_FILES['type_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $fileType = $finfo->file($image['tmp_name']);

                if (in_array($fileType, $allowedTypes)) {
                    $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
                    $imageName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $type_objet) . '.' . $extension; // Renommer l'image comme le type d'objet
                    $uploadPath = '../assets/images/' . $imageName;

                    if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                        // Ajouter le type d'objet dans la base de données
                        $stmt = $conn->prepare("INSERT INTO TypeObjet (Nom) VALUES (:nom)");
                        $stmt->execute([':nom' => $type_objet]);

                        $_SESSION['success_message'] = "Type d'objet créé avec succès.";
                        header("Location: creer_type_objet.php");
                        exit();
                    } else {
                        $_SESSION['error_message'] = "Erreur lors de l'upload de l'image.";
                    }
                } else {
                    $_SESSION['error_message'] = "Format d'image non autorisé. Utilisez JPG, PNG ou GIF.";
                }
            } else {
                $_SESSION['error_message'] = "Aucune image fournie pour le type d'objet.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Type d'Objet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="glass-nav fixed w-full z-50 top-0 bg-white shadow-md">
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
                    <a href="admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                </div>
                <a href="../public/logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="min-h-screen flex items-center justify-center pt-24">
        <div class="bg-white shadow-lg rounded-lg p-8 max-w-lg w-full">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Créer un Type d'Objet</h1>
            <?php if (isset($_SESSION['success_message'])) : ?>
                <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                    <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])) : ?>
                <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
                    <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            <form action="creer_type_objet.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="type_objet" class="block text-sm font-medium text-gray-700">Nom du Type d'Objet :</label>
                    <input type="text" name="type_objet" id="type_objet" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="type_image" class="block text-sm font-medium text-gray-700">Image du Type d'Objet :</label>
                    <input type="file" name="type_image" id="type_image" accept="image/jpeg, image/png, image/gif" required
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="flex justify-end space-x-4">
                    <a href="../public/objets.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Annuler
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Créer
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
