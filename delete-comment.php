<?php
require_once 'config/config.php';

// Vérification de l'authentification
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['danger' => 'Vous devez être connecté pour effectuer cette action'];
    redirect('/');
}

// Récupération des paramètres
$comment_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$document_id = filter_var($_GET['document_id'] ?? null, FILTER_VALIDATE_INT);

if (!$comment_id || !$document_id) {
    $_SESSION['flash'] = ['danger' => 'Paramètres invalides'];
    redirect('/');
}

try {
    // Vérification que l'utilisateur est autorisé à supprimer ce commentaire
    $stmt = $conn->prepare("SELECT user_id FROM commentaires WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment_user_id = $stmt->fetchColumn();

    if (!$comment_user_id) {
        $_SESSION['flash'] = ['danger' => 'Commentaire non trouvé'];
        redirect('/');
    }

    if ($comment_user_id != $_SESSION['user_id'] && !isAdmin()) {
        $_SESSION['flash'] = ['danger' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire'];
        redirect('/');
    }

    // Suppression du commentaire
    $stmt = $conn->prepare("DELETE FROM commentaires WHERE id = ?");
    $stmt->execute([$comment_id]);

    $_SESSION['flash'] = ['success' => 'Commentaire supprimé avec succès'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la suppression du commentaire'];
}

// Redirection vers la page du document
redirect('/documents.php?id=' . $document_id);
?> 