<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

try {
    // Vérifier si le formulaire est soumis via AJAX
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Récupérer les données du formulaire
        $email = trim($_POST['email']);
        $passwordSaisi = trim($_POST['password']);

        // Vérifier que les champs ne sont pas vides
        if (empty($email) || empty($passwordSaisi)) {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs.']);
            exit();
        }

        // Préparer la requête pour récupérer l'utilisateur par email
        $stmt = $conn->prepare("SELECT id, username, password ,is_confirmed,is_confirmed_by_ad FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        // Vérifier si un utilisateur est trouvé
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects.']);
            exit();
        }

        // Vérifier le mot de passe
        if (!password_verify($passwordSaisi, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects.']);
            exit();
        }

        // Authentification réussie, on stocke les infos dans la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_confirmed'] = $user['is_confirmed'];
        $_SESSION['is_confirmed_by_ad'] = $user['is_confirmed_by_ad'];
        if($user['is_confirmed'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Veuillez confirmer votre compte.']);
            exit();
        }
        else if($user['is_confirmed_by_ad'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Votre compte est en attente de validation par un administrateur.']);
            exit();
        }
        else {
            // Vérifier si une connexion récente existe déjà
            $recentLoginStmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM UserHistory 
                WHERE user_id = :user_id 
                  AND action_type = 'Connexion' 
                  AND action_date > (NOW() - INTERVAL 5 SECOND)
            ");
            $recentLoginStmt->execute([':user_id' => $user['id']]);
            $recentLoginCount = $recentLoginStmt->fetchColumn();

            if ($recentLoginCount > 0) {
                // Ne pas enregistrer une nouvelle connexion si une récente existe
                echo json_encode(['success' => true, 'message' => 'Connexion réussie']);
                exit();
            }

            // Enregistrer l'action dans l'historique
            $historyStmt = $conn->prepare("
                INSERT INTO UserHistory (user_id, action_type) 
                VALUES (:user_id, 'Connexion')
            ");
            $historyStmt->execute([':user_id' => $user['id']]);

            // Réponse JSON pour indiquer une connexion réussie
            echo json_encode(['success' => true, 'message' => 'Connexion réussie']);
            exit();
        }
    } else {
        // Si on accède directement sans soumettre le formulaire
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects.']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur PDO : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur interne. Veuillez réessayer plus tard.']);
    exit();
}
