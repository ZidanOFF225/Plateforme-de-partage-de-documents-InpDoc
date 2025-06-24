<?php
require_once 'config/config.php';

try {
    // Lecture du fichier SQL
    $sql = file_get_contents('create_tables.sql');
    
    // Exécution des requêtes SQL
    $conn->exec($sql);
    
    echo "Base de données créée avec succès !\n";
    
    // Exécution du script de remplissage des données
    require_once 'seed_database.php';
    
} catch (PDOException $e) {
    echo "Erreur lors de la création de la base de données : " . $e->getMessage() . "\n";
} 