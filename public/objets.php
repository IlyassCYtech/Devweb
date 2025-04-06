<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    log_error("Accès refusé : utilisateur non connecté.");
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT id, username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, is_confirmed,admin, gestion FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../public/index.php");
    die("Utilisateur non trouvé.");
}
if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
    header("Location: index.php");
    exit();
}
// Récupérer le statut admin
$isAdmin = $user['admin'] ?? 0;

// Récupérer les objets utilisés par l'utilisateur
$usedStmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID = :id");
$usedStmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$usedStmt->execute();

// Récupérer les objets disponibles (non associés)
$availableStmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE UtilisateurID IS NULL");
$availableStmt->execute();

// Récupérer les types d'objets disponibles
$typeStmt = $conn->prepare("SELECT * FROM TypeObjet");
$typeStmt->execute();
$types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mes Objets | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="../assets/js/theme.js"></script>

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

        .glass-effect {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .section-header {
            position: relative;
            margin-bottom: 2rem;
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .object-card {
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: var(--card-bg);
        }
        
        .object-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Dark mode toggle button */
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

        .theme-toggle svg {
            width: 20px; /* Ensure consistent size */
            height: 20px;
        }

        .theme-toggle:hover {
            background-color: #e5e7eb;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: #4b5563;
        }

        [data-theme="dark"] .nav-link {
            color: #e5e7eb;
        }

        [data-theme="dark"] .nav-link:hover {
            color: #ffffff;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #9ca3af;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #d1d5db;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #e5e7eb;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #f3f4f6;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #f9fafb;
        }

        [data-theme="dark"] .bg-gray-50 {
            background-color: #1f2937;
        }

        [data-theme="dark"] .bg-white {
            background-color: var(--card-bg);
        }


        [data-theme="dark"] .theme-toggle {
            background-color: #374151;
            color: #fbbf24;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: #4b5563;
}
    </style>
</head>
<body class="min-h-screen">
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="profil.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Profil</a>
                    <a href="dashboard.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Accueil</a>
                    <a href="objets.php" class="nav-link text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">Admin</a>
                    <?php endif; ?>
                    <a href="recherche.php" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">🔍</a>
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
                    <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Gestion des Objets Connectés</h1>
            <a href="ajouter_objet.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white gradient-bg hover:opacity-90 transition-opacity duration-200">
                + Ajouter un objet
            </a>
        </div>

        <div class="flex justify-end mb-8">
    <?php if ($user['admin']) : ?>
        <!-- Bouton pour les admins -->
        <a href="../admin/creer_type_objet.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-opacity duration-200">
            + Ajouter un type d'objet
        </a>
    <?php else : ?>
        <!-- Bouton pour les non-admins -->
        <button onclick="demanderCreationTypeObjet()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 transition-opacity duration-200">
            Demander un type d'objet
        </button>
    <?php endif; ?>
</div>

<?php if ($user['gestion'] == 1 || $user['admin'] == 1): ?>
    <div class="flex justify-end mb-8">
        <a href="create_report.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-opacity duration-200">
            📊 Générer un rapport
        </a>
    </div>
<?php endif; ?>

<script>
function demanderCreationTypeObjet() {
    const typeObjet = prompt("Entrez le nom du type d'objet que vous souhaitez demander :");
    if (typeObjet) {
        fetch('demande_type_objet.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `type_objet=${encodeURIComponent(typeObjet)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Votre demande a été envoyée avec succès !");
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(error => {
            console.error("Erreur lors de l'envoi de la demande :", error);
            alert("Une erreur est survenue. Veuillez réessayer.");
        });
    }
}
</script>

        <!-- Section Objets Utilisés -->
        <div class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 section-header">Objets Utilisés</h2>
            <div id="usedObjects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                <?php while ($object = $usedStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                    <div class="bg-white p-6 rounded-lg shadow-md relative card-hover glass-effect object-card"
                         onclick="window.location.href='modifier_objet.php?id=<?= $object['ID'] ?>'">
                        <button class="absolute top-4 right-4 text-red-600 hover:text-red-800 transition-colors duration-200" 
                                onclick="event.stopPropagation(); returnObject(<?= $object['ID'] ?>, <?= $user['id'] ?>)">
                            <span class="text-2xl">🔄</span>
                        </button>
                        <h3 class="font-semibold text-xl text-gray-800 mb-3"><?= htmlspecialchars($object['Type']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($object['Description']) ?></p>
                        <p class="text-gray-600 mb-2">Nom: <?= htmlspecialchars($object['Nom']) ?></p>
                        <div class="space-y-2">
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Marque:</span> 
                                <?= htmlspecialchars($object['Marque']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Connectivité:</span>
                                <?= htmlspecialchars($object['Connectivite']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">État:</span>
                                <span class="px-2 py-1 rounded-full text-xs <?= $object['Etat'] === 'Actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars($object['Etat']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Section Objets Disponibles -->
        <div>
            
            <h2 class="text-2xl font-semibold text-gray-800 section-header">Objets Disponibles</h2>
            <div id="availableObjects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                <?php while ($object = $availableStmt->fetch(PDO::FETCH_ASSOC)) : ?>
                    <div class="bg-white p-6 rounded-lg shadow-md relative card-hover glass-effect object-card"
                         onclick="window.location.href='modifier_objet.php?id=<?= $object['ID'] ?>'">
                        <button class="absolute top-4 right-4 text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                onclick="event.stopPropagation(); assignObject(<?= $object['ID'] ?>, <?= $user['id'] ?>)">
                            <span class="text-2xl">➕</span>
                        </button>
                        <h3 class="font-semibold text-xl text-gray-800 mb-3"><?= htmlspecialchars($object['Type']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($object['Description']) ?></p>
                        <p class="text-gray-600 mb-2">Nom: <?= htmlspecialchars($object['Nom']) ?></p>
                        <div class="space-y-2">
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Marque:</span>
                                <?= htmlspecialchars($object['Marque']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">Connectivité:</span>
                                <?= htmlspecialchars($object['Connectivite']) ?>
                            </p>
                            <p class="text-gray-500 flex items-center">
                                <span class="font-medium mr-2">État:</span>
                                <span class="px-2 py-1 rounded-full text-xs <?= $object['Etat'] === 'Actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars($object['Etat']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/objet.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.style.display = 'block'; // Assurez-vous qu'il est visible
    }
    });

            document.addEventListener("DOMContentLoaded", function () {
                const themeToggle = document.getElementById('theme-toggle');
                const darkIcon = document.getElementById('theme-toggle-dark-icon');
                const lightIcon = document.getElementById('theme-toggle-light-icon');
                
                // Get saved theme from localStorage
                const savedTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', savedTheme);

                // Show correct icon on page load
                if (savedTheme === 'dark') {
                    darkIcon.classList.add('hidden');
                    lightIcon.classList.remove('hidden');
                } else {
                    lightIcon.classList.add('hidden');
                    darkIcon.classList.remove('hidden');
                }

                if (themeToggle) {
                    themeToggle.style.display = 'block';
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
        const dynamicContent = document.getElementById('dynamicContent');
        const dynamicContentBody = document.getElementById('dynamicContentBody');
        const showDynamicContent = document.getElementById('showDynamicContent');
        const closeDynamicContent = document.getElementById('closeDynamicContent');

        // Afficher la div
        if (showDynamicContent) {
            showDynamicContent.addEventListener('click', function () {
                dynamicContent.classList.remove('hidden');
                dynamicContentBody.innerHTML = `
                    <h2 class="text-xl font-bold mb-4">Créer un Type d'Objet</h2>
                    <form id="createTypeForm" class="space-y-4">
                        <div>
                            <label for="type_objet" class="block text-sm font-medium text-gray-700">Nom du Type d'Objet :</label>
                            <input type="text" id="type_objet" name="type_objet" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="type_image" class="block text-sm font-medium text-gray-700">Image du Type d'Objet :</label>
                            <input type="file" id="type_image" name="type_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                        </div>
                        <div class="flex justify-end space-x-4">
                            <button type="button" id="cancelCreateType" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Annuler
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                                Créer
                            </button>
                        </div>
                    </form>
                `;

                // Gérer la soumission du formulaire
                const createTypeForm = document.getElementById('createTypeForm');
                createTypeForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(createTypeForm);

                    fetch('../admin/creer_type_objet.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            dynamicContent.classList.add('hidden');
                        })
                        .catch(error => {
                            console.error('Erreur lors de la création du type d\'objet :', error);
                            alert('Une erreur est survenue. Veuillez réessayer.');
                        });
                });

                // Gérer l'annulation
                const cancelCreateType = document.getElementById('cancelCreateType');
                cancelCreateType.addEventListener('click', function () {
                    dynamicContent.classList.add('hidden');
                });
            });
        }

        // Masquer la div
        if (closeDynamicContent) {
            closeDynamicContent.addEventListener('click', function () {
                dynamicContent.classList.add('hidden');
            });
        }
    });

    </script>
<div id="dynamicContent" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl relative">t>-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl relative">
            <button id="closeDynamicContent" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">X</button>
            <div id="dynamicContentBody">
                <!-- Le contenu dynamique sera chargé ici -->
            </div>
        </div>
    </div>
</body>
</html>
