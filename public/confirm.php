<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
$pdo = require_once('../includes/db_connect.php');  // Assurez-vous que ce chemin est correct

// Vérifier si un code de confirmation a été passé dans l'URL
if (isset($_GET['code'])) {
    $confirmationCode = $_GET['code'];

    // Debug: Vérifier le code de confirmation reçu
    echo "Code de confirmation reçu : " . $confirmationCode . "<br>";

    try {
        // Vérifier si le code de confirmation existe dans la base de données
        $stmt = $pdo->prepare('SELECT * FROM users WHERE confirmation_code = :confirmation_code');
        $stmt->execute([':confirmation_code' => $confirmationCode]);
        $user = $stmt->fetch();

        // Si l'utilisateur existe et n'est pas encore confirmé
        if ($user) {
            // Afficher la valeur actuelle de is_confirmed pour le débogage
            echo "Valeur actuelle de is_confirmed: " . $user['is_confirmed'] . "<br>";

            // Si l'utilisateur n'est pas encore confirmé
            if ($user['is_confirmed'] == 0) {
                // Le code de confirmation est valide, mettre à jour l'utilisateur pour confirmer
                $updateStmt = $pdo->prepare('UPDATE users SET is_confirmed = 1 WHERE id = :id');

                // Exécuter la mise à jour
                if ($updateStmt->execute([':id' => $user['id']])) {
                    echo "Mise à jour réussie.<br>";

                    // Vérifier la mise à jour en récupérant à nouveau l'utilisateur
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
                    $stmt->execute([':id' => $user['id']]);
                    $updatedUser = $stmt->fetch();
                    $user=$updatedUser;
                    echo "Nouvelle valeur de is_confirmed après mise à jour : " . $updatedUser['is_confirmed'] . "<br>";

                    // Vider la session existante pour éviter les conflits
                    session_unset();
                    session_destroy();
                    session_start();

                    // L'utilisateur est maintenant confirmé, enregistrer l'ID de l'utilisateur dans la session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['is_confirmed']=$user['is_confirmed'];
                    // Rediriger vers la page du tableau de bord
                    header('Location: ../public/index.php');
                    exit();
                } else {
                    $errorInfo = $updateStmt->errorInfo();
                    echo "Erreur lors de la mise à jour : " . $errorInfo[2];  // Afficher l'erreur SQL
                }
            } else {
                // Si l'utilisateur est déjà confirmé, rediriger vers le tableau de bord
                echo "Votre compte est déjà confirmé.";
            }
        } else {
            // Si le code est invalide ou a déjà été utilisé
            echo "Le code de confirmation est invalide ou a déjà été utilisé.";
        }
    } catch (PDOException $e) {
        // Gérer les erreurs de la base de données
        echo "Erreur lors de la confirmation de votre inscription : " . $e->getMessage();
    }
} else {
    echo "Aucun code de confirmation trouvé.";
}
?>
