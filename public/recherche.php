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
if ($_SESSION['is_confirmed'] != 1) {
    // Si l'utilisateur n'est pas confirmé, le rediriger vers la page de confirmation
    header("Location: confirm.php");
    exit();
}


$profilePicPath = "../uploads/{$userId}." . pathinfo($user['photo_profil'], PATHINFO_EXTENSION);

// Vérifier si le fichier existe
if (!file_exists($profilePicPath)) {
    $profilePicPath = "../uploads/default.jpg"; // Image par défaut
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Recherche | CY Tech</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
        <script src="../assets/js/objet.js"></script>
    
</head>
<body class="min-h-screen bg-gray-50">
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Recherche</h1>
        <div class="bg-white p-6 rounded-lg shadow-lg">
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
                    <?php foreach ($types as $type): ?>
                                <option value="<?= htmlspecialchars($type['Nom']) ?>">
                                    <?= htmlspecialchars($type['Nom']) ?>
                                </option>
                    <?php endforeach; ?>
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

        <div id="results" class="mt-6">
            <p class="text-gray-500">Les résultats apparaîtront ici...</p>
        </div>
    </main>

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
