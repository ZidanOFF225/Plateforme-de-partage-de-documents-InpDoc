<?php
require_once 'config/config.php';

try {
    // Vérifier si l'admin existe déjà
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@inphb.ci']);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Créer l'administrateur
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, nom, prenoms, role, date_creation) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'admin@inphb.ci',
            password_hash('admin', PASSWORD_DEFAULT),
            'Admin',
            'INPHB',
            'admin'
        ]);
        
        echo "Compte administrateur créé avec succès!\n";
        echo "Email: admin@inphb.ci\n";
        echo "Mot de passe: admin\n";
    } else {
        echo "Un compte administrateur existe déjà avec cet email.\n";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 