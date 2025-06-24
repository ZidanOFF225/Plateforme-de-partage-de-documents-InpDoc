<?php
require_once 'config/config.php';

// Vérification de l'authentification
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['danger' => 'Vous devez être connecté pour accéder à cette page'];
    redirect('/auth/login.php');
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'update_profile':
                $nom = trim($_POST['nom']);
                $prenoms = trim($_POST['prenoms']);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

                if (empty($nom) || empty($prenoms) || empty($email)) {
                    throw new Exception('Tous les champs sont obligatoires');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email invalide');
                }

                // Vérification si l'email existe déjà
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    throw new Exception('Cet email est déjà utilisé');
                }

                // Mise à jour du profil
                $stmt = $conn->prepare("UPDATE users SET nom = ?, prenoms = ?, email = ? WHERE id = ?");
                $stmt->execute([$nom, $prenoms, $email, $_SESSION['user_id']]);

                // Mise à jour de la session
                $_SESSION['nom'] = $nom;
                $_SESSION['prenoms'] = $prenoms;

                $_SESSION['flash'] = ['success' => 'Profil mis à jour avec succès'];
                break;

            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception('Tous les champs sont obligatoires');
                }

                if ($new_password !== $confirm_password) {
                    throw new Exception('Les nouveaux mots de passe ne correspondent pas');
                }

                if (strlen($new_password) < 8) {
                    throw new Exception('Le nouveau mot de passe doit contenir au moins 8 caractères');
                }

                // Vérification du mot de passe actuel
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception('Mot de passe actuel incorrect');
                }

                // Mise à jour du mot de passe
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['user_id']]);

                $_SESSION['flash'] = ['success' => 'Mot de passe modifié avec succès'];
                break;
        }
    } catch (Exception $e) {
        $_SESSION['flash'] = ['danger' => $e->getMessage()];
    }
}

// Récupération des informations de l'utilisateur
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM documents WHERE user_id = u.id) as nb_documents,
               (SELECT COUNT(*) FROM commentaires WHERE user_id = u.id) as nb_commentaires
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupération des derniers documents
    $stmt = $conn->prepare("
        SELECT d.*, c.nom as categorie_nom,
               (SELECT COUNT(*) FROM notes WHERE document_id = d.id) as nb_notes,
               (SELECT AVG(note) FROM notes WHERE document_id = d.id) as moyenne_notes
        FROM documents d
        LEFT JOIN categories c ON d.categorie_id = c.id
        WHERE d.user_id = ?
        ORDER BY d.date_upload DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['flash'] = ['danger' => 'Erreur lors de la récupération des informations'];
    redirect('/');
}

include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- Informations du profil -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Mon profil</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-placeholder mb-2">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        <h4><?= htmlspecialchars($user['nom'] . ' ' . $user['prenoms']) ?></h4>
                        <p class="text-muted mb-0"><?= ucfirst($user['role']) ?></p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Matricule</label>
                        <p class="mb-0"><?= htmlspecialchars($user['matricule']) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Membre depuis</label>
                        <p class="mb-0"><?= date('d/m/Y', strtotime($user['date_creation'])) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dernière connexion</label>
                        <p class="mb-0">
                            <?= $user['derniere_connexion'] ? date('d/m/Y à H:i', strtotime($user['derniere_connexion'])) : 'Jamais' ?>
                        </p>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5><?= $user['nb_documents'] ?></h5>
                            <small class="text-muted">Documents</small>
                        </div>
                        <div class="col-6">
                            <h5><?= $user['nb_commentaires'] ?></h5>
                            <small class="text-muted">Commentaires</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Modification du profil -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Modifier mon profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($user['nom']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenoms" class="form-label">Prénom(s)</label>
                                <input type="text" class="form-control" id="prenoms" name="prenoms" 
                                       value="<?= htmlspecialchars($user['prenoms']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </form>
                </div>
            </div>

            <!-- Modification du mot de passe -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Changer mon mot de passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required minlength="8">
                            <div class="form-text">Le mot de passe doit contenir au moins 8 caractères</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </form>
                </div>
            </div>

            <!-- Derniers documents -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Mes derniers documents</h5>
                        <a href="mes-documents.php" class="btn btn-sm btn-primary">
                            Voir tous mes documents
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <p class="text-muted">Vous n'avez pas encore partagé de documents.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($documents as $doc): ?>
                                <a href="document.php?id=<?= $doc['id'] ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($doc['titre']) ?></h6>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($doc['date_upload'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($doc['categorie_nom']) ?></p>
                                    <small class="text-muted">
                                        <?php if ($doc['nb_notes'] > 0): ?>
                                            <i class="fas fa-star text-warning"></i> 
                                            <?= number_format($doc['moyenne_notes'], 1) ?>/5
                                            (<?= $doc['nb_notes'] ?>) |
                                        <?php endif; ?>
                                        <i class="fas fa-download"></i> <?= $doc['nb_telechargements'] ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-placeholder {
    width: 100px;
    height: 100px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: #6c757d;
}
</style>

<?php include 'views/footer.php'; ?> 