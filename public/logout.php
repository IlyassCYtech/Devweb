<?php
session_start(); // Assurez-vous que la session est démarrée avant de la détruire
include('../includes/db_connect.php');

if (isset($_SESSION['user_id'])) {
    // Enregistrer l'action dans l'historique
    $historyStmt = $conn->prepare("
        INSERT INTO UserHistory (user_id, action_type) 
        VALUES (:user_id, 'Déconnexion')
    ");
    $historyStmt->execute([':user_id' => $_SESSION['user_id']]);
}

if (session_status() === PHP_SESSION_ACTIVE) { 
    session_destroy(); // Détruit la session
}

header("Location: ../public/index.php"); // Corrige la redirection
exit(); // Assure que le script s'arrête après la redirection
?>
