<?php
session_start();
include('../includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$id || !in_array($action, ['approve', 'reject'])) {
        header("Location: admin.php");
        exit();
    }

    try {
        if ($action === 'approve') {
            // Récupérer le type d'objet demandé
            $stmtDemande = $conn->prepare("SELECT type_objet FROM DemandesTypeObjet WHERE id = :id");
            $stmtDemande->execute([':id' => $id]);
            $typeObjet = $stmtDemande->fetchColumn();

            if (!$typeObjet) {
                die("Type d'objet introuvable.");
            }

            // Vérifier si une image a été uploadée
            if (isset($_FILES['type_image']) && $_FILES['type_image']['error'] === UPLOAD_ERR_OK) {
                $image = $_FILES['type_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $fileType = $finfo->file($image['tmp_name']);

                if (in_array($fileType, $allowedTypes)) {
                    $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
                    $imageName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $typeObjet) . '.' . $extension; // Renommer l'image comme le type d'objet
                    $uploadPath = '../assets/images/' . $imageName;

                    if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                        // Ajouter le type d'objet dans la base de données
                        $stmtInsert = $conn->prepare("INSERT INTO TypeObjet (Nom) VALUES (:nom)");
                        $stmtInsert->execute([':nom' => $typeObjet]);

                        // Mettre à jour le statut de la demande
                        $stmtUpdate = $conn->prepare("UPDATE DemandesTypeObjet SET statut = 'Approuvé' WHERE id = :id");
                        $stmtUpdate->execute([':id' => $id]);

                        header("Location: admin.php");
                        exit();
                    } else {
                        die("Erreur lors de l'upload de l'image.");
                    }
                } else {
                    die("Format d'image non autorisé. Utilisez JPG, PNG ou GIF.");
                }
            } else {
                die("Aucune image fournie pour le type d'objet.");
            }
        } elseif ($action === 'reject') {
            // Rejeter la demande
            $stmt = $conn->prepare("UPDATE DemandesTypeObjet SET statut = 'Rejeté' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            header("Location: admin.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
} else {
    header("Location: admin.php");
    exit();
}
?>
