<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if prescription ID is provided
if (!isset($_GET['prescription_id'])) {
    die("Prescription ID is missing.");
}

$prescription_id = intval($_GET['prescription_id']);

// Fetch prescription details
$stmt = $conn->prepare("
    SELECT p.*, u.name AS doctor_name 
    FROM prescriptions p 
    JOIN users u ON p.doctor_id = u.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prescription) {
    die("Prescription not found.");
}

// Include FPDF library
require('fpdf/fpdf.php'); // Update the path to FPDF

// Create PDF
class PDF extends FPDF
{
    // Header with MEDCONNECT logo
    function Header()
    {
        // Add logo
        $this->Image('assets/PDF.png', 10, 10, 30); // Update the path to your logo
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(80);
        $this->Cell(30, 10, 'MEDCONNECT', 0, 1, 'C');
        $this->Ln(20); // Add space after the header
    }

    // Footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Create a new PDF instance
$pdf = new PDF();
$pdf->AddPage();

// Set font for the prescription details
$pdf->SetFont('Arial', '', 12);

// Add prescription details
$pdf->Cell(0, 10, 'Prescription Details', 0, 1, 'C');
$pdf->Ln(10);

$pdf->Cell(40, 10, 'Doctor:', 0, 0);
$pdf->Cell(0, 10, $prescription['doctor_name'], 0, 1);

$pdf->Cell(40, 10, 'Date:', 0, 0);
$pdf->Cell(0, 10, $prescription['updated_at'], 0, 1);

$pdf->Cell(40, 10, 'Medication:', 0, 0);
$pdf->Cell(0, 10, $prescription['medication_name'], 0, 1);

$pdf->Cell(40, 10, 'Dosage:', 0, 0);
$pdf->Cell(0, 10, $prescription['dosage'], 0, 1);

$pdf->Cell(40, 10, 'Frequency:', 0, 0);
$pdf->Cell(0, 10, $prescription['frequency'], 0, 1);

$pdf->Cell(40, 10, 'Duration:', 0, 0);
$pdf->Cell(0, 10, $prescription['duration'], 0, 1);

$pdf->Cell(40, 10, 'Instructions:', 0, 0);
$pdf->MultiCell(0, 10, $prescription['instructions'], 0, 1);

$pdf->Cell(40, 10, 'Notes:', 0, 0);
$pdf->MultiCell(0, 10, $prescription['notes'], 0, 1);

// Output the PDF
$pdf->Output('D', 'Prescription_' . $prescription_id . '.pdf'); // Force download