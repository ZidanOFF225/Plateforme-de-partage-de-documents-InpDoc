<?php
// Configuration générale
define('SITE_NAME', 'INPHB Docs');
define('BASE_URL', 'http://localhost/inpDoc');

// Configuration des uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx']);

// Configuration de la session
ini_set('session.cookie_lifetime', 60 * 60 * 24); // 24 heures
ini_set('session.gc_maxlifetime', 60 * 60 * 24); // 24 heures
session_start();

// Fonctions utilitaires
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isEnseignant() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'enseignant';
}

function redirect($path) {
    if (headers_sent()) {
        echo "<script>window.location.href='" . BASE_URL . $path . "';</script>";
    } else {
        header("Location: " . BASE_URL . $path);
    }
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclusion de la configuration de la base de données
require_once __DIR__ . '/database.php';
?> 