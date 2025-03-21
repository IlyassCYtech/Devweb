<?php
session_start();
include('../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Accès interdit"]);
    exit();
}

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    http_response_code(400);
    echo json_encode(["error" => "Requête invalide"]);
    exit();
}

$query = trim($_GET['q']);
$sql = "SELECT id, username, nom AS last_name, prenom AS first_name, photo_profil AS profile_pic 
        FROM users 
        WHERE username LIKE :query 
        OR prenom LIKE :query 
        OR nom LIKE :query
        LIMIT 10";


$stmt = $conn->prepare($sql);
$searchTerm = "%" . $query . "%";
$stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
?>
