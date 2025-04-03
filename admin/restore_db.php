<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

// Inclure la connexion à la base de données
$pdo = require_once('../includes/db_connect.php');

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
} catch (PDOException $e) {
    log_error("Erreur lors de la récupération des données: " . $e->getMessage());
    die("Erreur interne, veuillez réessayer plus tard.");
}
// Vérifier si un fichier a été téléchargé
if ($_FILES['sqlFile']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['sqlFile']['tmp_name'];
    $fileName = $_FILES['sqlFile']['name'];

    // Vérifier l'extension du fichier
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    if ($fileExtension !== 'sql') {
        die("Erreur : Le fichier téléchargé n'est pas un fichier .sql.<br>");
    }

    // Lire le contenu du fichier SQL
    $sql = file_get_contents($fileTmpPath);
    if (!$sql) {
        die("Erreur : Impossible de lire le fichier SQL.<br>");
    }

    // Séparer les requêtes SQL si le fichier contient plusieurs instructions
    $queries = explode(";", $sql);

    try {
        // Désactiver les contraintes de clé étrangère
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // Démarrer une transaction
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $pdo->exec($query); // Exécuter chaque requête
            }
        }

        // Valider la transaction
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        // Réactiver les contraintes de clé étrangère
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        echo "Base de données restaurée avec succès !<br>";
        header('Location: ../admin/admin.php');
    } catch (PDOException $e) {
        // Vérifier si une transaction est active avant d'appeler rollBack()
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Réactiver les contraintes de clé étrangère en cas d'erreur
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        die("Erreur lors de la restauration de la base de données : " . $e->getMessage());
    }
} else {
    die("Erreur : Aucun fichier n'a été téléchargé.<br>");
}
?>