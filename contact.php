<?php
require_once 'config/config.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom = trim($_POST['nom']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $sujet = trim($_POST['sujet']);
        $message = trim($_POST['message']);

        // Validation
        if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
            throw new Exception('Tous les champs sont obligatoires');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email invalide');
        }

        // Envoi de l'email
        $to = ADMIN_EMAIL;
        $subject = "[InpDoc] $sujet";
        $headers = "From: $nom <$email>\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $email_content = "Nouveau message de contact :\n\n";
        $email_content .= "Nom : $nom\n";
        $email_content .= "Email : $email\n";
        $email_content .= "Sujet : $sujet\n\n";
        $email_content .= "Message :\n$message";

        if (mail($to, $subject, $email_content, $headers)) {
            $_SESSION['flash'] = ['success' => 'Votre message a été envoyé avec succès'];
            redirect('/contact.php');
        } else {
            throw new Exception('Erreur lors de l\'envoi du message');
        }

    } catch (Exception $e) {
        $_SESSION['flash'] = ['danger' => $e->getMessage()];
    }
}

include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Contactez-nous</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        Vous avez une question, une suggestion ou un problème ? N'hésitez pas à nous contacter 
                        en remplissant le formulaire ci-dessous. Notre équipe vous répondra dans les plus brefs délais.
                    </p>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>" 
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sujet" class="form-label">Sujet *</label>
                            <input type="text" class="form-control" id="sujet" name="sujet" 
                                   value="<?= isset($_POST['sujet']) ? htmlspecialchars($_POST['sujet']) : '' ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Autres moyens de nous contacter</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6><i class="fas fa-envelope me-2"></i>Email</h6>
                            <p class="mb-0">
                                <a href="mailto:<?= ADMIN_EMAIL ?>" class="text-decoration-none">
                                    <?= ADMIN_EMAIL ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-map-marker-alt me-2"></i>Adresse</h6>
                            <p class="mb-0">
                                Institut National Polytechnique<br>
                                Yamoussoukro, Côte d'Ivoire
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 