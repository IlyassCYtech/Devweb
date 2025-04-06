<?php
// Fichier: generate_report.php
session_start();
require('../includes/db_connect.php');
require('../vendor/autoload.php');

// Vérification de la connexion utilisateur
if (!isset($_SESSION['user_id'])) {
    log_error("Accès refusé : utilisateur non connecté.");
    header("Location: ../public/index.php");
    exit();
}
if ($_SESSION['is_confirmed'] != 1 || $_SESSION['is_confirmed_by_ad'] != 1) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Vérifier dans la base si l'utilisateur est admin
$stmt = $pdo->prepare("SELECT admin FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['admin'] != 1) {
    log_error("Accès refusé : utilisateur ID $user_id tenté d'accéder à l'admin.");
    header("Location: ../public/index.php");
    exit();
}

class PDF extends FPDF
{
    // En-tête
    function Header()
    {
        // Logo
        $this->Image('../assets/images/CY_Tech.png', 10, 6, 30);
        // Police Arial gras 15
        $this->SetFont('Arial', 'B', 15);
        // Décalage à droite
        $this->Cell(80);
        // Titre
        $this->Cell(30, 10, utf8_decode('Rapport de statistiques - Smart City San Francisco'), 0, 0, 'C');
        // Saut de ligne
        $this->Ln(20);
    }

    // Pied de page
    function Footer()
    {
        // Position à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Date de génération
        $this->Cell(0, 10, utf8_decode('Généré le ' . date('d/m/Y à H:i:s')), 0, 0, 'L');
        // Numéro de page
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    // Titre de section
    function SectionTitle($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 6, utf8_decode($title), 0, 1, 'L', true);
        $this->Ln(4);
    }

    // Sous-titre
    function SubTitle($subtitle)
    {
        $this->SetFont('Arial', 'I', 11);
        $this->Cell(0, 6, utf8_decode($subtitle), 0, 1, 'L');
        $this->Ln(2);
    }

    // Statistique avec description
    function Statistic($label, $value, $description = '')
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(120, 6, utf8_decode($label . ' : '), 0, 0, 'R');
        $this->SetFont('Arial', '', 10);
        $this->Cell(70, 6, utf8_decode($value), 0, 1, 'L');
        
        if (!empty($description)) {
            $this->SetFont('Arial', 'I', 9);
            $this->SetX(40);
            $this->MultiCell(150, 5, utf8_decode($description), 0, 'L');
            $this->Ln(2);
        }
    }
}

// Fonction pour convertir les coordonnées GPS en quartier de San Francisco
function getNeighborhood($coords) {
    // Dictionnaire de coordonnées approximatives pour les quartiers de San Francisco
    $neighborhoods = [
        '37.77' => [
            '-122.41' => 'SoMa (South of Market)',
            '-122.42' => 'Mission District',
            '-122.43' => 'Castro District'
        ],
        '37.78' => [
            '-122.40' => 'Financial District',
            '-122.41' => 'Union Square',
            '-122.42' => 'Civic Center'
        ],
        '37.76' => [
            '-122.41' => 'Mission Bay',
            '-122.42' => 'Potrero Hill',
            '-122.43' => 'Noe Valley'
        ],
        '37.79' => [
            '-122.40' => 'Embarcadero',
            '-122.41' => 'Chinatown',
            '-122.42' => 'Russian Hill'
        ],
        '37.80' => [
            '-122.41' => 'North Beach',
            '-122.42' => 'Fisherman\'s Wharf',
            '-122.43' => 'Marina District'
        ],
        '37.75' => [
            '-122.41' => 'Dogpatch', 
            '-122.42' => 'Bernal Heights',
            '-122.43' => 'Glen Park'
        ]
    ];
    
    // Extraire latitude et longitude
    list($lat, $long) = explode(',', $coords);
    
    // Simplifier pour la correspondance
    $lat_key = substr($lat, 0, 5);
    $long_key = substr($long, 0, 7);
    
    // Vérifier si les coordonnées correspondent à un quartier connu
    if (isset($neighborhoods[$lat_key]) && isset($neighborhoods[$lat_key][$long_key])) {
        return $neighborhoods[$lat_key][$long_key];
    }
    
    // Par défaut
    return 'San Francisco (centre)';
}

// Création du PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Date de génération
$date = date('d/m/Y à H:i:s');
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, utf8_decode('Rapport généré le ' . $date), 0, 1, 'R');
$pdf->Ln(5);

// Introduction
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, utf8_decode('Ce rapport présente une analyse complète des activités et statistiques de notre plateforme Smart City à San Francisco. Vous y trouverez des informations détaillées sur les utilisateurs, les objets connectés et leurs interactions.'), 0, 'J');
$pdf->Ln(5);

// 1. Statistiques des utilisateurs
$pdf->SectionTitle('1. Statistiques des utilisateurs');

// Nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];
$pdf->Statistic('Nombre total d\'utilisateurs', $total_users, 'Le nombre total d\'utilisateurs enregistrés sur notre plateforme.');

// Nombre d'administrateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE admin = 1");
$total_admins = $stmt->fetch()['total'];
$pdf->Statistic('Nombre d\'administrateurs', $total_admins, 'Utilisateurs ayant des droits d\'administration sur la plateforme.');

// Répartition par type de membre
$pdf->SubTitle('Répartition par type de membre :');
$stmt = $pdo->query("SELECT type_membre, COUNT(*) as count FROM users GROUP BY type_membre ORDER BY count DESC");
$member_types = $stmt->fetchAll();

foreach ($member_types as $type) {
    $percentage = round(($type['count'] / $total_users) * 100, 1);
    $pdf->Statistic($type['type_membre'], $type['count'] . ' (' . $percentage . '%)', '');
}

// Répartition par niveau d'expérience
$pdf->SubTitle('Répartition par niveau d\'expérience :');
$stmt = $pdo->query("SELECT niveau, COUNT(*) as count FROM users GROUP BY niveau ORDER BY count DESC");
$experience_levels = $stmt->fetchAll();

foreach ($experience_levels as $level) {
    $percentage = round(($level['count'] / $total_users) * 100, 1);
    $pdf->Statistic($level['niveau'], $level['count'] . ' (' . $percentage . '%)', '');
}

// Répartition par sexe
$pdf->SubTitle('Répartition par sexe :');
$stmt = $pdo->query("SELECT sexe, COUNT(*) as count FROM users GROUP BY sexe ORDER BY count DESC");
$gender_dist = $stmt->fetchAll();

foreach ($gender_dist as $gender) {
    $percentage = round(($gender['count'] / $total_users) * 100, 1);
    $pdf->Statistic($gender['sexe'], $gender['count'] . ' (' . $percentage . '%)', '');
}

// Utilisateur le plus actif
$stmt = $pdo->query("SELECT u.username, u.nom, u.prenom, COUNT(h.id) as actions_count 
                   FROM Historique_Actions h 
                   JOIN users u ON h.id_utilisateur = u.id 
                   GROUP BY h.id_utilisateur 
                   ORDER BY actions_count DESC 
                   LIMIT 1");
$most_active_user = $stmt->fetch();

if ($most_active_user) {
    $pdf->Statistic('Utilisateur le plus actif', $most_active_user['prenom'] . ' ' . $most_active_user['nom'] . ' (' . $most_active_user['username'] . ')', 
                  'Avec ' . $most_active_user['actions_count'] . ' actions enregistrées dans l\'historique.');
}

// 2. Statistiques des objets connectés
$pdf->AddPage();
$pdf->SectionTitle('2. Statistiques des objets connectés');

// Nombre total d'objets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ObjetConnecte");
$total_objects = $stmt->fetch()['total'];
$pdf->Statistic('Nombre total d\'objets connectés', $total_objects, 'Totalité des objets connectés enregistrés dans notre système.');

// Répartition par type d'objet
$pdf->SubTitle('Répartition par type d\'objet :');
$stmt = $pdo->query("SELECT Type, COUNT(*) as count FROM ObjetConnecte GROUP BY Type ORDER BY count DESC");
$object_types = $stmt->fetchAll();

foreach ($object_types as $type) {
    $percentage = round(($type['count'] / $total_objects) * 100, 1);
    $pdf->Statistic($type['Type'], $type['count'] . ' (' . $percentage . '%)', '');
}

// Répartition par état
$pdf->SubTitle('Répartition par état :');
$stmt = $pdo->query("SELECT Etat, COUNT(*) as count FROM ObjetConnecte GROUP BY Etat ORDER BY count DESC");
$object_states = $stmt->fetchAll();

foreach ($object_states as $state) {
    $percentage = round(($state['count'] / $total_objects) * 100, 1);
    $pdf->Statistic($state['Etat'], $state['count'] . ' (' . $percentage . '%)', '');
}

// Répartition par marque
$pdf->SubTitle('Répartition par marque :');
$stmt = $pdo->query("SELECT Marque, COUNT(*) as count FROM ObjetConnecte GROUP BY Marque ORDER BY count DESC");
$brands = $stmt->fetchAll();

foreach ($brands as $brand) {
    $percentage = round(($brand['count'] / $total_objects) * 100, 1);
    $pdf->Statistic($brand['Marque'], $brand['count'] . ' (' . $percentage . '%)', '');
}

// Objet le plus utilisé
$stmt = $pdo->query("SELECT o.Nom, o.Type, o.Marque, COUNT(h.id) as usage_count 
                   FROM ObjetConnecte o 
                   JOIN Historique_Actions h ON o.ID = h.id_objet_connecte 
                   GROUP BY h.id_objet_connecte 
                   ORDER BY usage_count DESC 
                   LIMIT 1");
$most_used_object = $stmt->fetch();

if ($most_used_object) {
    $pdf->Statistic('Objet le plus utilisé', $most_used_object['Nom'] . ' (' . $most_used_object['Type'] . ', ' . $most_used_object['Marque'] . ')', 
                  'Avec ' . $most_used_object['usage_count'] . ' utilisations enregistrées dans l\'historique.');
}

// Type d'objet le plus utilisé
$stmt = $pdo->query("SELECT o.Type, COUNT(h.id) as usage_count 
                   FROM ObjetConnecte o 
                   JOIN Historique_Actions h ON o.ID = h.id_objet_connecte 
                   GROUP BY o.Type 
                   ORDER BY usage_count DESC 
                   LIMIT 1");
$most_used_type = $stmt->fetch();

if ($most_used_type) {
    $pdf->Statistic('Type d\'objet le plus utilisé', $most_used_type['Type'], 
                  'Avec ' . $most_used_type['usage_count'] . ' utilisations enregistrées.');
}

// Objet utilisé le plus récemment
$stmt = $pdo->query("SELECT o.Nom, o.Type, o.Marque, h.date_heure 
                   FROM ObjetConnecte o 
                   JOIN Historique_Actions h ON o.ID = h.id_objet_connecte 
                   ORDER BY h.date_heure DESC 
                   LIMIT 1");
$last_used_object = $stmt->fetch();

if ($last_used_object) {
    $pdf->Statistic('Objet utilisé le plus récemment', $last_used_object['Nom'] . ' (' . $last_used_object['Type'] . ', ' . $last_used_object['Marque'] . ')', 
                  'Dernière utilisation le ' . date('d/m/Y à H:i:s', strtotime($last_used_object['date_heure'])));
}

// 3. Statistiques d'activité
$pdf->AddPage();
$pdf->SectionTitle('3. Statistiques d\'activité');

// Nombre total d'actions
$stmt = $pdo->query("SELECT COUNT(*) as total FROM Historique_Actions");
$total_actions = $stmt->fetch()['total'];
$pdf->Statistic('Nombre total d\'actions enregistrées', $total_actions, 'Total des actions enregistrées dans l\'historique.');

// Actions par type
$pdf->SubTitle('Répartition par type d\'action :');
$stmt = $pdo->query("SELECT type_action, COUNT(*) as count FROM Historique_Actions GROUP BY type_action ORDER BY count DESC");
$action_types = $stmt->fetchAll();

foreach ($action_types as $type) {
    $percentage = round(($type['count'] / $total_actions) * 100, 1);
    $pdf->Statistic($type['type_action'], $type['count'] . ' (' . $percentage . '%)', '');
}

// Moyenne d'actions par utilisateur
$avg_actions_per_user = $total_users > 0 ? round($total_actions / $total_users, 1) : 0;
$pdf->Statistic('Moyenne d\'actions par utilisateur', $avg_actions_per_user, 'Nombre moyen d\'actions effectuées par utilisateur.');

// Moyenne d'actions par objet connecté
$avg_actions_per_object = $total_objects > 0 ? round($total_actions / $total_objects, 1) : 0;
$pdf->Statistic('Moyenne d\'actions par objet connecté', $avg_actions_per_object, 'Nombre moyen d\'actions effectuées sur chaque objet connecté.');

// Tendance d'activité des derniers jours
$pdf->SubTitle('Tendance d\'activité sur les 7 derniers jours :');
$stmt = $pdo->query("SELECT DATE(date_heure) as day, COUNT(*) as count 
                   FROM Historique_Actions 
                   WHERE date_heure >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                   GROUP BY DATE(date_heure) 
                   ORDER BY day");
$daily_activity = $stmt->fetchAll();

foreach ($daily_activity as $day) {
    $formatted_date = date('d/m/Y', strtotime($day['day']));
    $pdf->Statistic($formatted_date, $day['count'] . ' actions', '');
}

// 4. Informations géographiques
$pdf->AddPage();
$pdf->SectionTitle('4. Informations géographiques sur les objets connectés');

// Mention de la localisation
$pdf->MultiCell(0, 6, utf8_decode('Tous les objets connectés sont situés à San Francisco. Voici une analyse de leur répartition dans les différents quartiers de la ville :'), 0, 'J');
$pdf->Ln(2);

// Récupérer les objets avec leur localisation GPS et convertir en quartiers
$stmt = $pdo->query("SELECT LocalisationGPS FROM ObjetConnecte WHERE LocalisationGPS IS NOT NULL");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les objets par quartier
$neighborhoods = [];
foreach ($locations as $loc) {
    $neighborhood = getNeighborhood($loc['LocalisationGPS']);
    if (!isset($neighborhoods[$neighborhood])) {
        $neighborhoods[$neighborhood] = 1;
    } else {
        $neighborhoods[$neighborhood]++;
    }
}

// Trier par nombre d'objets (du plus grand au plus petit)
arsort($neighborhoods);

// Afficher la répartition par quartier
$pdf->SubTitle('Répartition des objets connectés par quartier :');
foreach ($neighborhoods as $neighborhood => $count) {
    $percentage = round(($count / count($locations)) * 100, 1);
    $pdf->Statistic($neighborhood, $count . ' objets (' . $percentage . '%)', '');
}

// Répartition des types d'objets par quartier
$pdf->SubTitle('Types d\'objets par quartier :');
$stmt = $pdo->query("SELECT Type, LocalisationGPS FROM ObjetConnecte WHERE LocalisationGPS IS NOT NULL");
$type_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$neighborhood_types = [];
foreach ($type_locations as $item) {
    $neighborhood = getNeighborhood($item['LocalisationGPS']);
    $type = $item['Type'];
    
    if (!isset($neighborhood_types[$neighborhood])) {
        $neighborhood_types[$neighborhood] = [];
    }
    
    if (!isset($neighborhood_types[$neighborhood][$type])) {
        $neighborhood_types[$neighborhood][$type] = 1;
    } else {
        $neighborhood_types[$neighborhood][$type]++;
    }
}

// Afficher les types d'objets par quartier
foreach ($neighborhood_types as $neighborhood => $types) {
    $pdf->Statistic($neighborhood, '', 'Répartition des types d\'objets dans ce quartier :');
    foreach ($types as $type => $count) {
        $pdf->SetX(50);
        $pdf->Cell(0, 5, utf8_decode($type . ': ' . $count . ' objets'), 0, 1);
    }
    $pdf->Ln(2);
}

// 5. Conclusion et recommandations
$pdf->AddPage();
$pdf->SectionTitle('5. Conclusion et recommandations');

$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, utf8_decode('Ce rapport montre que notre plateforme Smart City à San Francisco est en pleine croissance avec ' . $total_users . ' utilisateurs et ' . $total_objects . ' objets connectés. ' .
                   'Les ' . $most_used_type['Type'] . 's sont le type d\'objet le plus populaire, représentant une part importante des interactions.'), 0, 'J');
$pdf->Ln(5);

// Identifier les quartiers sous-équipés
$min_objects_neighborhood = array_keys($neighborhoods, min($neighborhoods))[0];

$pdf->MultiCell(0, 6, utf8_decode('Recommandations pour améliorer l\'utilisation de la plateforme :'), 0, 'J');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(10, 6, '-', 0, 0);
$pdf->MultiCell(0, 6, utf8_decode('Encourager l\'utilisation des objets actuellement moins utilisés pour équilibrer l\'utilisation.'), 0, 'J');

$pdf->Cell(10, 6, '-', 0, 0);
$pdf->MultiCell(0, 6, utf8_decode('Mettre en place des formations pour les utilisateurs de niveau débutant afin d\'augmenter leur participation.'), 0, 'J');

$pdf->Cell(10, 6, '-', 0, 0);
$pdf->MultiCell(0, 6, utf8_decode('Améliorer la couverture des objets connectés dans le quartier de ' . $min_objects_neighborhood . ' qui est actuellement sous-équipé.'), 0, 'J');

$pdf->Cell(10, 6, '-', 0, 0);
$pdf->MultiCell(0, 6, utf8_decode('Organiser des événements pour promouvoir l\'utilisation de la mobilité connectée dans la ville.'), 0, 'J');

$pdf->Cell(10, 6, '-', 0, 0);
$pdf->MultiCell(0, 6, utf8_decode('Développer des partenariats avec les entreprises locales pour augmenter l\'adoption des technologies connectées.'), 0, 'J');

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('Smart City San Francisco - Vers une ville plus connectée et durable'), 0, 1, 'C');

// Sortie du PDF
$pdf->Output('rapport_statistiques_smartcity.pdf', 'D');
exit();
?>
