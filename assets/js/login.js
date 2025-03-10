document.addEventListener("DOMContentLoaded", function() {
    // Récupération des éléments
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');
    const showLoginButton = document.getElementById('showLogin');
    const showRegisterButton = document.getElementById('showRegister');
    const card = document.getElementById('card');
    const closeCardButton = document.getElementById('closeCard');
    const errorMessageDiv = document.getElementById('error-message');
    const form = document.getElementById('login-form');

    // Cacher les formulaires et la carte par défaut
    loginForm.style.display = 'none';
    registerForm.style.display = 'none';
    card.style.display = 'none';

    // Fonction pour ajuster la position de la carte
    function adjustCardPosition() {
        const windowHeight = window.innerHeight; // Hauteur de la fenêtre
        const cardHeight = card.offsetHeight; // Hauteur de la carte
        const cardTopPosition = card.getBoundingClientRect().top;

        // Si la carte dépasse la moitié de la fenêtre, on ajuste la position
        if (cardTopPosition < windowHeight / 2 && cardHeight > windowHeight / 2) {
            card.style.marginTop = '50px';
        } else {
            card.style.marginTop = '0';
        }
    }

    // Afficher le formulaire de connexion
    showLoginButton.addEventListener('click', function() {
        card.style.display = 'block';
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        adjustCardPosition();  // Ajuste la position dès l'affichage
    });

    // Afficher le formulaire d'inscription
    showRegisterButton.addEventListener('click', function() {
        card.style.display = 'block';
        registerForm.style.display = 'block';
        loginForm.style.display = 'none';
        adjustCardPosition();  // Ajuste la position dès l'affichage
    });

    // Fermer la carte (masquer la carte) lorsque la croix est cliquée
    closeCardButton.addEventListener('click', function() {
        card.style.display = 'none';
    });

    // Ajuster la position de la carte au redimensionnement de la fenêtre
    window.addEventListener('resize', adjustCardPosition);

    // Soumission du formulaire avec AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();  // Empêcher le rechargement de la page

        // Vider les messages d'erreur précédents
        errorMessageDiv.style.display = 'none';

        // Récupérer les valeurs du formulaire
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        // Vérifier que les champs ne sont pas vides
        if (email === '' || password === '') {
            errorMessageDiv.textContent = 'Tous les champs doivent être remplis.';
            errorMessageDiv.style.display = 'block';
            return;  // Arrêter l'exécution si les champs sont vides
        }

        // Créer un objet FormData pour envoyer les données du formulaire
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        // Initialiser une requête AJAX
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'process_login.php', true);

        xhr.onload = function() {
            if (xhr.status === 200) {
                console.log(xhr.responseText);  // Affichez la réponse brute du serveur
                try {
                    const response = JSON.parse(xhr.responseText);  // Essayez de parser la réponse JSON
                    console.log(response);  // Affichez l'objet JSON après parsing
                    if (response.success) {
                        // Redirection vers le tableau de bord
                        window.location.href = 'dashboard.php';
                    } else {
                        // Afficher l'erreur si la connexion échoue
                        errorMessageDiv.textContent = response.message;
                        errorMessageDiv.style.display = 'block';
                    }
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    // Si la réponse ne peut pas être parsée, afficher un message d'erreur
                    errorMessageDiv.textContent = 'Une erreur s\'est produite lors du traitement de la réponse.';
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
