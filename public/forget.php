<?php
// Page de connexion

include('../includes/header.php');
?>

<div class='container'>
    <h1 class='text-center mt-5'>Connexion</h1>
    <form action='process_login.php' method='POST' class='mt-4'>
        <div class='mb-3'>
            <label for='email' class='form-label'>Email</label>
            <input type='email' id='email' name='email' class='form-control' required>
        </div>
        <div class='mb-3'>
            <label for='password' class='form-label'>Mot de passe</label>
            <input type='password' id='password' name='password' class='form-control' required>
        </div>
         <button type='submit' class='btn btn-primary'>Se connecter</button>
    </form>

    <form action="register.php" method="POST">
    <label for="username">Pseudonyme</label>
    <input type="text" name="username" id="username" required><br>

    <label for="password">Mot de passe</label>
    <input type="password" name="password" id="password" required><br>

    <label for="nom">Nom</label>
    <input type="text" name="nom" id="nom" required><br>

    <label for="prenom">Prénom</label>
    <input type="text" name="prenom" id="prenom" required><br>

    <label for="date_naissance">Date de naissance</label>
    <input type="date" name="date_naissance" id="date_naissance" required><br>

    <label for="age">Âge</label>
    <input type="number" name="age" id="age" required><br>

    <label for="sexe">Sexe/Genre</label>
    <select name="sexe" id="sexe" required>
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
        <option value="Autre">Autre</option>
    </select><br>

    <label for="type_membre">Type de membre</label>
    <select name="type_membre" id="type_membre" required>
        <option value="élève">Élève</option>
        <option value="parent">Parent</option>
        <option value="développeur">Développeur</option>
        <option value="autre">Autre</option>
    </select><br>

    <label for="email">Email</label>
    <input type="email" name="email" id="email" required><br>

    <label for="niveau">Niveau</label>
    <select name="niveau" id="niveau" required>
        <option value="Débutant">Débutant</option>
        <option value="Intermédiaire">Intermédiaire</option>
        <option value="Avancé">Avancé</option>
        <option value="Expert">Expert</option>
    </select><br>

    <label for="points_experience">Points d'expérience</label>
    <input type="number" name="points_experience" id="points_experience" required><br>

    <label for="admin">Administrateur</label>
    <input type="checkbox" name="admin" id="admin" value="1"><br>

    <button type="submit">S'inscrire</button>
</form>

</div>
