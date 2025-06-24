<?php
require_once 'config/config.php';

// Vérification de l'authentification
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Vous devez être connecté pour noter.']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Si les données ne sont pas en JSON, essayer les données POST
if (!$input) {
    $document_id = filter_var($_POST['document_id'] ?? null, FILTER_VALIDATE_INT);
    $note = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
} else {
    $document_id = filter_var($input['document_id'] ?? null, FILTER_VALIDATE_INT);
    $note = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
}

// Validation des données
if (!$document_id || !$note || $note < 1 || $note > 5) {
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

    // Vérification si l'utilisateur a déjà noté
    $stmt = $conn->prepare("SELECT id FROM notes WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        // Mise à jour de la note existante
        $stmt = $conn->prepare("UPDATE notes SET note = ? WHERE document_id = ? AND user_id = ?");
        $stmt->execute([$note, $document_id, $_SESSION['user_id']]);
    } else {
        // Insertion d'une nouvelle note
        $stmt = $conn->prepare("INSERT INTO notes (document_id, user_id, note) VALUES (?, ?, ?)");
        $stmt->execute([$document_id, $_SESSION['user_id'], $note]);
    }

    // Calcul de la nouvelle moyenne
    $stmt = $conn->prepare("
        SELECT AVG(note) as moyenne, COUNT(*) as total
        FROM notes
        WHERE document_id = ?
    ");
    $stmt->execute([$document_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Réponse JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'moyenne' => round($result['moyenne'], 1),
        'total' => $result['total']
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 