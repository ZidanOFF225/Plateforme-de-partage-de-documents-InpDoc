<?php
require_once '../config/config.php';

// Destruction de la session
session_destroy();

// Suppression du cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Message de confirmation
$_SESSION['flash'] = ['info' => 'Vous avez été déconnecté avec succès.'];

// Redirection vers la page de connexion
redirect('/auth/login.php');
?> 