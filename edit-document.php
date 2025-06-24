<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';

// Vérification de l'authentification
if (!isLoggedIn()) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4>Erreur d\'authentification</h4>';
    echo '<p>Vous devez être connecté pour accéder à cette page.</p>';
    echo '</div>';
    echo '</div>';
    exit;
}

// Récupération de l'ID du document
$document_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$document_id) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4>Document non spécifié</h4>';
    echo '<p>Veuillez spécifier un ID de document valide.</p>';
    echo '</div>';
    echo '</div>';
    exit;
}

try {
    // Récupération du document
    $stmt = $conn->prepare("
        SELECT d.*, c.nom as categorie_nom 
        FROM documents d
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE d.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        echo '<div class="container mt-4">';
        echo '<div class="alert alert-danger">';
        echo '<h4>Document non trouvé ou accès non autorisé</h4>';
        echo '<p>Le document que vous recherchez n\'existe pas ou vous n\'avez pas les droits pour y accéder.</p>';
        echo '</div>';
        echo '</div>';
        exit;
    }

    // Vérification du statut
    if (isset($document['statut']) && $document['statut'] === 'approuve') {
        echo '<div class="container mt-4">';
        echo '<div class="alert alert-danger">';
        echo '<h4>Document approuvé</h4>';
        echo '<p>Les documents approuvés ne peuvent pas être modifiés.</p>';
        echo '</div>';
        echo '</div>';
        exit;
    }

    // Récupération des catégories
    $stmt = $conn->query("SELECT id, nom FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titre = trim($_POST['titre']);
        $description = trim($_POST['description']);
        $categorie_id = intval($_POST['categorie_id']);
        $tags = array_filter(array_map('trim', explode(',', $_POST['tags'])));

        // Validation
        if (empty($titre) || empty($description) || !$categorie_id) {
            throw new Exception('Tous les champs obligatoires doivent être remplis');
        }

        // Mise à jour du document
        $stmt = $conn->prepare("
            UPDATE documents 
            SET titre = ?, description = ?, categorie_id = ?, statut = 'en_attente'
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$titre, $description, $categorie_id, $document_id, $_SESSION['user_id']]);

        // Mise à jour des tags
        if (!empty($tags)) {
            // Suppression des anciens tags
            $stmt = $conn->prepare("DELETE FROM document_tags WHERE document_id = ?");
            $stmt->execute([$document_id]);

            // Ajout des nouveaux tags
            foreach ($tags as $tag) {
                // Vérification si le tag existe
                $stmt = $conn->prepare("SELECT id FROM tags WHERE nom = ?");
                $stmt->execute([$tag]);
                $tag_id = $stmt->fetchColumn();

                // Création du tag s'il n'existe pas
                if (!$tag_id) {
                    $stmt = $conn->prepare("INSERT INTO tags (nom) VALUES (?)");
                    $stmt->execute([$tag]);
                    $tag_id = $conn->lastInsertId();
                }

                // Association du tag au document
                $stmt = $conn->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$document_id, $tag_id]);
            }
        }

        $_SESSION['flash'] = ['success' => 'Document mis à jour avec succès'];
        redirect('/mes-documents.php');
    }

    // Récupération des tags du document
    $stmt = $conn->prepare("
        SELECT t.nom 
        FROM tags t
        JOIN document_tags dt ON t.id = dt.tag_id
        WHERE dt.document_id = ?
    ");
    $stmt->execute([$document_id]);
    $document_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    // Afficher l'erreur directement au lieu de rediriger
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4>Erreur lors de la récupération du document</h4>';
    echo '<p><strong>Message d\'erreur :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Code d\'erreur :</strong> ' . $e->getCode() . '</p>';
    echo '<p><strong>Fichier :</strong> ' . $e->getFile() . '</p>';
    echo '<p><strong>Ligne :</strong> ' . $e->getLine() . '</p>';
    echo '<p><strong>Trace :</strong></p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    echo '</div>';
    exit;
}

include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Modifier le document</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre *</label>
                            <input type="text" class="form-control" id="titre" name="titre" 
                                   value="<?= htmlspecialchars($document['titre']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" required><?= htmlspecialchars($document['description']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="categorie_id" class="form-label">Catégorie *</label>
                            <select class="form-select" id="categorie_id" name="categorie_id" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?= $categorie['id'] ?>" 
                                            <?= $categorie['id'] == $document['categorie_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categorie['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="tags" name="tags" 
                                   value="<?= htmlspecialchars(implode(', ', $document_tags)) ?>"
                                   placeholder="Séparez les tags par des virgules">
                            <div class="form-text">
                                Exemple : cours, informatique, programmation
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fichier</label>
                            <p class="mb-1">
                                <i class="fas fa-file"></i>
                                <?= htmlspecialchars(isset($document['filename']) ? $document['filename'] : 'Non spécifié') ?>
                                (<?= strtoupper(isset($document['type_document']) ? $document['type_document'] : 'Non spécifié') ?>)
                            </p>
                            <div class="form-text">
                                Le fichier ne peut pas être modifié. Si vous souhaitez changer de fichier, 
                                veuillez supprimer ce document et en créer un nouveau.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="mes-documents.php" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 