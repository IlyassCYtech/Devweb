<?php
session_start();
include('../includes/db_connect.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: objets.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$object_id = intval($_GET['id']);

// Récupérer les informations de l'objet
$stmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE ID = :id AND (UtilisateurID = :user_id OR UtilisateurID IS NULL)");
$stmt->execute([':id' => $object_id, ':user_id' => $user_id]);
$object = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$object) {
    header("Location: objets.php");
    exit();
}

// Traitement de la mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $updates = [];
        $params = [':id' => $object_id];

        if (isset($_POST['etat'])) {
            $updates[] = "Etat = :etat";
            $params[':etat'] = $_POST['etat'];
        }

        if (isset($_POST['lat']) && isset($_POST['lng'])) {
            $updates[] = "LocalisationGPS = :location";
            $params[':location'] = $_POST['lat'] . ',' . $_POST['lng'];
        }

        if (!empty($updates)) {
            $sql = "UPDATE ObjetConnecte SET " . implode(", ", $updates) . " WHERE ID = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            // Enregistrer dans l'historique
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
                ':objectId' => $object_id,
                ':action' => isset($_POST['lat']) ? 'Changement de position' : 'Modification état'
            ]);
        }

        $conn->commit();
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// Récupérer les coordonnées GPS actuelles
$coordinates = [];
if (!empty($object['LocalisationGPS'])) {
    $coordinates = explode(',', $object['LocalisationGPS']);
}
$lat = isset($coordinates[0]) ? trim($coordinates[0]) : 48.8566;
$lng = isset($coordinates[1]) ? trim($coordinates[1]) : 2.3522;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'objet | <?= htmlspecialchars($object['Nom']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map { height: 400px; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6 glass-effect">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    Modifier <?= htmlspecialchars($object['Nom']) ?>
                </h1>
                <a href="objets.php" class="text-gray-600 hover:text-gray-900">✕</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-semibold mb-4">État de l'objet</h2>
                    <div class="flex items-center space-x-4">
                        <button onclick="updateStatus('Actif')" 
                                class="px-4 py-2 rounded-lg <?= $object['Etat'] === 'Actif' ? 'bg-green-500 text-white' : 'bg-gray-200' ?>">
                            Actif
                        </button>
                        <button onclick="updateStatus('Inactif')" 
                                class="px-4 py-2 rounded-lg <?= $object['Etat'] === 'Inactif' ? 'bg-red-500 text-white' : 'bg-gray-200' ?>">
                            Inactif
                        </button>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-semibold mb-4">Informations</h2>
                    <div class="space-y-2 text-gray-600">
                        <p><span class="font-medium">Type:</span> <?= htmlspecialchars($object['Type']) ?></p>
                        <p><span class="font-medium">Marque:</span> <?= htmlspecialchars($object['Marque']) ?></p>
                        <p><span class="font-medium">Connectivité:</span> <?= htmlspecialchars($object['Connectivite']) ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-lg font-semibold mb-4">Localisation</h2>
                <div id="map" class="rounded-lg shadow-md"></div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;
        const initialLat = <?= $lat ?>;
        const initialLng = <?= $lng ?>;

        // Initialiser la carte
// Initialiser la carte
function initMap() {
    map = L.map('map', {
        center: [initialLat, initialLng], // Centre initial
        zoom: 13,                        // Niveau de zoom initial
        minZoom: 12,                     // Zoom minimum
        maxZoom: 18                      // Zoom maximum
    });

    // Ajouter les tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Définir les limites géographiques pour San Francisco
    let bounds = [
        [37.703399, -123.017395], // Sud-Ouest (Southwest corner)
        [37.812303, -122.348211]  // Nord-Est (Northeast corner)
    ];
    map.setMaxBounds(bounds); // Empêche de sortir des limites
    map.on('drag', function () {
        map.panInsideBounds(bounds, { animate: false }); // Empêche de glisser hors des limites
    });

    // Ajouter un marqueur draggable au centre
    marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    // Mettre à jour les coordonnées GPS lors du déplacement du marqueur
    marker.on('dragend', function (e) {
        const position = marker.getLatLng();
        updateLocation(position.lat, position.lng);
    });

    // Permettre à l'utilisateur de cliquer sur la carte pour déplacer le marqueur
    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateLocation(e.latlng.lat, e.latlng.lng);
    });
}

        // Mettre à jour l'état
        function updateStatus(status) {
    fetch('modifier_objet.php?id=<?= $object_id ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `etat=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Met à jour visuellement les boutons sans recharger la page
            document.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('bg-green-500', 'bg-red-500', 'text-white');
                btn.classList.add('bg-gray-200');
            });

            const activeBtn = status === 'Actif' 
                ? document.querySelector('button[onclick="updateStatus(\'Actif\')"]') 
                : document.querySelector('button[onclick="updateStatus(\'Inactif\')"]');

            if (activeBtn) {
                activeBtn.classList.remove('bg-gray-200');
                activeBtn.classList.add(status === 'Actif' ? 'bg-green-500' : 'bg-red-500', 'text-white');
            }
        }
    })
    .catch(error => console.error('Erreur:', error));
}


        // Mettre à jour la localisation
        function updateLocation(lat, lng) {
            fetch('modifier_objet.php?id=<?= $object_id ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lat=${lat}&lng=${lng}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erreur lors de la mise à jour de la localisation');
                }
            });
        }

        // Initialiser la carte au chargement
        window.addEventListener('load', initMap);
    </script>
</body>
</html>