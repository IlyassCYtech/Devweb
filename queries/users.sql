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
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, DateAjout, Vitesse, EtatBatterie, LocalisationGPS, UtilisateurID)
VALUES 
('Vélo Urbain Plus', 'Vélo', 'Vélo urbain électrique avec assistance jusqu\'à 25 km/h', 'VeloTech', 'Actif', 'Bluetooth 5.0', 'Batterie Lithium 36V', NOW(), 18.50, 87, '37.775,-122.419', 1),
('Vélo Montagne Pro', 'Vélo', 'VTT électrique tout-terrain avec amortisseurs renforcés', 'MountainRide', 'Actif', 'GPS + Bluetooth', 'Batterie Li-ion 48V', NOW(), 22.30, 95, '37.773,-122.421', 1),
('Vélo Pliant Compact', 'Vélo', 'Vélo pliant pour trajets multimodaux', 'FoldBike', 'Actif', 'NFC + Bluetooth', 'Batterie LFP 24V', NOW(), 15.80, 63, '37.778,-122.412', 1),
('Vélo Cargo Plus', 'Vélo', 'Vélo cargo électrique avec plateforme avant et arrière', 'CargoMax', 'Inactif', 'Wi-Fi + 4G', 'Batterie Lithium 52V', NOW(), 12.50, 45, '37.782,-122.417', 1),
('Vélo Route Carbone', 'Vélo', 'Vélo de route ultraléger avec cadre carbone et connectivité', 'SpeedCycle', 'Actif', 'ANT+ + Bluetooth', 'Batterie Lithium 36V', NOW(), 32.10, 78, '37.786,-122.413', 8),
('Vélo Urbain Classic', 'Vélo', 'Vélo urbain hollandais avec assistance électrique discrète', 'CityRide', 'Actif', 'Bluetooth 4.2', 'Batterie Intégrée 36V', NOW(), 16.70, 92, '37.787,-122.409', 13),
('Vélo Tandem Connect', 'Vélo', 'Vélo tandem connecté pour deux utilisateurs', 'TandemPro', 'Actif', 'Wi-Fi + GPS', 'Double Batterie 48V', NOW(), 20.40, 83, '37.779,-122.414', 15);


-- Insertion de 7 trottinettes à San Francisco
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, DateAjout, Vitesse, EtatBatterie, LocalisationGPS, UtilisateurID)
VALUES 
('Trottinette Urban Fly', 'Trottinette', 'Trottinette urbaine pliable avec autonomie de 35km', 'ScootCity', 'Actif', 'Bluetooth + 4G', 'Batterie Lithium 36V', NOW(), 21.60, 81, '37.774,-122.425', 2),
('Trottinette Extreme X3', 'Trottinette', 'Trottinette tout-terrain avec suspensions avant et arrière', 'XtremeScoot', 'Actif', 'GPS + Bluetooth', 'Batterie Li-ion 48V', NOW(), 29.80, 76, '37.771,-122.428', 5),
('Trottinette Mini Fold', 'Trottinette', 'Trottinette ultra-compacte et légère pour déplacements urbains', 'MiniScoot', 'Inactif', 'Bluetooth 5.0', 'Batterie Lithium 24V', NOW(), 15.20, 39, '37.769,-122.422', 5),
('Trottinette Pro Racer', 'Trottinette', 'Trottinette sportive haute performance', 'RacerScoot', 'Actif', 'Wi-Fi + NFC', 'Batterie Lithium 48V', NOW(), 32.50, 88, '37.767,-122.431', 5),
('Trottinette City Commuter', 'Trottinette', 'Trottinette pour trajets quotidiens avec porte-bagages', 'CommuteScoot', 'Actif', 'Bluetooth + GPS', 'Batterie Li-ion 36V', NOW(), 22.80, 72, '37.765,-122.418', 11),
('Trottinette Luxury S1', 'Trottinette', 'Trottinette premium avec éclairage LED et tableau de bord numérique', 'LuxScoot', 'Actif', 'Wi-Fi + 4G + Bluetooth', 'Batterie Lithium 48V', NOW(), 25.60, 95, '37.762,-122.416', 12),
('Trottinette Eco E1', 'Trottinette', 'Trottinette écologique avec matériaux recyclés', 'EcoScoot', 'Actif', 'Bluetooth Low Energy', 'Batterie Solaire + Li-ion', NOW(), 18.90, 85, '37.759,-122.426', 14);

-- Insertion de 6 voitures à San Francisco
INSERT INTO ObjetConnecte (Nom, Type, Description, Marque, Etat, Connectivite, EnergieUtilisee, DateAjout, Vitesse, EtatBatterie, LocalisationGPS, UtilisateurID)
VALUES 
('Voiture Électrique Model S', 'Voiture', 'Berline électrique connectée avec autonomie de 600km', 'EcoDrive', 'Actif', 'Wi-Fi + 5G + Bluetooth', 'Batterie Lithium-ion 100kWh', NOW(), 65.30, 92, '37.789,-122.432', 1),
('Voiture Hybride Compact', 'Voiture', 'Citadine hybride avec consommation réduite', 'HybridCity', 'Actif', 'Bluetooth + 4G', 'Hybride Essence-Électrique', NOW(), 45.80, 78, '37.791,-122.428', 4),
('SUV Électrique X1', 'Voiture', 'SUV électrique avec 7 places et connectivité avancée', 'GreenSUV', 'Actif', 'Wi-Fi + 5G + GPS', 'Batterie Lithium 120kWh', NOW(), 53.20, 85, '37.795,-122.421', 5),
('Voiture Autonome Pilote', 'Voiture', 'Berline avec capacités de conduite autonome niveau 3', 'AutoDrive', 'Actif', 'Wi-Fi + 5G + LIDAR', 'Batterie Lithium-ion 90kWh', NOW(), 58.70, 76, '37.798,-122.431', 8),
('Mini Électrique City', 'Voiture', 'Mini voiture électrique pour usage urbain', 'MiniElec', 'Inactif', 'Bluetooth + 4G', 'Batterie Lithium 50kWh', NOW(), 40.20, 32, '37.802,-122.419', 10),
('Sport Électrique GT', 'Voiture', 'Voiture sport électrique haute performance', 'SpeedElec', 'Actif', 'Wi-Fi + 5G + GPS', 'Batterie Lithium 130kWh', NOW(), 92.50, 88, '37.796,-122.407', 15);
