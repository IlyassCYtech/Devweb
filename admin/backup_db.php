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

// Vérifier que la connexion est bien initialisée
if (!$pdo) {
    die("Erreur : Connexion à la base de données échouée.<br>");
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
} catch (PDOException $e) {
    log_error("Erreur lors de la récupération des données: " . $e->getMessage());
    die("Erreur interne, veuillez réessayer plus tard.");
}

try {
    // Nom du fichier de sauvegarde
    $date = new DateTime('now', new DateTimeZone('Europe/Paris'));
    $backupFile = '../backups/backup_' . $date->format('Y-m-d_H-i-s') . '.sql';
    $handle = fopen($backupFile, 'w');

    if (!$handle) {
        die("Erreur : Impossible de créer le fichier de sauvegarde.<br>");
    }

    // Exporter la structure des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        // Ajouter une instruction DROP TABLE
        fwrite($handle, "-- Supprimer la table si elle existe\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");

        // Récupérer la structure de la table
        $createTableStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        fwrite($handle, "-- Structure de la table `$table`\n");
        fwrite($handle, $createTableStmt['Create Table'] . ";\n\n");

        // Exporter les données de la table
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            fwrite($handle, "-- Données de la table `$table`\n");
            foreach ($rows as $row) {
                $values = array_map(function ($value) use ($pdo) {
                    // Vérifier si la valeur est NULL
                    return $value === null ? 'NULL' : $pdo->quote($value); // Utiliser 'NULL' pour les valeurs nulles
                }, $row);
                $valuesString = implode(", ", $values);
                fwrite($handle, "INSERT INTO `$table` VALUES ($valuesString);\n");
            }
            fwrite($handle, "\n");
        }
    }

    fclose($handle);
    echo "Sauvegarde réussie : $backupFile<br>";
    header('Location: ../admin/admin.php');
} catch (PDOException $e) {
    die("Erreur lors de la sauvegarde de la base de données : " . $e->getMessage());
}
?>
