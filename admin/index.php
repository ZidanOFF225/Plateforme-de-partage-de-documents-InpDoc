<?php
require_once '../config/config.php';

// Vérification des droits d'administration
if (!isAdmin()) {
    $_SESSION['flash'] = ['danger' => 'Accès non autorisé'];
    redirect('/inpDoc/');
}

// Initialisation des variables
$stats = [
    'documents' => 0,
    'documents_attente' => 0,
    'documents_approuves' => 0,
    'documents_rejetes' => 0,
    'users' => 0,
    'categories' => 0,
    'tags' => 0
];

$documents_recents = [];
$documents_populaires = [];
$categories_populaires = [];

// Récupération des statistiques
try {
    // Vérification de la connexion
    if (!$conn) {
        throw new PDOException("La connexion à la base de données a échoué");
    }

    // Nombre total de documents
    $stmt = $conn->query("SELECT COUNT(*) FROM documents");
    $stats['documents'] = $stmt->fetchColumn();

    // Nombre de documents en attente
    $stmt = $conn->query("SELECT COUNT(*) FROM documents WHERE statut = 'en_attente'");
    $stats['documents_attente'] = $stmt->fetchColumn();

    // Nombre de documents approuve
    $stmt = $conn->query("SELECT COUNT(*) FROM documents WHERE statut = 'approuve'");
    $stats['documents_approuves'] = $stmt->fetchColumn();

    // Nombre de documents rejete
    $stmt = $conn->query("SELECT COUNT(*) FROM documents WHERE statut = 'rejete'");
    $stats['documents_rejetes'] = $stmt->fetchColumn();

    // Nombre total d'utilisateurs
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $stats['users'] = $stmt->fetchColumn();

    // Nombre de catégories
    $stmt = $conn->query("SELECT COUNT(*) FROM categories");
    $stats['categories'] = $stmt->fetchColumn();

    // Nombre de tags
    $stmt = $conn->query("SELECT COUNT(*) FROM tags");
    $stats['tags'] = $stmt->fetchColumn();

    // Documents récemment ajoutés
    $stmt = $conn->query("
        SELECT d.*, u.nom, u.prenoms, c.nom as categorie_nom
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.categorie_id = c.id
        ORDER BY d.date_upload DESC
        LIMIT 10
    ");
    $documents_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Documents les plus téléchargés
    $stmt = $conn->query("
        SELECT d.*, u.nom, u.prenoms, c.nom as categorie_nom
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE d.statut = 'approuve'
        ORDER BY d.nb_telechargements DESC
        LIMIT 5
    ");
    $documents_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Catégories les plus utilisées
    $stmt = $conn->query("
        SELECT c.id, c.nom, COUNT(d.id) as nombre_documents
        FROM categories c
        LEFT JOIN documents d ON c.id = d.categorie_id
        GROUP BY c.id, c.nom
        ORDER BY nombre_documents DESC
        LIMIT 5
    ");
    $categories_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur dans admin/index.php : " . $e->getMessage());
    $_SESSION['flash'] = ['warning' => 'Certaines données n\'ont pas pu être récupérées. Veuillez réessayer plus tard.'];
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
                    <a href="index.php" class="list-group-item list-group-item-action active">
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
                    <a href="tags.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tags me-2"></i> Tags
                    </a>
                </div>
            </div>
            
        </div>

        <!-- Contenu principal -->
        <div class="col-md-9">
            <!-- En-tête avec statistiques principales -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Tableau de bord</h2>
                        <div>
                            <span class="badge bg-primary"><?= date('d/m/Y') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques principales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Documents totaux</h6>
                                    <h2 class="mb-0"><?= $stats['documents'] ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">En attente</h6>
                                    <h2 class="mb-0"><?= $stats['documents_attente'] ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Approuvés</h6>
                                    <h2 class="mb-0"><?= $stats['documents_approuves'] ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Rejetés</h6>
                                    <h2 class="mb-0"><?= $stats['documents_rejetes'] ?></h2>
                                </div>
                                <div class="fs-1">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques secondaires -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted">Utilisateurs</h6>
                                    <h3 class="mb-0"><?= $stats['users'] ?></h3>
                                </div>
                                <div class="fs-1 text-info">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted">Catégories</h6>
                                    <h3 class="mb-0"><?= $stats['categories'] ?></h3>
                                </div>
                                <div class="fs-1 text-secondary">
                                    <i class="fas fa-folder"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted">Tags</h6>
                                    <h3 class="mb-0"><?= $stats['tags'] ?></h3>
                                </div>
                                <div class="fs-1 text-dark">
                                    <i class="fas fa-tags"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?> 