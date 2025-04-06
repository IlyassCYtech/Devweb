<?php
session_start();
include('../includes/db_connect.php');

// Ajoutez un fichier de log pour déboguer
function log_error($message) {
    $logFile = '../logs/demande_type_objet.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $type_objet = trim($_POST['type_objet'] ?? '');

    // Log des données reçues
    log_error("Données reçues : user_id = $user_id, type_objet = $type_objet");

    if (!$user_id || empty($type_objet)) {
        log_error("Données invalides : user_id ou type_objet manquant.");
        echo json_encode(['success' => false, 'message' => 'Données invalides.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO DemandesTypeObjet (user_id, type_objet, date_demande) VALUES (:user_id, :type_objet, NOW())");
        $stmt->execute([
            ':user_id' => $user_id,
            ':type_objet' => $type_objet
        ]);

        log_error("Demande insérée avec succès.");
        echo json_encode(['success' => true, 'message' => 'Demande envoyée avec succès.']);
    } catch (PDOException $e) {
        log_error("Erreur PDO : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur interne.']);
    }
} else {
    log_error("Méthode non autorisée.");
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
