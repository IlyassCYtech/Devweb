<?php
require_once '../includes/dbconnect.php'; // Connexion à la base de données

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

try {
    if (!isset($_GET['object_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de l\'objet manquant']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $objectId = $_GET['object_id'];

    // Vérifier si l'utilisateur est admin
    $adminCheck = $pdo->prepare("SELECT admin FROM users WHERE id = ?");
    $adminCheck->execute([$userId]);
    $isAdmin = $adminCheck->fetchColumn();

    // Vérifier si l'utilisateur a accès à cet objet
    if (!$isAdmin) {
        $stmt = $pdo->prepare("SELECT ID FROM ObjetConnecte WHERE ID = ?");
        $stmt->execute([$objectId]);
        $object = $stmt->fetch();

        if (!$object) {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
            exit;
        }
    }
    
    // Récupérer les données avec pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM Historique_Actions 
            WHERE id_objet_connecte = ? 
            ORDER BY date_heure DESC 
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$objectId, $limit, $offset]);
    $data = $stmt->fetchAll();

    // Compter le total des enregistrements
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Historique_Actions WHERE id_objet_connecte = ?");
    $stmt->execute([$objectId]);
    $total = $stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}
