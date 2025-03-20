<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
$pdo = require_once('../includes/db_connect.php');

// Fonction de log
function log_error($message) {
    $logFile = '../logs/edit_user.log'; // Chemin du fichier de log
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Vérifier si la requête est bien de type POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Récupérer les données du formulaire avec nettoyage
        $id = trim($_POST['id']);
        $username = trim($_POST['username']);
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $type_membre = trim($_POST['type_membre']);
        $niveau = trim($_POST['niveau']);
        $points_experience = (int)$_POST['points_experience'];
        $admin = isset($_POST['admin']) ? (int)$_POST['admin'] : 0;

        // Vérifier que tous les champs obligatoires sont remplis
        if (empty($id) || empty($username) || empty($nom) || empty($prenom) || empty($email) || empty($type_membre) || empty($niveau)) {
            log_error("Champs manquants dans l'édition de l'utilisateur ID : $id");
            echo "Erreur : Tous les champs sont obligatoires.";
            exit();
        }

        // Vérifier que l'ID est un nombre valide
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            log_error("ID utilisateur invalide : $id");
            echo "Erreur : ID utilisateur invalide.";
            exit();
        }

        // Vérifier que l'email est valide
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            log_error("Adresse email invalide : $email");
            echo "Erreur : Adresse email invalide.";
            exit();
        }

        // Vérifier si l'utilisateur existe avant modification
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            log_error("Utilisateur non trouvé avec ID : $id");
            echo "Erreur : Utilisateur non trouvé.";
            exit();
        }

        // Mise à jour des informations de l'utilisateur
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = :username, nom = :nom, prenom = :prenom, email = :email, 
                type_membre = :type_membre, niveau = :niveau, points_experience = :points_experience, admin = :admin 
            WHERE id = :id
        ");

        $stmt->execute([
            ':username'         => $username,
            ':nom'              => $nom,
            ':prenom'           => $prenom,
            ':email'            => $email,
            ':type_membre'      => $type_membre,
            ':niveau'           => $niveau,
            ':points_experience'=> $points_experience,
            ':admin'            => $admin,
            ':id'               => $id
        ]);

        echo "Mise à jour réussie pour l'utilisateur ID : $id.";
        exit();
    } else {
        log_error("Méthode de requête invalide.");
        echo "Erreur : Méthode de requête invalide.";
        exit();
    }
} catch (PDOException $e) {
    // Enregistrer l'erreur et afficher un message
    log_error("Erreur interne : " . $e->getMessage());
    echo "Erreur interne. Veuillez réessayer plus tard.";
    exit();
}
