browser-sync start --proxy "localhost:8000" --files "accueil.php" "assets/css/*.css" "assets/js/*.js" "includes/*.php" "models/*.php" "public/*.php" "queries/*.sql"

php -S localhost:8000 -t projet





historique fonctionnel emprunt fonctionnelle

systeme photo a faire
modification de profil a faire
historique admin et historique general de connection à faire 
gestion des niveau (niveau operationel mais pas les droits attribuer)
systeme de recherche fait
partie admin:
- il manque toutes la gestions des objets
- possiblite de voir les vue


il manque possiblement d'autres choses






###ne pas lire


├── Projet ING1 Dev Web_v4.pdf
├── README.md
├── admin/                     # 🛠 Fichiers liés à l'administration
│   ├── admin.php
│   ├── delete_user.php
│   └── edit_user.php
├── assets/                    # 🎨 Fichiers statiques
│   ├── css/                   # CSS et styles
│   │   └── bootstrap.min.css
│   ├── images/                # Images
│   │   ├── CY_Tech.png
│   │   ├── background.png
│   │   ├── default.jpg
│   │   ├── trottinette.jpg
│   │   ├── velo.jpg
│   │   ├── vélo.jpg
│   │   └── Projet ING1 Dev Web_v4.pdf (déplace-le ailleurs si ce n'est pas une image)
│   └── js/                    # Scripts JavaScript
│       ├── login.js
│       ├── objet.js
│       ├── register.js
│       └── script.js
├── config/                    # ⚙️ Configuration et connexion BDD
│   ├── config.php
│   ├── db_connect.php
│   ├── initialize_database.php
├── controllers/               # 🎯 Logique métier et gestion des requêtes
│   ├── assign_object.php
│   ├── return_object.php
│   ├── process_login.php
│   ├── search_object.php
│   ├── search_user.php
│   ├── suppression.php
│   ├── ajouter_objet.php
├── includes/                  # 📎 Inclusion des fichiers communs
│   ├── header.php
├── logs/                      # 📝 Fichiers logs
│   ├── admin.log
│   └── inscription.log
├── models/                    # 🏗 Modèles de données (classes PHP)
│   └── User.php
├── database/                  # 🗄 Scripts SQL et base de données
│   ├── users.sql
├── public/                    # 🌍 Fichiers accessibles via le navigateur
│   ├── index.php              # Page d'accueil
│   ├── objets.php             # Page listant les objets
│   ├── dashboard.php          # Tableau de bord utilisateur
│   ├── profil.php             # Profil utilisateur
│   ├── recherche.php          # Recherche d'objets
│   ├── register.php           # Inscription
│   ├── forget.php             # Mot de passe oublié
│   ├── logout.php             # Déconnexion
│   ├── background.png         # Image d'arrière-plan (déplacer dans assets/images/)
├── uploads/                   # 📂 Stockage des fichiers uploadés
│   └── default.jpg
