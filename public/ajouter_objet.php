<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fonction de validation
function validateInput($data, $maxLength = 255, $type = 'text') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    // Vérification de la longueur
    if (strlen($data) > $maxLength) {
        throw new Exception("La longueur maximale autorisée est de $maxLength caractères");
    }

    // Validation selon le type
    switch($type) {
        case 'number':
            if (!is_numeric($data)) {
                throw new Exception("La valeur doit être un nombre");
            }
            break;
        case 'gps':
            if ($data && !preg_match('/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?$/', $data)) {
                throw new Exception("Format GPS invalide");
            }
            break;
        case 'text':
            // Autoriser uniquement les lettres, chiffres et caractères de base
            if (!preg_match('/^[a-zA-Z0-9\s\-_.,\'\"àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ]+$/', $data)) {
                throw new Exception("Caractères non autorisés détectés");
            }
            break;
    }
    
    return $data;
}

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT id, username, nom, prenom, date_naissance, sexe, email, niveau, points_experience, admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les types d'objets disponibles
$typeStmt = $conn->prepare("SELECT * FROM TypeObjet");
$typeStmt->execute();
$types = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des entrées
        $nom = validateInput($_POST['nom'], 100);
        $type = validateInput($_POST['type'], 50);
        $description = validateInput($_POST['description'], 1000);
        $marque = validateInput($_POST['marque'], 100);
        $etat = in_array($_POST['etat'], ['Actif', 'Inactif']) ? $_POST['etat'] : throw new Exception("État invalide");
        $connectivite = validateInput($_POST['connectivite'], 50);
        $energie = validateInput($_POST['energie'], 50);
        
        // Validation des champs numériques
        $luminosite = !empty($_POST['luminosite']) ? validateInput($_POST['luminosite'], 10, 'number') : null;
        $vitesse = !empty($_POST['vitesse']) ? validateInput($_POST['vitesse'], 10, 'number') : null;
        $batterie = !empty($_POST['batterie']) ? validateInput($_POST['batterie'], 10, 'number') : null;
        
        // Validation des champs spéciaux
        $etatLuminaire = !empty($_POST['etatLuminaire']) ? validateInput($_POST['etatLuminaire'], 50) : null;
        $localisation = !empty($_POST['localisation']) ? validateInput($_POST['localisation'], 100, 'gps') : null;

        // Début de la transaction
        $conn->beginTransaction();

        // Insertion de l'objet
        $stmt = $conn->prepare("
            INSERT INTO ObjetConnecte (
                Nom, Type, Description, Marque, Etat,
                Connectivite, EnergieUtilisee, Luminosite,
                EtatLuminaire, LocalisationGPS, Vitesse, EtatBatterie
            ) VALUES (
                :nom, :type, :description, :marque, :etat,
                :connectivite, :energie, :luminosite,
                :etatLuminaire, :localisation, :vitesse, :batterie
            )
        ");

        $stmt->execute([
            ':nom' => $nom,
            ':type' => $type,
            ':description' => $description,
            ':marque' => $marque,
            ':etat' => $etat,
            ':connectivite' => $connectivite,
            ':energie' => $energie,
            ':luminosite' => $luminosite,
            ':etatLuminaire' => $etatLuminaire,
            ':localisation' => $localisation,
            ':vitesse' => $vitesse,
            ':batterie' => $batterie
        ]);

        $objectId = $conn->lastInsertId();

        // Enregistrement dans l'historique avec plus de détails
        $historyStmt = $conn->prepare("
            INSERT INTO Historique_Actions (
                id_utilisateur, 
                id_objet_connecte, 
                type_action, 
                date_heure
            ) VALUES (
                :userId,
                :objectId,
                :action,
                NOW()
            )
        ");

        $historyStmt->execute([
            ':userId' => $user_id,
            ':objectId' => $objectId,
            ':action' => 'Création'
        ]);

        // Validation de la transaction
        $conn->commit();

        $_SESSION['success'] = "Objet connecté ajouté avec succès !";
        header("Location: ajouter_objet.php");
        exit();

    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajouter un Objet | CY Tech</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="objets.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>
                <a href="logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
    <div class="bg-white shadow-lg rounded-lg p-6 max-w-3xl mx-auto relative">
    <a href="../public/objets.php" class="absolute top-2 right-2 text-gray-700 hover:text-gray-900 text-xl font-bold border-2 border-transparent p-2">
    ❌
</a>


                    <h1 class="text-3xl font-bold text-gray-900 mb-8">Ajouter un Objet Connecté</h1>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
                <?php endif; ?>


            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" onsubmit="return validateForm()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom de l'objet</label>
                        <input type="text" name="nom" id="nom" required maxlength="100" pattern="[a-zA-Z0-9\s\-_.,\'\"àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ]+"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php foreach ($types as $type): ?>
                                <option value="<?= htmlspecialchars($type['Nom']) ?>">
                                    <?= htmlspecialchars($type['Nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="marque" class="block text-sm font-medium text-gray-700">Marque</label>
                        <input type="text" name="marque" id="marque" required maxlength="100" pattern="[a-zA-Z0-9\s\-_.,\'\"àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ]+"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="etat" class="block text-sm font-medium text-gray-700">État</label>
                        <select name="etat" id="etat" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Actif">Actif</option>
                            <option value="Inactif">Inactif</option>
                        </select>
                    </div>

                    <div>
                        <label for="connectivite" class="block text-sm font-medium text-gray-700">Connectivité</label>
                        <select name="connectivite" id="connectivite" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Wi-Fi">Wi-Fi</option>
                            <option value="Bluetooth">Bluetooth</option>
                            <option value="Zigbee">Zigbee</option>
                            <option value="LoRa">LoRa</option>
                        </select>
                    </div>

                    <div>
                        <label for="energie" class="block text-sm font-medium text-gray-700">Énergie Utilisée</label>
                        <select name="energie" id="energie" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Batterie Lithium">Batterie Lithium</option>
                            <option value="Batterie Li-ion">Batterie Li-ion</option>
                            <option value="Électricité">Électricité</option>
                            <option value="Solaire">Solaire</option>
                        </select>
                    </div>

                    <div>
                        <label for="batterie" class="block text-sm font-medium text-gray-700">État de la Batterie (%)</label>
                        <input type="number" name="batterie" id="batterie" min="0" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="vitesse" class="block text-sm font-medium text-gray-700">Vitesse (km/h)</label>
                        <input type="number" name="vitesse" id="vitesse" step="0.1" min="0" max="999.9"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="luminosite" class="block text-sm font-medium text-gray-700">Luminosité (%)</label>
                        <input type="number" name="luminosite" id="luminosite" min="0" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="etatLuminaire" class="block text-sm font-medium text-gray-700">État Luminaire</label>
                        <select name="etatLuminaire" id="etatLuminaire"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Non applicable</option>
                            <option value="Allumé">Allumé</option>
                            <option value="Éteint">Éteint</option>
                        </select>
                    </div>

                    <div>
                        <label for="localisation" class="block text-sm font-medium text-gray-700">Localisation GPS</label>
                        <input type="text" name="localisation" id="localisation" placeholder="latitude,longitude"
                               pattern="^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?$"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="4" required maxlength="1000"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    <p class="mt-1 text-sm text-gray-500">Caractères restants: <span id="charCount">1000</span></p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Ajouter l'objet
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Validation côté client
        function validateForm() {
            const nom = document.getElementById('nom').value;
            const description = document.getElementById('description').value;
            const localisation = document.getElementById('localisation').value;

            // Vérification des caractères spéciaux
            const regex = /^[a-zA-Z0-9\s\-_.,\'\"àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ]+$/;
            
            if (!regex.test(nom)) {
                alert("Le nom contient des caractères non autorisés");
                return false;
            }

            if (description.length > 1000) {
                alert("La description ne doit pas dépasser 1000 caractères");
                return false;
            }

            if (localisation && !localisation.match(/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?$/)) {
                alert("Format GPS invalide. Utilisez le format : latitude,longitude");
                return false;
            }

            return true;
        }

        // Compteur de caractères pour la description
        document.getElementById('description').addEventListener('input', function(e) {
            const maxLength = 1000;
            const currentLength = e.target.value.length;
            const remaining = maxLength - currentLength;
            document.getElementById('charCount').textContent = remaining;
            
            if (currentLength > maxLength) {
                e.target.value = e.target.value.substring(0, maxLength);
            }
        });

        // Validation en temps réel des champs numériques
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function(e) {
                const value = parseFloat(e.target.value);
                const min = parseFloat(e.target.min);
                const max = parseFloat(e.target.max);
                
                if (value < min) e.target.value = min;
                if (value > max) e.target.value = max;
            });
        });
    </script>
</body>
</html>