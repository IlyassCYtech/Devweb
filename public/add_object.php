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
    
    // Validation des données requises
    $requiredFields = ['nom', 'type', 'description', 'marque', 'etat', 'connectivite', 'energie_utilisee', 'localisationGPS'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => 'Champ manquant: ' . $field]);
            exit;
        }
    }
    
    $userId = $_SESSION['user_id'];
    
    // Insertion du nouvel objet
    $sql = "INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, LocalisationGPS) 
            VALUES (:nom, :type, :description, :marque, :etat, :connectivite, :energie_utilisee, :localisationGPS)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom' => htmlspecialchars($data['nom']),
        ':type' => htmlspecialchars($data['type']),
        ':description' => htmlspecialchars($data['description']),
        ':marque' => htmlspecialchars($data['marque']),
        ':etat' => htmlspecialchars($data['etat']),
        ':connectivite' => htmlspecialchars($data['connectivite']),
        ':energie_utilisee' => htmlspecialchars($data['energie_utilisee']),
        ':localisationGPS' => htmlspecialchars($data['localisationGPS'])
    ]);

    $objectId = $pdo->lastInsertId();
    
    // Journaliser l'action dans Historique_Actions
    $logSql = "INSERT INTO Historique_Actions (id_utilisateur, id_objet_connecte, type_action) 
               VALUES (:user_id, :objet_id, 'Ajout d\'un nouvel objet')";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':user_id' => $userId,
        ':objet_id' => $objectId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Objet ajouté avec succès',
        'id' => $objectId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}
