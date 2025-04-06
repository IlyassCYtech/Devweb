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

// Inclure PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

try {
    // Vérifier si le formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Vider la session existante pour éviter les conflits
        session_unset();
        session_destroy();
        session_start();

        // Récupérer les données du formulaire avec nettoyage
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $date_naissance = trim($_POST['date_naissance']);

        // Vérification de la date de naissance
        $currentDate = new DateTime();
        $birthDate = DateTime::createFromFormat('Y-m-d', $date_naissance);
        if (!$birthDate || $birthDate > $currentDate) {
            log_error("Date de naissance invalide : $date_naissance");
            echo json_encode(['success' => false, 'message' => 'Date de naissance invalide.']);
            exit();
        }

        // Calcul de l'âge
        $age = $currentDate->diff($birthDate)->y;

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
        $photo = 'default.jpg';

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

        // Générer un code de confirmation aléatoire
        $confirmationCode = bin2hex(random_bytes(16));

        // Préparer et exécuter la requête avec PDO
        $stmt = $pdo->prepare('INSERT INTO users (username, password, nom, prenom, date_naissance, age, sexe, type_membre, email, photo_profil, confirmation_code, is_confirmed, is_confirmed_by_ad) 
        VALUES (:username, :password, :nom, :prenom, :date_naissance, :age, :sexe, :type_membre, :email, :photo_profil, :confirmation_code, 0, 0)');
        
        $stmt->execute([
            ':username'         => $username,
            ':password'         => $hashedPassword,
            ':nom'              => $nom,
            ':prenom'           => $prenom,
            ':date_naissance'   => $date_naissance,
            ':age'              => $age,
            ':sexe'             => $sexe,
            ':type_membre'      => $type_membre,
            ':email'            => $email,
            ':photo_profil'     => $photo,
            ':confirmation_code'=> $confirmationCode
        ]);

        // Récupérer l'ID du dernier utilisateur inséré
        $user_id = $pdo->lastInsertId();

        // Enregistrer l'ID de l'utilisateur dans la session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;

        // Enregistrer l'action dans l'historique
        $historyStmt = $pdo->prepare("
            INSERT INTO UserHistory (user_id, action_type) 
            VALUES (:user_id, 'Inscription')
        ");
        $historyStmt->execute([':user_id' => $user_id]);

        // Envoi de l'email de confirmation
        $mail = new PHPMailer(true);

        // Paramètres SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.mailtrap.io';  // Remplacer par votre SMTP
        $mail->SMTPAuth = true;
        $mail->Username = '04abe8c7d2cd06'; // Remplacer par votre email
        $mail->Password = 'cd48fdee9c6933';  // Remplacer par votre mot de passe
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Expéditeur et destinataire
        $mail->setFrom('votre_email@mailtrap.io', 'Nom de votre site');
        $mail->addAddress($email);

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = 'Code de confirmation';
        $confirmationLink = "http://localhost:3000/public/confirm.php?code=" . $confirmationCode;
        $mail->Body = "Voici votre code de confirmation : <a href='$confirmationLink'>Cliquez ici pour confirmer votre inscription</a>";

        // Envoyer l'email
        if (!$mail->send()) {
            log_error("Erreur lors de l'envoi de l'email.");
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi de l\'email.']);
            exit();
        }

        // Retourner une réponse JSON avec succès
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès. Veuillez vérifier votre email pour confirmer votre inscription.']);
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
} catch (Exception $e) {
    log_error("Erreur dans PHPMailer : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur dans PHPMailer.']);
    exit();
}
