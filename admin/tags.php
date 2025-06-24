<?php
require_once '../config/config.php';

// Vérification des droits d'administration
if (!isAdmin()) {
    $_SESSION['flash'] = ['danger' => 'Accès non autorisé'];
    redirect('/');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add':
                $nom = trim($_POST['nom']);
                $description = trim($_POST['description'] ?? '');
                
                
                if (!empty($nom)) {
                    $stmt = $conn->prepare("INSERT INTO tags (nom, description) VALUES (?, ?)");
                    $stmt->execute([$nom, $description]);
                    $_SESSION['flash'] = ['success' => 'Tag ajouté avec succès'];
                }
                break;

            case 'edit':
                $id = filter_var($_POST['tag_id'], FILTER_VALIDATE_INT);
                $nom = trim($_POST['nom']);
            
                if ($id && !empty($nom)) {
                    $stmt = $conn->prepare("UPDATE tags SET nom = ? WHERE id = ?");
                    $stmt->execute([$nom, $id]);
                    $_SESSION['flash'] = ['success' => 'Tag modifié avec succès'];
                }
                break;
                            

            case 'delete':
                $id = filter_var($_POST['tag_id'], FILTER_VALIDATE_INT);
                if ($id) {
                    $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['flash'] = ['success' => 'Tag supprimé avec succès'];
                }
                break;
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Code d'erreur pour violation de contrainte unique
            $_SESSION['flash'] = ['danger' => 'Ce tag existe déjà'];
        } else {
            $_SESSION['flash'] = ['danger' => 'Erreur lors de l\'opération'];
        }
    }
}

try {
    // Initialisation de la variable de recherche
    $q = $_GET['q'] ?? '';

    // Récupération des tags avec le nombre de documents associés
    $query = "
        SELECT t.*, 
               (SELECT COUNT(*) FROM document_tags WHERE tag_id = t.id) as nb_documents
        FROM tags t
        WHERE 1=1
    ";
    
    // Ajout du filtre de recherche si présent
    if (!empty($q)) {
        $query .= " AND t.nom LIKE ?";
        $search_param = "%$q%";
    }
    
    $query .= " ORDER BY t.nom";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($q)) {
        $stmt->execute([$search_param]);
    } else {
        $stmt->execute();
    }
    
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul du nombre total de tags
    $total_tags = count($tags);
    
    // Calcul pour la pagination
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
    $per_page = 20;
    $total_pages = ceil($total_tags / $per_page);
    
    // Pagination des tags
    $offset = ($page - 1) * $per_page;
    $tags = array_slice($tags, $offset, $per_page);

} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des tags'];
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
                    <a href="documents.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Documents
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Utilisateurs
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-folder me-2"></i> Catégories
                    </a>
                    <a href="tags.php" class="list-group-item list-group-item-action active">
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
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTagModal">
                            <i class="fas fa-plus me-2"></i> Nouveau tag
                        </button>
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
                        <h2>Gestion des tags</h2>
                        <div>
                            <span class="badge bg-primary"><?= $total_tags ?> tag(s)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des tags -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tag</th>
                                    <th>Documents</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-tag text-primary me-2"></i>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($tag['nom']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-file-alt me-1"></i> <?= $tag['nb_documents'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editTagModal"
                                                        data-id="<?= htmlspecialchars($tag['id']) ?>"
                                                        data-nom="<?= htmlspecialchars($tag['nom']) ?>"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="tag_id" value="<?= $tag['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce tag ?')"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal d'édition unique -->
            <div class="modal fade" id="editTagModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier le tag</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="tag_id" id="edit_tag_id">
                                <div class="mb-3">
                                    <label for="edit_nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="edit_nom" name="nom" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
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

<!-- Modal pour ajouter un tag -->
<div class="modal fade" id="addTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de modification
    const editModal = document.getElementById('editTagModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nom = button.getAttribute('data-nom');
            
            const modal = this;
            modal.querySelector('#edit_tag_id').value = id;
            modal.querySelector('#edit_nom').value = nom;
        });
    }
});
</script>

<?php include '../views/footer.php'; ?> 