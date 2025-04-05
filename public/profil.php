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
        
        // Récupération des données du formulaire
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $prenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
        $date_naissance = filter_input(INPUT_POST, 'date_naissance', FILTER_SANITIZE_STRING);
        $sexe = filter_input(INPUT_POST, 'sexe', FILTER_SANITIZE_STRING);
        $type_membre = filter_input(INPUT_POST, 'type_membre', FILTER_SANITIZE_STRING);

        // Traitement de l'upload de la photo de profil
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo_profil'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($photo['type'], $allowedTypes)) {
                $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
                $newFileName = $user_id . '.' . $extension;
                $uploadPath = '../uploads/' . $newFileName;
                if(file_exists($uploadPath)){
                    unlink($uploadPath);
                }
                if (move_uploaded_file($photo['tmp_name'], $uploadPath)) {
                    // Mise à jour de la photo dans la base de données
                    $stmt = $conn->prepare("UPDATE users SET photo_profil = :photo WHERE id = :id");
                    $stmt->execute([
                        ':photo' => $newFileName,
                        ':id' => $user_id
                    ]);
                }
            }
        }

        // Mise à jour des informations de l'utilisateur
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
        header("Location: profil.php");
        exit();
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
                                if ($action['objet_type'] == 'Trottinette') {
                                    $image_src = '../assets/images/trottinette.jpg';
                                } elseif ($action['objet_type'] == 'Vélo') {
                                    $image_src = '../assets/images/vélo.jpg';
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
        // Toggle display entre formulaire et affichage du profil
        document.getElementById('editProfileBtn').addEventListener('click', function() {
            document.getElementById('profileDisplay').classList.add('hidden');
            document.getElementById('profileForm').classList.remove('hidden');
        });

        document.getElementById('cancelEdit').addEventListener('click', function() {
            document.getElementById('profileForm').classList.add('hidden');
            document.getElementById('profileDisplay').classList.remove('hidden');
        });

        // Fonction pour définir le thème
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Mettre à jour les icônes
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            
            if (theme === 'dark') {
                darkIcon.classList.add('hidden');
                lightIcon.classList.remove('hidden');
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        }

        // Initialiser le thème
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);

        // Gestionnaire d'événements pour le bouton de basculement
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    </script>
</body>
</html>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileDisplay = document.getElementById('profileDisplay');
            const profileForm = document.getElementById('profileForm');
            const editProfileBtn = document.getElementById('editProfileBtn');
            const cancelEditBtn = document.getElementById('cancelEdit');

            editProfileBtn.addEventListener('click', function() {
                profileDisplay.classList.add('hidden');
                profileForm.classList.remove('hidden');
                editProfileBtn.classList.add('hidden');
            });

            cancelEditBtn.addEventListener('click', function() {
                profileDisplay.classList.remove('hidden');
                profileForm.classList.add('hidden');
                editProfileBtn.classList.remove('hidden');
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const niveauDiv = document.getElementById("niveauDiv");
            const roleDiv = document.getElementById("roleDiv");
            const xpDiv = document.getElementById("xpDiv");

            const userIsAdmin = <?php echo json_encode($user['admin']); ?>;

            function createPopup(content) {
                let existingPopup = document.getElementById("popupOverlay");
                if (existingPopup) existingPopup.remove();

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

                document.getElementById("closePopup").addEventListener("click", function () {
                    overlay.remove();
                });
            }

            niveauDiv.addEventListener("click", function () {
                createPopup(`
                    🌟 <strong>Explication des niveaux</strong> 🌟<br><br>
                    - Débutant → Intermédiaire → Avancé → <span class="text-red-600 font-bold">Expert</span> 🔥<br>
                    - <strong>Au niveau Expert</strong>, vous pouvez demander à devenir <span class="text-green-600">Administrateur</span> et aider à gérer le site avec les créateurs !
                `);
            });

            roleDiv.addEventListener("click", function () {
                if (userIsAdmin) {
                    window.location.href = "../admin/admin.php";
                } else {
                    createPopup(`
                        🔒 <strong>Vos droits actuels</strong> 🔒<br><br>
                        - Vous pouvez consulter les objets connectés.<br>
                        - Vous pouvez gagner de l'XP et monter de niveau.<br>
                        - <span class="text-blue-600">Au niveau Expert</span>, vous pourrez devenir Administrateur !
                    `);
                }
            });

            xpDiv.addEventListener("click", function () {
                window.location.href = "objets.php";
            });
        });
    </script>
</body>
</html>