<?php
// Exemple de code correct
$username = "monUtilisateur";  // Déclaration correcte d'une variable
$password = "monMotDePasse";  // Déclaration correcte d'une variable

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "base_de_donnees");

// Vérification de la connexion
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
} else {
    echo "Connexion réussie";
}
?>
