<?php
require_once '../includes/dbconnect.php'; // Connexion à la base de données

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];

    // Pagination (valeurs par défaut)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
    $offset = ($page - 1) * $limit;

    // Si l'utilisateur est admin, il voit tous les objets
    if (isAdmin()) {
        $sql = "SELECT OC.*, HA.type_action, HA.date_heure 
                FROM ObjetConnecte OC
                LEFT JOIN Historique_Actions HA ON OC.ID = HA.id_objet_connecte
                ORDER BY OC.DateAjout DESC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
    } else {
        // Sinon, il ne voit que ses propres objets
        $sql = "SELECT OC.*, HA.type_action, HA.date_heure 
                FROM ObjetConnecte OC
                LEFT JOIN Historique_Actions HA ON OC.ID = HA.id_objet_connecte
                WHERE OC.id_utilisateur = ?
                ORDER BY OC.DateAjout DESC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
    }

    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter le nombre total d'objets
    if (isAdmin()) {
        $countSql = "SELECT COUNT(*) as total FROM ObjetConnecte";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute();
    } else {
        $countSql = "SELECT COUNT(*) as total FROM ObjetConnecte WHERE id_utilisateur = ?";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([$userId]);
    }
    $total = $countStmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'data' => $objects,
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
