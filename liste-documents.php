<?php
require_once 'config/config.php';

// Activation des logs d'erreur
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de la connexion à la base de données
if (!isset($conn) || !$conn) {
    error_log("Erreur : La connexion à la base de données n'est pas établie");
    die("Erreur de connexion à la base de données");
}

try {
    // Vérification de la table documents
    $check_table = $conn->query("SHOW TABLES LIKE 'documents'");
    if ($check_table->rowCount() === 0) {
        throw new Exception("La table 'documents' n'existe pas");
    }

    // Vérification du contenu de la table documents
    $check_content = $conn->query("SELECT * FROM documents");
    $all_documents = $check_content->fetchAll(PDO::FETCH_ASSOC);
    error_log("Contenu de la table documents : " . print_r($all_documents, true));

    // Vérification du statut des documents
    $check_status = $conn->query("SELECT statut, COUNT(*) as count FROM documents GROUP BY statut");
    $status_count = $check_status->fetchAll(PDO::FETCH_ASSOC);
    error_log("Nombre de documents par statut : " . print_r($status_count, true));

    // Paramètres de filtrage
    $page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT));
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    $categorie_id = filter_var($_GET['categorie'] ?? null, FILTER_VALIDATE_INT);
    $type_document = $_GET['type'] ?? '';
    $search = trim($_GET['search'] ?? '');

    // Construction de la requête
    $where_conditions = ["d.statut = 'approuve'"];
    $params = [];

    if ($categorie_id) {
        $where_conditions[] = "d.categorie_id = :categorie_id";
        $params[':categorie_id'] = $categorie_id;
    }

    if ($type_document) {
        $where_conditions[] = "d.type_document = :type_document";
        $params[':type_document'] = $type_document;
    }

    if ($search) {
        $where_conditions[] = "(d.titre LIKE :search OR d.description LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $where_clause = implode(" AND ", $where_conditions);
    error_log("Clause WHERE : " . $where_clause);
    error_log("Paramètres : " . print_r($params, true));

    // Récupération du nombre total de documents avec filtres
    $count_stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM documents d 
        WHERE $where_clause
    ");
    $count_stmt->execute($params);
    $total_documents = $count_stmt->fetchColumn();
    error_log("Nombre de documents après filtres : " . $total_documents);
    
    $total_pages = ceil($total_documents / $per_page);

    // Récupération des documents
    $stmt = $conn->prepare("
        SELECT d.*, u.nom as auteur_nom, c.nom as categorie_nom,
               (SELECT COUNT(*) FROM notes WHERE document_id = d.id) as nb_notes,
               (SELECT AVG(note) FROM notes WHERE document_id = d.id) as moyenne_notes
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE $where_clause
        ORDER BY d.date_upload DESC
        LIMIT :limit OFFSET :offset
    ");
    
    // Ajout des paramètres de pagination
    $params[':limit'] = $per_page;
    $params[':offset'] = $offset;
    
    // Liaison des paramètres avec leurs types
    foreach ($params as $key => $value) {
        if (is_int($value)) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    
    error_log("Requête SQL : " . $stmt->queryString);
    
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Nombre de documents récupérés : " . count($documents));
    error_log("Documents récupérés : " . print_r($documents, true));

    // Récupération des catégories pour le filtre
    $categories_stmt = $conn->query("SELECT * FROM categories ORDER BY nom");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Catégories récupérées : " . print_r($categories, true));

} catch (PDOException $e) {
    error_log("Erreur PDO dans liste-documents.php : " . $e->getMessage());
    error_log("Code d'erreur : " . $e->getCode());
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des documents : ' . $e->getMessage()];
    $documents = [];
    $categories = [];
    $total_pages = 0;
} catch (Exception $e) {
    error_log("Erreur générale dans liste-documents.php : " . $e->getMessage());
    $_SESSION['flash'] = ['danger' => 'Erreur : ' . $e->getMessage()];
    $documents = [];
    $categories = [];
    $total_pages = 0;
}

include 'views/header.php';
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
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Rechercher...">
                        </div>

                        <div class="mb-3">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                            <?= $categorie_id == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Type de document</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous les types</option>
                                <option value="cours" <?= $type_document === 'cours' ? 'selected' : '' ?>>Cours</option>
                                <option value="td" <?= $type_document === 'td' ? 'selected' : '' ?>>TD</option>
                                <option value="tp" <?= $type_document === 'tp' ? 'selected' : '' ?>>TP</option>
                                <option value="examen" <?= $type_document === 'examen' ? 'selected' : '' ?>>Examen</option>
                                <option value="correction" <?= $type_document === 'correction' ? 'selected' : '' ?>>Correction</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des documents -->
        <div class="col-md-9">
            <h2 class="mb-4">Documents</h2>
            
            <?php if (empty($documents)): ?>
                <div class="alert alert-info">
                    Aucun document trouvé.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($documents as $doc): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($doc['titre']) ?></h5>
                                    <p class="card-text text-muted">
                                        <small>
                                            Par <?= htmlspecialchars($doc['auteur_nom']) ?> | 
                                            <?= date('d/m/Y', strtotime($doc['date_upload'])) ?>
                                        </small>
                                    </p>
                                    <p class="card-text"><?= htmlspecialchars(substr($doc['description'], 0, 100)) ?>...</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary"><?= htmlspecialchars($doc['categorie_nom']) ?></span>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($doc['type_document']) ?></span>
                                    </div>

                                    <?php if ($doc['nb_notes'] > 0): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <?= $doc['nb_notes'] ?> note(s) - 
                                                Moyenne : <?= number_format($doc['moyenne_notes'], 1) ?>/5
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                        <a href="<?= BASE_URL ?>/documents.php?id=<?= $doc['id'] ?>" 
                                           class="btn btn-outline-primary">Voir le document</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navigation des pages" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&categorie=<?= $categorie_id ?>&type=<?= $type_document ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 