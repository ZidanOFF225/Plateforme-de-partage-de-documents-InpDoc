<?php
require_once '../config/config.php';

// Vérification des droits d'administration
if (!isAdmin()) {
    $_SESSION['flash'] = ['danger' => 'Accès non autorisé'];
    redirect('/inpDoc/');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $document_id = filter_var($_POST['document_id'] ?? null, FILTER_VALIDATE_INT);

    if ($document_id) {
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $conn->prepare("UPDATE documents SET statut = 'approuve' WHERE id = ?");
                    $stmt->execute([$document_id]);
                    $_SESSION['flash'] = ['success' => 'Document approuvé avec succès'];
                    break;

                case 'reject':
                    $stmt = $conn->prepare("UPDATE documents SET statut = 'rejete' WHERE id = ?");
                    $stmt->execute([$document_id]);
                    $_SESSION['flash'] = ['success' => 'Document rejeté avec succès'];
                    break;

                case 'delete':
                    // Récupération du nom du fichier
                    $stmt = $conn->prepare("SELECT filename FROM documents WHERE id = ?");
                    $stmt->execute([$document_id]);
                    $filename = $stmt->fetchColumn();

                    // Suppression du fichier physique
                    if ($filename && file_exists(UPLOAD_DIR . $filename)) {
                        unlink(UPLOAD_DIR . $filename);
                    }

                    // Suppression des enregistrements associés
                    $stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
                    $stmt->execute([$document_id]);
                    $_SESSION['flash'] = ['success' => 'Document supprimé avec succès'];
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['danger' => 'Erreur lors de l\'opération'];
        }
    }
}

// Filtres
$statut = $_GET['statut'] ?? '';
$categorie = filter_var($_GET['categorie'] ?? null, FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? '';
$q = trim($_GET['q'] ?? '');
$order = $_GET['order'] ?? '';
$auteur = filter_var($_GET['auteur'] ?? null, FILTER_VALIDATE_INT);

// Pagination
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
$per_page = 10;

try {
    // Construction de la requête de base
    $query = "
        SELECT d.*, 
               u.nom as user_nom, 
               u.prenoms as user_prenoms,
               c.nom as categorie_nom
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE 1=1
    ";
    
    // Ajout des conditions de filtrage
    $params = [];
    if (!empty($statut)) {
        $query .= " AND d.statut = ?";
        $params[] = $statut;
    }
    if ($categorie) {
        $query .= " AND d.categorie_id = ?";
        $params[] = $categorie;
    }
    if (!empty($type)) {
        $query .= " AND d.type_document = ?";
        $params[] = $type;
    }
    if (!empty($q)) {
        $query .= " AND (d.titre LIKE ? OR d.description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($auteur) {
        $query .= " AND d.user_id = ?";
        $params[] = $auteur;
    }
    
    // Ajout du tri et du filtre des téléchargements uniquement pour le bouton "Plus téléchargés"
    if ($order === 'downloads') {
        $query .= " ORDER BY d.nb_telechargements DESC";
    } else {
        $query .= " ORDER BY d.date_upload DESC";
    }
    
    // Comptage total des documents - Requête séparée et plus simple
    $count_query = "SELECT COUNT(*) FROM documents d WHERE 1=1";
    if (!empty($statut)) {
        $count_query .= " AND d.statut = ?";
    }
    if ($categorie) {
        $count_query .= " AND d.categorie_id = ?";
    }
    if (!empty($type)) {
        $count_query .= " AND d.type_document = ?";
    }
    if (!empty($q)) {
        $count_query .= " AND (d.titre LIKE ? OR d.description LIKE ?)";
    }
    if ($auteur) {
        $count_query .= " AND d.user_id = ?";
    }
    
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_documents = $stmt->fetchColumn();
    $total_pages = ceil($total_documents / $per_page);
    
    // Récupération des documents avec pagination
    $query .= " LIMIT " . (int)$per_page . " OFFSET " . (int)(($page - 1) * $per_page);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des catégories pour le filtre
    $stmt = $conn->query("SELECT * FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des auteurs pour le filtre
    $stmt = $conn->query("SELECT id, nom, prenoms FROM users ORDER BY nom, prenoms");
    $auteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des documents'];
    redirect('/admin');
}

include '../views/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Menu d'administration - Sidebar -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Administration</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                    </a>
                    <a href="documents.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-file-alt me-2"></i> Documents
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Utilisateurs
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-folder me-2"></i> Catégories
                    </a>
                    <a href="tags.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tags me-2"></i> Tags
                    </a>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Actions rapides</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?statut=en_attente" class="btn btn-warning btn-sm">
                            <i class="fas fa-clock me-2"></i> En attente
                        </a>
                        <a href="?statut=approuve" class="btn btn-success btn-sm">
                            <i class="fas fa-check me-2"></i> Approuvés
                        </a>
                        <a href="?statut=rejete" class="btn btn-danger btn-sm">
                            <i class="fas fa-times me-2"></i> Rejetés
                        </a>
                        <a href="?order=downloads" class="btn btn-info btn-sm">
                            <i class="fas fa-download me-2"></i> Plus téléchargés
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Filtres</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="q" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="q" name="q" 
                                   value="<?= htmlspecialchars($q) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous</option>
                                <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>
                                    En attente
                                </option>
                                <option value="approuve" <?= $statut === 'approuve' ? 'selected' : '' ?>>
                                    Approuvé
                                </option>
                                <option value="rejete" <?= $statut === 'rejete' ? 'selected' : '' ?>>
                                    Rejeté
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
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

                        <div class="mb-3">
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

                        <div class="mb-3">
                            <label for="auteur" class="form-label">Auteur</label>
                            <select class="form-select" id="auteur" name="auteur">
                                <option value="">Tous</option>
                                <?php foreach ($auteurs as $aut): ?>
                                    <option value="<?= $aut['id'] ?>" <?= $auteur == $aut['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($aut['nom'] . ' ' . $aut['prenoms']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="col-md-9">
            <!-- En-tête avec statistiques principales -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Gestion des documents</h2>
                        <div>
                            <span class="badge bg-primary"><?= $total_documents ?> document(s)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des documents -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;">Titre</th>
                                    <th style="min-width: 150px;">Auteur</th>
                                    <th style="min-width: 120px;">Catégorie</th>
                                    <th style="min-width: 100px;">Type</th>
                                    <th style="min-width: 100px;">Statut</th>
                                    <th style="min-width: 100px;">Date</th>
                                    <th style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-alt text-primary me-2"></i>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($doc['titre']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($doc['description']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($doc['user_nom'] . ' ' . $doc['user_prenoms']) ?></td>
                                        <td><?= htmlspecialchars($doc['categorie_nom']) ?></td>
                                        <td><?= htmlspecialchars($doc['type_document']) ?></td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <span class="badge bg-<?= $doc['statut'] === 'approuve' ? 'success' : ($doc['statut'] === 'rejete' ? 'danger' : 'warning') ?>">
                                                    <?= ucfirst($doc['statut']) ?>
                                                </span>
                                                <?php if ($order === 'downloads'): ?>
                                                <span class="badge bg-info">
                                                        <i class="fas fa-download me-1"></i> <?= $doc['nb_telechargements'] ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($doc['date_upload'])) ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionsDropdown<?= $doc['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="actionsDropdown<?= $doc['id'] ?>">
                                                    <li>
                                                        <a class="dropdown-item" href="../documents.php?id=<?= $doc['id'] ?>">
                                                            <i class="fas fa-eye me-2"></i> Voir
                                                        </a>
                                                    </li>
                                                    <?php if ($doc['statut'] === 'en_attente'): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="approve">
                                                                <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="fas fa-check me-2"></i> Approuver
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="reject">
                                                                <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-times me-2"></i> Rejeter
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?')">
                                                                <i class="fas fa-trash me-2"></i> Supprimer
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
         


            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?> 