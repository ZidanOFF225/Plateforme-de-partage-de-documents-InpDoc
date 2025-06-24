<?php
require_once '../config/config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = sanitize($_POST['matricule']);
    $nom = sanitize($_POST['nom']);
    $prenoms = sanitize($_POST['prenoms']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validation
    if (empty($matricule)) {
        $errors[] = "Le matricule est requis";
    } elseif (!preg_match('/^[A-Z0-9]{8,}$/', $matricule)) {
        $errors[] = "Le matricule doit contenir au moins 8 caractères alphanumériques";
    }

    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }

    if (empty($prenoms)) {
        $errors[] = "Le(s) prénom(s) est/sont requis";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Vérification de l'unicité du matricule et de l'email
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE matricule = ? OR email = ?");
            $stmt->execute([$matricule, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Ce matricule ou cet email est déjà utilisé";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de connexion à la base de données";
        }
    }

    // Insertion dans la base de données
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO users (matricule, nom, prenoms, email, password, role) VALUES (?, ?, ?, ?, ?, 'etudiant')");
            $stmt->execute([
                $matricule,
                $nom,
                $prenoms,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $_SESSION['flash'] = ['success' => 'Inscription réussie ! Vous pouvez maintenant vous connecter.'];
            redirect('/auth/login.php');
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'inscription";
        }
    }
}

include '../views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Inscription</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="matricule" class="form-label">Matricule</label>
                                <input type="text" class="form-control" id="matricule" name="matricule" 
                                       value="<?= isset($_POST['matricule']) ? htmlspecialchars($_POST['matricule']) : '' ?>" 
                                       pattern="[A-Z0-9]{8,}" required>
                                <div class="form-text">Le matricule doit contenir au moins 8 caractères alphanumériques</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="prenoms" class="form-label">Prénom(s)</label>
                                <input type="text" class="form-control" id="prenoms" name="prenoms" 
                                       value="<?= isset($_POST['prenoms']) ? htmlspecialchars($_POST['prenoms']) : '' ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="8" required>
                                <div class="form-text">Le mot de passe doit contenir au moins 8 caractères</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="password_confirm" 
                                       name="password_confirm" required>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                J'accepte les <a href="../terms.php">conditions d'utilisation</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                    </form>

                    <div class="mt-3 text-center">
                        <p>Déjà inscrit ? <a href="login.php">Connectez-vous</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?> 