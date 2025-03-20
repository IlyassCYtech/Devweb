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


        // Vérifier que tous les champs obligatoires sont remplis
        if (empty($id)) {
            log_error("probleme avec lid");
            echo "Erreur : Tous les champs sont obligatoires.";
            exit();
        }

        // Vérifier que l'ID est un nombre valide
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            log_error("ID utilisateur invalide : $id");
            echo "Erreur : ID utilisateur invalide.";
            exit();
        }

    // Suppression de l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // Vérifier si un utilisateur a bien été supprimé
        if ($stmt->rowCount() === 0) {
            log_error("Utilisateur non trouvé ou déjà supprimé, ID : $id");
            echo "Erreur : Utilisateur non trouvé ou déjà supprimé.";
            exit();
        }

        echo "Utilisateur supprimé avec succès, ID : $id.";
        exit();
    } else {
        log_error("Méthode de requête invalide.");
        echo "Erreur : Méthode de requête invalide.";
        exit();
    }

        // Vérifier si l'utilisateur supprime son propre compte
        if (isset($_SESSION['id']) && $_SESSION['id'] == $id) {
            // Détruire la session
            session_destroy();
            log_error("L'utilisateur ID $id a supprimé son propre compte. Déconnexion effectuée.");
        }

} catch (PDOException $e) {
    // Enregistrer l'erreur et afficher un message
    log_error("Erreur interne : " . $e->getMessage());
    echo "Erreur interne. Veuillez réessayer plus tard.";
    exit();
}