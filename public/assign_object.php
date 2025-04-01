<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['is_confirmed'] != 1) {
    // Si l'utilisateur n'est pas confirmé, le rediriger vers la page de confirmation
    header("Location: confirm.php");
    exit();
}
// Vérifier si l'ID de l'objet et de l'utilisateur sont envoyés
if (isset($_POST['objectId']) && isset($_POST['userId'])) {
    $objectId = $_POST['objectId'];
    $userId = $_POST['userId'];

    // Préparer la requête pour mettre à jour l'ID utilisateur de l'objet
    $stmt = $conn->prepare("UPDATE ObjetConnecte SET UtilisateurID = :userId WHERE ID = :objectId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':objectId', $objectId, PDO::PARAM_INT);

    // Exécuter la requête
    if ($stmt->execute()) {
        // Mise à jour réussie, retourner une réponse positive
        echo 'Objet assigné avec succès';
        $stmt2 = $conn->prepare("INSERT INTO Historique_Actions (id_utilisateur, id_objet_connecte, type_action) VALUES (:userId, :objectId, 'Assignation')");
        $stmt2->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt2->bindParam(':objectId', $objectId, PDO::PARAM_INT);

        if ($stmt2->execute()) {
            // Réponse de succès si tout se passe bien
            echo 'Objet assigné avec succès et action enregistrée dans l\'historique';
        } else {
            // Si l'enregistrement de l'historique échoue
            echo 'Erreur lors de l\'enregistrement de l\'action dans l\'historique';
        }
    } 
} else {
    echo 'Paramètres manquants';
}
?>
