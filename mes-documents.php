<?php
require_once 'config/config.php';

// Vérification de l'authentification
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['danger' => 'Vous devez être connecté pour accéder à cette page'];
    redirect('/auth/login.php');
}

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$status = isset($_GET['status']) ? $_GET['status'] : '';
$categorie = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

try {
    // Construction de la requête de base
    $where = ['d.user_id = ?'];
    $params = [$_SESSION['user_id']];

    if ($status) {
        $where[] = 'd.statut = ?';
        $params[] = $status;
    }
    if ($categorie) {
        $where[] = 'd.categorie_id = ?';
        $params[] = $categorie;
    }
    if ($type) {
        $where[] = 'd.type_document = ?';
        $params[] = $type;
    }

    $whereClause = implode(' AND ', $where);

    // Récupération du nombre total de documents
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM documents d 
        WHERE $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Calcul du nombre de pages
    $pages = ceil($total / $limit);
    $page = min($pages, $page);

    // Récupération des documents
    $stmt = $conn->prepare("
        SELECT d.*, c.nom as categorie_nom,
               (SELECT COUNT(*) FROM notes WHERE document_id = d.id) as nb_notes,
               (SELECT AVG(note) FROM notes WHERE document_id = d.id) as moyenne_notes
        FROM documents d
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE $whereClause
        ORDER BY d.date_upload DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des catégories pour le filtre
    $stmt = $conn->query("SELECT id, nom FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Afficher l'erreur directement au lieu de rediriger
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4>Erreur lors de la récupération des documents</h4>';
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Mes documents</h1>
        <a href="create-document.php" class="btn btn-primary">
            <i class="fas fa-upload"></i> Partager un document
        </a>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous</option>
                        <option value="en_attente" <?= $status === 'en_attente' ? 'selected' : '' ?>>
                            En attente
                        </option>
                        <option value="approuve" <?= $status === 'approuve' ? 'selected' : '' ?>>
                            Approuvé
                        </option>
                        <option value="rejete" <?= $status === 'rejete' ? 'selected' : '' ?>>
                            Rejeté
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="categorie" class="form-label">Catégorie</label>
                    <select class="form-select" id="categorie" name="categorie">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categorie == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Tous</option>
                        <option value="cours" <?= $type === 'cours' ? 'selected' : '' ?>>Cours</option>
                        <option value="td" <?= $type === 'td' ? 'selected' : '' ?>>TD</option>
                        <option value="tp" <?= $type === 'tp' ? 'selected' : '' ?>>TP</option>
                        <option value="examen" <?= $type === 'examen' ? 'selected' : '' ?>>Examen</option>
                        <option value="correction" <?= $type === 'correction' ? 'selected' : '' ?>>Correction</option>
                        <option value="autre" <?= $type === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($documents)): ?>
        <div class="alert alert-info">
            Aucun document ne correspond à vos critères de recherche.
        </div>
    <?php else: ?>
        <!-- Liste des documents -->
        <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
            <?php foreach ($documents as $doc): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1">
                                    <a href="documents.php?id=<?= $doc['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($doc['titre']) ?>
                                    </a>
                                </h5>
                                <span class="badge <?= getStatusBadgeClass($doc['statut']) ?>">
                                    <?= getStatusLabel($doc['statut']) ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-2">
                                <?= htmlspecialchars($doc['categorie_nom']) ?> | 
                                <?= strtoupper($doc['type_document']) ?> |
                                <?= date('d/m/Y', strtotime($doc['date_upload'])) ?>
                            </p>
                            <p class="card-text mb-3">
                                <?= nl2br(htmlspecialchars(substr($doc['description'], 0, 150))) ?>...
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($doc['nb_notes'] > 0): ?>
                                        <span class="me-2">
                                            <i class="fas fa-star text-warning"></i>
                                            <?= number_format($doc['moyenne_notes'], 1) ?>/5
                                            (<?= $doc['nb_notes'] ?>)
                                        </span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fas fa-download"></i>
                                        <?= $doc['nb_telechargements'] ?>
                                    </span>
                                </div>
                                <div class="btn-group">
                                    <a href="documents.php?id=<?= $doc['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <?php if ($doc['statut'] === 'en_attente'): ?>
                                        <a href="edit-document.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <nav aria-label="Navigation des pages">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= $status ?>&categorie=<?= $categorie ?>&type=<?= $type ?>">
                                Précédent
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page-2); $i <= min($pages, $page+2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&categorie=<?= $categorie ?>&type=<?= $type ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= $status ?>&categorie=<?= $categorie ?>&type=<?= $type ?>">
                                Suivant
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'approuve':
            return 'bg-success';
        case 'en_attente':
            return 'bg-warning text-dark';
        case 'rejete':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'approuve':
            return 'Approuvé';
        case 'en_attente':
            return 'En attente';
        case 'rejete':
            return 'Rejeté';
        default:
            return 'Inconnu';
    }
}
?>

<?php include 'views/footer.php'; ?> 