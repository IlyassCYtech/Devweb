<?php
require_once '../includes/dbconnect.php'; // Connexion via PDO

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de l\'objet manquant']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Vérifier si l'utilisateur a le droit de supprimer cet objet
    if (!isAdmin()) {
        $sql = "SELECT id_utilisateur FROM ObjetConnecte WHERE ID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['id']]);
        $object = $stmt->fetch();
        
        if (!$object || $object['id_utilisateur'] !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
    }
    
    // Supprimer l'objet
    $sql = "DELETE FROM ObjetConnecte WHERE ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['id']]);
    
    // Journaliser l'action dans Historique_Actions
    $logSql = "INSERT INTO Historique_Actions (id_utilisateur, id_objet_connecte, type_action) 
               VALUES (:user_id, :objet_id, 'Suppression d\'un objet')";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':user_id' => $userId,
        ':objet_id' => $data['id']
    ]);
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}
