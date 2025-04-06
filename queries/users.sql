-- Suppression des tables dans le bon ordre pour respecter les contraintes de clé étrangère
DROP TABLE IF EXISTS UserHistory;
DROP TABLE IF EXISTS DeleteRequests;
DROP TABLE IF EXISTS Historique_Actions;
DROP TABLE IF EXISTS Acces;
DROP TABLE IF EXISTS Administration;
DROP TABLE IF EXISTS ObjetConnecte;
DROP TABLE IF EXISTS NivUtilisateur;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS TypeObjet;
DROP TABLE IF EXISTS email_confirmations;
DROP TABLE IF EXISTS DemandesTypeObjet;

-- Création de la table des utilisateurs
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
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin BOOLEAN NOT NULL DEFAULT 0,
    gestion BOOLEAN NOT NULL DEFAULT 0,
    photo_profil VARCHAR(255) DEFAULT 'default.jpg',
    confirmation_code VARCHAR(255) DEFAULT NULL,
    is_confirmed INT DEFAULT 0,
    is_confirmed_by_ad INT DEFAULT 0
);

-- Création de la table des accès
CREATE TABLE Acces (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUtilisateur INT NOT NULL,
    DateHeureAcces DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ActionsEffectuees TEXT,
    PointsGagnes INT DEFAULT 0,
    FOREIGN KEY (IDUtilisateur) REFERENCES users(id) ON DELETE CASCADE
);

-- Création de la table des objets connectés
-- Création de la table des objets connectés avec la colonne UtilisateurID correctement définie
CREATE TABLE ObjetConnecte (
    ID INT AUTO_INCREMENT PRIMARY KEY,  
    Nom VARCHAR(255) NOT NULL,  
    Type VARCHAR(100) NOT NULL,  
    Description TEXT,  
    Marque VARCHAR(100),  
    Etat ENUM('Actif', 'Inactif') NOT NULL,  
    Connectivite VARCHAR(255) NOT NULL,  
    EnergieUtilisee VARCHAR(100) NOT NULL,  
    DerniereInteraction TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  
    DateAjout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Luminosite DECIMAL(5,2) DEFAULT NULL,  
    EtatLuminaire VARCHAR(50) DEFAULT NULL,  
    LocalisationGPS VARCHAR(255) DEFAULT NULL,  
    Vitesse DECIMAL(5,2) DEFAULT NULL,   
    EtatBatterie INT DEFAULT NULL,
    UtilisateurID INT DEFAULT NULL,  -- Ajout de la colonne UtilisateurID qui peut être NULL
    FOREIGN KEY (UtilisateurID) REFERENCES users(id) ON DELETE SET NULL  -- Lien avec la table users, permettant UtilisateurID = NULL
);

CREATE TABLE TypeObjet (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Nom VARCHAR(100) NOT NULL UNIQUE
);

-- Création de la table de l'historique des actions
CREATE TABLE Historique_Actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_objet_connecte INT NULL,  -- ✅ Correction ici : INT au lieu de VARCHAR(50)
    type_action VARCHAR(255) NOT NULL,
    date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_objet_connecte) REFERENCES ObjetConnecte(ID) ON DELETE SET NULL
);

-- Création de la table d'administration
CREATE TABLE Administration (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    IDUtilisateur INT NOT NULL,
    ActionsRealisees VARCHAR(255),
    FOREIGN KEY (IDUtilisateur) REFERENCES users(id) ON DELETE CASCADE
);

-- Création de la table des demandes de suppression
CREATE TABLE DeleteRequests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    object_id INT NOT NULL,
    request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (object_id) REFERENCES ObjetConnecte(ID) ON DELETE CASCADE
);

-- Création de la table de l'historique des connexions/inscriptions
CREATE TABLE UserHistory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('Connexion', 'Déconnexion', 'Inscription') NOT NULL,
    action_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Création de la table des demandes de type d'objet
CREATE TABLE DemandesTypeObjet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_objet VARCHAR(255) NOT NULL,
    date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('En attente', 'Approuvé', 'Rejeté') DEFAULT 'En attente',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO TypeObjet (Nom) VALUES 
('Vélo'),
('Trottinette'),
('voiture');

-- Insertion d'un utilisateur test
INSERT INTO users (username, password, nom, prenom, date_naissance, age, sexe, type_membre, email, niveau, points_experience, admin, photo_profil,confirmation_code,is_confirmed,is_confirmed_by_ad)
VALUES ('johndoe', '123', 'Doe', 'John', '1990-05-15', 34, 'Homme', 'élève', 'admin@gmail.com', 'Débutant', 100, 1, 'default.jpg',000000,1,1);

-- Insertion d'un utilisateur test
INSERT INTO users (username, password, nom, prenom, date_naissance, age, sexe, type_membre, email, niveau, points_experience, admin, photo_profil,confirmation_code,is_confirmed,is_confirmed_by_ad)
VALUES 
('janedoe', '123456', 'Doe', 'Jane', '1995-08-20', 28, 'Femme', 'développeur', 'jane.doe@example.com', 'Intermédiaire', 100, 1, 'default.jpg',000001,1,1);

-- Insertion d'un objet connecté avec un utilisateur spécifique
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, Luminosite, EtatLuminaire, LocalisationGPS, Vitesse, EtatBatterie, UtilisateurID)
VALUES ('Lampe intelligente', 'Éclairage', 'Lampe connectée avec réglage de lintensité', 'Philips', 'Actif', 'Wi-Fi', 'Électricité', 75.5, 'Allumé', '37.7749,-122.4194', NULL, 95, 1); -- UtilisateurID = 1

-- Insertion d'un autre objet connecté sans utilisateur (UtilisateurID = NULL)
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, Luminosite, EtatLuminaire, LocalisationGPS, Vitesse, EtatBatterie, UtilisateurID)
VALUES ('Caméra de sécurité', 'Surveillance', 'Caméra HD avec vision nocturne', 'Arlo', 'Actif', 'Wi-Fi', 'Batterie', NULL, NULL, '37.7749,-122.4194', NULL, 80, NULL); -- Pas d'utilisateur (UtilisateurID = NULL)

-- Insertion d'un autre objet connecté avec un autre utilisateur
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, Luminosite, EtatLuminaire, LocalisationGPS, Vitesse, EtatBatterie, UtilisateurID)
VALUES ('Thermostat intelligent', 'Chauffage', 'Thermostat connecté réglable à distance', 'Nest', 'Actif', 'Wi-Fi', 'Électricité', NULL, NULL, '37.7749,-122.4194', NULL, 100, 2); -- UtilisateurID = 2

-- Insertion de trois vélos à San Francisco
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, DateAjout, Vitesse, EtatBatterie, LocalisationGPS)
VALUES 
('Vélo Urbain 1000', 'Vélo', 'Vélo électrique pour la ville avec moteur de 250W.', 'Marque A', 'Actif', 'Bluetooth', 'Batterie Lithium', NOW(), 25.00, 100, '37.7749,-122.4194'),
('Vélo Sport 2000', 'Vélo', 'Vélo de sport avec transmission Shimano 21 vitesses.', 'Marque B', 'Actif', 'Wi-Fi', 'Batterie Li-ion', NOW(), 30.00, 95, '37.7749,-122.4194'),
('Vélo Cargo', 'Vélo', 'Vélo cargo pour transport de charges lourdes.', 'Marque C', 'Inactif', 'Bluetooth', 'Batterie NiMH', NOW(), 20.00, 80, '37.7749,-122.4194');

-- Insertion de quatre trottinettes à San Francisco
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, DateAjout, Vitesse, EtatBatterie, LocalisationGPS)
VALUES
('Trottinette Electrique X1', 'Trottinette', 'Trottinette électrique avec moteur de 250W.', 'Marque D', 'Actif', 'Bluetooth', 'Batterie Lithium', NOW(), 15.00, 100, '37.7749,-122.4194'),
('Trottinette Electrique X2', 'Trottinette', 'Trottinette pour trajets urbains avec système de freinage électronique.', 'Marque E', 'Actif', 'Wi-Fi', 'Batterie Li-ion', NOW(), 18.00, 85, '37.7749,-122.4194'),
('Trottinette Sport T3', 'Trottinette', 'Trottinette sport avec roues renforcées.', 'Marque F', 'Actif', 'Bluetooth', 'Batterie Lithium', NOW(), 20.00, 50, '37.7749,-122.4194'),
('Trottinette Connectée T4', 'Trottinette', 'Trottinette connectée avec suivi GPS et autonomie améliorée.', 'Marque G', 'Inactif', 'Wi-Fi', 'Batterie Li-ion', NOW(), 22.00, 90, '37.7749,-122.4194');
