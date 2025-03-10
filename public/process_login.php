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
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = :email");
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

        // Réponse JSON pour indiquer une connexion réussie
        echo json_encode(['success' => true, 'message' => 'Connexion réussie']);
        exit();
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
