<?php
// Ta clé API d'OpenWeatherMap
$apiKey = "9e6ee20ca0ca58178f12739e95a6798e"; // Remplacez par ta clé API
$city = "San Francisco"; // Ville pour afficher la météo
$unit = "metric"; // Unité pour la température (metric pour Celsius, imperial pour Fahrenheit)

// URL de l'API OpenWeatherMap
//$apiUrl = "http://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=$unit";
$apiUrl = "http://api.openweathermap.org/data/2.5/weather?q=".urlencode($city)."&appid=$apiKey&units=$unit";

// Appel à l'API OpenWeatherMap pour récupérer les données météo
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Vérification des erreurs dans la réponse de l'API
if ($data['cod'] != 200) {
    $error_message = "Erreur: " . $data['message'];
} else {
    // Récupération des informations météo
    $cityName = $data['name'];
    $weatherDescription = $data['weather'][0]['description'];
    $temperature = $data['main']['temp'];
    $humidity = $data['main']['humidity'];
    $windSpeed = $data['wind']['speed'];
    $country = $data['sys']['country'];
    $icon = $data['weather'][0]['icon'];
    $iconUrl = "http://openweathermap.org/img/w/$icon.png";
}

// Mapping des icônes d'OpenWeatherMap aux icônes personnalisées
$iconMapping = [
    '01d' => 'sunny.png',    // Ciel dégagé jour
    '01n' => 'clear_night.png', // Ciel dégagé nuit
    '02d' => 'partly_cloudy_day.png', // Nuageux partiellement jour
    '02n' => 'partly_cloudy_night.png', // Nuageux partiellement nuit
    '03d' => 'cloudy.png',   // Nuageux
    '04d' => 'overcast.png', // Couvert
    '09d' => 'rain.png',     // Pluie
    '10d' => 'rain_day.png', // Pluie jour
    '11d' => 'storm.png',    // Orage
    '13d' => 'snow.png',     // Neige
    '50d' => 'fog.png',      // Brouillard
];

// Récupération de l'icône météo de l'API
$icon = $data['weather'][0]['icon'];

// Vérification si une icône personnalisée existe pour la condition
if (array_key_exists($icon, $iconMapping)) {
    $iconPath = "icons/" . $iconMapping[$icon]; // Chemin vers l'icône personnalisée
} else {
    $iconPath = "icons/default.png"; // Icône par défaut si aucune correspondance
}

// Ajout d'émojis pour les conditions météo
$emojiMapping = [
    'clear sky' => '☀️',
    'few clouds' => '🌤️',
    'scattered clouds' => '☁️',
    'broken clouds' => '🌥️',
    'shower rain' => '🌦️',
    'rain' => '🌧️',
    'thunderstorm' => '⛈️',
    'snow' => '❄️',
    'mist' => '🌫️',
    'overcast clouds' => '☁️', // Ajout de l'émoji pour "nuages couverts"
];

// Ajout de l'émoji correspondant à la description météo
$weatherEmoji = $emojiMapping[strtolower($weatherDescription)] ?? '🌍';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Météo de San Francisco</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('../assets/images/backgrounddeux.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #333;
        }

        .container {
            max-width: 800px; /* Réduction de la largeur */
            margin: 125px auto; /* Réduction de la marge */
            padding: 15px; /* Réduction du padding */
            background: rgba(255, 255, 255, 0.85); /* Transparence plus marquée */
            border-radius: 10px; /* Bordures légèrement arrondies */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); /* Ombre plus douce */
            display: flex;
            flex-direction: row;
            align-items: center;
        }

        .header {
            flex: 1;
            text-align: left;
            padding-right: 20px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #007bff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2rem;
            color: #555;
        }

        .weather-card {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9); /* Légère transparence */
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .weather-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .weather-card .icon img {
            width: 100px;
            height: 100px;
        }

        .weather-card .details {
            flex-grow: 1;
            margin-left: 20px;
        }

        .weather-card .details h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .weather-card .details p {
            font-size: 1.2rem;
            color: #555;
            margin: 5px 0;
        }

        .footer {
            max-width: 500px; /* Réduction de la largeur */
            margin: 20px auto; /* Centrage horizontal et espacement vertical */
            padding: 15px; /* Ajustement du padding */
            background: rgba(255, 255, 255, 0.85); /* Transparence pour le fond */
            border-radius: 10px; /* Bordures légèrement arrondies */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); /* Ombre plus douce */
            text-align: center; /* Centrage du texte */
        }

        .footer a {
            color: #007bff; /* Couleur bleue pour le lien */
            font-weight: bold;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: #0056b3; /* Couleur bleue plus sombre au survol */
        }

        .alert {
            font-size: 1.1rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Météo de San Francisco</h1>
            <p>Consultez les prévisions météorologiques pour les prochains jours.</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger text-center">
                <?php echo $error_message; ?>
            </div>
        <?php else: ?>
            <div class="weather-card">
                <div class="icon">
                    <img src="<?php echo $iconUrl; ?>" alt="Icône météo" />
                </div>
                <div class="details">
                    <h3><?php echo $cityName . ', ' . $country; ?> <?php echo $weatherEmoji; ?></h3>
                    <p><strong>Condition météo:</strong> <?php echo ucfirst($weatherDescription); ?> <?php echo $weatherEmoji; ?></p>
                    <p><strong>Température:</strong> <?php echo $temperature; ?> °C 🌡️</p>
                    <p><strong>Humidité:</strong> <?php echo $humidity; ?> % 💧</p>
                    <p><strong>Vitesse du vent:</strong> <?php echo $windSpeed; ?> m/s 🌬️</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Powered by CY Tech | <a href="index.php">Retour à l'accueil</a></p>
    </div>
</body>
</html>
