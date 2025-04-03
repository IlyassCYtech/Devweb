<?php

// Inclure la connexion à la base de données
include('../includes/db_connect.php');

try {
    // Récupérer les statistiques principales
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ObjetConnecte");
    $total_objets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as disponible FROM ObjetConnecte WHERE UtilisateurID IS NULL");
    $objets_disponibles = $stmt->fetch(PDO::FETCH_ASSOC)['disponible'];

    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Récupérer les types d'objets
    $typeStmt = $conn->prepare("SELECT * FROM TypeObjet");
    $typeStmt->execute();
    $types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger text-center">
        <?php 
        echo htmlspecialchars($_SESSION['error_message']);
        unset($_SESSION['error_message']); // Supprimer le message après l'affichage
        ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 4rem;
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

    </style>
</head>
<body class="bg-gray-50">

<!-- Navbar -->
<nav class="glass-nav fixed w-full z-50 top-0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex">
                <a href="Travail.php">
                    <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                </a>
            </div>
            <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                <a href="#footer" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍 Recherche</a>
            </div>
            <div class="hidden sm:flex sm:items-center sm:justify-end space-x-4">
                <button id="showRegister" class="btn btn-success py-2 px-4 text-white bg-green-500 hover:bg-green-600 rounded-lg">S'inscrire</button>
                <button id="showLogin" class="btn btn-primary py-2 px-4 text-white bg-blue-500 hover:bg-blue-600 rounded-lg">Se connecter</button>
            </div>
        </div>
    </div>
</nav>

<!-- Section principale -->
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background-image: url('../assets/images/background.png'); background-size: cover; background-position: center;">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Bienvenue !</h1>
        <p class="text-gray-600">Découvrez votre ville à travers ses objets connectés!</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <!-- Carte: Nombre d'utilisateurs -->
            <div class="stat-card p-6" style="background-color: rgba(255, 165, 0, 0.1);">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nombre d'Utilisateurs</h3>
                <p class="text-3xl font-bold text-orange-600"><?php echo number_format($total_users, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Utilisateurs enregistrés</p>
            </div>
            <!-- Carte: Total des objets -->
            <div class="stat-card p-6" style="background-color: rgba(59, 130, 246, 0.1);">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Total des Objets</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo number_format($total_objets, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Objets dans la base de données</p>
            </div>
            <!-- Carte: Objets disponibles -->
            <div class="stat-card p-6" style="background-color: rgba(34, 197, 94, 0.1);">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Objets Disponibles</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo number_format($objets_disponibles, 0, ',', ' '); ?></p>
                <p class="text-sm text-gray-500 mt-2">Objets actuellement disponibles</p>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de connexion et d'inscription -->
<div id="card" class="card p-5 shadow-lg position-absolute top-50 start-50 translate-middle" style="max-width: 800px; width: 100%; border-radius: 10px; z-index: 1; display: none; margin-top: 20px;">
    <!-- Bouton de fermeture -->
    <button id="closeCard" class="btn btn-close position-absolute top-0 end-0 m-4" style="font-size: 1.5rem; z-index: 2;"></button>
    
    <!-- Formulaire de Connexion -->
    <div id="loginForm" class="form-section" style="display: none;">
        <h2 class="text-center mb-4 text-primary">Connexion</h2>
        <form id="login-form" action="process_login.php" method="POST">
            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control border-primary" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control border-primary" required>
            </div>
            <div id="error-message" class="alert alert-danger" style="display: none;" role="alert"></div>
            <button type="submit" class="btn btn-primary w-100 py-2">Se connecter</button>
        </form>
    </div>

    <!-- Formulaire d'Inscription -->
    <div id="registerForm" class="form-section" style="display: none;">
        <h2 class="text-center mb-3 text-success">Inscription</h2>
        <form id="register-form" action="register.php" method="POST">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="username" class="form-label">Pseudonyme</label>
                    <input type="text" name="username" id="username" class="form-control border-success" required>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password-register" class="form-control border-success" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control border-success" required>
            </div>
            <div class="mb-4">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" name="prenom" id="prenom" class="form-control border-success" required>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input type="date" name="date_naissance" id="date_naissance" class="form-control border-success" required>
                </div>
                <div class="col-md-6">
                    <label for="age" class="form-label">Âge</label>
                    <input type="number" name="age" id="age" class="form-control border-success" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="sexe" class="form-label">Sexe/Genre</label>
                <select name="sexe" id="sexe" class="form-select border-success" required>
                    <option value="Homme">Homme</option>
                    <option value="Femme">Femme</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="type_membre" class="form-label">Type de membre</label>
                <select name="type_membre" id="type_membre" class="form-select border-success" required>
                    <option value="élève">Élève</option>
                    <option value="parent">Parent</option>
                    <option value="développeur">Développeur</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email-register" class="form-control border-success" required>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="niveau" class="form-label">Niveau</label>
                    <select name="niveau" id="niveau" class="form-select border-success" required>
                        <option value="Débutant">Débutant</option>
                        <option value="Intermédiaire">Intermédiaire</option>
                        <option value="Avancé">Avancé</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="points_experience" class="form-label">Points d'expérience</label>
                    <input type="number" name="points_experience" id="points_experience" class="form-control border-success" required>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="admin" id="admin" value="1">
                <label for="admin" class="form-check-label">Administrateur</label>
            </div>
            <div id="error-message-register" class="alert alert-danger" style="display: none;" role="alert"></div>
            <button type="submit" class="btn btn-success w-100 py-2">S'inscrire</button>
        </form>
    </div>
</div>

<!-- Footer -->
<div id="footer" class="container mt-4">
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
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script src="../assets/js/login.js"></script>
<script src="../assets/js/register.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const showLoginButton = document.getElementById('showLogin');
        const showRegisterButton = document.getElementById('showRegister');
        const closeCardButton = document.getElementById('closeCard');
        const card = document.getElementById('card');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Afficher le formulaire de connexion
        showLoginButton.addEventListener('click', () => {
            card.style.display = 'block';
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        });

        // Afficher le formulaire d'inscription
        showRegisterButton.addEventListener('click', () => {
            card.style.display = 'block';
            registerForm.style.display = 'block';
            loginForm.style.display = 'none';
        });

        // Fermer la carte
        closeCardButton.addEventListener('click', () => {
            card.style.display = 'none';
            loginForm.style.display = 'none';
            registerForm.style.display = 'none';
        });
    });

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
        fetch(`../freetourphp/search_user.php?q=${encodeURIComponent(query)}`)
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

            fetch(`../freetourphp/search_object.php?type=${encodeURIComponent(typeObjet)}&etat=${encodeURIComponent(etatObjet)}&batterieMin=${encodeURIComponent(batterieMin)}&disponibilite=${encodeURIComponent(disponibiliteObjet)}`)
                .then(response => response.json())
                .then(data => {
                    resultsContainer.innerHTML = "";
                    if (data.length === 0) {
                        resultsContainer.innerHTML = "<p class='text-gray-500'>Aucun objet trouvé.</p>";
                        return;
                    }

                    const gridContainer = document.createElement("div");
                    gridContainer.className = "grid grid-cols-2 gap-4";

                    data.forEach(objet => {
                        const objetCard = document.createElement("div");
                        objetCard.className = "relative flex items-center bg-white p-4 shadow rounded-lg";

                        objetCard.innerHTML = `
                            <div>
                                <h2 class="text-lg font-semibold">${objet.Nom}</h2>
                                <p class="text-gray-600">Type : ${objet.Type}</p>
                                <p class="text-gray-600">État : ${objet.Etat}</p>
                                <p class="text-gray-600">Disponibilité : ${objet.UtilisateurID === null ? "✅ Disponible" : "❌ Indisponible"}</p>
                            </div>
                        `;
                        gridContainer.appendChild(objetCard);
                    });

                    resultsContainer.appendChild(gridContainer);
                })
                .catch(error => {
                    resultsContainer.innerHTML = "<p class='text-red-500'>Erreur lors de la recherche.</p>";
                });
        }
    });
</script>
</body>
</html>