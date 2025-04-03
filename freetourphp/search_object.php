<?php
header('Content-Type: application/json');
include('../includes/db_connect.php');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
$batterieMin = isset($_GET['batterieMin']) ? (int)$_GET['batterieMin'] : 0;
$disponibilite = isset($_GET['disponibilite']) ? trim($_GET['disponibilite']) : '';

try {
    $query = "SELECT Nom, Type, Etat, EtatBatterie, UtilisateurID FROM ObjetConnecte WHERE 1=1";
    $params = [];

    if (!empty($type)) {
        $query .= " AND Type = :type";
        $params[':type'] = $type;
    }
    if (!empty($etat)) {
        $query .= " AND Etat = :etat";
        $params[':etat'] = $etat;
    }
    if ($batterieMin > 0) {
        $query .= " AND EtatBatterie >= :batterieMin";
        $params[':batterieMin'] = $batterieMin;
    }
    if ($disponibilite !== '') {
        $query .= " AND UtilisateurID IS " . ($disponibilite == '1' ? 'NULL' : 'NOT NULL');
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    error_log("Erreur dans search_object.php : " . $e->getMessage());
    echo json_encode(['error' => 'Erreur interne du serveur.']);
}
?>
