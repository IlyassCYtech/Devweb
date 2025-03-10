<?php
// Démarrer la session
session_start();

// Inclure la connexion à la DB
$pdo = require_once('../includes/db_connect.php');

// Fonction de log
function log_error($message) {
    $logFile = '../logs/inscription.log'; // Chemin du fichier de log
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Vérifier si le formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Récupérer les données du formulaire avec nettoyage
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $date_naissance = trim($_POST['date_naissance']);
        $age = (int)$_POST['age'];
        
        // Vérifier et forcer la validité de "sexe"
        $sexe = ucfirst(strtolower(trim($_POST['sexe'])));
        $valid_sexes = ['Homme', 'Femme', 'Autre'];

        if (!in_array($sexe, $valid_sexes)) {
            log_error("Sexe invalide : $sexe");
            echo json_encode(['success' => false, 'message' => 'Valeur sexe invalide. Définition sur "Autre"']);
            exit();
        }

        // Validation de l'email avec expression régulière
        $email = trim(strtolower($_POST['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            log_error("Email invalide : $email");
            echo json_encode(['success' => false, 'message' => 'Email invalide.']);
            exit();
        }

        $type_membre = trim($_POST['type_membre']);
        $niveau = trim($_POST['niveau']);
        $points_experience = (int)$_POST['points_experience'];
        $admin = isset($_POST['admin']) ? (int)$_POST['admin'] : 0;

        // Vérification de la longueur et la complexité du mot de passe
        if (strlen($password) < 8) {
            log_error("Mot de passe trop court.");
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.']);
            exit();
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            log_error("Mot de passe trop simple.");
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins une majuscule et un chiffre.']);
            exit();
        }

        // Vérification du pseudo unique
        $usernameQuery = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $usernameQuery->execute([':username' => $username]);
        if ($usernameQuery->fetchColumn() > 0) {
            log_error("Pseudonyme déjà utilisé : $username");
            echo json_encode(['success' => false, 'message' => 'Le pseudonyme est déjà utilisé.']);
            exit();
        }

        // Vérification de l'email unique
        $emailQuery = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)');
        $emailQuery->execute([':email' => $email]);

        $emailCount = $emailQuery->fetchColumn();

        if ($emailCount > 0) {
            log_error("Email déjà utilisé : $email");
            echo json_encode(['success' => false, 'message' => 'L\'email existe déjà dans la base de données.']);
            exit();
        }

        // Hachage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            log_error("Échec du hachage du mot de passe.");
            echo json_encode(['success' => false, 'message' => 'Échec du hachage du mot de passe.']);
            exit();
        }

        // Préparer et exécuter la requête avec PDO
        $stmt = $pdo->prepare('INSERT INTO users (username, password, nom, prenom, date_naissance, age, sexe, type_membre, email, niveau, points_experience, admin) 
        VALUES (:username, :password, :nom, :prenom, :date_naissance, :age, :sexe, :type_membre, :email, :niveau, :points_experience, :admin)');

        $stmt->execute([
            ':username'         => $username,
            ':password'         => $hashedPassword,  // Mot de passe haché
            ':nom'              => $nom,
            ':prenom'           => $prenom,
            ':date_naissance'   => $date_naissance,
            ':age'              => $age,
            ':sexe'             => $sexe,
            ':type_membre'      => $type_membre,
            ':email'            => $email,
            ':niveau'           => $niveau,
            ':points_experience'=> $points_experience,
            ':admin'            => $admin
        ]);

        // Récupérer l'ID du dernier utilisateur inséré
        $user_id = $pdo->lastInsertId();

        // Enregistrer l'ID de l'utilisateur dans la session
        $_SESSION['user_id'] = $user_id;

        // Retourner une réponse JSON avec succès
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès']);
        exit();
    } else {
        log_error("Méthode de requête non valide.");
        echo json_encode(['success' => false, 'message' => 'Méthode de requête non valide.']);
        exit();
    }
} catch (PDOException $e) {
    // Si une erreur PDO survient
    log_error("Erreur interne : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur interne.']);
    exit();
}
