<?php
require_once 'config/config.php';

// Vérification de l'ID du document
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash'] = ['danger' => 'Document invalide'];
    redirect('/');
}

try {
    // Récupération du document avec ses informations associées
    $stmt = $conn->prepare("
        SELECT d.*, u.nom, u.prenoms, c.nom as categorie_nom,
               (SELECT COUNT(*) FROM notes WHERE document_id = d.id) as nb_notes,
               (SELECT AVG(note) FROM notes WHERE document_id = d.id) as moyenne_notes
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE d.id = ? AND (d.statut = 'approuve' OR d.user_id = ?)
    ");
    $stmt->execute([$id, isLoggedIn() ? $_SESSION['user_id'] : 0]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        $_SESSION['flash'] = ['danger' => 'Document non trouvé'];
        redirect('/');
    }

    // Récupération des tags
    $stmt = $conn->prepare("
        SELECT t.nom
        FROM tags t
        JOIN document_tags dt ON t.id = dt.tag_id
        WHERE dt.document_id = ?
    ");
    $stmt->execute([$id]);
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Récupération des commentaires
    $stmt = $conn->prepare("
        SELECT c.*, u.nom, u.prenoms
        FROM commentaires c
        JOIN users u ON c.user_id = u.id
        WHERE c.document_id = ?
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute([$id]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérification si l'utilisateur a déjà noté
    $note_utilisateur = null;
    if (isLoggedIn()) {
        $stmt = $conn->prepare("SELECT note FROM notes WHERE document_id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $note_utilisateur = $stmt->fetchColumn();
    }

    // Incrémentation du compteur de téléchargements si demandé
    if (isset($_GET['download'])) {
        // Vérifier si l'utilisateur est connecté
        if (!isLoggedIn()) {
            $_SESSION['flash'] = ['warning' => 'Vous devez être connecté pour télécharger un document'];
            redirect('auth/login.php');
        }
        
        // Vérification si l'utilisateur a déjà téléchargé ce document récemment
        $download_key = 'document_' . $id . '_downloaded';
        $download_time = $_SESSION[$download_key] ?? 0;
        $current_time = time();
        
        // Si le dernier téléchargement date de plus d'une heure ou n'a jamais été téléchargé
        if ($current_time - $download_time > 3600) {
            $stmt = $conn->prepare("UPDATE documents SET nb_telechargements = nb_telechargements + 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            // Mise à jour du timestamp de téléchargement
            $_SESSION[$download_key] = $current_time;
        }

        // Redirection vers le fichier
        $file_path = UPLOAD_DIR . $document['filename'];
        if (file_exists($file_path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($document['filename']) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }

} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération du document'];
    redirect('/');
}

include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?= htmlspecialchars($document['titre']) ?></h4>
                    <?php if (isLoggedIn() && ($document['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                        <div class="btn-group">
                            <?php if (isset($document['statut']) && $document['statut'] === 'en_attente'): ?>
                                <a href="edit-document.php?id=<?= $document['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                            <?php endif; ?>
                            <a href="delete-document.php?id=<?= $document['id'] ?>" class="btn btn-sm btn-outline-danger delete-confirm">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted">
                            <?= htmlspecialchars($document['categorie_nom']) ?> | 
                            <?= htmlspecialchars($document['type_document']) ?>
                        </h6>
                        <p class="mb-0">
                            Par <?= htmlspecialchars($document['nom'] . ' ' . $document['prenoms']) ?><br>
                            Ajouté le <?= date('d/m/Y à H:i', strtotime($document['date_upload'])) ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($document['description'])) ?></p>
                    </div>

                    <?php if (!empty($tags)): ?>
                        <div class="mb-3">
                            <h5>Tags</h5>
                            <?php foreach ($tags as $tag): ?>
                                <a href="search.php?tag=<?= urlencode($tag) ?>" class="badge bg-secondary text-decoration-none">
                                    <?= htmlspecialchars($tag) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-download"></i> <?= $document['nb_telechargements'] ?> téléchargements
                            <?php if ($document['nb_notes'] > 0): ?>
                                <span class="ms-3">
                                    <i class="fas fa-star text-warning"></i> 
                                    <?= number_format($document['moyenne_notes'], 1) ?>/5 
                                    (<?= $document['nb_notes'] ?> notes)
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <a href="?id=<?= $document['id'] ?>&download=1" class="btn btn-primary">
                                <i class="fas fa-download"></i> Télécharger
                            </a>
                        <?php else: ?>
                            <a href="auth/login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt"></i> Connectez-vous pour télécharger
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (isLoggedIn()): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Noter ce document</h5>
                    </div>
                    <div class="card-body">
                        <form action="rate.php" method="POST" class="rating-form">
                            <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                            <div class="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?= $i ?>" 
                                           class="rating-input" data-document-id="<?= $document['id'] ?>"
                                           id="rating-<?= $i ?>" <?= $note_utilisateur == $i ? 'checked' : '' ?>>
                                    <label for="rating-<?= $i ?>">☆</label>
                                <?php endfor; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ajouter un commentaire</h5>
                    </div>
                    <div class="card-body">
                        <form action="comment.php" method="POST" class="comment-form">
                            <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                            <div class="mb-3">
                                <textarea class="form-control" name="contenu" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Commenter</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Commentaires (<?= count($commentaires) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($commentaires)): ?>
                        <p class="text-muted">Aucun commentaire pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($commentaires as $commentaire): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong>
                                        <?= htmlspecialchars($commentaire['nom'] . ' ' . $commentaire['prenoms']) ?>
                                    </strong>
                                    <div>
                                        <small class="text-muted">
                                            <?= date('d/m/Y à H:i', strtotime($commentaire['date_creation'])) ?>
                                        </small>
                                        <?php if (isLoggedIn() && ($commentaire['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
                                            <a href="delete-comment.php?id=<?= $commentaire['id'] ?>&document_id=<?= $document['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger ms-2 delete-confirm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($commentaire['contenu'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Documents similaires</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Récupération des documents similaires
                    $stmt = $conn->prepare("
                        SELECT d.*, u.nom, u.prenoms
                        FROM documents d
                        JOIN users u ON d.user_id = u.id
                        WHERE d.categorie_id = ? 
                        AND d.id != ? 
                        AND d.statut = 'approuve'
                        ORDER BY d.date_upload DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$document['categorie_id'], $document['id']]);
                    $documents_similaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (empty($documents_similaires)): ?>
                        <p class="text-muted">Aucun document similaire trouvé.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($documents_similaires as $doc): ?>
                                <a href="documents.php?id=<?= $doc['id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($doc['titre']) ?></h6>
                                        <small class="text-muted"><?= $doc['type_document'] ?></small>
                                    </div>
                                    <small>
                                        Par <?= htmlspecialchars($doc['nom'] . ' ' . $doc['prenoms']) ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating input {
    display: none;
}

.rating label {
    cursor: pointer;
    font-size: 30px;
    color: #ddd;
    padding: 5px;
}

.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #ffc107;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('.rating-input');
    
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            const formData = new FormData(form);
            
            fetch('rate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Erreur serveur: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mise à jour de l'affichage de la moyenne
                    const ratingDisplay = document.querySelector('.rating-display');
                    if (ratingDisplay) {
                        ratingDisplay.innerHTML = `
                            <i class="fas fa-star text-warning"></i> 
                            ${data.moyenne}/5 
                            (${data.total} notes)
                        `;
                    }
                } else {
                    throw new Error(data.error || 'Erreur lors de la notation');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la notation: ' + error.message);
            });
        });
    });

    // Gestion du formulaire de commentaire
    const commentForm = document.querySelector('.comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('comment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès
                    const toast = document.createElement('div');
                    toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');
                    toast.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body">
                                ${data.message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.show();

                    // Attendre un court instant avant de recharger la page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.error || 'Erreur lors de l\'ajout du commentaire');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout du commentaire: ' + error.message);
            });
        });
    }
});
</script>

<?php include 'views/footer.php'; ?> 