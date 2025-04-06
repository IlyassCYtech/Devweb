<?php
session_start();
require('../includes/db_connect.php');
require('../vendor/autoload.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Vérifier si l'utilisateur est admin ou gestionnaire
    $stmt = $conn->prepare("SELECT admin, gestion FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || ($user['admin'] != 1 && $user['gestion'] != 1)) {
        header("Location: ../public/index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur interne : " . $e->getMessage());
}

class PDF extends FPDF
{
    function Header()
    {
        $this->Image('../assets/images/CY_Tech.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, utf8_decode('Rapport d\'utilisation des services et objets connectés'), 0, 0, 'C');
        $this->Ln(20);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Généré le ' . date('d/m/Y à H:i:s')), 0, 0, 'L');
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }

    function SectionTitle($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 6, utf8_decode($title), 0, 1, 'L', true);
        $this->Ln(4);
    }

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

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Introduction
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, utf8_decode('Ce rapport présente une analyse des services et objets connectés.'), 0, 'J');
$pdf->Ln(5);

// Statistiques des objets connectés
$pdf->SectionTitle('Statistiques des objets connectés');

// Nombre total d'objets
$stmt = $conn->query("SELECT COUNT(*) as total FROM ObjetConnecte");
$total_objects = $stmt->fetch()['total'];
$pdf->Statistic('Nombre total d\'objets connectés', $total_objects, 'Totalité des objets connectés enregistrés.');

// Répartition par type d'objet
$stmt = $conn->query("SELECT Type, COUNT(*) as count FROM ObjetConnecte GROUP BY Type ORDER BY count DESC");
$object_types = $stmt->fetchAll();

foreach ($object_types as $type) {
    $pdf->Statistic($type['Type'], $type['count'], '');
}

// Répartition par état
$stmt = $conn->query("SELECT Etat, COUNT(*) as count FROM ObjetConnecte GROUP BY Etat ORDER BY count DESC");
$object_states = $stmt->fetchAll();

foreach ($object_states as $state) {
    $pdf->Statistic($state['Etat'], $state['count'], '');
}

// Statistiques sur les emprunts
$pdf->SectionTitle('Statistiques sur les emprunts');

// Nombre d'objets empruntés
$stmt = $conn->query("SELECT COUNT(DISTINCT UtilisateurID) as borrowed_count FROM ObjetConnecte WHERE UtilisateurID IS NOT NULL");
$borrowed_count = $stmt->fetch()['borrowed_count'];
$pdf->Statistic('Nombre d\'objets empruntés', $borrowed_count, 'Nombre total d\'objets actuellement empruntés.');

// Nombre total d'emprunts
$stmt = $conn->query("SELECT COUNT(*) as total_borrows FROM Historique_Actions WHERE type_action = 'Assignation'");
$total_borrows = $stmt->fetch()['total_borrows'];
$pdf->Statistic('Nombre total d\'emprunts', $total_borrows, 'Nombre total d\'emprunts enregistrés.');

// Nombre d'emprunts par objet
$pdf->SectionTitle('Nombre d\'emprunts par objet');
$stmt = $conn->query("
    SELECT o.Nom, COUNT(h.id) as borrow_count
    FROM ObjetConnecte o
    JOIN Historique_Actions h ON o.ID = h.id_objet_connecte
    WHERE h.type_action = 'Assignation'
    GROUP BY o.ID
    ORDER BY borrow_count DESC
");
$object_borrows = $stmt->fetchAll();

foreach ($object_borrows as $object) {
    $pdf->Statistic($object['Nom'], $object['borrow_count'], 'Nombre de fois que cet objet a été emprunté.');
}

// Conclusion
$pdf->AddPage();
$pdf->SectionTitle('Conclusion');
$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(0, 6, utf8_decode('Ce rapport fournit un aperçu des objets connectés, de leur utilisation et des emprunts effectués.'), 0, 'J');

// Sortie du PDF
$pdf->Output('rapport_utilisation.pdf', 'D');
exit();
?>
