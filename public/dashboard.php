<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données (assure-toi que db_connect.php utilise PDO)
include('../includes/db_connect.php');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        die("Utilisateur non connecté.");
    }

    // Récupérer l'ID de l'utilisateur depuis la session
    $user_id = $_SESSION['user_id'];

    // Préparer la requête pour récupérer les informations de l'utilisateur
    $stmt = $conn->prepare("SELECT username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, admin FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Vérifier si un utilisateur est trouvé
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("Utilisateur non trouvé.");
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}


$stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email)");
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();
?>

<div class="container">
    <h1>Bienvenue sur le Dashboard, <?php echo htmlspecialchars($user['username']); ?>!</h1>
    <table class="table">
        <tr>
            <th>Nom</th>
            <td><?php echo htmlspecialchars($user['nom']); ?></td>
        </tr>
        <tr>
            <th>Prénom</th>
            <td><?php echo htmlspecialchars($user['prenom']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
        </tr>
        <tr>
            <th>Date de naissance</th>
            <td><?php echo htmlspecialchars($user['date_naissance']); ?></td>
        </tr>
        <tr>
            <th>Âge</th>
            <td>
                <?php 
                    // Calcul de l'âge à partir de la date de naissance
                    $date_naissance = new DateTime($user['date_naissance']);
                    $today = new DateTime();
                    echo $today->diff($date_naissance)->y;
                ?>
            </td>
        </tr>
        <tr>
            <th>Sexe</th>
            <td><?php echo htmlspecialchars($user['sexe']); ?></td>
        </tr>
        <tr>
            <th>Niveau</th>
            <td><?php echo htmlspecialchars($user['niveau']); ?></td>
        </tr>
        <tr>
            <th>Points d'expérience</th>
            <td><?php echo htmlspecialchars($user['points_experience']); ?></td>
        </tr>
        
        <tr>
            <th>Rôle</th>
            <td><?php echo $user['admin'] ? 'Administrateur' : 'Utilisateur'; ?></td>
        </tr>
    </table>
</div>
