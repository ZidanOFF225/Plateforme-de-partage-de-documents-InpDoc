<?php
require_once 'config/config.php';
include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-body">
                    <h1 class="text-center mb-4">À propos d'InpDoc</h1>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <h3>Notre Mission</h3>
                            <p>
                                InpDoc est une plateforme de partage de documents académiques conçue spécifiquement 
                                pour la communauté de l'Institut National Polytechnique de Yamoussoukro. Notre mission 
                                est de faciliter l'accès aux ressources pédagogiques et de promouvoir le partage des 
                                connaissances entre étudiants et enseignants.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h3>Notre Vision</h3>
                            <p>
                                Nous aspirons à créer un environnement collaboratif où le savoir est accessible à tous. 
                                Notre plateforme vise à devenir le point de référence pour l'échange de ressources 
                                académiques au sein de l'INP-HB, contribuant ainsi à l'excellence académique de notre 
                                institution.
                            </p>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-12">
                            <h3>Nos Valeurs</h3>
                            <div class="row mt-3">
                                <div class="col-md-4 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-share-alt fa-3x text-primary mb-3"></i>
                                        <h4>Partage</h4>
                                        <p>
                                            Nous encourageons le partage des connaissances et la collaboration 
                                            entre les membres de notre communauté.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                                        <h4>Qualité</h4>
                                        <p>
                                            Nous veillons à la qualité des documents partagés grâce à un 
                                            processus de validation rigoureux.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                        <h4>Communauté</h4>
                                        <p>
                                            Nous favorisons l'entraide et la solidarité au sein de notre 
                                            communauté académique.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-12">
                            <h3>Comment ça marche ?</h3>
                            <div class="row mt-3">
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <div class="circle-step mb-3">1</div>
                                        <h5>Inscription</h5>
                                        <p>Créez votre compte avec votre adresse email institutionnelle</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <div class="circle-step mb-3">2</div>
                                        <h5>Partage</h5>
                                        <p>Partagez vos documents académiques avec la communauté</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <div class="circle-step mb-3">3</div>
                                        <h5>Validation</h5>
                                        <p>Les documents sont validés par notre équipe</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <div class="circle-step mb-3">4</div>
                                        <h5>Accès</h5>
                                        <p>Accédez à une bibliothèque de ressources validées</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h3>Statistiques</h3>
                            <div class="row mt-3">
                                <?php
                                try {
                                    // Nombre total de documents approuvés
                                    $stmt = $conn->query("SELECT COUNT(*) FROM documents WHERE status = 'approuve'");
                                    $nb_documents = $stmt->fetchColumn();

                                    // Nombre total d'utilisateurs
                                    $stmt = $conn->query("SELECT COUNT(*) FROM users");
                                    $nb_users = $stmt->fetchColumn();

                                    // Nombre total de téléchargements
                                    $stmt = $conn->query("SELECT SUM(nb_telechargements) FROM documents");
                                    $nb_downloads = $stmt->fetchColumn() ?: 0;

                                    // Nombre total de catégories
                                    $stmt = $conn->query("SELECT COUNT(*) FROM categories");
                                    $nb_categories = $stmt->fetchColumn();
                                } catch (PDOException $e) {
                                    // En cas d'erreur, on met des valeurs par défaut
                                    $nb_documents = 0;
                                    $nb_users = 0;
                                    $nb_downloads = 0;
                                    $nb_categories = 0;
                                }
                                ?>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                        <h2 class="mb-1"><?= number_format($nb_documents) ?></h2>
                                        <p class="text-muted mb-0">Documents</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                        <h2 class="mb-1"><?= number_format($nb_users) ?></h2>
                                        <p class="text-muted mb-0">Utilisateurs</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-download fa-2x text-primary mb-2"></i>
                                        <h2 class="mb-1"><?= number_format($nb_downloads) ?></h2>
                                        <p class="text-muted mb-0">Téléchargements</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <div class="text-center">
                                        <i class="fas fa-folder fa-2x text-primary mb-2"></i>
                                        <h2 class="mb-1"><?= number_format($nb_categories) ?></h2>
                                        <p class="text-muted mb-0">Catégories</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.circle-step {
    width: 40px;
    height: 40px;
    background-color: var(--bs-primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    margin: 0 auto;
}
</style>

<?php include 'views/footer.php'; ?> 