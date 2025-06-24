<?php
require_once 'config/config.php';
require_once 'config/database.php';

try {
    // Vérifier la connexion à la base de données
    if (!isset($conn)) {
        throw new Exception("La connexion à la base de données n'est pas établie");
    }

    // Vérifier si la table categories existe
    $stmt = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($stmt->rowCount() === 0) {
        echo "La table 'categories' n'existe pas. Création...\n";
        
        // Créer la table categories
        $sql = "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        )";
        
        $conn->exec($sql);
        echo "Table 'categories' créée avec succès.\n";
    }

    // Vérifier la structure de la table
    $stmt = $conn->query("DESCRIBE categories");
    echo "\nStructure de la table 'categories' :\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']}: {$row['Type']} " . 
             ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
             ($row['Default'] ? " DEFAULT {$row['Default']}" : '') . "\n";
    }

    // Tester l'ajout d'une catégorie sans parent
    echo "\nTest d'ajout d'une catégorie sans parent :\n";
    $sql = "INSERT INTO categories (nom, description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    
    try {
        $stmt->execute(['Test Category', 'Description de test']);
        echo "Catégorie ajoutée avec succès. ID: " . $conn->lastInsertId() . "\n";
    } catch (PDOException $e) {
        echo "Erreur lors de l'ajout de la catégorie : " . $e->getMessage() . "\n";
    }

    // Afficher toutes les catégories existantes
    echo "\nCatégories existantes :\n";
    $stmt = $conn->query("SELECT id, nom, parent_id FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID: {$row['id']}, Nom: {$row['nom']}, Parent ID: " . 
             ($row['parent_id'] ? $row['parent_id'] : 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 