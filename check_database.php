<?php
require_once 'config/config.php';

try {
    // Vérification de la connexion
    if (!isset($conn) || !$conn) {
        throw new Exception("La connexion à la base de données n'est pas établie");
    }

    // Vérification de la table documents
    $check_table = $conn->query("SHOW TABLES LIKE 'documents'");
    if ($check_table->rowCount() === 0) {
        echo "La table 'documents' n'existe pas. Création des tables...\n";
        require_once 'create_tables.sql';
        $sql = file_get_contents('create_tables.sql');
        $conn->exec($sql);
        echo "Tables créées avec succès.\n";
    }

    // Vérification du contenu de la table documents
    $check_content = $conn->query("SELECT COUNT(*) FROM documents");
    $count = $check_content->fetchColumn();
    
    if ($count === 0) {
        echo "La table 'documents' est vide. Ajout des données de test...\n";
        require_once 'seed_database.php';
        echo "Données de test ajoutées avec succès.\n";
    }

    // Vérification du statut des documents
    $check_status = $conn->query("SELECT statut, COUNT(*) as count FROM documents GROUP BY statut");
    $status_count = $check_status->fetchAll(PDO::FETCH_ASSOC);
    echo "Nombre de documents par statut :\n";
    print_r($status_count);

    // Vérification des catégories
    $check_categories = $conn->query("SELECT COUNT(*) FROM categories");
    $categories_count = $check_categories->fetchColumn();
    echo "Nombre de catégories : " . $categories_count . "\n";

    // Vérification des utilisateurs
    $check_users = $conn->query("SELECT COUNT(*) FROM users");
    $users_count = $check_users->fetchColumn();
    echo "Nombre d'utilisateurs : " . $users_count . "\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 