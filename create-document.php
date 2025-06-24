<?php
require_once 'config/config.php';

// Vérification de la connexion de l'utilisateur
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['danger' => 'Veuillez vous connecter pour créer un document'];
    redirect('/login.php');
}

// Création du dossier uploads s'il n'existe pas
if (!file_exists(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0777, true)) {
        $_SESSION['flash'] = ['danger' => 'Erreur lors de la création du dossier uploads'];
        redirect('/');
    }
}

// Vérification des permissions du dossier uploads
if (!is_writable(UPLOAD_DIR)) {
    $_SESSION['flash'] = ['danger' => 'Le dossier uploads n\'est pas accessible en écriture'];
    redirect('/');
}

// Récupération des catégories
try {
    // Vérification de la connexion
    if (!$conn) {
        throw new Exception("La connexion à la base de données n'est pas établie");
    }

    $stmt = $conn->query("SELECT * FROM categories ORDER BY nom");
    if (!$stmt) {
        throw new Exception("Erreur lors de l'exécution de la requête des catégories");
    }
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($categories === false) {
        throw new Exception("Erreur lors de la récupération des catégories");
    }
} catch (Exception $e) {
    error_log("Erreur dans create-document.php (catégories) : " . $e->getMessage());
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des catégories'];
    redirect('/');
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categorie_id = filter_var($_POST['categorie_id'] ?? null, FILTER_VALIDATE_INT);
    $type_document = $_POST['type_document'] ?? '';
    $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));

    $errors = [];

    // Validation
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    if (empty($description)) {
        $errors[] = "La description est obligatoire";
    }
    if (!$categorie_id) {
        $errors[] = "La catégorie est obligatoire";
    }
    if (empty($type_document)) {
        $errors[] = "Le type de document est obligatoire";
    }

    // Validation du fichier
    if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Le fichier est obligatoire";
    } else {
        $file = $_FILES['document'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Vérification de l'extension
        if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
            $errors[] = "Format de fichier non autorisé. Formats acceptés : " . implode(', ', ALLOWED_EXTENSIONS);
        }
        
        // Vérification de la taille
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = "Le fichier est trop volumineux. Taille maximale : " . (MAX_FILE_SIZE / 1024 / 1024) . " MB";
        }
    }

    if (empty($errors)) {
        try {
            // Vérification de la connexion
            if (!$conn) {
                throw new Exception("La connexion à la base de données n'est pas établie");
            }

            // Gestion du fichier
            $file = $_FILES['document'];
            $filename = uniqid() . '_' . basename($file['name']);
            $filepath = UPLOAD_DIR . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                error_log("Erreur lors du téléchargement du fichier : " . error_get_last()['message']);
                throw new Exception("Erreur lors du téléchargement du fichier");
            }

            // Insertion du document
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                INSERT INTO documents (titre, description, filename, categorie_id, type_document, user_id, date_upload, statut)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_attente')
            ");
            
            if (!$stmt->execute([$titre, $description, $filename, $categorie_id, $type_document, $_SESSION['user_id']])) {
                throw new Exception("Erreur lors de l'insertion du document");
            }
            
            $document_id = $conn->lastInsertId();
            if (!$document_id) {
                throw new Exception("Erreur lors de la récupération de l'ID du document");
            }

            // Insertion des tags
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    // Vérifier si le tag existe déjà
                    $stmt = $conn->prepare("SELECT id FROM tags WHERE nom = ?");
                    if (!$stmt->execute([$tag])) {
                        throw new Exception("Erreur lors de la vérification du tag");
                    }
                    $tag_id = $stmt->fetchColumn();

                    if (!$tag_id) {
                        // Créer le tag s'il n'existe pas
                        $stmt = $conn->prepare("INSERT INTO tags (nom) VALUES (?)");
                        if (!$stmt->execute([$tag])) {
                            throw new Exception("Erreur lors de la création du tag");
                        }
                        $tag_id = $conn->lastInsertId();
                    }

                    // Associer le tag au document
                    $stmt = $conn->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)");
                    if (!$stmt->execute([$document_id, $tag_id])) {
                        throw new Exception("Erreur lors de l'association du tag au document");
                    }
                }
            }

            $conn->commit();
            
            // Vérification que le document a bien été créé
            $stmt = $conn->prepare("SELECT id FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            if ($stmt->fetch()) {
                // Afficher un message de succès en vert avec une redirection automatique
                include 'views/header.php';
                echo '<div class="container mt-5">';
                echo '<div class="row justify-content-center">';
                echo '<div class="col-md-8">';
                echo '<div class="card border-success">';
                echo '<div class="card-header bg-success text-white">';
                echo '<h4 class="mb-0"><i class="fas fa-check-circle me-2"></i> Succès !</h4>';
                echo '</div>';
                echo '<div class="card-body">';
                echo '<div class="text-center mb-4">';
                echo '<i class="fas fa-file-upload fa-4x text-success mb-3"></i>';
                echo '<h5 class="card-title">Document soumis avec succès</h5>';
                echo '<p class="card-text">Votre document a été soumis avec succès. Il sera visible après validation par un administrateur.</p>';
                echo '</div>';
                echo '<div class="alert alert-info">';
                echo '<i class="fas fa-info-circle me-2"></i> Vous allez être redirigé vers la page "Mes documents" dans quelques secondes.';
                echo '</div>';
                echo '<div class="text-center mt-3">';
                echo '<a href="mes-documents.php" class="btn btn-primary me-2"><i class="fas fa-folder-open me-2"></i>Voir mes documents</a>';
                echo '<a href="create-document.php" class="btn btn-outline-secondary"><i class="fas fa-plus me-2"></i>Ajouter un autre document</a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                // Script de redirection automatique
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "mes-documents.php";
                    }, 10000);
                </script>';
                
                include 'views/footer.php';
                exit;
            } else {
                throw new Exception("Le document n'a pas été créé correctement");
            }

        } catch (Exception $e) {
            $conn->rollBack();
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            error_log("Erreur dans create-document.php (création) : " . $e->getMessage());
            
            // Afficher un message d'erreur en rouge avec une redirection automatique
            include 'views/header.php';
            echo '<div class="container mt-5">';
            echo '<div class="row justify-content-center">';
            echo '<div class="col-md-8">';
            echo '<div class="card border-danger">';
            echo '<div class="card-header bg-danger text-white">';
            echo '<h4 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i> Erreur !</h4>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<div class="text-center mb-4">';
            echo '<i class="fas fa-times-circle fa-4x text-danger mb-3"></i>';
            echo '<h5 class="card-title">Erreur lors de la création du document</h5>';
            echo '<p class="card-text">Une erreur est survenue lors de la création du document :</p>';
            echo '<div class="alert alert-danger">';
            echo '<strong>' . htmlspecialchars($e->getMessage()) . '</strong>';
            echo '</div>';
            echo '</div>';
            echo '<div class="alert alert-info">';
            echo '<i class="fas fa-info-circle me-2"></i> Vous allez être redirigé vers le formulaire dans quelques secondes.';
            echo '</div>';
            echo '<div class="text-center mt-3">';
            echo '<a href="create-document.php" class="btn btn-primary me-2"><i class="fas fa-redo me-2"></i>Réessayer</a>';
            echo '<a href="mes-documents.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour à mes documents</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // Script de redirection automatique
            echo '<script>
                setTimeout(function() {
                    window.location.href = "create-document.php";
                }, 5000);
            </script>';
            
            include 'views/footer.php';
            exit;
        }
    }
}

include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Créer un nouveau document</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre *</label>
                            <input type="text" class="form-control" id="titre" name="titre" required
                                   value="<?= htmlspecialchars($titre ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($description ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="categorie_id" class="form-label">Catégorie *</label>
                            <select class="form-select" id="categorie_id" name="categorie_id" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?= $categorie['id'] ?>" <?= isset($categorie_id) && $categorie_id == $categorie['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categorie['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="type_document" class="form-label">Type de document *</label>
                            <select class="form-select" id="type_document" name="type_document" required>
                                <option value="">Sélectionnez un type</option>
                                <option value="cours" <?= isset($type_document) && $type_document === 'cours' ? 'selected' : '' ?>>Cours</option>
                                <option value="td" <?= isset($type_document) && $type_document === 'td' ? 'selected' : '' ?>>TD</option>
                                <option value="tp" <?= isset($type_document) && $type_document === 'tp' ? 'selected' : '' ?>>TP</option>
                                <option value="examen" <?= isset($type_document) && $type_document === 'examen' ? 'selected' : '' ?>>Examen</option>
                                <option value="correction" <?= isset($type_document) && $type_document === 'correction' ? 'selected' : '' ?>>Correction</option>
                                <option value="autre" <?= isset($type_document) && $type_document === 'autre' ? 'selected' : '' ?>>Autre</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (séparés par des virgules)</label>
                            <input type="text" class="form-control" id="tags" name="tags"
                                   value="<?= htmlspecialchars(implode(', ', $tags ?? [])) ?>"
                                   placeholder="exemple: math, algèbre, semestre1">
                        </div>

                        <div class="mb-3">
                            <label for="document" class="form-label">Fichier *</label>
                            <input type="file" class="form-control" id="document" name="document" required
                                   accept=".pdf,.doc,.docx,.ppt,.pptx">
                            <div class="form-text">
                                Formats acceptés : PDF, DOC, DOCX, PPT, PPTX<br>
                                Taille maximale : <?= MAX_FILE_SIZE / 1024 / 1024 ?> MB
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Créer le document</button>
                            <a href="/mes-documents.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 