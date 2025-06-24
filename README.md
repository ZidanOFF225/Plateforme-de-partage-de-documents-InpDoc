# Plateforme de Partage de Cours - INPHB

## Description
Plateforme web permettant aux étudiants de l'INPHB de partager et d'accéder à des ressources académiques (cours, exercices corrigés, etc.). Cette application facilite le partage de documents entre étudiants et enseignants, avec un système de modération pour garantir la qualité du contenu.

## Fonctionnalités principales
- **Gestion des utilisateurs** : Inscription, connexion et gestion des profils avec différents rôles (étudiant, enseignant, admin)
- **Gestion des documents** : Upload, téléchargement, modification et suppression de documents
- **Catégorisation** : Organisation hiérarchique des documents par catégories
- **Système de recherche avancée** : Recherche par titre, description, catégorie et tags
- **Système de commentaires** : Possibilité de commenter les documents
- **Système de notation** : Évaluation des documents par les utilisateurs (1-5 étoiles)
- **Modération** : Validation des documents par les administrateurs
- **Interface responsive** : Compatible avec tous les appareils
- **Interface d'administration améliorée** : Tableaux de bord intuitifs pour la gestion des utilisateurs, documents, catégories et tags
- **Système de rejet de documents** : Possibilité pour les administrateurs de rejeter les documents avec justification
- **Statistiques avancées** : Suivi des téléchargements, notations et commentaires

## Technologies utilisées
- **Backend** : PHP 8.x
- **Base de données** : MySQL 8.0
- **Frontend** : HTML5, CSS3, JavaScript
- **Framework CSS** : Bootstrap 5
- **Contrôle de version** : Git

## Installation
1. Cloner le repository
2. Configurer un serveur web (Apache/Nginx) avec PHP 8.x
3. Créer une base de données MySQL
4. Importer le schéma de la base de données depuis `database/schema.sql`
5. Configurer les paramètres de connexion dans `config/database.php`
6. Configurer les paramètres généraux dans `config/config.php`
7. Créer un compte administrateur avec `create_admin.php`
8. Lancer l'application

## Structure du projet
```
/
├── admin/           # Interface d'administration
│   ├── categories.php  # Gestion des catégories
│   ├── documents.php   # Gestion des documents
│   ├── index.php       # Tableau de bord admin
│   ├── tags.php        # Gestion des tags
│   ├── users.php       # Gestion des utilisateurs
│   ├── approve.php     # Validation des documents
│   └── reject.php      # Rejet des documents
├── assets/          # Fichiers statiques (CSS, JS, images)
├── auth/            # Authentification (login, register, logout)
├── config/          # Configuration de l'application
│   ├── config.php      # Configuration générale
│   └── database.php    # Configuration de la base de données
├── database/        # Scripts SQL et migrations
│   └── schema.sql      # Schéma de la base de données
├── includes/        # Fichiers d'inclusion PHP
├── uploads/         # Dossier pour les documents uploadés
├── views/           # Templates et vues de l'application
│   ├── header.php      # En-tête commun
│   └── footer.php      # Pied de page commun
├── about.php        # Page À propos
├── contact.php      # Page Contact
├── create-document.php # Création de document
├── documents.php    # Affichage des documents
├── edit-document.php # Modification de document
├── index.php        # Page d'accueil
├── liste-documents.php # Liste des documents
├── mes-documents.php # Documents de l'utilisateur connecté
├── profile.php      # Profil utilisateur
├── rate.php         # Système de notation
├── search.php       # Recherche de documents
└── terms.php        # Conditions d'utilisation
```

## Fonctionnalités détaillées

### Gestion des utilisateurs
- Inscription avec validation par email
- Connexion avec gestion des sessions
- Profils utilisateurs personnalisables
- Gestion des rôles (étudiant, enseignant, administrateur)
- Interface d'administration pour la gestion des utilisateurs

### Gestion des documents
- Upload de documents avec validation des types de fichiers
- Système de modération (en attente, approuvé, rejeté)
- Téléchargement de documents
- Modification et suppression de documents
- Statistiques de téléchargement
- Système de rejet avec justification

### Catégorisation et organisation
- Structure hiérarchique des catégories
- Tags pour une meilleure organisation
- Filtrage par catégorie et type de document
- Interface d'administration pour la gestion des catégories et tags

### Administration
- Tableau de bord avec statistiques
- Gestion des utilisateurs
- Gestion des catégories
- Gestion des documents
- Gestion des tags
- Modération des documents (approbation et rejet)
- Interface responsive pour tous les appareils

## Équipe
- ANOMAN Sibah Christ Yohann
- KOUADIO Abraham

## Licence
Tous droits réservés © 2024 INPHB 