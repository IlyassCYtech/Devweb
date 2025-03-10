<?php
// Inclure le fichier de connexion à la base de données
$pdo = require_once('db_connect.php');

// Vérifier que la connexion est bien initialisée
if (!$pdo) {
    die("Erreur : Connexion à la base de données échouée.<br>");
}

// Charger le fichier SQL
$sqlFile = 'queries/users.sql';

if (!file_exists($sqlFile)) {
    die("Erreur : Le fichier SQL '$sqlFile' est introuvable.<br>");
}

// Lire le contenu du fichier SQL
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Erreur : Impossible de lire le fichier SQL.<br>");
}

// Séparer les requêtes SQL si le fichier contient plusieurs instructions
$queries = explode(";", $sql);

try {
    // Vérification avant de commencer la transaction
    if (!$pdo->inTransaction()) {
        echo "Démarrage de la transaction...<br>";
        $pdo->beginTransaction();
    }

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            echo "Exécution de la requête : " . htmlspecialchars($query) . "<br>";
            $pdo->exec($query); // Exécuter la requête
        }
    }

        // Mise à jour des mots de passe hachés
        echo "Démarrage de la mise à jour des mots de passe...<br>";

        // Récupérer tous les utilisateurs ayant un mot de passe en clair
        $stmt = $pdo->query("SELECT id, password FROM users WHERE password NOT LIKE '$2y$%'");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (!$users) {
            echo "Tous les mots de passe sont déjà hachés !<br>";
        } else {
            // Mettre à jour chaque mot de passe avec hash
            foreach ($users as $user) {
                $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $updateStmt->execute([
                    ':password' => $hashedPassword,
                    ':id' => $user['id']
                ]);
                echo "Mot de passe mis à jour pour l'utilisateur ID : {$user['id']}<br>";
            }
        }

    // Vérifier si la transaction est bien active avant de la valider
    if ($pdo->inTransaction()) {
        echo "Commit de la transaction...<br>";
        $pdo->commit();
    } else {
        echo "Aucune transaction active au moment du commit.<br>";
    }

    echo "Base de données initialisée avec succès !<br>";

} catch (PDOException $e) {
    // Vérifier si une transaction est active avant d'appeler rollBack()
    if ($pdo->inTransaction()) {
        echo "Rollback de la transaction...<br>";
        $pdo->rollBack();
    } else {
        echo "Aucune transaction active au moment du rollback.<br>";
    }
    
    die("Erreur lors de l'initialisation de la base de données : " . $e->getMessage());
}

// Fermer la connexion (facultatif en PDO)
$pdo = null;
?>
