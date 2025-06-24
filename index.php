<?php
require_once 'config/config.php';

// Récupération des documents récents
try {
    // Vérification de la connexion
    if (!$conn) {
        throw new Exception("La connexion à la base de données n'est pas établie");
    }

    // Requête simplifiée pour les documents récents
    $stmt = $conn->query("
        SELECT d.*, u.nom as auteur_nom, c.nom as categorie_nom 
        FROM documents d 
        LEFT JOIN users u ON d.user_id = u.id 
        LEFT JOIN categories c ON d.categorie_id = c.id 
        WHERE d.statut = 'approuve' 
        ORDER BY d.date_upload DESC 
        LIMIT 6
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur lors de l'exécution de la requête des documents récents");
    }
    
    $documents_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erreur dans index.php (documents récents) : " . $e->getMessage());
    $documents_recents = [];
}

// Récupération des catégories populaires
try {
    // Requête simplifiée pour les catégories
    $stmt = $conn->query("
        SELECT c.*, COUNT(d.id) as nombre_documents 
        FROM categories c 
        LEFT JOIN documents d ON c.id = d.categorie_id AND d.statut = 'approuve'
        GROUP BY c.id, c.nom, c.description 
        ORDER BY nombre_documents DESC 
        LIMIT 4
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur lors de l'exécution de la requête des catégories");
    }
    
    $categories_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erreur dans index.php (catégories) : " . $e->getMessage());
    $categories_populaires = [];
}

include 'views/header.php';
?>

<!-- Section Héro -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">Bienvenue sur <?= SITE_NAME ?></h1>
                <p class="lead">La plateforme de partage de documents pour les étudiants de l'INPHB</p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/create-document.php" class="btn btn-light btn-lg">Partager un document</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-light btn-lg">Se connecter</a>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <img src="<?= BASE_URL ?>/assets/images/hero-image.png" alt="Illustration" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Section Documents Récents -->
<div class="container py-5">
    <h2 class="mb-4">Documents Récents</h2>
    <div class="row">
        <?php foreach ($documents_recents as $doc): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-truncate"><?= htmlspecialchars($doc['titre']) ?></h5>
                        <p class="card-text text-muted">
                            <small>
                                Par <?= htmlspecialchars($doc['auteur_nom']) ?> | 
                                <?= date('d/m/Y', strtotime($doc['date_upload'])) ?>
                            </small>
                        </p>
                        <p class="card-text flex-grow-1"><?= htmlspecialchars(substr($doc['description'], 0, 100)) ?>...</p>
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-auto">
                            <span class="badge bg-primary mb-2 mb-md-0" style="white-space: normal; word-wrap: break-word; max-width: 60%;"><?= htmlspecialchars($doc['categorie_nom']) ?></span>
                            <a href="<?= BASE_URL ?>/documents.php?id=<?= $doc['id'] ?>" class="btn btn-outline-primary ms-2">Voir</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>/liste-documents.php" class="btn btn-primary">Voir tous les documents</a>
    </div>
</div>

<!-- Section Catégories Populaires -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="mb-4">Catégories Populaires</h2>
        <div class="row">
            <?php foreach ($categories_populaires as $categorie): ?>
                <div class="col-md-3 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($categorie['nom']) ?></h5>
                            <p class="card-text">
                                <span class="badge bg-primary"><?= $categorie['nombre_documents'] ?> documents</span>
                            </p>
                            <a href="<?= BASE_URL ?>/liste-documents.php?categorie=<?= $categorie['id'] ?>" class="btn btn-outline-primary">Explorer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Section Fonctionnalités -->
<div class="container py-5">
    <h2 class="text-center mb-4">Pourquoi utiliser <?= SITE_NAME ?> ?</h2>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <i class="fas fa-share-alt fa-3x text-primary mb-3"></i>
                <h4>Partage Facile</h4>
                <p>Partagez vos documents en quelques clics avec vos camarades</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                <h4>Recherche Rapide</h4>
                <p>Trouvez rapidement les documents dont vous avez besoin</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <i class="fas fa-check-circle fa-3x text-primary mb-3"></i>
                <h4>Contenu Vérifié</h4>
                <p>Tous les documents sont validés par nos modérateurs</p>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 
// Redirection vers la liste des documents

 
