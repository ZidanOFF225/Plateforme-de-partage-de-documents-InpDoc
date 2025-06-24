<?php
require_once 'config/config.php';
include 'views/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-body">
                    <h1 class="text-center mb-4">Conditions d'utilisation</h1>

                    <div class="mb-5">
                        <h3>1. Acceptation des conditions</h3>
                        <p>
                            En accédant et en utilisant la plateforme InpDoc, vous acceptez d'être lié par les 
                            présentes conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez 
                            ne pas utiliser la plateforme.
                        </p>
                    </div>

                    <div class="mb-5">
                        <h3>2. Description du service</h3>
                        <p>
                            InpDoc est une plateforme de partage de documents académiques destinée exclusivement 
                            aux membres de l'Institut National Polytechnique de Yamoussoukro. Le service permet 
                            aux utilisateurs de :
                        </p>
                        <ul>
                            <li>Partager des documents académiques</li>
                            <li>Télécharger des documents approuvés</li>
                            <li>Commenter et noter les documents</li>
                            <li>Interagir avec d'autres membres de la communauté</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>3. Inscription et compte</h3>
                        <p>
                            Pour utiliser InpDoc, vous devez :
                        </p>
                        <ul>
                            <li>Être membre de l'INP-HB (étudiant, enseignant ou personnel)</li>
                            <li>Créer un compte avec une adresse email institutionnelle valide</li>
                            <li>Fournir des informations exactes et complètes</li>
                            <li>Maintenir la confidentialité de vos identifiants</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>4. Contenu et propriété intellectuelle</h3>
                        <p>
                            En partageant du contenu sur InpDoc, vous :
                        </p>
                        <ul>
                            <li>Garantissez avoir les droits nécessaires pour partager ce contenu</li>
                            <li>Accordez à InpDoc une licence non exclusive pour héberger et diffuser ce contenu</li>
                            <li>Acceptez que votre contenu soit modéré par notre équipe</li>
                            <li>Comprenez que le contenu doit respecter les droits d'auteur</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>5. Règles de conduite</h3>
                        <p>
                            Les utilisateurs s'engagent à :
                        </p>
                        <ul>
                            <li>Ne pas partager de contenu illégal ou inapproprié</li>
                            <li>Respecter les autres utilisateurs</li>
                            <li>Ne pas utiliser la plateforme à des fins commerciales</li>
                            <li>Ne pas tenter de compromettre la sécurité du site</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>6. Modération et sanctions</h3>
                        <p>
                            InpDoc se réserve le droit de :
                        </p>
                        <ul>
                            <li>Modérer tout contenu avant publication</li>
                            <li>Supprimer tout contenu inapproprié</li>
                            <li>Suspendre ou supprimer les comptes en infraction</li>
                            <li>Signaler aux autorités compétentes tout contenu illégal</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>7. Limitation de responsabilité</h3>
                        <p>
                            InpDoc ne peut être tenu responsable :
                        </p>
                        <ul>
                            <li>Des contenus partagés par les utilisateurs</li>
                            <li>Des problèmes techniques ou interruptions de service</li>
                            <li>Des dommages directs ou indirects liés à l'utilisation du service</li>
                            <li>De la perte ou de l'altération de données</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>8. Protection des données</h3>
                        <p>
                            InpDoc s'engage à :
                        </p>
                        <ul>
                            <li>Protéger vos données personnelles</li>
                            <li>Ne pas les partager avec des tiers</li>
                            <li>Les utiliser uniquement dans le cadre du service</li>
                            <li>Respecter la réglementation en vigueur</li>
                        </ul>
                    </div>

                    <div class="mb-5">
                        <h3>9. Modification des conditions</h3>
                        <p>
                            InpDoc se réserve le droit de modifier ces conditions à tout moment. Les utilisateurs 
                            seront informés des changements importants. L'utilisation continue du service après 
                            modification implique l'acceptation des nouvelles conditions.
                        </p>
                    </div>

                    <div>
                        <h3>10. Contact</h3>
                        <p>
                            Pour toute question concernant ces conditions d'utilisation, veuillez nous contacter :
                        </p>
                        <ul>
                            <li>Par email : <a href="mailto:<?= ADMIN_EMAIL ?>"><?= ADMIN_EMAIL ?></a></li>
                            <li>Via notre <a href="contact.php">formulaire de contact</a></li>
                        </ul>
                    </div>

                    <hr class="my-5">

                    <div class="text-center">
                        <p class="mb-0">
                            <small class="text-muted">
                                Dernière mise à jour : <?= date('d/m/Y') ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?> 