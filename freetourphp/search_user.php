<?php
include('../includes/db_connect.php');

// Vérifier si une requête de recherche est fournie
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    // Si la barre de recherche est vide, récupérer tous les utilisateurs sauf l'utilisateur connecté
    $sql = "SELECT id, username, nom AS last_name, prenom AS first_name, photo_profil AS profile_pic 
            FROM users 
            ORDER BY username ASC"; // Trier par ordre alphabétique

    $stmt = $conn->prepare($sql);
    
} else {
    // Si une requête de recherche est fournie, rechercher les utilisateurs correspondants
    $query = trim($_GET['q']);
    $sql = "SELECT id, username, nom AS last_name, prenom AS first_name, photo_profil AS profile_pic 
            FROM users 
            WHERE (username LIKE :query 
            OR prenom LIKE :query 
            OR nom LIKE :query)
            ORDER BY username ASC"; // Trier par ordre alphabétique

    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $query . "%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':currentUserId', $currentUserId, PDO::PARAM_INT);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];

foreach ($users as $user) {
    $userId = $user['id'];
    
    // Construire le chemin de la photo de profil
    if (!empty($user['profile_pic'])) {
        // Si l'utilisateur a une photo, on récupère le chemin
        $profilePicPath = "../uploads/{$userId}." . pathinfo($user['profile_pic'], PATHINFO_EXTENSION);
        
        // Si le fichier n'existe pas, on utilise une image par défaut
        if (!file_exists($profilePicPath)) {
            $profilePicPath = "../uploads/default.jpg";
        }
    } else {
        // Si aucune photo n'est définie, utiliser l'image par défaut
        $profilePicPath = "../uploads/default.jpg";
    }

    // Ajouter chaque utilisateur avec le chemin de sa photo dans les résultats
    $results[] = [
        'id' => $userId,
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'profile_pic_path' => $profilePicPath // Ajoute le chemin de la photo de profil
    ];
}

header('Content-Type: application/json');
echo json_encode($results);
?>
