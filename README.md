# Application CRUD Basique en PHP

Ceci est une application PHP simple qui illustre les opérations basiques de Création, Lecture, Mise à Jour et Suppression (CRUD) sur une base de données. Elle est conçue comme un outil d'apprentissage pour comprendre les interactions fondamentales avec une base de données en utilisant PHP et PDO (PHP Data Objects).

## Fonctionnalités

* **Créer :** Ajouter de nouveaux enregistrements à la base de données.
* **Lire :** Afficher une liste de tous les enregistrements.
* **Mettre à Jour :** Modifier les enregistrements existants dans la base de données.
* **Supprimer :** Retirer des enregistrements de la base de données.
* **Sécurité :** Protection contre l'injection SQL à l'aide des reqêtes préparées.
* Utilise PDO pour une interaction sécurisée et cohérente avec différentes bases de données.
* Formulaires HTML + Bootstrap pour l'interaction utilisateur.
* Structure de code simple et facile à comprendre.
* **Gestion des erreurs :** Gestion des erreurs avec un retour d'information à l'utilisateur.

## Prérequis

Avant d'exécuter cette application, assurez-vous d'avoir les éléments suivants installés :

* **PHP :** La version 7.0 ou supérieure est recommandée.
* **Serveur Web :** (par exemple, Apache, Nginx) configuré pour exécuter PHP.
* **Base de données :** (par exemple, MySQL, PostgreSQL, SQLite)

## Installation

1.  **Cloner le dépôt (si applicable) ou télécharger les fichiers :**
    ```bash
    git clone <URL_du_dépôt>
    ```
    ou téléchargez le fichier ZIP et extrayez-le.

2.  **Placer les fichiers de l'application dans la racine des documents de votre serveur web :**
    Cela peut être `htdocs` (pour Apache sur XAMPP/WAMP), `www` (pour Nginx), ou un répertoire similaire en fonction de votre configuration.

3.  **Configuration de la base de données :**
    * **Créer une base de données :** En utilisant votre outil de gestion de base de données (par exemple, phpMyAdmin pour MySQL, pgAdmin pour PostgreSQL), créez une nouvelle base de données. Supposons que vous la nommiez `basic_crud_db`.
    * **Créer une table :** Créez une table dans la base de données pour stocker vos données. Un exemple simple de structure de table pour stocker des "utilisateurs" pourrait être :

        ```sql
        CREATE TABLE `users` (
          `id` int NOT NULL AUTO_INCREMENT,
          `email` varchar(255) DEFAULT NULL,
          `nom` varchar(255) DEFAULT NULL,
          `prenoms` varchar(255) DEFAULT NULL,
          `password` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`)
        )
        ```

## Utilisation

1.  **Accéder à l'application dans votre navigateur web :**
    Ouvrez votre navigateur web et naviguez vers le répertoire où vous avez placé les fichiers de l'application dans la racine des documents de votre serveur web. Par exemple, si vous avez placé les fichiers directement à la racine, vous pourriez y accéder via `http://localhost/`. Si vous l'avez placé dans un sous-répertoire nommé `php_basic_crud`, vous y accéderiez via `http://localhost/php_basic_crud/`.

2.  **Naviguer dans l'application :**
    L'application devrait avoir des liens ou une interface basique pour effectuer les opérations CRUD :
    * **Afficher :** Affiche une liste des enregistrements existants.
    * **Ajouter Nouveau :** Présente un formulaire pour créer un nouvel enregistrement.
    * **Modifier :** Fournit un formulaire pour modifier un enregistrement existant (généralement accessible depuis la page d'affichage).
    * **Supprimer :** Permet de supprimer un enregistrement (généralement accessible depuis la page d'affichage).

3.  **Suivez les instructions à l'écran pour interagir avec les données.**
