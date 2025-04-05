<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Vérifier si l'utilisateur est admin
$stmt = $conn->prepare("SELECT admin FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['admin']) {
    header("Location: ../public/index.php");
    exit();
}

// Récupérer les objets avec leurs coordonnées GPS
$stmt = $conn->prepare("SELECT ID, Nom, Type, Etat, LocalisationGPS FROM ObjetConnecte WHERE LocalisationGPS IS NOT NULL");
$stmt->execute();
$objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte des Objets Connectés</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map { height: 600px; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .leaflet-popup-content {
            font-family: Arial, sans-serif;
        }
        /* Conteneur pour l'icône de localisation */
        .custom-icon .pin-container {
            position: relative;
            display: flex;
            justify-content: center;
        }

        /* L'image ronde à l'intérieur du pin */
        .custom-icon .pin-image {
            width: 40px; /* Taille de l'image */
            height: 40px;
            border-radius: 50%; /* Rend l'image ronde */
            object-fit: cover; /* S'assurer que l'image couvre le cercle */
            border: 3px solid white; /* Bordure blanche autour de l'image pour la séparer du pin */
        }

        /* Flèche en dessous de l'image */
        .custom-icon .pin-arrow {
            position: absolute;
            top: 100%; /* Placer la flèche en dessous de l'image */
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 10px solid black; /* Crée une flèche vers le bas */
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Nav Bar -->
    <nav class="glass-nav fixed w-full z-50 top-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="../assets/images/CY_Tech.png" alt="CY Tech Logo">
                    </div>
                </div>
                <div class="hidden sm:flex sm:items-center sm:justify-center flex-grow space-x-8">
                    <a href="../public/profil.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Profil</a>
                    <a href="../public/dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Accueil</a>
                    <a href="../public/objets.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">Objets</a>
                    <?php if ($user['admin']) : ?>
                        <a href="../admin/admin.php" class="text-yellow-600 hover:text-yellow-700 px-3 py-2 rounded-md text-sm font-medium">Admin</a>
                    <?php endif; ?>
                    <a href="../public/recherche.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">🔍</a>
                </div>
                <a href="../public/logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="pt-24 flex items-center justify-center">
        <div class="max-w-6xl w-full p-6">
            <div class="bg-white rounded-lg shadow-lg p-6 glass-effect">
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Carte des Objets Connectés</h1>
                <div id="map" class="rounded-lg shadow-md"></div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        let map = L.map('map', {
            center: [37.7749, -122.4194], // Centre de San Francisco
            zoom: 13,                     // Niveau de zoom initial
            minZoom: 12,                  // Zoom minimum pour éviter de trop dézoomer
            maxZoom: 18                   // Zoom maximum pour les détails
        });

        // Ajouter les tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Limiter la zone visible à San Francisco
        let bounds = [
            [37.703399, -123.017395], // Sud-Ouest (Southwest corner)
            [37.812303, -122.348211]  // Nord-Est (Northeast corner)
        ];
        map.setMaxBounds(bounds); // Empêche de sortir des limites
        map.on('drag', function () {
            map.panInsideBounds(bounds, { animate: false }); // Empêche de glisser hors des limites
        });

        // Fonction pour vérifier si une image existe
        async function checkImageExists(url) {
            try {
                const response = await fetch(url, { method: 'HEAD' });
                return response.ok;
            } catch {
                return false;
            }
        }

        let objects = <?= json_encode($objects, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        objects.forEach(async obj => {
            if (obj.LocalisationGPS) {
                let coords = obj.LocalisationGPS.split(',');
                let lat = parseFloat(coords[0]);
                let lng = parseFloat(coords[1]);

                // Définir les extensions possibles
                const extensions = ['.jpg', '.jpeg', '.png', '.gif'];
                let iconUrl = '/assets/images/default.jpg'; // Image par défaut

                // Vérifier chaque extension possible
                const baseUrl = `/assets/images/${obj.Type.toLowerCase()}`;
                for (const ext of extensions) {
                    const url = baseUrl + ext;
                    if (await checkImageExists(url)) {
                        iconUrl = url;
                        break;
                    }
                }

                // Créer le marqueur avec l'image trouvée
                let customIcon = L.divIcon({
                    className: 'custom-icon',
                    html: `
                        <div class="relative">
                            <div class="pin-container">
                                <img src="${iconUrl}" class="rounded-full pin-image" />
                                <div class="pin-arrow"></div>
                            </div>
                        </div>
                    `,
                    iconSize: [50, 60],
                    iconAnchor: [25, 60],
                    popupAnchor: [0, -60]
                });

                let marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);

                marker.bindPopup(`
                    <div class="text-sm">
                        <h3 class="font-bold text-lg">${obj.Nom}</h3>
                        <p><strong>Type:</strong> ${obj.Type}</p>
                        <p><strong>État:</strong> <span class="${obj.Etat === 'Actif' ? 'text-green-500' : 'text-red-500'}">${obj.Etat}</span></p>
                        <a href="../public/modifier_objet.php?id=${obj.ID}" class="text-blue-500 underline">Modifier</a>
                    </div>
                `);
            }
        });
    </script>
</body>
</html>
