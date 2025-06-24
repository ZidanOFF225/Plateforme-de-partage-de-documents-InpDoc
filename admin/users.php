<?php
require_once '../config/config.php';

// Vérification des droits d'administration
if (!isAdmin()) {
    $_SESSION['flash'] = ['danger' => 'Accès non autorisé'];
    redirect('/');
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);

    if ($user_id) {
        try {
            switch ($action) {
                case 'change_role':
                    $role = $_POST['role'];
                    if (in_array($role, ['etudiant', 'enseignant', 'admin'])) {
                        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$role, $user_id]);
                        $_SESSION['flash'] = ['success' => 'Rôle modifié avec succès'];
                    }
                    break;

                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['flash'] = ['success' => 'Utilisateur supprimé avec succès'];
                    break;

                case 'edit':
                    $id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
                    $nom = trim($_POST['nom']);
                    $prenoms = trim($_POST['prenoms']);
                    $email = trim($_POST['email']);
                    $role = trim($_POST['role']);
                
                    if ($id && !empty($nom) && !empty($prenoms) && !empty($email) && !empty($role)) {
                        $stmt = $conn->prepare("UPDATE users SET nom = ?, prenoms = ?, email = ?, role = ? WHERE id = ?");
                        $stmt->execute([$nom, $prenoms, $email, $role, $id]);
                        $_SESSION['flash'] = ['success' => 'Utilisateur modifié avec succès'];
                    }
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['danger' => 'Erreur lors de l\'opération'];
        }
    }
}

try {
    // Récupération du rôle sélectionné
    $role = $_GET['role'] ?? '';
    
    // Construction de la requête de base
    $query = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM documents WHERE user_id = u.id) as nb_documents
        FROM users u
    ";
    
    // Ajout du filtre par rôle si présent
    $params = [];
    if (!empty($role)) {
        $query .= " WHERE u.role = ?";
        $params[] = $role;
    }
    
    $query .= " ORDER BY u.nom ";
    
    // Pagination
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
    $per_page = 10;
    
    // Comptage total des utilisateurs - Requête séparée et plus simple
    $count_query = "SELECT COUNT(*) FROM users";
    if (!empty($role)) {
        $count_query .= " WHERE role = ?";
    }
    
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);
    
    // Récupération des utilisateurs avec pagination
    $query .= " LIMIT " . (int)$per_page . " OFFSET " . (int)(($page - 1) * $per_page);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des utilisateurs'];
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
                    <a href="users.php" class="list-group-item list-group-item-action active">
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
                        <a href="?role=etudiant" class="btn btn-info btn-sm">
                            <i class="fas fa-user-graduate me-2"></i> Étudiants
                        </a>
                        <a href="?role=enseignant" class="btn btn-primary btn-sm">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Enseignants
                        </a>
                        <a href="?role=admin" class="btn btn-warning btn-sm">
                            <i class="fas fa-user-shield me-2"></i> Administrateurs
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
                                   value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">Tous</option>
                                <option value="etudiant" <?= $role === 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                                <option value="enseignant" <?= $role === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateur</option>
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
                        <h2>Gestion des utilisateurs</h2>
                        <div>
                            <span class="badge bg-primary"><?= $total_users ?> utilisateur(s)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 200px;">Utilisateur</th>
                                    <th style="min-width: 110px;">Email</th>
                                    <th style="min-width: 100px;">Rôle</th>
                                    <th style="min-width: 100px;">Documents</th>
                                    <th style="min-width: 100px;">Date d'inscription</th>
                                    <th style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user text-primary me-2"></i>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($user['nom'] . ' ' . $user['prenoms']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($user['matricule']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'warning' : ($user['role'] === 'enseignant' ? 'primary' : 'info') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-file-alt me-1"></i> <?= $user['nb_documents'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($user['date_creation'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-id="<?= htmlspecialchars($user['id']) ?>"
                                                        data-nom="<?= htmlspecialchars($user['nom']) ?>"
                                                        data-prenoms="<?= htmlspecialchars($user['prenoms']) ?>"
                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-role="<?= htmlspecialchars($user['role']) ?>"
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')"
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
            <div class="modal fade" id="editUserModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier l'utilisateur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="user_id" id="edit_user_id">
                                <div class="mb-3">
                                    <label for="edit_nom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="edit_nom" name="nom" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_prenoms" class="form-label">Prénoms</label>
                                    <input type="text" class="form-control" id="edit_prenoms" name="prenoms" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Rôle</label>
                                    <select class="form-select" id="edit_role" name="role" required>
                                        <option value="etudiant">Étudiant</option>
                                        <option value="enseignant">Enseignant</option>
                                        <option value="admin">Administrateur</option>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de modification
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nom = button.getAttribute('data-nom');
            const prenoms = button.getAttribute('data-prenoms');
            const email = button.getAttribute('data-email');
            const role = button.getAttribute('data-role');
            
            const modal = this;
            modal.querySelector('#edit_user_id').value = id;
            modal.querySelector('#edit_nom').value = nom;
            modal.querySelector('#edit_prenoms').value = prenoms;
            modal.querySelector('#edit_email').value = email;
            modal.querySelector('#edit_role').value = role;
        });
    }
});
</script>

<?php include '../views/footer.php'; ?> 