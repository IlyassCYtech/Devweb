<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../public/index.php");
        exit();
    }
    if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
        header("Location: index.php");
        exit();
    }
    // Traitement du formulaire de modification
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $user_id = $_SESSION['user_id'];
        
        // Définir les messages d'erreur
        $errors = [];

        // Validation du nom d'utilisateur (alphanumériques et quelques caractères spéciaux)
        $username = trim($_POST['username']);
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis.";
        } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]{3,30}$/', $username)) {
            $errors[] = "Le nom d'utilisateur ne doit contenir que des lettres, chiffres, tirets, points et underscores (3-30 caractères).";
        }

        // Validation du nom (alphabétique uniquement)
        $nom = trim($_POST['nom']);
        if (empty($nom)) {
            $errors[] = "Le nom est requis.";
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]{2,50}$/', $nom)) {
            $errors[] = "Le nom ne doit contenir que des lettres, espaces et tirets (2-50 caractères).";
        }

        // Validation du prénom (alphabétique uniquement)
        $prenom = trim($_POST['prenom']);
        if (empty($prenom)) {
            $errors[] = "Le prénom est requis.";
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]{2,50}$/', $prenom)) {
            $errors[] = "Le prénom ne doit contenir que des lettres, espaces et tirets (2-50 caractères).";
        }

        // Validation de la date de naissance (vérification de l'âge >= 13 ans)
        $date_naissance = $_POST['date_naissance'];
        if (empty($date_naissance)) {
            $errors[] = "La date de naissance est requise.";
        } else {
            $birth_date = new DateTime($date_naissance);
            $today = new DateTime();
            $age = $today->diff($birth_date)->y;
            
            if ($age < 13) {
                $errors[] = "Vous devez avoir au moins 13 ans pour utiliser ce site.";
            } elseif ($age > 120) {
                $errors[] = "Veuillez entrer une date de naissance valide.";
            }
        }

        // Validation du sexe (limité aux options du formulaire)
        $sexe = trim($_POST['sexe']);
        if (!in_array($sexe, ['Homme', 'Femme', 'Autre'])) {
            $errors[] = "Le sexe sélectionné n'est pas valide.";
        }

        // Validation du type de membre (limité aux options du formulaire)
        $type_membre = trim($_POST['type_membre']);
        if (!in_array($type_membre, ['élève', 'parent', 'développeur', 'autre'])) {
            $errors[] = "Le type de membre sélectionné n'est pas valide.";
        }

        // Si aucune erreur, procéder à la mise à jour
        if (empty($errors)) {
            // Traitement de l'upload de la photo de profil
            if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
                $photo = $_FILES['photo_profil'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                // Vérification du type MIME
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $fileType = $finfo->file($photo['tmp_name']);
                
                if (in_array($fileType, $allowedTypes)) {
                    // Vérifier la taille du fichier (max 5MB)
                    if ($photo['size'] <= 5 * 1024 * 1024) {
                        $extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
                        // Générer un nom de fichier sécurisé
                        $newFileName = $user_id . '_' . uniqid() . '.' . $extension;
                        $uploadPath = '../uploads/' . $newFileName;
                        
                        // Supprimer l'ancienne photo si elle existe
                        $stmt = $conn->prepare("SELECT photo_profil FROM users WHERE id = :id");
                        $stmt->execute([':id' => $user_id]);
                        $old_photo = $stmt->fetchColumn();
                        
                        if ($old_photo && $old_photo !== 'default.jpg' && file_exists('../uploads/' . $old_photo)) {
                            unlink('../uploads/' . $old_photo);
                        }
                        
                        if (move_uploaded_file($photo['tmp_name'], $uploadPath)) {
                            // Mise à jour de la photo dans la base de données
                            $stmt = $conn->prepare("UPDATE users SET photo_profil = :photo WHERE id = :id");
                            $stmt->execute([
                                ':photo' => $newFileName,
                                ':id' => $user_id
                            ]);
                        } else {
                            $errors[] = "Erreur lors de l'upload de l'image.";
                        }
                    } else {
                        $errors[] = "La taille de l'image ne doit pas dépasser 5 Mo.";
                    }
                } else {
                    $errors[] = "Format d'image non autorisé. Utilisez JPG, PNG ou GIF.";
                }
            }
            
            // Si toujours aucune erreur, mise à jour des informations utilisateur
            if (empty($errors)) {
                // Mise à jour des informations de l'utilisateur avec prepared statement
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = :username,
                        nom = :nom,
                        prenom = :prenom,
                        date_naissance = :date_naissance,
                        sexe = :sexe,
                        type_membre = :type_membre
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':username' => $username,
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':date_naissance' => $date_naissance,
                    ':sexe' => $sexe,
                    ':type_membre' => $type_membre,
                    ':id' => $user_id
                ]);

                // Redirection pour rafraîchir les données
                $_SESSION['success_message'] = "Profil mis à jour avec succès!";
                header("Location: profil.php");
                exit();
            }
        }
        
        // Si des erreurs existent, les stocker en session pour les afficher
        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            $_SESSION['form_data'] = $_POST; // Conserver les données du formulaire
            header("Location: profil.php");
            exit();
        }
    }

    // Récupérer l'ID de l'utilisateur depuis la session
    $user_id = $_SESSION['user_id'];
    
    // Récupérer les informations de l'utilisateur
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: ../public/index.php");
        exit();
    }

    $photo_profil = !empty($user['photo_profil']) ? $user['photo_profil'] : 'default.jpg';

    // Récupération de l'historique des actions
    $stmt_actions = $conn->prepare("SELECT ha.id, ha.type_action, ha.date_heure, oc.Nom AS objet_nom, oc.Type AS objet_type
        FROM Historique_Actions ha
        LEFT JOIN ObjetConnecte oc ON ha.id_objet_connecte = oc.ID
        WHERE ha.id_utilisateur = :id_utilisateur
        ORDER BY ha.date_heure DESC");
    $stmt_actions->bindParam(':id_utilisateur', $user_id, PDO::PARAM_INT);
    $stmt_actions->execute();
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter l'action dans l'historique
    $stmt = $conn->prepare("INSERT INTO Historique_Actions (id_utilisateur, type_action) VALUES (:id_utilisateur, :type_action)");
    $stmt->execute([
        ':id_utilisateur' => $user_id,
        ':type_action' => 'Modification du profil'
    ]);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Mode clair */
        :root {
            --bg-primary: #f9fafb;
            --text-primary: #111827;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
        }

        /* Mode sombre */
        [data-theme="dark"] {
            --bg-primary: #111827;
            --text-primary: #f9fafb;
            --card-bg: #1f2937;
            --card-border: #374151;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .glass-nav {
            background: rgba(17, 24, 39, 0.8);
            border-bottom: 1px solid rgba(55, 65, 81, 0.5);
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, background-color 0.3s;
            border: 1px solid var(--card-border);
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .progress-bar {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 9999px;
            height: 0.5rem;
        }

        /* Theme toggle button */
        .theme-toggle {
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        [data-theme="dark"] .theme-toggle {
            background-color: #374151;
            color: #fbbf24;
        }

        .theme-toggle:hover {
            background-color: #e5e7eb;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: #4b5563;
        }

        /* Dark mode text adjustments */
        [data-theme="dark"] .text-gray-500 { color: #9ca3af; }
        [data-theme="dark"] .text-gray-600 { color: #d1d5db; }
        [data-theme="dark"] .text-gray-700 { color: #e5e7eb; }
        [data-theme="dark"] .text-gray-900 { color: #f9fafb; }
        [data-theme="dark"] .bg-gray-50 { background-color: #374151; }
        [data-theme="dark"] .bg-white { background-color: var(--card-bg); }
        [data-theme="dark"] .border-gray-200 { border-color: #374151; }
        [data-theme="dark"] .border-gray-300 { border-color: #4b5563; }
        
        /* Form input style adjustments for dark mode */
        [data-theme="dark"] input, [data-theme="dark"] select {
            background-color: #1f2937;
            border-color: #4b5563;
            color: #f9fafb;
        }
        
        [data-theme="dark"] input:focus, [data-theme="dark"] select:focus {
            border-color: #3b82f6;
            ring-color: #3b82f6;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="profil.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="dashboard.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="objets.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="recherche.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>

                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="theme-toggle">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                    </button>
                    <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <!-- Welcome Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Bienvenue, <?php echo htmlspecialchars($user['username']); ?> !
            </h1>
            <p class="text-gray-600">Voici votre tableau de bord personnel</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <!-- Level Card -->
            <div id="niveauDiv" class="stat-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Niveau</h3>
                    <span class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars($user['niveau']); ?></span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-100">
                        <div class="progress-bar" style="width: <?php echo min(($user['points_experience'] % 1000) / 10, 100); ?>%"></div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span><?php echo $user['points_experience'] % 1000; ?> XP</span>
                        <span>1000 XP</span>
                    </div>
                </div>
            </div>

            <!-- Role Card -->
            <div id="roleDiv" class="stat-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Rôle</h3>
                <div class="flex items-center">
                    <?php if ($user['admin']) : ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            Administrateur
                        </span>
                    <?php elseif ($user['gestion']) : ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            Gestionnaire
                        </span>
                    <?php else : ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Utilisateur
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Experience Card -->
            <div id="xpDiv" class="stat-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Points d'expérience accumulés</h3>
                <p class="text-3xl font-bold text-blue-600">
                    <?php
                    $niveaux = ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'];
                    $niveauIndex = array_search($user['niveau'], $niveaux);
                    $totalXP = ($niveauIndex * 1000) + $user['points_experience'];
                    echo number_format($totalXP, 0, ',', ' ');
                    ?>
                </p>
                <h4 class="text-base font-semibold text-blue-600 mb-2">🚀 Gagnez encore plus d'XP !!!</h4>
            </div>
        </div>

        <!-- Section du profil -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900">Informations personnelles</h2>
                <button id="editProfileBtn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Modifier le profil
                </button>
            </div>

            <!-- Affichage normal du profil -->
            <div id="profileDisplay" class="divide-y divide-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                    <div class="flex items-center space-x-4">
                        <img src="../uploads/<?php echo htmlspecialchars($photo_profil); ?>" alt="Photo de profil" class="w-20 h-20 rounded-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Nom complet</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Email</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Date de naissance</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['date_naissance']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Sexe</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['sexe']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Type de membre</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['type_membre']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Formulaire de modification (caché par défaut) -->
            <div id="profileForm" class="hidden p-6">
                <form action="profil.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
                            <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($user['nom']); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom</label>
                            <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="date_naissance" class="block text-sm font-medium text-gray-700">Date de naissance</label>
                            <input type="date" name="date_naissance" id="date_naissance" value="<?php echo htmlspecialchars($user['date_naissance']); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="sexe" class="block text-sm font-medium text-gray-700">Sexe</label>
                            <select name="sexe" id="sexe" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="Homme" <?php echo $user['sexe'] === 'Homme' ? 'selected' : ''; ?>>Homme</option>
                                <option value="Femme" <?php echo $user['sexe'] === 'Femme' ? 'selected' : ''; ?>>Femme</option>
                                <option value="Autre" <?php echo $user['sexe'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>

                        <div>
                            <label for="type_membre" class="block text-sm font-medium text-gray-700">Type de membre</label>
                            <select name="type_membre" id="type_membre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="élève" <?php echo $user['type_membre'] === 'élève' ? 'selected' : ''; ?>>Élève</option>
                                <option value="parent" <?php echo $user['type_membre'] === 'parent' ? 'selected' : ''; ?>>Parent</option>
                                <option value="développeur" <?php echo $user['type_membre'] === 'développeur' ? 'selected' : ''; ?>>Développeur</option>
                                <option value="autre" <?php echo $user['type_membre'] === 'autre' ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>

                        <div>
                            <label for="photo_profil" class="block text-sm font-medium text-gray-700">Photo de profil</label>
                            <input type="file" name="photo_profil" id="photo_profil" accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="button" id="cancelEdit" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Annuler
                        </button>
                        <button type="submit" name="update_profile" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Historic Objects Section -->
        <div class="stat-card p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Historique des Actions</h2>
            <?php if (empty($actions)) : ?>
                <p class="text-gray-500">Aucune action effectuée récemment.</p>
            <?php else : ?>
                <div class="space-y-6">
                    <?php foreach ($actions as $action) : ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 ease-in-out">
                            <!-- Photo de l'objet à gauche -->
                        <?php
                            if ($action['objet_type'] != null) {
                                $basename = strtolower($action['objet_type']);
                                $found = false;
                                $extensions = ['jpg', 'png', 'gif'];
                                
                                foreach ($extensions as $ext) {
                                    $test_path = "../assets/images/{$basename}.{$ext}";
                                    if (file_exists($test_path)) {
                                        $image_src = $test_path;
                                        $found = true;
                                        break;
                                    }
                                }
                                
                                if (!$found) {
                                    $image_src = '../assets/images/default.jpg';
                                }
                            } else {
                                $image_src = '../assets/images/default.jpg';
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="Image de l'objet" class="w-16 h-16 rounded-full object-cover mr-4">
                            
                            <!-- Type d'action et objet sur la même ligne -->
                            <div class="flex flex-col md:flex-row md:space-x-4 w-full">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($action['type_action']); ?></h3>
                                </div>
                                <?php if ($action['objet_nom']) : ?>
                                    <div class="flex-1 text-sm text-gray-500">
                                        <p>Objet : <?php echo htmlspecialchars($action['objet_nom']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Date de l'action à droite -->
                            <div class="text-sm text-gray-500">
                                <p><?php echo date('d/m/Y H:i', strtotime($action['date_heure'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
   document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM pour le formulaire de profil
    const profileDisplay = document.getElementById('profileDisplay');
    const profileForm = document.getElementById('profileForm');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEdit');

    // Vérification que les éléments existent avant d'ajouter des événements
    if (editProfileBtn && profileDisplay && profileForm) {
        editProfileBtn.addEventListener('click', function() {
            profileDisplay.classList.add('hidden');
            profileForm.classList.remove('hidden');
            editProfileBtn.classList.add('hidden');
        });
    }

    if (cancelEditBtn && profileDisplay && profileForm && editProfileBtn) {
        cancelEditBtn.addEventListener('click', function() {
            profileDisplay.classList.remove('hidden');
            profileForm.classList.add('hidden');
            editProfileBtn.classList.remove('hidden');
        });
    }

    // Gestion du thème
    function setTheme(theme) {
        try {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Mettre à jour les icônes
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            
            if (darkIcon && lightIcon) {
                if (theme === 'dark') {
                    darkIcon.classList.add('hidden');
                    lightIcon.classList.remove('hidden');
                } else {
                    lightIcon.classList.add('hidden');
                    darkIcon.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error("Erreur lors du changement de thème:", error);
        }
    }

    // Initialiser le thème
    try {
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);
    } catch (error) {
        console.error("Erreur lors de l'initialisation du thème:", error);
        // Définir un thème par défaut en cas d'erreur
        setTheme('light');
    }

    // Gestionnaire d'événements pour le bouton de basculement
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            try {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                setTheme(currentTheme === 'dark' ? 'light' : 'dark');
            } catch (error) {
                console.error("Erreur lors du basculement de thème:", error);
            }
        });
    }

    // Gestion des popups et des événements de clic sur les cartes
    const niveauDiv = document.getElementById("niveauDiv");
    const roleDiv = document.getElementById("roleDiv");
    const xpDiv = document.getElementById("xpDiv");

    // Récupérer l'état admin de l'utilisateur (défini dans le PHP)
    let userIsAdmin;
    try {
        // Cette valeur devrait être définie par PHP dans le HTML
        userIsAdmin = typeof userIsAdmin !== 'undefined' ? userIsAdmin : false;
    } catch (error) {
        console.error("Erreur lors de la récupération du statut admin:", error);
        userIsAdmin = false;
    }

    // Récupérer l'état gestionnaire de l'utilisateur (défini dans le PHP)
    let userIsGestion;
    try {
        // Cette valeur devrait être définie par PHP dans le HTML
        userIsGestion = typeof userIsGestion !== 'undefined' ? userIsGestion : false;
    } catch (error) {
        console.error("Erreur lors de la récupération du statut gestionnaire:", error);
        userIsGestion = false;
    }

    function createPopup(content) {
        try {
            // Supprimer tout popup existant
            let existingPopup = document.getElementById("popupOverlay");
            if (existingPopup) existingPopup.remove();

            // Créer le nouveau popup
            let overlay = document.createElement("div");
            overlay.id = "popupOverlay";
            overlay.className = "fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50";

            let popup = document.createElement("div");
            popup.className = "bg-white rounded-lg shadow-xl p-6 max-w-md text-center relative";
            popup.innerHTML = `
                <button id="closePopup" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">X</button>
                <div class="text-lg font-bold text-blue-600 mb-3">💡 Information</div>
                <p class="text-gray-700">${content}</p>
            `;

            overlay.appendChild(popup);
            document.body.appendChild(overlay);

            // Gestionnaire pour fermer le popup
            const closeButton = document.getElementById("closePopup");
            if (closeButton) {
                closeButton.addEventListener("click", function() {
                    overlay.remove();
                });
            }
            
            // Fermer le popup en cliquant en dehors
            overlay.addEventListener("click", function(e) {
                if (e.target === overlay) {
                    overlay.remove();
                }
            });
        } catch (error) {
            console.error("Erreur lors de la création du popup:", error);
        }
    }

    // Ajouter les événements aux divs seulement s'ils existent
    if (niveauDiv) {
        niveauDiv.addEventListener("click", function() {
            createPopup(`
                🌟 <strong>Explication des niveaux</strong> 🌟<br><br>
                - Débutant → Intermédiaire → Avancé → <span class="text-red-600 font-bold">Expert</span> 🔥<br>
                - <strong>Au niveau Expert</strong>, vous pouvez demander à devenir <span class="text-green-600">Administrateur</span> et aider à gérer le site avec les créateurs !
            `);
        });
    }

    if (roleDiv) {
        roleDiv.addEventListener("click", function() {
            if (userIsAdmin) {
                try {
                    window.location.href = "../admin/admin.php";
                } catch (error) {
                    console.error("Erreur lors de la redirection vers la page admin:", error);
                    alert("Impossible d'accéder à la page d'administration. Veuillez réessayer.");
                }
            } else if (userIsGestion) {
                createPopup(`
                    🔑 <strong>Vos droits actuels</strong> 🔑<br><br>
                    - Vous pouvez consulter les objets connectés.<br>
                    - Vous pouvez gérer les objets connectés.<br>
                    - Vous pouvez gagner de l'XP et monter de niveau.<br>
                    - <span class="text-blue-600">Au niveau Expert</span>, vous pourrez devenir Administrateur !
                `);
            } else {
                createPopup(`
                    🔒 <strong>Vos droits actuels</strong> 🔒<br><br>
                    - Vous pouvez consulter les objets connectés.<br>
                    - Vous pouvez gagner de l'XP et monter de niveau.<br>
                    - <span class="text-blue-600">Au niveau Expert</span>, vous pourrez devenir Administrateur ! <br>
                    - <span class="text-yellow-600">Au niveau Avancé</span>, vous pourrez devenir Gestionnaire !
                `);
            }
        });
    }

    if (xpDiv) {
        xpDiv.addEventListener("click", function() {
            try {
                window.location.href = "objets.php";
            } catch (error) {
                console.error("Erreur lors de la redirection vers la page objets:", error);
                alert("Impossible d'accéder à la page des objets. Veuillez réessayer.");
            }
        });
    }

    // Gestion des erreurs de formulaire (s'ils existent dans la session)
    if (document.querySelector('.alert-error')) {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-error');
            alerts.forEach(alert => {
                alert.classList.add('opacity-0');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    }

    // Validation du formulaire avant soumission
    const profileFormElement = document.querySelector('form[action="profil.php"]');
    if (profileFormElement) {
        profileFormElement.addEventListener('submit', function(e) {
            let hasError = false;
            const username = document.getElementById('username');
            const nom = document.getElementById('nom');
            const prenom = document.getElementById('prenom');
            const date_naissance = document.getElementById('date_naissance');
            
            // Réinitialiser les messages d'erreur
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            
            // Validation côté client
            if (username && (!username.value || username.value.trim() === '')) {
                showFieldError(username, "Le nom d'utilisateur est requis");
                hasError = true;
            }
            
            if (nom && (!nom.value || nom.value.trim() === '')) {
                showFieldError(nom, "Le nom est requis");
                hasError = true;
            }
            
            if (prenom && (!prenom.value || prenom.value.trim() === '')) {
                showFieldError(prenom, "Le prénom est requis");
                hasError = true;
            }
            
            if (date_naissance && !date_naissance.value) {
                showFieldError(date_naissance, "La date de naissance est requise");
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
            }
        });
    }
    
    function showFieldError(field, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-red-500 text-sm mt-1';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
        field.classList.add('border-red-500');
    }
});
    </script>
</body>
</html>