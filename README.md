# Pollux
> Créé le 12 juillet 2020 par Jean-Baptiste Caplan

Pollux est une plateforme qui permet entre autre de créer et gérer des processus à travers un éditeur et une connexion 
avec le client Python Castor.

## Installation

Après avoir récupéré le code depuis le dépôt GitHub il faut installer les dépendances en utilisant 
[Composer](https://getcomposer.org) :
```
    composer install
    composer dumpautoload
```

Vérifier bien que que votre serveur web pointe sur le dossier `public` et que le fichier `public/.htaccess` est bien 
pris en compte.

Vérifier aussi que l'utilisateur du serveur web ait les permissions en écriture sur tout le dossier de l'application.

Avant de procéder au premier lancement, il faut aussi faire un tour dans le dossier `config` (le créer à la racine de 
l'application si il n'existe pas) et mettre en place les fichiers suivants :

Fichier `config/application.ini` :

```
# Configuration de l'environnement
# Valeurs possibles : 
# - `dev` : permet d'afficher le stacktrace des exceptions 
# - `prod` : se contente d'une affichage lambda lors des exceptions
environment=prod
```

Fichier `config/databases.ini` :  

```
# Configuration de la base de données principale
# À ajuster selon votre environnement 
[database]
type=mysql
host=localhost
port=3306
username=username
password=password
database=bds
```