<?php
require_once 'config/config.php';

// Récupération des paramètres de recherche
$q = trim($_GET['q'] ?? '');
$categorie = filter_var($_GET['categorie'] ?? null, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? '';
$tag = trim($_GET['tag'] ?? '');
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$per_page = 12;

try {
    // Construction de la requête de base
    $query = "
        SELECT DISTINCT d.*, u.nom, u.prenoms, c.nom as categorie_nom,
               (SELECT COUNT(*) FROM notes WHERE document_id = d.id) as nb_notes,
               (SELECT AVG(note) FROM notes WHERE document_id = d.id) as moyenne_notes
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.categorie_id = c.id
    ";

    $params = [];
    $conditions = ["d.statut = 'approuve'"];

    // Ajout des conditions de recherche
    if (!empty($q)) {
        $conditions[] = "(d.titre LIKE ? OR d.description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    if ($categorie) {
        $conditions[] = "d.categorie_id = ?";
        $params[] = $categorie;
    }

    if (!empty($type)) {
        $conditions[] = "d.type_document = ?";
        $params[] = $type;
    }

    if (!empty($tag)) {
        $query .= " LEFT JOIN document_tags dt ON d.id = dt.document_id
                   LEFT JOIN tags t ON dt.tag_id = t.id";
        $conditions[] = "t.nom = ?";
        $params[] = $tag;
    }

    // Assemblage de la requête
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Comptage du total de résultats
    $count_query = str_replace("DISTINCT d.*, u.nom, u.prenoms, c.nom as categorie_nom", "COUNT(DISTINCT d.id)", $query);
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_results = $stmt->fetchColumn();
    $total_pages = ceil($total_results / $per_page);

    // Ajout de l'ordre et de la pagination
    $query .= " ORDER BY d.date_upload DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = ($page - 1) * $per_page;

    // Exécution de la requête principale
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si c'est une requête AJAX
    if (!empty($_GET['ajax'])) {
        ob_start();
        if (empty($documents)): ?>
            <div class="alert alert-info">Aucun résultat trouvé.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($documents as $doc): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($doc['titre']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?= htmlspecialchars($doc['categorie_nom']) ?> | 
                                    <?= htmlspecialchars($doc['type_document']) ?>
                                </h6>
                                <p class="card-text"><?= htmlspecialchars(substr($doc['description'], 0, 100)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Par <?= htmlspecialchars($doc['nom'] . ' ' . $doc['prenoms']) ?>
                                    </small>
                                    <a href="document.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-primary">
                                        Voir plus
                                    </a>
                                </div>
                            </div>
                            <div class="card-footer text-muted">
                                <small>
                                    <?php if ($doc['nb_notes'] > 0): ?>
                                        <i class="fas fa-star text-warning"></i> 
                                        <?= number_format($doc['moyenne_notes'], 1) ?>/5
                                        (<?= $doc['nb_notes'] ?>) |
                                    <?php endif; ?>
                                    <i class="fas fa-download"></i> <?= $doc['nb_telechargements'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif;

        header('Content-Type: application/json');
        echo json_encode([
            'html' => ob_get_clean(),
            'total_pages' => $total_pages,
            'current_page' => $page
        ]);
        exit;
    }

    // Récupération des catégories pour le filtre
    $stmt = $conn->query("SELECT * FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include 'views/header.php';
} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la recherche'];
    redirect('/');
}
?>

<div class="container mt-4">
    <div class="row">
        <!-- Filtres -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filtres</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET" id="search-filters">
                        <div class="mb-3">
                            <label for="q" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="q" name="q" 
                                   value="<?= htmlspecialchars($q) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categorie == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type de document</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous les types</option>
                                <option value="cours" <?= $type === 'cours' ? 'selected' : '' ?>>Cours</option>
                                <option value="td" <?= $type === 'td' ? 'selected' : '' ?>>TD</option>
                                <option value="tp" <?= $type === 'tp' ? 'selected' : '' ?>>TP</option>
                                <option value="examen" <?= $type === 'examen' ? 'selected' : '' ?>>Examen</option>
                                <option value="correction" <?= $type === 'correction' ? 'selected' : '' ?>>Correction</option>
                                <option value="autre" <?= $type === 'autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Résultats -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>
                    <?php if (empty($q) && empty($categorie) && empty($type) && empty($tag)): ?>
                        Tous les documents
                    <?php else: ?>
                        Résultats de recherche
                        <?php if (!empty($q)): ?>
                            pour "<?= htmlspecialchars($q) ?>"
                        <?php endif; ?>
                    <?php endif; ?>
                </h4>
                <span class="text-muted"><?= $total_results ?> document(s) trouvé(s)</span>
            </div>

            <div id="search-results">
                <?php if (empty($documents)): ?>
                    <div class="alert alert-info">Aucun résultat trouvé.</div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($documents as $doc): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($doc['titre']) ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?= htmlspecialchars($doc['categorie_nom']) ?> | 
                                            <?= htmlspecialchars($doc['type_document']) ?>
                                        </h6>
                                        <p class="card-text"><?= htmlspecialchars(substr($doc['description'], 0, 100)) ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Par <?= htmlspecialchars($doc['nom'] . ' ' . $doc['prenoms']) ?>
                                            </small>
                                            <a href="document.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-primary">
                                                Voir plus
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer text-muted">
                                        <small>
                                            <?php if ($doc['nb_notes'] > 0): ?>
                                                <i class="fas fa-star text-warning"></i> 
                                                <?= number_format($doc['moyenne_notes'], 1) ?>/5
                                                (<?= $doc['nb_notes'] ?>) |
                                            <?php endif; ?>
                                            <i class="fas fa-download"></i> <?= $doc['nb_telechargements'] ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des pages" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 