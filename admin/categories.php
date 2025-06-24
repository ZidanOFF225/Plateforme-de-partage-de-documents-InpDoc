<?php
require_once '../config/config.php';

// Vérification des droits d'administration
if (!isAdmin()) {
    $_SESSION['flash'] = ['danger' => 'Accès non autorisé'];
    redirect('/login.php');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'add':
                $nom = trim($_POST['nom']);
                $description = trim($_POST['description']);
                $parent_id = filter_var($_POST['parent_id'] ?? null, FILTER_VALIDATE_INT);

                if (!empty($nom)) {
                    $stmt = $conn->prepare("INSERT INTO categories (nom, description, parent_id) VALUES (?, ?, ?)");
                    $stmt->execute([$nom, $description, $parent_id]);
                    $_SESSION['flash'] = ['success' => 'Catégorie ajoutée avec succès'];
                }
                break;

            case 'edit':
                $id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
                $nom = trim($_POST['nom']);
                $description = trim($_POST['description']);
                $parent_id = filter_var($_POST['parent_id'] ?? null, FILTER_VALIDATE_INT);

                if ($id && !empty($nom)) {
                    $stmt = $conn->prepare("UPDATE categories SET nom = ?, description = ?, parent_id = ? WHERE id = ?");
                    $stmt->execute([$nom, $description, $parent_id, $id]);
                    $_SESSION['flash'] = ['success' => 'Catégorie modifiée avec succès'];
                }
                break;

            case 'delete':
                $id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
                if ($id) {
                    // Vérifier si la catégorie a des documents
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM documents WHERE categorie_id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->fetchColumn() > 0) {
                        $_SESSION['flash'] = ['danger' => 'Impossible de supprimer une catégorie contenant des documents'];
                    } else {
                        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->execute([$id]);
                        $_SESSION['flash'] = ['success' => 'Catégorie supprimée avec succès'];
                    }
                }
                break;

            case 'move':
                $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
                $new_parent_id = filter_var($_POST['new_parent_id'], FILTER_VALIDATE_INT);
                
                if ($category_id) {
                    // Vérifier que la nouvelle catégorie parente n'est pas un descendant
                    if ($new_parent_id) {
                        $stmt = $conn->prepare("WITH RECURSIVE descendants AS (
                            SELECT id FROM categories WHERE id = ?
                            UNION ALL
                            SELECT c.id FROM categories c
                            INNER JOIN descendants d ON c.parent_id = d.id
                        )
                        SELECT COUNT(*) FROM descendants WHERE id = ?");
                        $stmt->execute([$new_parent_id, $category_id]);
                        if ($stmt->fetchColumn() > 0) {
                            $_SESSION['flash'] = ['danger' => 'Impossible de déplacer une catégorie dans un de ses descendants'];
                            break;
                        }
                    }

                    $stmt = $conn->prepare("UPDATE categories SET parent_id = ? WHERE id = ?");
                    $stmt->execute([$new_parent_id ?: null, $category_id]);
                    $_SESSION['flash'] = ['success' => 'Catégorie déplacée avec succès'];
                }
                break;

            case 'bulk_edit':
                $categories = $_POST['categories'] ?? [];
                $bulk_parent_id = filter_var($_POST['bulk_parent_id'], FILTER_VALIDATE_INT);
                
                if (!empty($categories)) {
                    // Vérifier que la nouvelle catégorie parente n'est pas un descendant
                    if ($bulk_parent_id) {
                        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
                        $stmt = $conn->prepare("WITH RECURSIVE descendants AS (
                            SELECT id FROM categories WHERE id = ?
                            UNION ALL
                            SELECT c.id FROM categories c
                            INNER JOIN descendants d ON c.parent_id = d.id
                        )
                        SELECT COUNT(*) FROM descendants WHERE id IN ($placeholders)");
                        $stmt->execute(array_merge([$bulk_parent_id], $categories));
                        if ($stmt->fetchColumn() > 0) {
                            $_SESSION['flash'] = ['danger' => 'Impossible de déplacer une catégorie dans un de ses descendants'];
                            break;
                        }
                    }

                    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
                    $stmt = $conn->prepare("UPDATE categories SET parent_id = ? WHERE id IN ($placeholders)");
                    $stmt->execute(array_merge([$bulk_parent_id ?: null], $categories));
                    $_SESSION['flash'] = ['success' => 'Catégories mises à jour avec succès'];
                }
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['danger' => 'Erreur lors de l\'opération'];
    }
}

try {
    // Récupération du terme de recherche
    $q = trim($_GET['q'] ?? '');

    // Construction de la requête de base
    $query = "
        SELECT c.*, 
               (SELECT COUNT(*) FROM documents WHERE categorie_id = c.id) as nb_documents,
               p.nom as parent_nom
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
    ";

    // Ajout des conditions de recherche si un terme est fourni
    $params = [];
    if (!empty($q)) {
        $query .= " WHERE c.nom LIKE ? OR c.description LIKE ?";
        $search_term = "%{$q}%";
        $params = [$search_term, $search_term];
    }

    $query .= " ORDER BY c.nom";

    // Exécution de la requête
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categories2 =$categories;

    // Récupération des catégories parentes pour le formulaire
    $stmt = $conn->query("SELECT id, nom FROM categories WHERE parent_id IS NULL ORDER BY nom");
    $parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fonction pour construire l'arborescence des catégories
    function buildCategoryTree($categories, $parent_id = null) {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parent_id) {
                $category['children'] = buildCategoryTree($categories, $category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    // Construction de l'arborescence complète
    $category_tree = buildCategoryTree($categories2);

    // Calcul du nombre total de catégories
    $total_categories = count($categories);
    
    // Calcul pour la pagination
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
    $per_page = 15;
    $total_pages = ceil($total_categories / $per_page);
    
    // Pagination des catégories
    $offset = ($page - 1) * $per_page;
    $categories = array_slice($categories, $offset, $per_page);

} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des catégories'];
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
                    <a href="categories.php" class="list-group-item list-group-item-action active">
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
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i> Nouvelle catégorie
                        </button>
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#moveCategoryModal">
                            <i class="fas fa-sitemap me-2"></i> Réorganiser l'arborescence
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                            <i class="fas fa-edit me-2"></i> Édition en masse
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#viewTreeModal">
                            <i class="fas fa-project-diagram me-2"></i> Voir l'arborescence
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
                        <h2>Gestion des catégories</h2>
                        <div>
                            <span class="badge bg-primary"><?= $total_categories ?> catégorie(s)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des catégories -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Description</th>
                                    <th>Catégorie parente</th>
                                    <th>Documents</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-folder text-primary me-2"></i>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($category['nom']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $category['description'] ?></td>
                                        <td>
                                            <?php if ($category['parent_nom']): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-level-up-alt me-1"></i> <?= htmlspecialchars($category['parent_nom']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Racine</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-file-alt me-1"></i> <?= $category['nb_documents'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editCategoryModal"
                                                        data-id="<?= $category['id'] ?>"
                                                        data-nom="<?= htmlspecialchars($category['nom']) ?>"
                                                        data-description="<?= $category['description'] ?>"
                                                        data-parent-id="<?= $category['parent_id'] ?>"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')"
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
            <div class="modal fade" id="editCategoryModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier la catégorie</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="category_id" id="edit_category_id">
                                <div class="mb-3">
                                    <label for="edit_nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="edit_nom" name="nom" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_parent_id" class="form-label">Catégorie parente</label>
                                    <select class="form-select" id="edit_parent_id" name="parent_id">
                                        <option value="">Aucune (catégorie racine)</option>
                                        <?php foreach ($categories2 as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
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

<!-- Modal pour ajouter une catégorie -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Catégorie parente</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Aucune (catégorie racine)</option>
                            <?php foreach ($categories2 as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

<!-- Modal pour réorganiser l'arborescence -->
<div class="modal fade" id="moveCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Réorganiser l'arborescence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="move">
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Catégorie à déplacer</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories2 as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_parent_id" class="form-label">Nouvelle catégorie parente</label>
                        <select class="form-select" id="new_parent_id" name="new_parent_id">
                            <option value="">Aucune (catégorie racine)</option>
                            <?php foreach ($categories2 as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Déplacer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour l'édition en masse -->
<div class="modal fade" id="bulkEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Édition en masse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_edit">
                    <div class="mb-3">
                        <label class="form-label">Sélectionner les catégories</label>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($categories2 as $cat): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]" 
                                           value="<?= $cat['id'] ?>" id="cat_<?= $cat['id'] ?>">
                                    <label class="form-check-label" for="cat_<?= $cat['id'] ?>">
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="bulk_parent_id" class="form-label">Définir une nouvelle catégorie parente</label>
                        <select class="form-select" id="bulk_parent_id" name="bulk_parent_id">
                            <option value="">Ne pas modifier</option>
                            <option value="0">Aucune (catégorie racine)</option>
                            <?php foreach ($categories2 as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Appliquer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de modification
    const editModal = document.getElementById('editCategoryModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nom = button.getAttribute('data-nom');
            const description = button.getAttribute('data-description');
            const parentId = button.getAttribute('data-parent-id');
            
            const modal = this;
            modal.querySelector('#edit_category_id').value = id;
            modal.querySelector('#edit_nom').value = nom;
            modal.querySelector('#edit_description').value = description;
            modal.querySelector('#edit_parent_id').value = parentId;
        });
    }

    // Gestion du formulaire de suppression
    const deleteModal = document.getElementById('deleteCategoryModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nom = button.getAttribute('data-nom');
            
            const modal = this;
            modal.querySelector('#delete_id').value = id;
            modal.querySelector('#delete_category_name').textContent = nom;
        });
    }

    // Gestion de la réorganisation de l'arborescence
    const moveModal = document.getElementById('moveCategoryModal');
    if (moveModal) {
        moveModal.addEventListener('show.bs.modal', function() {
            // Réinitialiser les sélections
            this.querySelector('#category_id').value = '';
            this.querySelector('#new_parent_id').value = '';
        });
    }

    // Gestion de l'édition en masse
    const bulkModal = document.getElementById('bulkEditModal');
    if (bulkModal) {
        bulkModal.addEventListener('show.bs.modal', function() {
            // Réinitialiser les sélections
            this.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            this.querySelector('#bulk_parent_id').value = '';
        });
    }
});
</script>

<?php include '../views/footer.php'; ?>

<!-- Modal pour voir l'arborescence des catégories -->
<div class="modal fade" id="viewTreeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Arborescence des catégories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="category-tree">
                    <?php
                    // Fonction récursive pour afficher l'arborescence
                    function displayCategoryTree($categories, $level = 0) {
                        if (empty($categories)) {
                            echo '<div class="text-muted">Aucune catégorie</div>';
                            return;
                        }
                        
                        echo '<ul class="list-unstyled' . ($level > 0 ? ' ms-4' : '') . '">';
                        foreach ($categories as $category) {
                            $hasChildren = !empty($category['children']);
                            $iconClass = $hasChildren ? 'fa-folder-open' : 'fa-folder';
                            $badgeClass = $hasChildren ? 'bg-primary' : 'bg-secondary';
                            
                            echo '<li class="mb-2">';
                            echo '<div class="d-flex align-items-center">';
                            echo '<i class="fas ' . $iconClass . ' text-primary me-2"></i>';
                            echo '<div class="fw-bold">' . htmlspecialchars($category['nom']) . '</div>';
                            
                            // N'afficher le badge que si la catégorie contient des documents
                            if ($category['nb_documents'] > 0) {
                                echo '<span class="badge ' . $badgeClass . ' ms-2">' . $category['nb_documents'] . ' doc(s)</span>';
                            }
                            
                            echo '</div>';
                            
                            if ($category['description']) {
                                echo '<div class="text-muted small ms-4">' . htmlspecialchars($category['description']) . '</div>';
                            }
                            
                            if ($hasChildren) {
                                displayCategoryTree($category['children'], $level + 1);
                            }
                            
                            echo '</li>';
                        }
                        echo '</ul>';
                    }
                    
                    // Affichage de l'arborescence
                    displayCategoryTree($category_tree);
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
.category-tree ul {
    list-style-type: none;
    padding-left: 0;
}
.category-tree li {
    position: relative;
    padding-left: 0;
}
.category-tree li:before {
    content: '';
    position: absolute;
    top: 0;
    left: -15px;
    border-left: 1px dashed #ccc;
    height: 100%;
}
.category-tree li:last-child:before {
    height: 15px;
}
</style> 