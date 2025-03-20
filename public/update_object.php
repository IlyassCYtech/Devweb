<?php
require_once '../includes/dbconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Vérifier si les données requises sont présentes
    if (!isset($data['id']) || !isset($data['etat'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Données manquantes']);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Vérifier si l'utilisateur a le droit de modifier cet objet
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

    // Vérifier si l'état est valide
    $validEtats = ['Actif', 'Inactif', 'Occupé', 'Libéré'];
    if (!in_array($data['etat'], $validEtats)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valeur d\'état invalide']);
        exit;
    }

    // Mettre à jour l'état de l'objet
    $sql = "UPDATE ObjetConnecte SET Etat = ? WHERE ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['etat'], $data['id']]);

    // Journaliser l'action
    logAction($userId, $data['id'], "Modification état: " . $data['etat']);

    echo json_encode(['success' => true, 'message' => 'État mis à jour avec succès']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}
