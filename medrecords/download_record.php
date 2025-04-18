<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selectedDate = isset($_GET['date']) ? $_GET['date'] : '';

if (empty($selectedDate)) {
    echo "No date specified.";
    exit;
}

// Fetch the health records for the given date and user
$query = "
    SELECT 
        health_metrics.temperature, health_metrics.blood_pressure, 
        health_metrics.stress_level, health_metrics.glucose_level, 
        health_metrics.spo2_level, health_metrics.ecg_data,
        health_metrics.heart_rate, health_metrics.timestamp,
        users.name, users.age, users.height, users.weight
    FROM health_metrics 
    JOIN users ON health_metrics.user_id = users.id 
    WHERE DATE(health_metrics.timestamp) = '$selectedDate'
      AND health_metrics.user_id = '$user_id'
";
$result = mysqli_query($conn, $query);
if (!$result) {
    echo "Error: " . mysqli_error($conn);
    exit;
}

$records = mysqli_fetch_all($result, MYSQLI_ASSOC);
if (empty($records)) {
    echo "No records found for the selected date.";
    exit;
}

// Include FPDF library (ensure the FPDF files are in the 'fpdf' folder)
require('../fpdf/fpdf.php');

class PDF extends FPDF {
    // Page header with logo, brand, and slogan
    function Header() {
        // Display site logo in the top left
        $this->Image('../assets/PDF.png', 10, 6, 30); // x=10, y=6, width=30mm
        // Move to the right for the brand name
        $this->Cell(40);
        // Brand name in large bold font
        $this->SetFont('Arial','B',28);
        $this->Cell(0,10,'MEDCONNECT',0,1);
        // Slogan in a smaller font
        $this->SetFont('Arial','',12);
        $this->Cell(40);
        $this->Cell(0,10,'A Smart Telemedicine solution for Remote Healthcare',0,1);
        $this->Ln(10);
    }
    
    // Page footer with page numbers
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Increase base font size and define spacing variables
$pdf->SetFont('Arial','',14);
$lineHeight = 10;    // increased line height for extra spacing
$padding = 8;        // increased card padding

foreach ($records as $record) {
    // ----------------------------
    // Card 1: Health Metrics Card
    // ----------------------------
    // Define card dimensions
    $x = 10;
    $y = $pdf->GetY();
    $width = 190; 
    // We'll display 7 rows: a title row + 6 rows for metrics
    $numRows = 7;
    $cardHeight = $numRows * $lineHeight + 2 * $padding;
    
    // Draw card background with a light border
    $pdf->SetFillColor(255,255,255);
    $pdf->SetDrawColor(200,200,200);
    $pdf->Rect($x, $y, $width, $cardHeight, 'DF');
    
    // --- Card Title ---
    $pdf->SetXY($x, $y);
    $pdf->SetFillColor(220,220,255); // light blue background for the title
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell($width, $lineHeight, "Health Metrics", 0, 1, 'C', true);
    
    // Set position for metrics data
    $pdf->SetFont('Arial','',14);
    $pdf->SetXY($x + $padding, $y + $padding + $lineHeight);
    $iconW = 8;
    $iconH = 8;
    
    // Row 1: Temperature
    $pdf->Image('../assets/icons/tempreture.png', $pdf->GetX(), $pdf->GetY(), $iconW, $iconH);
    $pdf->Cell(12);
    $pdf->Cell(0, $lineHeight, "Temperature: " . $record['temperature'] . " °C", 0, 1);
    
    // Row 2: Blood Pressure
    $pdf->SetX($x + $padding);
    $pdf->Image('../assets/icons/bloodpresure.png', $pdf->GetX(), $pdf->GetY(), $iconW, $iconH);
    $pdf->Cell(12);
    $pdf->Cell(0, $lineHeight, "Blood Pressure: " . $record['blood_pressure'] . " mmHg", 0, 1);
    
    // Row 3: Stress Level
    $pdf->SetX($x + $padding);
    $pdf->Image('../assets/icons/stress.png', $pdf->GetX(), $pdf->GetY(), $iconW, $iconH);
    $pdf->Cell(12);
    $pdf->Cell(0, $lineHeight, "Stress Level: " . $record['stress_level'], 0, 1);
    
    // Row 4: Glucose Level
    $pdf->SetX($x + $padding);
    $pdf->Image('../assets/icons/glucose.png', $pdf->GetX(), $pdf->GetY(), $iconW, $iconH);
    $pdf->Cell(12);
    $pdf->Cell(0, $lineHeight, "Glucose Level: " . $record['glucose_level'] . " mg/dL", 0, 1);
    
    // Row 5: SpO2 Level
    $pdf->SetX($x + $padding);
    $pdf->Image('../assets/icons/spo2.png', $pdf->GetX(), $pdf->GetY(), $iconW, $iconH);
    $pdf->Cell(12);
    $pdf->Cell(0, $lineHeight, "SpO2 Level: " . $record['spo2_level'] . " %", 0, 1);
    
    // Row 6: Heart Rate
    $pdf->SetX($x + $padding);
    $pdf->Image('../assets/icons/heart.png', $pdf->GetX(), $pdf->GetY(), $iconW, $iconH);
    $pdf->Cell(12);
    $pdf->Cell(0, $lineHeight, "Heart Rate: " . $record['heart_rate'] . " BPM", 0, 1);
    
    // Add gap after the card
    $pdf->Ln(10);
    
    // ----------------------------
    // Card 2: ECG Graph Card
    // ----------------------------
    // Only include if ECG data exists
    if (!empty($record['ecg_data'])) {
        // Draw a title for the ECG graph card with a colored background
        $pdf->SetFont('Arial','B',16);
        $pdf->SetFillColor(220,255,220); // light green background
        $pdf->Cell(0,10,"ECG Graph",0,1,'C', true);
        $pdf->SetFont('Arial','',14);
        
        // Define graph card dimensions
        $graphX = 10;
        $graphY = $pdf->GetY();
        $graphWidth = 190;
        $graphHeight = 60;
        
        // Draw graph card border
        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(200,200,200);
        $pdf->Rect($graphX, $graphY, $graphWidth, $graphHeight, 'DF');
        
        // Process ECG data: Convert comma-separated string into an array of floats
        $ecgArray = array_map('floatval', explode(',', $record['ecg_data']));
        if (empty($ecgArray)) {
            $ecgArray = [0];
        }
        $minVal = min($ecgArray);
        $maxVal = max($ecgArray);
        $range = $maxVal - $minVal;
        if ($range == 0) { $range = 1; }
        
        // Set graph plotting area (with padding)
        $plotPadding = 8;
        $plotX = $graphX + $plotPadding;
        $plotY = $graphY + $plotPadding;
        $plotWidth = $graphWidth - 2 * $plotPadding;
        $plotHeight = $graphHeight - 2 * $plotPadding;
        
        // Determine horizontal spacing for ECG points
        $numPoints = count($ecgArray);
        $step = $numPoints > 1 ? $plotWidth / ($numPoints - 1) : 0;
        
        // Set line style for ECG graph
        $pdf->SetDrawColor(75,192,192);
        $pdf->SetLineWidth(0.7);
        
        // Plot ECG data as a line graph
        for ($i = 0; $i < $numPoints - 1; $i++) {
            $x1 = $plotX + $i * $step;
            $x2 = $plotX + ($i + 1) * $step;
            $y1 = $plotY + $plotHeight - (($ecgArray[$i] - $minVal) / $range * $plotHeight);
            $y2 = $plotY + $plotHeight - (($ecgArray[$i+1] - $minVal) / $range * $plotHeight);
            $pdf->Line($x1, $y1, $x2, $y2);
        }
        
        // Add gap after the ECG graph card
        $pdf->Ln(15);
    }
}



// ----------------------------
// Final Report Timestamp
// ----------------------------
$pdf->Ln(50);
$pdf->SetFont('Arial','I',12);
$pdf->Cell(0,10,"Report generated on: " . date("Y-m-d H:i:s"), 0, 1, 'C');

$pdf->Output('D', 'health_record_' . $selectedDate . '.pdf');
exit;
?>
