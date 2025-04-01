<?php
session_start();
include('../includes/db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilisateur non authentifié.']);
    exit();
}
if ($_SESSION['is_confirmed'] != 1) {
    // Si l'utilisateur n'est pas confirmé, le rediriger vers la page de confirmation
    header("Location: confirm.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Récupération des filtres
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$etat = isset($_GET['etat']) ? trim($_GET['etat']) : '';
$batterieMin = isset($_GET['batterieMin']) ? (int) $_GET['batterieMin'] : 0;
$disponibilite = isset($_GET['disponibilite']) ? trim($_GET['disponibilite']) : '';

// Construction de la requête SQL dynamique
$query = "SELECT * FROM ObjetConnecte WHERE 1=1";
$params = [];

// Ajout des filtres si spécifiés
if ($type !== '') {
    $query .= " AND Type = :type";
    $params[':type'] = $type;
}
if ($etat !== '') {
    $query .= " AND Etat = :etat";
    $params[':etat'] = $etat;
}
if ($batterieMin > 0) {
    $query .= " AND EtatBatterie >= :batterieMin";
    $params[':batterieMin'] = $batterieMin;
}
if ($disponibilite === "1") { // "1" pour disponible
    $query .= " AND UtilisateurID IS NULL";
} elseif ($disponibilite === "0") { // "0" pour indisponible
    $query .= " AND UtilisateurID IS NOT NULL";
}


try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur SQL : ' . $e->getMessage()]);
}
?>
