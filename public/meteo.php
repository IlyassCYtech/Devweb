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
            animation: fadeIn 1.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .container {
            max-width: 900px;
            margin: 100px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: slideIn 1s ease-in-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #007bff;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.2rem;
            color: #555;
        }

        .weather-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            transition: transform 0.3s, box-shadow 0.3s;
            width: 100%;
            max-width: 700px;
        }

        .weather-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
        }

        .weather-card .icon img {
            width: 120px;
            height: 120px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .weather-card .details {
            flex-grow: 1;
            margin-left: 20px;
        }

        .weather-card .details h3 {
            font-size: 2rem;
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
            margin-top: 30px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .footer a {
            color: #007bff;
            font-weight: bold;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: color 0.3s, background-color 0.3s;
        }

        .footer a:hover {
            color: #fff;
            background-color: #007bff;
        }

        .alert {
            font-size: 1.2rem;
            font-weight: 500;
            color: #d9534f;
            background: rgba(255, 0, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            text-align: center;
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
