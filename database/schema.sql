-- Base de données pour la plateforme de partage de documents INPHB
CREATE DATABASE IF NOT EXISTS inphb_docs;
USE inphb_docs;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricule VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prenoms VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('etudiant', 'enseignant', 'admin') DEFAULT 'etudiant',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME
);

-- Table des catégories de documents
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table des documents
CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    type_document ENUM('cours', 'td', 'tp', 'examen', 'correction', 'autre') NOT NULL,
    categorie_id INT,
    user_id INT,
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    nb_telechargements INT DEFAULT 0,
    statut ENUM('en_attente', 'approuve', 'rejete') DEFAULT 'en_attente',
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des commentaires
CREATE TABLE commentaires (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT,
    user_id INT,
    contenu TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des notes
CREATE TABLE notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT,
    user_id INT,
    note INT CHECK (note BETWEEN 1 AND 5),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_note (document_id, user_id)
);

-- Table des tags
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL UNIQUE
);

-- Table de liaison documents-tags
CREATE TABLE document_tags (
    document_id INT,
    tag_id INT,
    PRIMARY KEY (document_id, tag_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
); 