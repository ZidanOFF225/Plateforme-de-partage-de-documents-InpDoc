# Rapport Technique - Plateforme de Partage de Cours INPHB

## Table des matières
1. [Introduction](#introduction)
2. [Analyse des besoins](#analyse-des-besoins)
3. [Architecture du système](#architecture-du-système)
4. [Base de données](#base-de-données)
5. [Fonctionnalités détaillées](#fonctionnalités-détaillées)
6. [Description des modules](#description-des-modules)
7. [Interface utilisateur](#interface-utilisateur)
8. [Sécurité](#sécurité)
9. [Tests et validation](#tests-et-validation)
10. [Déploiement](#déploiement)
11. [Maintenance et évolutions futures](#maintenance-et-évolutions-futures)
12. [Difficultés rencontrées et solutions](#difficultés-rencontrées-et-solutions)
13. [Conclusion](#conclusion)

## Introduction

La Plateforme de Partage de Cours INPHB est une application web développée pour faciliter le partage de ressources académiques entre les étudiants et les enseignants de l'Institut National Polytechnique Félix Houphouët-Boigny (INPHB). Ce rapport présente une analyse technique complète du projet, détaillant son architecture, ses fonctionnalités et ses aspects techniques.

### Contexte du projet

L'INPHB, comme de nombreuses institutions académiques, fait face au défi de la gestion et du partage efficace des ressources pédagogiques. Les étudiants et les enseignants ont besoin d'un accès facile aux documents de cours, exercices, examens et autres ressources académiques. Cette plateforme répond à ce besoin en offrant un espace centralisé pour le partage et l'accès aux documents.

### Objectifs du projet

- Créer une plateforme centralisée pour le partage de documents académiques
- Faciliter l'accès aux ressources pédagogiques pour les étudiants et les enseignants
- Mettre en place un système de modération pour garantir la qualité du contenu
- Offrir des fonctionnalités de recherche avancée pour trouver rapidement les documents
- Permettre l'interaction entre utilisateurs via des commentaires et des notations
- Fournir une interface d'administration complète et intuitive
- Assurer une expérience utilisateur optimale sur tous les appareils

## Analyse des besoins

### Besoins fonctionnels

1. **Gestion des utilisateurs**
   - Inscription et authentification des utilisateurs
   - Gestion des profils utilisateurs
   - Attribution de rôles (étudiant, enseignant, administrateur)
   - Interface d'administration pour la gestion des utilisateurs

2. **Gestion des documents**
   - Upload et téléchargement de documents
   - Modification et suppression de documents
   - Système de modération des documents
   - Système de rejet avec justification

3. **Organisation des documents**
   - Catégorisation hiérarchique des documents
   - Système de tags pour une meilleure organisation
   - Filtrage par catégorie et type de document
   - Interface d'administration pour la gestion des catégories et tags

4. **Recherche et découverte**
   - Recherche avancée par titre, description, catégorie et tags
   - Affichage des documents récents et populaires
   - Navigation intuitive entre les catégories

5. **Interaction sociale**
   - Commentaires sur les documents
   - Système de notation des documents
   - Statistiques de téléchargement

### Besoins non fonctionnels

1. **Performance**
   - Temps de chargement rapide des pages
   - Gestion efficace des uploads de documents
   - Optimisation des requêtes de base de données

2. **Sécurité**
   - Protection contre les injections SQL
   - Validation des entrées utilisateur
   - Gestion sécurisée des sessions
   - Protection contre les attaques XSS et CSRF

3. **Utilisabilité**
   - Interface responsive compatible avec tous les appareils
   - Navigation intuitive
   - Messages d'erreur clairs et informatifs
   - Tableaux de bord intuitifs pour les administrateurs

4. **Maintenabilité**
   - Code bien structuré et documenté
   - Séparation des préoccupations (MVC)
   - Facilité d'ajout de nouvelles fonctionnalités

## Architecture du système

### Architecture générale

La plateforme suit une architecture web classique avec une séparation claire entre la présentation, la logique métier et l'accès aux données :

- **Présentation** : Interface utilisateur HTML/CSS/JavaScript avec Bootstrap 5
- **Logique métier** : Scripts PHP
- **Accès aux données** : Requêtes SQL via PDO

### Structure du projet

Le projet est organisé de manière modulaire avec une séparation claire des responsabilités :

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
├── auth/            # Authentification
├── config/          # Configuration
├── database/        # Scripts SQL
├── includes/        # Fichiers d'inclusion PHP
├── uploads/         # Documents uploadés
├── views/           # Templates et vues
└── [fichiers PHP]   # Pages principales
```

### Technologies utilisées

- **Backend** : PHP 8.x
- **Base de données** : MySQL 8.0
- **Frontend** : HTML5, CSS3, JavaScript
- **Framework CSS** : Bootstrap 5
- **Contrôle de version** : Git

### Flux de données

1. L'utilisateur interagit avec l'interface utilisateur
2. Les requêtes sont traitées par les scripts PHP
3. Les scripts PHP interagissent avec la base de données via PDO
4. Les résultats sont renvoyés à l'interface utilisateur

## Base de données

### Schéma de la base de données

La base de données est structurée autour de plusieurs tables principales :

1. **users** : Stockage des informations utilisateurs
   - Champs : id, matricule, nom, prenoms, email, password, role, date_creation, derniere_connexion

2. **categories** : Organisation hiérarchique des catégories
   - Champs : id, nom, description, parent_id

3. **documents** : Gestion des documents partagés
   - Champs : id, titre, description, filename, type_document, categorie_id, user_id, date_upload, nb_telechargements, statut

4. **commentaires** : Commentaires sur les documents
   - Champs : id, document_id, user_id, contenu, date_creation

5. **notes** : Système de notation des documents
   - Champs : id, document_id, user_id, note, date_creation

6. **tags** : Tags pour catégoriser les documents
   - Champs : id, nom

7. **document_tags** : Relation many-to-many entre documents et tags
   - Champs : document_id, tag_id

### Relations entre les tables

- Une catégorie peut avoir plusieurs documents (one-to-many)
- Une catégorie peut avoir plusieurs sous-catégories (one-to-many)
- Un utilisateur peut avoir plusieurs documents (one-to-many)
- Un document peut avoir plusieurs commentaires (one-to-many)
- Un document peut avoir plusieurs notes (one-to-many)
- Un document peut avoir plusieurs tags (many-to-many)

### Optimisations

- Utilisation d'index pour améliorer les performances des requêtes
- Contraintes de clés étrangères pour maintenir l'intégrité des données
- Utilisation de transactions pour les opérations critiques

## Fonctionnalités détaillées

### Gestion des utilisateurs

#### Inscription et authentification

Le système d'inscription et d'authentification est géré dans le dossier `auth/` avec les fichiers suivants :
- `register.php` : Formulaire d'inscription avec validation des données
- `login.php` : Formulaire de connexion avec gestion des sessions
- `logout.php` : Déconnexion et nettoyage des sessions

Les mots de passe sont stockés de manière sécurisée avec hachage, et les sessions sont gérées pour maintenir l'état de connexion des utilisateurs.

#### Gestion des profils

La gestion des profils utilisateurs est implémentée dans `profile.php`, permettant aux utilisateurs de :
- Modifier leurs informations personnelles
- Changer leur mot de passe
- Voir leurs documents partagés
- Consulter leur historique d'activité

#### Administration des utilisateurs

La gestion des utilisateurs par les administrateurs est implémentée dans `admin/users.php`, permettant de :
- Voir la liste complète des utilisateurs
- Filtrer les utilisateurs par nom et rôle
- Modifier les informations des utilisateurs
- Supprimer des utilisateurs
- Voir les statistiques d'utilisation par utilisateur

### Gestion des documents

#### Upload et téléchargement

Le processus d'upload de documents est géré dans `create-document.php` avec les fonctionnalités suivantes :
- Validation des types de fichiers autorisés
- Limitation de la taille des fichiers
- Génération de noms de fichiers uniques
- Association des documents aux catégories et tags

Le téléchargement de documents est géré via des scripts PHP qui :
- Vérifient les droits d'accès
- Incrémentent le compteur de téléchargements
- Enregistrent l'activité dans les logs

#### Modération des documents

Le système de modération est implémenté dans l'interface d'administration avec :
- Liste des documents en attente de modération
- Options pour approuver ou rejeter les documents
- Notifications aux utilisateurs concernant le statut de leurs documents
- Système de rejet avec justification pour les documents non conformes

Les fichiers `admin/approve.php` et `admin/reject.php` gèrent respectivement l'approbation et le rejet des documents, avec des notifications appropriées aux utilisateurs.

#### Administration des documents

La gestion des documents par les administrateurs est implémentée dans `admin/documents.php`, permettant de :
- Voir la liste complète des documents
- Filtrer les documents par statut, catégorie et type
- Modifier les informations des documents
- Supprimer des documents
- Voir les statistiques de téléchargement et de notation

### Catégorisation et organisation

#### Structure hiérarchique des catégories

La gestion des catégories est implémentée dans `admin/categories.php` avec :
- Création, modification et suppression de catégories
- Organisation hiérarchique avec catégories parentes et enfants
- Validation pour éviter les cycles dans la hiérarchie
- Interface intuitive pour la gestion des catégories

#### Système de tags

La gestion des tags est implémentée dans `admin/tags.php` avec :
- Création, modification et suppression de tags
- Association des tags aux documents
- Recherche par tags
- Interface intuitive pour la gestion des tags

### Recherche et découverte

#### Recherche avancée

Le système de recherche est implémenté dans `search.php` avec :
- Recherche par titre, description, catégorie et tags
- Filtrage par type de document, date et popularité
- Pagination des résultats

#### Navigation et découverte

La page d'accueil (`index.php`) présente :
- Documents récents
- Catégories populaires
- Statistiques générales
- Interface responsive adaptée à tous les appareils

### Interaction sociale

#### Commentaires

Le système de commentaires est implémenté dans `comment.php` avec :
- Ajout de commentaires sur les documents
- Affichage des commentaires existants
- Suppression de commentaires (pour les modérateurs)

#### Notation

Le système de notation est implémenté dans `rate.php` avec :
- Attribution de notes de 1 à 5 étoiles
- Calcul de la moyenne des notes
- Affichage des statistiques de notation

## Description des modules

Cette section présente une description détaillée des différents modules de la plateforme, accompagnée de captures d'écran pour illustrer leur fonctionnement.

### Module d'authentification

Le module d'authentification gère l'inscription, la connexion et la déconnexion des utilisateurs.

#### Page de connexion
![Page de connexion](captures/auth/login.png)

La page de connexion permet aux utilisateurs de s'authentifier avec leur email et mot de passe. Elle inclut également un lien vers la page d'inscription pour les nouveaux utilisateurs.

#### Page d'inscription
![Page d'inscription](captures/auth/register.png)

La page d'inscription permet aux nouveaux utilisateurs de créer un compte en fournissant leurs informations personnelles (nom, prénom, email, mot de passe).

### Module de gestion des documents

Ce module permet aux utilisateurs de créer, modifier, supprimer et télécharger des documents.

#### Création de document
![Création de document](captures/documents/create.png)

Le formulaire de création de document permet aux utilisateurs de télécharger un nouveau document, de spécifier son titre, sa description, sa catégorie et ses tags.

#### Liste des documents
![Liste des documents](captures/documents/list.png)

La page de liste des documents affiche tous les documents disponibles, avec des options de filtrage par catégorie, type et statut.

#### Détail d'un document
![Détail d'un document](captures/documents/detail.png)

La page de détail d'un document affiche toutes les informations relatives au document, ainsi que les options pour le télécharger, le modifier ou le supprimer.

### Module de recherche

Le module de recherche permet aux utilisateurs de trouver rapidement des documents.

#### Page de recherche
![Page de recherche](captures/search/search.png)

La page de recherche offre des options avancées pour filtrer les résultats par titre, description, catégorie et tags.

#### Résultats de recherche
![Résultats de recherche](captures/search/results.png)

Les résultats de recherche sont affichés de manière claire, avec des informations sur chaque document et des options de tri.

### Module d'administration

Le module d'administration permet aux administrateurs de gérer les utilisateurs, les documents, les catégories et les tags.

#### Tableau de bord administrateur
![Tableau de bord administrateur](captures/admin/dashboard.png)

Le tableau de bord administrateur affiche des statistiques générales sur l'utilisation de la plateforme, comme le nombre d'utilisateurs, de documents et de téléchargements.

#### Gestion des utilisateurs
![Gestion des utilisateurs](captures/admin/users.png)

L'interface de gestion des utilisateurs permet aux administrateurs de voir la liste complète des utilisateurs, de filtrer par nom et rôle, et de modifier ou supprimer des utilisateurs.

#### Gestion des documents
![Gestion des documents](captures/admin/documents.png)

L'interface de gestion des documents permet aux administrateurs de voir tous les documents, de filtrer par statut, catégorie et type, et de modifier ou supprimer des documents.

#### Modération des documents
![Modération des documents](captures/admin/moderation.png)

L'interface de modération permet aux administrateurs d'approuver ou de rejeter les documents en attente, avec la possibilité de fournir une justification en cas de rejet.

#### Gestion des catégories
![Gestion des catégories](captures/admin/categories.png)

L'interface de gestion des catégories permet aux administrateurs de créer, modifier et supprimer des catégories, ainsi que d'organiser la hiérarchie des catégories.

#### Gestion des tags
![Gestion des tags](captures/admin/tags.png)

L'interface de gestion des tags permet aux administrateurs de créer, modifier et supprimer des tags, ainsi que de les associer aux documents.

### Module de profil utilisateur

Le module de profil utilisateur permet aux utilisateurs de gérer leurs informations personnelles et de voir leurs documents.

#### Profil utilisateur
![Profil utilisateur](captures/profile/profile.png)

La page de profil affiche les informations personnelles de l'utilisateur et permet de les modifier.

#### Documents de l'utilisateur
![Documents de l'utilisateur](captures/profile/my-documents.png)

La page "Mes documents" affiche tous les documents créés par l'utilisateur connecté, avec leur statut et des options pour les modifier ou les supprimer.

### Module d'interaction sociale

Ce module permet aux utilisateurs d'interagir avec les documents via des commentaires et des notations.

#### Commentaires
![Commentaires](captures/social/comments.png)

La section des commentaires permet aux utilisateurs de voir les commentaires existants sur un document et d'ajouter leurs propres commentaires.

#### Notation
![Notation](captures/social/rating.png)

Le système de notation permet aux utilisateurs d'évaluer les documents de 1 à 5 étoiles et de voir la moyenne des notes.

### Interface responsive

L'interface de la plateforme est responsive et s'adapte à tous les appareils.

#### Version desktop
![Version desktop](captures/responsive/desktop.png)

La version desktop de la plateforme offre une expérience complète avec toutes les fonctionnalités.

#### Version tablette
![Version tablette](captures/responsive/tablet.png)

La version tablette de la plateforme adapte l'interface pour les écrans de taille moyenne.

#### Version mobile
![Version mobile](captures/responsive/mobile.png)

La version mobile de la plateforme optimise l'interface pour les petits écrans, avec un menu hamburger et des éléments redimensionnés.

## Interface utilisateur

### Design et ergonomie

L'interface utilisateur est conçue avec Bootstrap 5 pour :
- Assurer une expérience responsive sur tous les appareils
- Offrir une navigation intuitive
- Présenter les informations de manière claire et organisée
- S'adapter automatiquement aux différentes tailles d'écran

### Composants principaux

1. **En-tête** (`views/header.php`)
   - Logo et nom du site
   - Menu de navigation principal
   - Barre de recherche
   - Liens de connexion/inscription ou menu utilisateur

2. **Pied de page** (`views/footer.php`)
   - Liens vers les pages importantes
   - Informations de copyright
   - Liens vers les réseaux sociaux

3. **Tableaux de bord**
   - Tableau de bord utilisateur avec statistiques personnelles
   - Tableau de bord administrateur avec statistiques globales
   - Interface responsive pour tous les appareils

4. **Formulaires**
   - Formulaires d'inscription et de connexion
   - Formulaire d'upload de documents
   - Formulaires de modification de profil
   - Formulaires de gestion des catégories et tags

### Expérience utilisateur

L'expérience utilisateur est optimisée pour :
- Minimiser le nombre d'étapes pour accomplir les tâches courantes
- Fournir des retours visuels clairs sur les actions
- Gérer les erreurs de manière élégante avec des messages informatifs
- S'adapter aux différentes tailles d'écran (desktop, tablette, mobile)

### Interface d'administration

L'interface d'administration a été améliorée avec :
- Tableaux de bord intuitifs pour la gestion des utilisateurs, documents, catégories et tags
- Formulaires de recherche et de filtrage pour faciliter la navigation
- Statistiques détaillées sur l'utilisation de la plateforme
- Système de modération complet avec approbation et rejet de documents
- Interface responsive adaptée à tous les appareils

## Sécurité

### Protection des données

- Utilisation de PDO avec des requêtes préparées pour éviter les injections SQL
- Validation et assainissement des entrées utilisateur
- Protection contre les attaques XSS avec `htmlspecialchars()`

### Gestion des sessions

- Configuration sécurisée des sessions PHP
- Régénération des ID de session pour éviter les attaques de fixation de session
- Nettoyage des sessions expirées

### Contrôle d'accès

- Vérification des droits d'accès pour chaque page
- Redirection vers la page de connexion pour les utilisateurs non authentifiés
- Restriction d'accès aux fonctionnalités d'administration
- Vérification des rôles pour les actions sensibles

### Sécurité des fichiers

- Validation des types de fichiers uploadés
- Génération de noms de fichiers uniques
- Stockage des fichiers en dehors du répertoire web public

## Tests et validation

### Tests unitaires

- Tests des fonctions utilitaires
- Tests des requêtes de base de données
- Tests de validation des formulaires

### Tests d'intégration

- Tests des flux de données complets
- Tests des interactions entre les différents modules
- Tests de performance sous charge

### Tests utilisateurs

- Tests avec des utilisateurs réels
- Collecte de retours et suggestions
- Itérations basées sur les retours utilisateurs
- Tests de l'interface responsive sur différents appareils

## Déploiement

### Prérequis

- Serveur web Apache/Nginx
- PHP 8.x
- MySQL 8.0
- Extensions PHP requises (PDO, GD, etc.)

### Processus de déploiement

1. Configuration du serveur web
2. Installation des dépendances
3. Configuration de la base de données
4. Configuration des paramètres de l'application
5. Vérification des permissions des dossiers
6. Tests post-déploiement

### Environnements

- **Développement** : Environnement local pour le développement
- **Test** : Environnement de test pour la validation
- **Production** : Environnement de production pour les utilisateurs finaux

## Maintenance et évolutions futures

### Maintenance

- Surveillance régulière des logs d'erreurs
- Sauvegardes périodiques de la base de données
- Mises à jour de sécurité

### Évolutions futures

1. **Fonctionnalités additionnelles**
   - Système de notifications par email
   - Application mobile
   - Intégration avec d'autres plateformes éducatives
   - Système de chat entre utilisateurs

2. **Améliorations techniques**
   - Optimisation des performances
   - Refactoring du code pour une meilleure maintenabilité
   - Migration vers un framework PHP moderne
   - Amélioration de l'interface responsive

3. **Améliorations utilisateur**
   - Interface utilisateur plus intuitive
   - Fonctionnalités de collaboration avancées
   - Personnalisation accrue des profils
   - Tableaux de bord personnalisables

## Difficultés rencontrées et solutions

Au cours du développement de la plateforme, plusieurs difficultés techniques et organisationnelles ont été rencontrées. Cette section présente les principaux défis et les solutions apportées.

### Difficultés techniques

#### 1. Gestion des fichiers uploadés

**Problème** : La gestion des fichiers uploadés par les utilisateurs présentait plusieurs défis, notamment la validation des types de fichiers, la limitation de la taille et la sécurisation du stockage.

**Solution** : 
- Mise en place d'une validation stricte des types de fichiers autorisés (PDF, DOC, DOCX, etc.)
- Limitation de la taille des fichiers à 10 Mo maximum
- Génération de noms de fichiers uniques pour éviter les conflits
- Stockage des fichiers en dehors du répertoire web public pour plus de sécurité
- Création d'un système de nettoyage périodique des fichiers orphelins

#### 2. Performance de la base de données

**Problème** : Avec l'augmentation du nombre de documents et d'utilisateurs, les requêtes de base de données devenaient de plus en plus lentes, particulièrement pour les recherches complexes.

**Solution** :
- Optimisation des requêtes SQL avec des index appropriés
- Mise en place d'un système de pagination pour limiter le nombre de résultats affichés
- Utilisation de requêtes préparées pour améliorer les performances et la sécurité
- Mise en cache des résultats de recherche fréquents
- Structuration de la base de données pour minimiser les jointures complexes

#### 3. Interface responsive

**Problème** : L'adaptation de l'interface à différentes tailles d'écran (desktop, tablette, mobile) s'est avérée complexe, particulièrement pour les tableaux de données et les formulaires.

**Solution** :
- Utilisation systématique des classes responsives de Bootstrap 5
- Création de versions alternatives des tableaux pour les petits écrans
- Simplification des formulaires sur mobile
- Mise en place d'un menu hamburger pour la navigation sur mobile
- Tests réguliers sur différents appareils pour valider l'expérience utilisateur

#### 4. Sécurité des données

**Problème** : La protection des données utilisateurs et des documents contre les attaques (injection SQL, XSS, CSRF) était une préoccupation majeure.

**Solution** :
- Utilisation de PDO avec des requêtes préparées pour éviter les injections SQL
- Validation et assainissement de toutes les entrées utilisateur
- Protection contre les attaques XSS avec `htmlspecialchars()`
- Mise en place de tokens CSRF pour les formulaires
- Chiffrement des mots de passe avec des algorithmes robustes
- Gestion sécurisée des sessions avec régénération des ID

### Difficultés organisationnelles

#### 1. Coordination de l'équipe

**Problème** : La coordination entre les membres de l'équipe pour le développement de fonctionnalités interdépendantes a été un défi.

**Solution** :
- Mise en place de réunions régulières pour synchroniser le travail
- Utilisation de Git pour la gestion de versions et la collaboration
- Documentation claire des interfaces entre les différents modules
- Création d'un tableau de suivi des tâches partagé
- Définition de standards de code communs

#### 2. Gestion des délais

**Problème** : Certaines fonctionnalités ont pris plus de temps que prévu à développer, notamment l'interface d'administration et le système de modération.

**Solution** :
- Révision des priorités pour se concentrer sur les fonctionnalités essentielles
- Décomposition des tâches complexes en sous-tâches plus petites et gérables
- Mise en place d'un système de suivi des progrès quotidien
- Identification précoce des risques et des goulots d'étranglement
- Ajustement des objectifs en fonction des retours utilisateurs

#### 3. Tests et validation

**Problème** : La validation complète de l'application, particulièrement pour les cas d'utilisation complexes, était difficile à réaliser.

**Solution** :
- Création d'un plan de test détaillé couvrant tous les scénarios d'utilisation
- Mise en place de tests automatisés pour les fonctionnalités critiques
- Organisation de sessions de test avec des utilisateurs réels
- Collecte systématique des retours et des bugs signalés
- Itérations rapides pour corriger les problèmes identifiés

### Leçons apprises

Le développement de cette plateforme a permis d'acquérir plusieurs leçons importantes :

1. **Planification préalable** : Une analyse approfondie des besoins et une architecture bien pensée sont essentielles pour éviter les problèmes ultérieurs.

2. **Itérations courtes** : Des cycles de développement courts avec des retours utilisateurs fréquents permettent d'identifier et de corriger les problèmes plus rapidement.

3. **Documentation continue** : La documentation du code et des décisions techniques doit être maintenue tout au long du projet.

4. **Tests précoces** : L'intégration de tests dès le début du développement permet d'éviter l'accumulation de bugs.

5. **Flexibilité** : La capacité à adapter le plan initial en fonction des retours et des difficultés rencontrées est cruciale pour le succès du projet.

Ces difficultés et leurs solutions ont contribué à améliorer la qualité finale de la plateforme et à enrichir l'expérience de l'équipe de développement.

## Conclusion

La Plateforme de Partage de Cours INPHB est une application web complète qui répond aux besoins de partage de ressources académiques entre les étudiants et les enseignants de l'INPHB. Elle offre une interface intuitive, des fonctionnalités avancées de gestion des documents et un système de modération pour garantir la qualité du contenu.

Le projet a été développé avec les technologies modernes (PHP 8.x, MySQL 8.0, Bootstrap 5) et suit les bonnes pratiques de développement web. L'architecture modulaire facilite la maintenance et l'évolution future du système.

Les améliorations récentes incluent une interface d'administration complète et intuitive, un système de rejet de documents avec justification, et une interface responsive adaptée à tous les appareils. Ces fonctionnalités améliorent significativement l'expérience utilisateur et facilitent la gestion de la plateforme.

Les tests utilisateurs ont montré une satisfaction générale avec l'interface et les fonctionnalités offertes. Les retours ont été intégrés dans les itérations successives du projet pour améliorer l'expérience utilisateur.

Le projet est maintenant prêt pour le déploiement en production et continuera à évoluer en fonction des besoins des utilisateurs et des retours d'expérience. 