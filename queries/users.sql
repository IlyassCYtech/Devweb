DROP TABLE users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    age INT NOT NULL,
    sexe ENUM('Homme', 'Femme', 'Autre') NOT NULL,
    type_membre ENUM('élève', 'parent', 'développeur', 'autre') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    niveau ENUM('Débutant', 'Intermédiaire', 'Avancé', 'Expert') NOT NULL,
    points_experience INT NOT NULL DEFAULT 0,
    admin BOOLEAN NOT NULL DEFAULT 0
);

-- Insérer un utilisateur avec email et mot de passe
INSERT INTO users (
    username, password, nom, prenom, date_naissance, age, sexe, type_membre, email, niveau, points_experience, admin
) 
VALUES (
    'johndoe', 
    '123',  -- Hachage MySQL (non recommandé)
    'Doe', 
    'John', 
    '1990-05-15', 
    34, 
    'Homme', 
    'élève', 
    'admin@gmail.com', 
    'Débutant', 
    0, 
    0
);
