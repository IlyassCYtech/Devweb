<?php
session_start();
include('../includes/db_connect.php'); // Ensure this file defines $pdo

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Accès refusé.']));
}

$user_id = $_SESSION['user_id'];

// Vérifier si $pdo est défini
if (!isset($pdo)) {
    die(json_encode(['success' => false, 'message' => 'Erreur : La connexion à la base de données n\'est pas établie.']));
}

$stmt = $pdo->prepare("SELECT admin FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['admin'] != 1) {
    die(json_encode(['success' => false, 'message' => 'Accès refusé.']));
}

// Vérifier si un ID utilisateur est fourni
if (!isset($_POST['id']) || empty($_POST['id'])) {
    die(json_encode(['success' => false, 'message' => 'ID utilisateur manquant.']));
}

$approveUserId = (int)$_POST['id'];

try {
    // Mettre à jour is_confirmed_by_ad à 1 pour approuver l'utilisateur
    $stmt = $pdo->prepare("UPDATE users SET is_confirmed_by_ad = 1 WHERE id = :id");
    $stmt->execute([':id' => $approveUserId]);

    // Vérifier si la mise à jour a réussi
    if ($stmt->rowCount() == 0) {
        die(json_encode(['success' => false, 'message' => 'Erreur lors de l\'approbation de l\'utilisateur.']));
    }

    echo json_encode(['success' => true, 'message' => 'Utilisateur approuvé avec succès.']);
} catch (PDOException $e) {
    error_log("Erreur lors de l'approbation de l'utilisateur : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'approbation de l\'utilisateur.']);
}
?>
