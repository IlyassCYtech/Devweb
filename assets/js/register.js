document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.getElementById('register-form');
    const errorMessageDiv = document.getElementById('error-message-register');

    // Soumission du formulaire d'inscription avec AJAX
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();  // Empêcher le rechargement de la page

        // Vider les messages d'erreur précédents
        errorMessageDiv.style.display = 'none';

        // Récupérer les valeurs du formulaire
        const username = document.getElementById('username').value;
        const password = document.getElementById('password-register').value;
        const nom = document.getElementById('nom').value;
        const prenom = document.getElementById('prenom').value;
        const date_naissance = document.getElementById('date_naissance').value;
        const age = document.getElementById('age').value;
        const sexe = document.getElementById('sexe').value;
        const type_membre = document.getElementById('type_membre').value;
        const email = document.getElementById('email-register').value;
        const niveau = document.getElementById('niveau').value;
        const points_experience = document.getElementById('points_experience').value;
        const admin = document.getElementById('admin').checked ? 1 : 0;

        // Créer un objet FormData pour envoyer les données du formulaire
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        formData.append('nom', nom);
        formData.append('prenom', prenom);
        formData.append('date_naissance', date_naissance);
        formData.append('age', age);
        formData.append('sexe', sexe);
        formData.append('type_membre', type_membre);
        formData.append('email', email);
        formData.append('niveau', niveau);
        formData.append('points_experience', points_experience);
        formData.append('admin', admin);

        // Initialiser une requête AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'register.php', true);

        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);  // Parser la réponse JSON
                if (response.success) {
                    // Afficher un message de succès ou rediriger l'utilisateur
                    alert(response.message); // Affiche un message d'alerte
                    window.location.href = 'dashboard.php';  // Rediriger vers la page de connexion
                } else {
                    // Afficher un message d'erreur
                    errorMessageDiv.textContent = response.message;
                    errorMessageDiv.style.display = 'block';
                }
            } else {
                // Afficher une erreur générique si une erreur serveur se produit
                console.error('Erreur serveur:', xhr.status);
                errorMessageDiv.textContent = 'Une erreur serveur est survenue, veuillez réessayer plus tard.';
                errorMessageDiv.style.display = 'block';
            }
        };

        // Envoi de la requête AJAX
        xhr.send(formData);
    });
});
