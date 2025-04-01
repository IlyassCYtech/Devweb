<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../public/index.php"); // Corrige la redirection

  

    }

    // Récupérer l'ID de l'utilisateur depuis la session
    $user_id = $_SESSION['user_id'];
    // Récupérer les informations de l'utilisateur
    $stmt = $conn->prepare("SELECT username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, admin FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    $stmt_actions = $conn->prepare("SELECT ha.id, ha.type_action, ha.date_heure, oc.Nom AS objet_nom, oc.Type AS objet_type
        FROM Historique_Actions ha
        LEFT JOIN ObjetConnecte oc ON ha.id_objet_connecte = oc.ID
        WHERE ha.id_utilisateur = :id_utilisateur
        ORDER BY ha.date_heure DESC");
    $stmt_actions->bindParam(':id_utilisateur', $user_id, PDO::PARAM_INT);
    $stmt_actions->execute();
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);


    if (!$user) {
        header("Location: ../public/index.php");
        die("Utilisateur non trouvé.");
    }
    $photo_profil = !empty($user['photo_profil']) ? $user['photo_profil'] : 'default.jpg';
    // Vérifier si l'utilisateur existe
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .progress-bar {
            background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 9999px;
            height: 0.5rem;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
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

                    <a href="profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="objets.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                        <?php endif; ?>
                    <a href="recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>
                <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
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
                    <span class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars(disabled value="<?php echo $user['niveau']; ?>"); ?></span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-100">
                        <div class="progress-bar" style="width: <?php echo min((disabled value="<?php echo $user['points_experience']; ?>" % 1000) / 10, 100); ?>%"></div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span><?php echo disabled value="<?php echo $user['points_experience']; ?>" % 1000; ?> XP</span>
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
                    // Liste des niveaux
                    $niveaux = ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'];

                    // Trouver l'index du niveau actuel
                    $niveauIndex = array_search(disabled value="<?php echo $user['niveau']; ?>", $niveaux);

                    // Calcul des XP totaux accumulés
                    $totalXP = ($niveauIndex * 1000) + disabled value="<?php echo $user['points_experience']; ?>";

                    // Affichage formaté avec espace
                    echo number_format($totalXP, 0, ',', ' ');
                    ?>
                </p>
                <h4 class="text-base font-semibold text-blue-600 mb-2">🚀 Gagnez encore plus d'XP !!!</h4>

            </div>

        </div>

        <!-- User Information -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Informations personnelles</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                    <!-- Photo de profil -->
                    <div class="flex items-center space-x-4">
                        <!-- Assurez-vous que le chemin d'accès à l'image est relatif à la racine du site web -->
                    
                    <img src="../uploads/<?php echo htmlspecialchars($photo_profil); ?>" alt="Photo de profil" class="w-20 h-20 rounded-full object-cover">                    </div>

                                        <!-- Informations personnelles -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Nom complet</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Email</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars(disabled value="<?php echo $user['email']; ?>"); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Date de naissance</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['date_naissance']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Âge</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php 
                                $date_naissance = new DateTime($user['date_naissance']);
                                $today = new DateTime();
                                echo $today->diff($date_naissance)->y . ' ans';
                            ?>
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Sexe</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['sexe']); ?></p>
                    </div>
                </div>
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
                            $image_src = '../assets/images/trottinette.jpg'; // Remplace par le chemin de l'image de la trottinette
                        } elseif ($action['objet_type'] == 'Vélo') {
                            $image_src = '../assets/images/vélo.jpg'; // Remplace par le chemin de l'image du vélo
                        } else {
                            $image_src = '../assets/images/default.jpg'; // Image par défaut si aucun type ne correspond
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
    function enableEditing() {
        var formElements = document.querySelectorAll('input');
        formElements.forEach(function(element) {
            if (!element.disabled) {
                element.disabled = false;
            }
        });
        document.getElementById('save-btn').style.display = 'block';
        document.getElementById('edit-btn').style.display = 'none';
    }
</script>

<button id="edit-btn" onclick="enableEditing()" class="bg-blue-500 text-white px-4 py-2 rounded">Edit Profile</button>
<button id="save-btn" style="display:none;" class="bg-green-500 text-white px-4 py-2 rounded">Save Changes</button>

</body>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Récupérer les éléments
    const niveauDiv = document.getElementById("niveauDiv");
    const roleDiv = document.getElementById("roleDiv");
    const xpDiv = document.getElementById("xpDiv");

    const userIsAdmin = <?php echo json_encode($user['admin']); ?>; // Vérifier si admin

    // Créer le div popup
    function createPopup(content) {
        // Vérifier si un popup existe déjà
        let existingPopup = document.getElementById("popupOverlay");
        if (existingPopup) existingPopup.remove();

        // Créer l'overlay
        let overlay = document.createElement("div");
        overlay.id = "popupOverlay";
        overlay.className = "fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50";

        // Créer la boîte du message
        let popup = document.createElement("div");
        popup.className = "bg-white rounded-lg shadow-xl p-6 max-w-md text-center relative";
        popup.innerHTML = `
            <button id="closePopup" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">X</button>
            <div class="text-lg font-bold text-blue-600 mb-3">💡 Information</div>
            <p class="text-gray-700">${content}</p>
        `;

        // Ajouter popup dans overlay
        overlay.appendChild(popup);
        document.body.appendChild(overlay);

        // Fermer popup au clic sur le bouton
        document.getElementById("closePopup").addEventListener("click", function () {
            overlay.remove();
        });
    }

    // Gestion du clic sur le niveau
    niveauDiv.addEventListener("click", function () {
        createPopup(`
            🌟 <strong>Explication des niveaux</strong> 🌟<br><br>
            - Débutant → Intermédiaire → Avancé → <span class="text-red-600 font-bold">Expert</span> 🔥<br>
            - <strong>Au niveau Expert</strong>, vous pouvez demander à devenir <span class="text-green-600">Administrateur</span> et aider à gérer le site avec les créateurs !
        `);
    });

    // Gestion du clic sur le rôle
    roleDiv.addEventListener("click", function () {
        if (userIsAdmin) {
            window.location.href = "../admin/admin.php"; // Redirection vers admin
        } else {
            createPopup(`
                🔒 <strong>Vos droits actuels</strong> 🔒<br><br>
                - Vous pouvez consulter les objets connectés.<br>
                - Vous pouvez gagner de l'XP et monter de niveau.<br>
                - <span class="text-blue-600">Au niveau Expert</span>, vous pourrez devenir Administrateur !
            `);
        }
    });

    // Gestion du clic sur XP
    xpDiv.addEventListener("click", function () {
        window.location.href = "objets.php"; // Redirection vers objets.php
    });
});
</script>


</html>