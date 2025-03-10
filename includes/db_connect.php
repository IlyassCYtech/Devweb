<?php
$host = 'localhost';
$username = 'root';
$password = 'cytech0001';
$database = 'mon_projet';

try {
    // Connexion à MySQL avec PDO
    $conn = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Mode d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Mode de récupération
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4" // Encodage UTF-8
    ]);

    

    
    // Vérifier si la base de données existe
    $stmt = $conn->query("SHOW DATABASES LIKE '$database'");
    $dbExists = $stmt->fetch();

    if($dbExists === false){
     //Créer la base de données
       $conn->exec("CREATE DATABASE $database");
     echo "Base de données '$database' créée avec succès.<br>";
    }

    // Se connecter à la base de données
    $conn->exec("USE $database");

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}



// Retourner la connexion PDO
return $conn;
?>
