<?php
session_start();
include('../includes/db_connect.php');

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
if ($_SESSION['is_confirmed'] != 1) {
    // Si l'utilisateur n'est pas confirmé, le rediriger vers la page de confirmation
    header("Location: ../public/confirm.php");
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
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-6xl w-full p-6">
        <div class="bg-white rounded-lg shadow-lg p-6 glass-effect">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Carte des Objets Connectés</h1>
            <div id="map" class="rounded-lg shadow-md"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
let map = L.map('map').setView([48.8566, 2.3522], 6); // Paris par défaut

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

let objects = <?= json_encode($objects, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

objects.forEach(obj => {
    if (obj.LocalisationGPS) {
        let coords = obj.LocalisationGPS.split(',');
        let lat = parseFloat(coords[0]);
        let lng = parseFloat(coords[1]);

        let iconUrl = `/assets/images/${obj.Type.toLowerCase()}.jpg`;
        let fallbackIcon = `/assets/images/default.jpg`;

        // Vérifier si l'image existe avant de l'utiliser
        fetch(iconUrl, { method: 'HEAD' })
            .then(response => {
                if (!response.ok) throw new Error('Image not found');
                return iconUrl;
            })
            .catch(() => fallbackIcon) // Si erreur, utiliser l'image par défaut
            .then(finalIconUrl => {
                // Créer un "divIcon" personnalisé avec une flèche vers le bas et l'image à l'intérieur
                let customIcon = L.divIcon({
                    className: 'custom-icon', // Classe CSS pour l'icône personnalisée
                    html: `
                        <div class="relative">
                            <div class="pin-container">
                                <img src="${finalIconUrl}" class="rounded-full pin-image" />
                                <div class="pin-arrow"></div>
                            </div>
                        </div>
                    `,
                    iconSize: [50, 60], // Ajuster la taille de l'icône pour inclure la flèche
                    iconAnchor: [25, 60], // Ancrage au bas de l'icône pour aligner la flèche
                    popupAnchor: [0, -60] // Pour positionner la popup au-dessus de l'icône
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
            });
    }
});


    </script>

</body>
</html>
