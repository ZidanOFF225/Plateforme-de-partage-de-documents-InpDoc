<?php
require_once '../config/config.php';

// Vérification des droits d'administration
if (!isAdmin()) {
    $_SESSION['flash'] = ['danger' => 'Accès non autorisé'];
    redirect('/');
}

// Vérification de l'ID du document
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash'] = ['danger' => 'Document invalide'];
    //redirect('/admin/documents.php');
}

try {
    // Mise à jour du statut du document
    $stmt = $conn->prepare("UPDATE documents SET statut = 'rejete' WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['success' => 'Document rejeté avec succès'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors du rejet du document'];
}

//redirect('/admin/documents.php'); 