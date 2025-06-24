<?php
require_once '../config/config.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = sanitize($_POST['matricule']);
    $password = $_POST['password'];

    if (empty($matricule)) {
        $errors[] = "Le matricule est requis";
    }
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE matricule = ?");
            $stmt->execute([$matricule]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Mise à jour de la dernière connexion
                $stmt = $conn->prepare("UPDATE users SET derniere_connexion = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                // Création de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['matricule'] = $user['matricule'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenoms'] = $user['prenoms'];
                $_SESSION['role'] = $user['role'];

                $_SESSION['flash'] = ['success' => 'Connexion réussie !'];
                redirect('/');
            } else {
                $errors[] = "Matricule ou mot de passe incorrect";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de connexion à la base de données";
        }
    }
}

include '../views/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Connexion</h4>
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

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="matricule" class="form-label">Matricule</label>
                            <input type="text" class="form-control" id="matricule" name="matricule" 
                                   value="<?= isset($_POST['matricule']) ? htmlspecialchars($_POST['matricule']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="forgot-password.php">Mot de passe oublié ?</a>
                        <p class="mt-2">
                            Pas encore de compte ? 
                            <a href="register.php">Inscrivez-vous</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../views/footer.php'; ?> 