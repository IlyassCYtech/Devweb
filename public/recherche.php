<?php
session_start();
include('../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, id, admin, is_confirmed,photo_profil FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);


$typeStmt = $conn->prepare("SELECT * FROM TypeObjet");
$typeStmt->execute();
$types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: ../public/index.php");
    exit();
}
if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
    header("Location: index.php");
    exit();
}


$profilePicPath = "../uploads/{$userId}." . pathinfo($user['photo_profil'], PATHINFO_EXTENSION);

// Vérifier si le fichier existe
if (!file_exists($profilePicPath)) {
    $profilePicPath = "../uploads/default.jpg"; // Image par défaut
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recherche | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <script src="../assets/js/objet.js"></script>
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

        .theme-toggle:hover {
            background-color: #e5e7eb;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: #4b5563;
        }

        /* Dark mode text & bg adjustments */
        [data-theme="dark"] .text-gray-500 {
            color: #9ca3af;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #d1d5db;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #e5e7eb;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #f9fafb;
        }

        [data-theme="dark"] .bg-white, 
        [data-theme="dark"] .bg-gray-50 {
            background-color: var(--card-bg);
        }

        [data-theme="dark"] .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
        }

        [data-theme="dark"] .border-gray-300 {
            border-color: #4b5563;
        }


        /* Styles spécifiques pour les éléments de recherche en mode sombre */
        [data-theme="dark"] input,
        [data-theme="dark"] select {
            background-color: #1f2937;
            border-color: #4b5563;
            color: #f9fafb;
        }

        [data-theme="dark"] input:focus,
        [data-theme="dark"] select:focus {
            border-color: #3b82f6;
            ring-color: #3b82f6;
            background-color: #374151;
        }

        [data-theme="dark"] input::placeholder {
            color: #9ca3af;
        }

        [data-theme="dark"] label {
            color: #e5e7eb;
        }

        [data-theme="dark"] .glass-effect {
            background-color: rgba(31, 41, 55, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #374151;
        }

        [data-theme="dark"] #results {
            background-color: #1f2937;
            border-color: #374151;
        }

        [data-theme="dark"] #results .text-gray-500 {
            color: #9ca3af;
        }

        /* Style pour les cartes de résultats en mode sombre */
        [data-theme="dark"] .result-card {
            background-color: #374151;
            border-color: #4b5563;
        }

        /* Style pour le hover des éléments en mode sombre */
        [data-theme="dark"] input:hover,
        [data-theme="dark"] select:hover {
            border-color: #6b7280;
        }

        /* Style pour les options du select en mode sombre */
        [data-theme="dark"] option {
            background-color: #1f2937;
            color: #f9fafb;
        }

        /* Style pour les messages d'état en mode sombre */
        [data-theme="dark"] .search-status {
            background-color: #374151;
            color: #e5e7eb;
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
                    <a href="profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="objets.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if (isset($user) && $user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Recherche</h1>
        <div class="glass-effect rounded-lg shadow-lg p-6">
            <div class="mb-4">
                <label for="searchType" class="block text-sm font-medium text-gray-700">Rechercher par :</label>
                <select id="searchType" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="objet">Objet</option>
                    <option value="utilisateur">Utilisateur</option>
                </select>
            </div>

            <div id="searchBar" class="mb-4 hidden">
                <label for="searchInput" class="block text-sm font-medium text-gray-700">Rechercher un utilisateur :</label>
                <input type="text" id="searchInput" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Nom d'utilisateur...">
            </div>

            <div id="filters" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label for="typeObjet" class="block text-sm font-medium text-gray-700">Type d'objet :</label>
                    <select id="typeObjet" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tous</option>
                        <?php if(isset($types)): ?>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= htmlspecialchars($type['Nom']) ?>">
                                    <?= htmlspecialchars($type['Nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label for="etatObjet" class="block text-sm font-medium text-gray-700">État :</label>
                    <select id="etatObjet" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tous</option>
                        <option value="Actif">Actif</option>
                        <option value="Inactif">Inactif</option>
                    </select>
                </div>
                <div>
                    <label for="batterieMin" class="block text-sm font-medium text-gray-700">Batterie min. (%) :</label>
                    <input type="number" id="batterieMin" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" min="0" max="100" placeholder="Ex: 20">
                </div>
                <div>
                    <label for="disponibiliteObjet" class="block text-sm font-medium text-gray-700">Disponibilité :</label>
                    <select id="disponibiliteObjet" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Toutes</option>
                        <option value="1">Disponible</option>
                        <option value="0">Indisponible</option>
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <button id="searchButton" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                    Rechercher
                </button>
            </div>
        </div>

        <div id="results" class="mt-6 glass-effect rounded-lg shadow-lg p-6">
            <p class="text-gray-500">Les résultats apparaîtront ici...</p>
        </div>
    </main>

    <script>
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




    <script>
       document.addEventListener("DOMContentLoaded", function () {
    const searchType = document.getElementById("searchType");
    const searchBar = document.getElementById("searchBar");
    const filters = document.getElementById("filters");
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.getElementById("searchButton");
    const resultsContainer = document.getElementById("results");

    // Affichage conditionnel des filtres
    searchType.addEventListener("change", function () {
        if (searchType.value === "utilisateur") {
            searchBar.classList.remove("hidden");
            filters.classList.add("hidden");
        } else {
            searchBar.classList.add("hidden");
            filters.classList.remove("hidden");
        }
    });

    // Gestion du bouton de recherche
    searchButton.addEventListener("click", function () {
        if (searchType.value === "utilisateur") {
            searchUser();
        } else {
            searchObject();
        }
    });

    function searchUser() {
        const query = searchInput.value.trim();

        // Afficher un message de chargement
        resultsContainer.innerHTML = "<p class='text-blue-600'>🔍 Recherche en cours...</p>";

        // Effectuer une requête à `search_user.php` avec le contenu de la barre de recherche
        fetch(`search_user.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                // Effacer les résultats précédents
                resultsContainer.innerHTML = "";

                // Si aucun utilisateur n'est trouvé
                if (data.length === 0) {
                    resultsContainer.innerHTML = "<p class='text-gray-500'>Aucun utilisateur trouvé.</p>";
                    return;
                }

                // Afficher les utilisateurs trouvés
                data.forEach(user => {
                    const userCard = document.createElement("div");
                    userCard.className = "flex items-center bg-white p-4 shadow rounded-lg mb-4";

                    // Utilisation du chemin de la photo de profil de chaque utilisateur
                    const profilePic = user.profile_pic_path;

                    userCard.innerHTML = `
                        <img src="${profilePic}" alt="Photo de profil" class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <p class="text-lg font-semibold">${user.username}</p>
                            <p class="text-gray-600">${user.first_name} ${user.last_name}</p>
                        </div>
                    `;
                    resultsContainer.appendChild(userCard);
                });
            })
            .catch(error => {
                // Afficher un message d'erreur en cas de problème
                resultsContainer.innerHTML = "<p class='text-red-500'>Erreur lors de la recherche.</p>";
            });
    }



    function searchObject() {
    const typeObjet = document.getElementById("typeObjet").value;
    const etatObjet = document.getElementById("etatObjet").value;
    const batterieMin = document.getElementById("batterieMin").value;
    const disponibiliteObjet = document.getElementById("disponibiliteObjet").value;

    resultsContainer.innerHTML = "<p class='text-blue-600'>🔍 Recherche en cours...</p>";

    fetch(`search_object.php?type=${encodeURIComponent(typeObjet)}&etat=${encodeURIComponent(etatObjet)}&batterieMin=${encodeURIComponent(batterieMin)}&disponibilite=${encodeURIComponent(disponibiliteObjet)}`)
        .then(response => response.json())
        .then(data => {
            resultsContainer.innerHTML = "";
            if (data.length === 0) {
                resultsContainer.innerHTML = "<p class='text-gray-500'>Aucun objet trouvé.</p>";
                return;
            }

            // Création de la grille
            const gridContainer = document.createElement("div");
            gridContainer.className = "grid grid-cols-2 gap-4"; // 2 objets par ligne

            data.forEach(objet => {
                const objetCard = document.createElement("div");
                objetCard.className = "relative flex items-center bg-white p-4 shadow rounded-lg";

                // Générer l'URL de l'image en fonction du type d'objet
                const imageUrl = `../assets/images/${objet.Type.toLowerCase()}.jpg`;

                // Calcul du temps écoulé depuis la dernière interaction
                const lastInteraction = new Date(objet.DerniereInteraction);
                const now = new Date();
                const timeDiff = Math.floor((now - lastInteraction) / 1000);
                let timeText = "Il y a plus d'un mois";

                if (timeDiff < 3600) {
                    timeText = `Il y a ${Math.floor(timeDiff / 60)} minutes`;
                } else if (timeDiff < 86400) {
                    timeText = `Il y a ${Math.floor(timeDiff / 3600)} heures`;
                } else if (timeDiff < 2592000) {
                    timeText = `Il y a ${Math.floor(timeDiff / 86400)} jours`;
                } else if (timeDiff < 31536000) {
                    timeText = `Il y a ${Math.floor(timeDiff / 2592000)} mois`;
                }

                // Vérifier la disponibilité de l'objet
                const isAvailable = objet.UtilisateurID === null;
                const idSession = <?= $user_id ?>; // Remplace par l'ID réel de l'utilisateur connecté

                // Ajout des boutons d'action avec emojis en haut à droite
                let actionButton = "";
                if (isAvailable) {
                    actionButton = `<button data-object-id="${objet.ID}" class="absolute top-2 right-2 text-green-500 text-2xl" onclick="addObject(${objet.ID})">➕</button>`;
                } else if (objet.UtilisateurID === idSession) {
                    actionButton = `<button data-object-id="${objet.ID}" class="absolute top-2 right-2 text-red-500 text-2xl" onclick="removeObject(${objet.ID})">🚫</button>`;
                }

                objetCard.innerHTML = `
                    ${actionButton}
                    <img src="${imageUrl}" alt="Photo de ${objet.Nom}" class="w-24 h-24 object-cover rounded-lg mr-4">
                    <div>
                        <h2 class="text-lg font-semibold">${objet.Nom}</h2>
                        <p class="text-gray-600">Type : ${objet.Type}</p>
                        <p class="text-gray-600">État : ${objet.Etat}</p>
                        <p class="text-gray-600">Batterie : ${objet.EtatBatterie}%</p>
                        <p class="text-gray-600">Disponibilité : ${isAvailable ? "✅ Disponible" : "❌ Indisponible"}</p>
                        <p class="text-sm text-gray-500">🕒 Dernière interaction : ${timeText}</p>
                    </div>
                `;
                gridContainer.appendChild(objetCard);
            });

            resultsContainer.appendChild(gridContainer);
        })
        .catch(error => {
            resultsContainer.innerHTML = "<p class='text-red-500'>❌ Erreur lors de la recherche.</p>";
        });
}

// Fonctions pour ajouter ou retirer un objet


});

function addObject(objetID) {
    const userId = <?= $user_id ?>;  // Récupération de l'ID utilisateur

    // Sélectionne le bouton correspondant
    const button = document.querySelector(`button[data-object-id="${objetID}"]`);
    if (button) {
        button.innerHTML = "🚫";  // Change l'icône du bouton
        button.setAttribute("onclick", `removeObject(${objetID})`); // Change l'événement onclick
        assignObject(objetID, userId);  // Appel de la fonction pour assigner l'objet
    }
}

function removeObject(objetID) {
    const userId = <?= $user_id ?>;  // Récupération de l'ID utilisateur

    // Sélectionne le bouton correspondant
    const button = document.querySelector(`button[data-object-id="${objetID}"]`);
    if (button) {
        button.innerHTML = "➕";  // Change l'icône du bouton
        button.setAttribute("onclick", `addObject(${objetID})`); // Change l'événement onclick
        returnObject(objetID, userId);  // Appel de la fonction pour retirer l'objet
    }
}



    </script>
</body>
</html>
