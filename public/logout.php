<?php
session_start(); // Assurez-vous que la session est démarrée avant de la détruire

if (session_status() === PHP_SESSION_ACTIVE) { 
    session_destroy(); // Détruit la session
}

header("Location: ../public/index.php"); // Corrige la redirection
exit(); // Assure que le script s'arrête après la redirection
?>
