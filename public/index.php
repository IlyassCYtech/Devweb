<?php
// Page de connexion
include('../includes/header.php');
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container-fluid min-vh-100 position-relative" style="background-image: url('../assets/images/background.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
 <!-- Boutons de connexion et inscription dans le fond -->
    <!-- Logo en haut à gauche qui redirige vers public/index.php -->
    <a href="index.php">
    <img src="../assets/images/CY_Tech.png" alt="Logo" style="position: absolute; top: 10px; left: 10px; width: 100px; height: auto;">
</a>
<!-- Boutons de connexion et inscription dans le fond -->
<button id="showLogin" class="btn btn-primary py-3 position-absolute top-0 end-0 m-4" 
    style="font-size: 1.2rem; border-radius: 10px; top: 10px;">Se connecter</button>
<button id="showRegister" class="btn btn-success py-3 position-absolute top-50 start-0 translate-middle-y m-4" 
    style="font-size: 1.2rem; border-radius: 10px; top: 45%;">S'inscrire</button>

    <!-- Contenu de la carte centrale avec formulaire -->
    <div id="card" class="card p-5 shadow-lg position-absolute top-50 start-50 translate-middle" style="max-width: 800px; width: 100%; border-radius: 10px; z-index: 1; display: none; margin-top: 20px;">
    
    <!-- Bouton de fermeture (croix) en haut à droite -->
    <button id="closeCard" class="btn btn-close position-absolute top-0 end-0 m-4" style="font-size: 1.5rem; z-index: 2;"></button>
    
    <!-- Formulaire de Connexion (initialement caché) -->
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

    <!-- Affichage de l'erreur si elle existe (pour JS) -->
    <div id="error-message" class="alert alert-danger" style="display: none;" role="alert"></div>

    <button type="submit" class="btn btn-primary w-100 py-2">Se connecter</button>
</form>

</div>


    <!-- Formulaire d'Inscription (initialement caché) -->
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

    
</div>

<!-- Bootstrap JS et dépendances -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<!-- Script JavaScript -->

<script src="../assets/js/login.js"></script>
<script src="../assets/js/register.js"></script>



</body>
</html>
