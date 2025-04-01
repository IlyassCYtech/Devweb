<?php
// Assurez-vous d'inclure le fichier de connexion à la base de données
include('../includes/db_connect.php');

// Vérifiez si l'ID de l'objet et de l'utilisateur sont passés
if (isset($_POST['objectID']) && isset($_POST['userID'])) {
    $objectID = $_POST['objectID'];
    $userID = $_POST['userID'];
    
    try {
        // Commencer une transaction pour garantir la cohérence des données
        $conn->beginTransaction();

        // 1️⃣ Rendre l'objet disponible (remettre UtilisateurID à NULL)
        $stmt = $conn->prepare("UPDATE ObjetConnecte SET UtilisateurID = NULL WHERE ID = :id");
        $stmt->bindParam(':id', $objectID, PDO::PARAM_INT);
        $stmt->execute();

        // 2️⃣ Ajouter une entrée dans l'historique des actions
        $stmt2 = $conn->prepare("INSERT INTO Historique_Actions (id_utilisateur, id_objet_connecte, type_action) VALUES (:userID, :objectID, 'Retour')");
        $stmt2->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt2->bindParam(':objectID', $objectID, PDO::PARAM_INT);
        $stmt2->execute();

        // 3️⃣ Récupérer l'XP et le niveau actuel de l'utilisateur
        $stmt3 = $conn->prepare("SELECT niveau, points_experience FROM users WHERE id = :userID");
        $stmt3->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt3->execute();
        $user = $stmt3->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $currentXP = $user['points_experience'] + 100; // Ajout des 100 XP
            $currentLevel = $user['niveau'];

            // Liste des niveaux
            $niveaux = ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'];

            // Vérifier si l'utilisateur doit passer au niveau supérieur
            if ($currentLevel !== 'Expert' && $currentXP >= 1000) {
                // Passer au niveau supérieur et remettre l'XP à 0
                $nextLevelIndex = array_search($currentLevel, $niveaux) + 1;
                $newLevel = $niveaux[$nextLevelIndex] ?? 'Expert'; // Si on dépasse, on reste "Expert"
                $newXP = 0;
            } else {
                // Sinon, garder le même niveau et les nouveaux XP
                $newLevel = $currentLevel;
                $newXP = $currentXP;
            }

            // 4️⃣ Mettre à jour l'XP et le niveau de l'utilisateur
            $stmt4 = $conn->prepare("UPDATE users SET points_experience = :xp, niveau = :niveau WHERE id = :userID");
            $stmt4->bindParam(':xp', $newXP, PDO::PARAM_INT);
            $stmt4->bindParam(':niveau', $newLevel, PDO::PARAM_STR);
            $stmt4->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt4->execute();
        }

        // Valider la transaction
        $conn->commit();

        // Réponse de succès
        echo "Objet rendu, action enregistrée et XP mis à jour. Nouveau niveau : $newLevel (XP : $newXP)";
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollBack();
        echo "Erreur: " . $e->getMessage();
    }
} else {
    echo "Aucun ID d'objet ou utilisateur fourni.";
}
?>
