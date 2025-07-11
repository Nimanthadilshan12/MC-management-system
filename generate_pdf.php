<?php
require_once('tcpdf/tcpdf.php'); // Include TCPDF library

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $patient_name = isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : 'Unknown';
    $patient_id = isset($_POST['patient_id']) ? htmlspecialchars($_POST['patient_id']) : 'Unknown';
    $illness = isset($_POST['illness']) ? htmlspecialchars($_POST['illness']) : 'Not specified';
    $medications = isset($_POST['medications']) ? $_POST['medications'] : [];

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Medical Center');
    $pdf->SetTitle('Prescription');
    $pdf->SetSubject('Patient Prescription');
    $pdf->SetKeywords('Prescription, Medical, Patient');

    // Set default header data
    $pdf->SetHeaderData('', 0, 'Medical Center Prescription', "Generated on: " . date('Y-m-d'));

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Add a page
    $pdf->AddPage();

    // Create HTML content for the PDF
    $html = '<h1>Prescription</h1>';
    $html .= '<h3>Patient Details</h3>';
    $html .= '<p><strong>Patient Name:</strong> ' . $patient_name . '</p>';
    $html .= '<p><strong>Patient ID:</strong> ' . $patient_id . '</p>';
    $html .= '<p><strong>Illness/Diagnosis:</strong> ' . $illness . '</p>';
    $html .= '<h3>Medications</h3>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Time</th></tr>';

    // Add medication details
    foreach ($medications as $med) {
        $medicine = isset($med['medicine']) ? htmlspecialchars($med['medicine']) : 'N/A';
        $dosage = isset($med['dosage']) ? htmlspecialchars($med['dosage']) : 'N/A';
        $frequency = isset($med['frequency']) ? htmlspecialchars($med['frequency']) : 'N/A';
        $time = isset($med['time']) ? htmlspecialchars($med['time']) : 'N/A';
        $html .= '<tr>';
        $html .= '<td>' . $medicine . '</td>';
        $html .= '<td>' . $dosage . '</td>';
        $html .= '<td>' . $frequency . '</td>';
        $html .= '<td>' . $time . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output the PDF as a download
    $pdf->Output('prescription_' . $patient_id . '.pdf', 'D');
} else {
    // If not a POST request, return an error
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Method Not Allowed';
}
?>