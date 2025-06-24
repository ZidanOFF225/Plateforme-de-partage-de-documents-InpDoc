<?php
require_once 'config/config.php';

// Vérification de l'authentification
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Vous devez être connecté pour commenter.']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données
$document_id = filter_var($_POST['document_id'] ?? null, FILTER_VALIDATE_INT);
$contenu = trim($_POST['contenu'] ?? '');

// Validation des données
if (!$document_id || empty($contenu)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

try {
    // Vérification de l'existence du document
    $stmt = $conn->prepare("SELECT id FROM documents WHERE id = ? AND statut = 'approuve'");
    $stmt->execute([$document_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Document non trouvé ou non approuvé');
    }

    // Insertion du commentaire
    $stmt = $conn->prepare("
        INSERT INTO commentaires (document_id, user_id, contenu)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$document_id, $_SESSION['user_id'], $contenu]);

    // Réponse JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Commentaire ajouté avec succès'
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 