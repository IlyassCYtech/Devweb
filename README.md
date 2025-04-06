# Site San Francisco - Gestion d'une ville connecté

Ce projet est une application web sur San Francisco développée en PHP avec une architecture modulaire. Il inclut des fonctionnalités d'administration, de gestion des utilisateurs, de sauvegarde/restauration de base de données avec des rapports PDF, d'informations sur la ville et des interactions avec les objets connectés (réservation, modifier les états etc...).

## Structure du projet

- composer.json        
- composer.lock         
- package.json          
- suppression.php       
- tempo.php             
- admin/                
- assets/               
- backups/              
- logs/                 
- models/               
- public/               
- queries/              
- uploads/             
- vendor/               

## Prérequis

- **PHP** : Version 7.4 ou supérieure
- **Composer** : Pour gérer les dépendances PHP
- **MySQL** : Base de données utilisée par l'application
- **Node.js** (optionnel) : Si des outils front-end sont utilisés

## Installation

1. Lancez le serveur local :
   ```php -S localhost:8000 -t public dans le Terminal```
   
2. Ajouter dans la barre de recherche /public et vous serez redirigé vers l'index

3. Pour lancer en mode développeur, mettez la commande dans me terminal :
   ```browser-sync start --proxy "localhost:8000" --files "accueil.php" "assets/css/*.css" "assets/js/*.js" "includes/*.php" "models/*.php" "public/*.php" "queries/*.sql"```


## Fonctionnalités principales

- **Information sur la ville** : Information en temps réelle de la ville. 
- **Administration** : Gestion des utilisateurs, des demandes, historique des actions sur le site et possibilité d'obtenir des rapports sur le site.
- **Sauvegarde et restauration** : Sauvegarde et restauration de la base de données.
- **Gestion des objets** : Upload et gestion des des objets connectés.
- **Logs** : Suivi des activités via des fichiers de logs et backups si besoin.


## Auteurs

- ALLUCHON Nicolas BANTOS-ARNAUD Damien CHAKHMOUN Ilyass NEHAD Younes SYEDA Abida

