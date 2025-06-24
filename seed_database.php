<?php
require_once 'config/config.php';

// Création du dossier uploads s'il n'existe pas
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Création des fichiers PDF factices
$sample_files = [
    'doc_analyse.pdf',
    'td_algo.pdf',
    'tp_chimie.pdf',
    'exam_physique.pdf',
    'cours_elec.pdf'
];

foreach ($sample_files as $file) {
    $content = "Ceci est un fichier de test pour " . $file;
    file_put_contents(UPLOAD_DIR . $file, $content);
}

try {
    $conn->beginTransaction();

    // Création des catégories
    $categories = [
        ['Mathématiques', 'Cours et exercices de mathématiques'],
        ['Physique', 'Cours et exercices de physique'],
        ['Informatique', 'Cours et documents informatiques'],
        ['Chimie', 'Cours et travaux pratiques de chimie'],
        ['Électronique', 'Documents sur l\'électronique'],
        ['Mécanique', 'Cours de mécanique générale']
    ];

    $stmt = $conn->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Création des utilisateurs
    $users = [
        ['prof.math@inphb.ci', password_hash('password123', PASSWORD_DEFAULT), 'Konan', 'Jean', 'enseignant', 'PROF001'],
        ['prof.info@inphb.ci', password_hash('password123', PASSWORD_DEFAULT), 'Koffi', 'Pierre', 'enseignant', 'PROF002'],
        ['etudiant1@inphb.ci', password_hash('password123', PASSWORD_DEFAULT), 'Kouassi', 'Marie', 'etudiant', 'CI2023001'],
        ['etudiant2@inphb.ci', password_hash('password123', PASSWORD_DEFAULT), 'Kouamé', 'Paul', 'etudiant', 'CI2023002']
    ];

    $stmt = $conn->prepare("INSERT INTO users (email, password, nom, prenoms, role, matricule, date_creation) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    foreach ($users as $user) {
        $stmt->execute($user);
    }

    // Création des tags
    $tags = ['Semestre 1', 'Semestre 2', 'Licence', 'Master', 'Exercices', 'Examens', 'TD', 'TP', 'Cours'];
    
    $stmt = $conn->prepare("INSERT INTO tags (nom) VALUES (?)");
    foreach ($tags as $tag) {
        $stmt->execute([$tag]);
    }

    // Création des documents
    $documents = [
        ['Cours Analyse 1', 'Introduction à l\'analyse mathématique', 1, 'cours', 1, 'doc_analyse.pdf', 'approuve'],
        ['TD Algorithmique', 'Exercices sur les algorithmes de base', 3, 'td', 2, 'td_algo.pdf', 'approuve'],
        ['TP Chimie Organique', 'Travaux pratiques de chimie organique', 4, 'tp', 1, 'tp_chimie.pdf', 'approuve'],
        ['Examen Physique 2022', 'Examen final de physique', 2, 'examen', 2, 'exam_physique.pdf', 'approuve'],
        ['Cours Électronique', 'Introduction aux circuits', 5, 'cours', 1, 'cours_elec.pdf', 'approuve'],
        ['TD Mécanique', 'Exercices de mécanique générale', 6, 'td', 2, 'td_mecanique.pdf', 'approuve'],
        ['TP Informatique', 'Travaux pratiques de programmation', 3, 'tp', 1, 'tp_info.pdf', 'approuve'],
        ['Examen Mathématiques', 'Examen de mathématiques avancées', 1, 'examen', 2, 'exam_math.pdf', 'approuve'],
        ['Cours Chimie', 'Introduction à la chimie', 4, 'cours', 1, 'cours_chimie.pdf', 'approuve']
    ];

    $stmt = $conn->prepare("
        INSERT INTO documents (titre, description, categorie_id, type_document, user_id, filename, statut, date_upload) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    foreach ($documents as $doc) {
        $stmt->execute($doc);
        $doc_id = $conn->lastInsertId();

        // Ajout de quelques tags aléatoires pour chaque document
        $random_tags = array_rand(array_flip($tags), 3);
        foreach ($random_tags as $tag) {
            $tag_id_stmt = $conn->prepare("SELECT id FROM tags WHERE nom = ?");
            $tag_id_stmt->execute([$tag]);
            $tag_id = $tag_id_stmt->fetchColumn();

            if ($tag_id) {
                $conn->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)")
                     ->execute([$doc_id, $tag_id]);
            }
        }
    }

    // Ajout de quelques notes et commentaires
    $notes = [
        [1, 3, 5, 'Très bon cours, bien expliqué'],
        [1, 4, 4, 'Document utile'],
        [2, 3, 3, 'Exercices intéressants'],
        [4, 4, 5, 'Excellent support de révision']
    ];

    $stmt = $conn->prepare("INSERT INTO notes (document_id, user_id, note, commentaire) VALUES (?, ?, ?, ?)");
    foreach ($notes as $note) {
        $stmt->execute($note);
    }

    $conn->commit();
    echo "Base de données remplie avec succès !\n\n";
    echo "Comptes créés :\n";
    echo "- Admin : admin@inphb.ci / admin\n";
    echo "- Enseignant 1 (PROF001) : prof.math@inphb.ci / password123\n";
    echo "- Enseignant 2 (PROF002) : prof.info@inphb.ci / password123\n";
    echo "- Étudiant 1 (CI2023001) : etudiant1@inphb.ci / password123\n";
    echo "- Étudiant 2 (CI2023002) : etudiant2@inphb.ci / password123\n";

} catch (Exception $e) {
    $conn->rollBack();
    echo "Erreur : " . $e->getMessage() . "\n";
} 