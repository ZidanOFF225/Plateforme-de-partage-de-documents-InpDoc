<?php
require_once 'config/config.php';

try {
    // Mettre à jour le mot de passe de l'admin
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
    
    $new_password = password_hash('admin', PASSWORD_DEFAULT);
    $stmt->execute([$new_password, 'admin@inphb.ci']);
    
    if ($stmt->rowCount() > 0) {
        echo "Le mot de passe de l'administrateur a été réinitialisé avec succès!\n";
        echo "Email: admin@inphb.ci\n";
        echo "Nouveau mot de passe: admin\n";
    } else {
        echo "Aucun compte administrateur trouvé avec cet email.\n";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 