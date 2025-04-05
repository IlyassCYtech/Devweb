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
$stmt = $conn->prepare("SELECT * FROM ObjetConnecte WHERE ID = :id");
$stmt->execute([':id' => $object_id]);
$object = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les informations de l'utilisateur pour vérifier s'il est admin
$userStmt = $conn->prepare("SELECT admin FROM users WHERE id = :user_id");
$userStmt->execute([':user_id' => $user_id]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
$isAdmin = $userInfo['admin'] ?? 0;

// Vérifications de sécurité
if (!$object) {
    $_SESSION['error_message'] = "Cet objet n'existe pas.";
    header("Location: objets.php");
    exit();
}

// Vérifier si l'objet est disponible, appartient à l'utilisateur actuel, ou si l'utilisateur est admin
if (!$isAdmin && $object['UtilisateurID'] !== null && $object['UtilisateurID'] != $user_id) {
    $_SESSION['error_message'] = "Cet objet est actuellement utilisé par un autre utilisateur.";
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Mode clair */
        :root {
            --bg-primary: #f9fafb;
            --text-primary: #111827;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
        }

        /* Mode sombre */
        [data-theme="dark"] {
            --bg-primary: #111827;
            --text-primary: #f9fafb;
            --card-bg: #1f2937;
            --card-border: #374151;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .glass-nav {
            background: rgba(17, 24, 39, 0.8);
            border-bottom: 1px solid rgba(55, 65, 81, 0.5);
        }

        .glass-effect {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
        }

        #map { height: 400px; }

        /* Dark mode toggle button */
        .theme-toggle {
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        [data-theme="dark"] .theme-toggle {
            background-color: #374151;
            color: #fbbf24;
        }

        .theme-toggle:hover {
            background-color: #e5e7eb;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: #4b5563;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #d1d5db;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #f9fafb;
        }

        [data-theme="dark"] .bg-white {
            background-color: var(--card-bg);
        }

        [data-theme="dark"] .bg-gray-200 {
            background-color: #374151;
        }

        [data-theme="dark"] .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <!-- Navbar avec bouton de thème -->
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="theme-toggle">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                    </button>
                    <a href="objets.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6 pt-24">
        <div class="glass-effect rounded-lg shadow-lg p-6 mb-6">
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
        function initMap() {
            map = L.map('map', {
                center: [initialLat, initialLng],
                zoom: 13,
                minZoom: 12,
                maxZoom: 18
            });

            // Ajouter les tuiles OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Définir les limites géographiques
            let bounds = [
                [37.703399, -123.017395],
                [37.812303, -122.348211]
            ];
            map.setMaxBounds(bounds);
            map.on('drag', function () {
                map.panInsideBounds(bounds, { animate: false });
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
                        if (btn.id !== 'theme-toggle') {
                            btn.classList.remove('bg-green-500', 'bg-red-500', 'text-white');
                            btn.classList.add('bg-gray-200');
                        }
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

        // Fonction pour définir le thème
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            // Mettre à jour les icônes
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            
            if (theme === 'dark') {
                darkIcon.classList.add('hidden');
                lightIcon.classList.remove('hidden');
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        }

        // Initialiser le thème
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);

        // Gestionnaire d'événements pour le bouton de basculement
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Initialiser la carte au chargement
        window.addEventListener('load', initMap);
    </script>
</body>
</html>
