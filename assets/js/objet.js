function assignObject(objectId, userId) {
    // Crée un objet de données à envoyer
 

    // Envoi des données via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../../public/assign_object.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    // Paramètres à envoyer : ID de l'objet et ID de l'utilisateur
    const params = 'objectId=' + objectId + '&userId=' + userId;

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Lorsque la requête est terminée avec succès, recharge la page
            if (window.location.pathname === "/public/objets.php") {
                window.location.reload();
            }        }
    };

    xhr.send(params);
}

function returnObject(objectID, userId) {
 // L'utilisateur connecté récupère son ID à partir de PHP

// Demander la confirmation avant de rendre l'objet
if (confirm("Êtes-vous sûr de vouloir rendre cet objet ?")) {
    // Envoyer la requête AJAX pour mettre à jour l'objet dans la base de données
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../../public/return_object.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    const params = 'objectID=' + objectID + '&userID=' + userId;  // Utilisation de objectID et userID

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Si tout se passe bien, recharger la page pour mettre à jour les objets
            if (window.location.pathname === "/public/objets.php") {
                window.location.reload();
            }        }
    };
    xhr.send(params);
}
}