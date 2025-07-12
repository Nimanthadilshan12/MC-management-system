<?php
// Start session to store certificate number and submitted data
session_start();

// Initialize variables to store form data and errors
$errors = [];
$success = '';
$patient_name = $address = $employment = $signs_symptoms = $medical_opinion = $recommendations = $fit_for_duty = $absence_period = $medical_officer_signature = '';
$certificate_number = '';
$submission_date = date('Y-m-d'); // Current date for submission

// Store submitted data for PDF generation
$submitted_data = isset($_SESSION['submitted_data']) ? $_SESSION['submitted_data'] : null;

// Database connection
$host = "localhost";
$db = "mc1";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Generate a unique certificate number if not already set for this session
if (!isset($_SESSION['certificate_number']) || !empty($_POST)) {
    $_SESSION['certificate_number'] = 'MC-' . strtoupper(uniqid()); // Generate unique ID with prefix
}
$certificate_number = $_SESSION['certificate_number'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $patient_name = filter_input(INPUT_POST, 'patient_name', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $employment = filter_input(INPUT_POST, 'employment', FILTER_SANITIZE_STRING);
    $signs_symptoms = filter_input(INPUT_POST, 'signs_symptoms', FILTER_SANITIZE_STRING);
    $medical_opinion = filter_input(INPUT_POST, 'medical_opinion', FILTER_SANITIZE_STRING);
    $recommendations = filter_input(INPUT_POST, 'recommendations', FILTER_SANITIZE_STRING);
    $fit_for_duty = filter_input(INPUT_POST, 'fit_for_duty', FILTER_SANITIZE_STRING);
    $absence_period = filter_input(INPUT_POST, 'absence_period', FILTER_SANITIZE_STRING);
    $medical_officer_signature = filter_input(INPUT_POST, 'medical_officer_signature', FILTER_SANITIZE_STRING);

    // Basic validation
    if (empty($patient_name)) {
        $errors[] = "Name of Patient is required.";
    }
    if (empty($address)) {
        $errors[] = "Usual Address of Residence is required.";
    }
    if (empty($fit_for_duty)) {
        $errors[] = "Fit for Duty selection is required.";
    }
    if ($fit_for_duty === 'No' && empty($absence_period)) {
        $errors[] = "Period of Absence is required if not fit for duty.";
    }
    if (empty($medical_officer_signature) || strpos($medical_officer_signature, 'data:image/png;base64,iVBORw0KGgo=') === 0) {
        $errors[] = "Medical Officer’s Signature is required.";
    }

    // If no errors, process the form
    if (empty($errors)) {
        try {
            // Insert data into the database
            $stmt = $conn->prepare("INSERT INTO medical_records (certificate_number, patient_name, address, employment, signs_symptoms, medical_opinion, recommendations, fit_for_duty, absence_period, medical_officer_signature, submission_date) VALUES (:certificate_number, :patient_name, :address, :employment, :signs_symptoms, :medical_opinion, :recommendations, :fit_for_duty, :absence_period, :medical_officer_signature, :submission_date)");
            $stmt->execute([
                ':certificate_number' => $certificate_number,
                ':patient_name' => $patient_name,
                ':address' => $address,
                ':employment' => $employment,
                ':signs_symptoms' => $signs_symptoms,
                ':medical_opinion' => $medical_opinion,
                ':recommendations' => $recommendations,
                ':fit_for_duty' => $fit_for_duty,
                ':absence_period' => $absence_period,
                ':medical_officer_signature' => $medical_officer_signature,
                ':submission_date' => $submission_date
            ]);

            // Store submitted data for PDF generation
            $_SESSION['submitted_data'] = [
                'certificate_number' => $certificate_number,
                'patient_name' => $patient_name,
                'address' => $address,
                'employment' => $employment ?: 'N/A',
                'signs_symptoms' => $signs_symptoms ?: 'N/A',
                'medical_opinion' => $medical_opinion ?: 'N/A',
                'recommendations' => $recommendations ?: 'N/A',
                'fit_for_duty' => $fit_for_duty,
                'absence_period' => $absence_period ?: 'N/A',
                'medical_officer_signature' => $medical_officer_signature,
                'submission_date' => $submission_date
            ];

            $success = "Form submitted successfully! Data saved to database.";

            // Clear form data
            $patient_name = $address = $employment = $signs_symptoms = $medical_opinion = $recommendations = $fit_for_duty = $absence_period = $medical_officer_signature = '';
            // Reset certificate number for next submission
            unset($_SESSION['certificate_number']);
            $_SESSION['certificate_number'] = 'MC-' . strtoupper(uniqid()); // Generate new certificate number
            $certificate_number = $_SESSION['certificate_number'];

        } catch (PDOException $e) {
            $errors[] = "Error saving data: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Certificate Form</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
        :root {
            --primary-color: #005b96;
            --secondary-color: #f4f7fa;
            --accent-color: #00a3e0;
            --error-color: #d32f2f;
            --success-color: #2e7d32;
            --text-color: #333;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', Arial, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 700;
        }

        .certificate-number {
            background-color: #fff;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: #fff;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(0, 163, 224, 0.2);
        }

        input.error-field, select.error-field, textarea.error-field {
            border-color: var(--error-color);
            background-color: #fff5f5;
        }

        .error {
            color: var(--error-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .success {
            background-color: #e8f5e9;
            color: var(--success-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeIn 0.5s ease-in;
        }

        .success::before {
            content: '✔';
            font-size: 1.2rem;
        }

        .signature-section {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid var(--primary-color);
        }

        .signature-pad {
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            background-color: #fff;
            width: 100%;
            height: 150px;
            box-shadow: var(--shadow);
        }

        .signature-image {
            max-width: 200px;
            height: auto;
            margin-top: 0.5rem;
        }

        button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        button[type="submit"] {
            background-color: var(--primary-color);
            color: white;
        }

        button[type="submit"]:hover {
            background-color: #004a7c;
        }

        .clear-signature {
            background-color: var(--error-color);
            color: white;
        }

        .clear-signature:hover {
            background-color: #b71c1c;
        }

        .pdf-button {
            background-color: var(--accent-color);
            color: white;
        }

        .pdf-button:hover {
            background-color: #0086b3;
        }

        button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 163, 224, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            body {
                padding: 1rem;
                margin: 1rem auto;
            }
            h2 {
                font-size: 1.5rem;
            }
            .certificate-number {
                font-size: 1rem;
            }
            button {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <h2>Medical Certificate Form</h2>
    <div class="certificate-number">
        Medical Certificate Number: <?php echo htmlspecialchars($certificate_number); ?>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="patient_name">Name of Patient *</label>
            <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($patient_name); ?>">
        </div>
        <div class="form-group">
            <label for="address">Usual Address of Residence *</label>
            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
        </div>
        <div class="form-group">
            <label for="employment">Where Employed</label>
            <input type="text" id="employment" name="employment" value="<?php echo htmlspecialchars($employment); ?>">
        </div>
        <div class="form-group">
            <label for="signs_symptoms">Signs and Symptoms Observed by Medical Officer</label>
            <textarea id="signs_symptoms" name="signs_symptoms" rows="4"><?php echo htmlspecialchars($signs_symptoms); ?></textarea>
        </div>
        <div class="form-group">
            <label for="medical_opinion">Medical Officer’s Opinion</label>
            <textarea id="medical_opinion" name="medical_opinion" rows="4"><?php echo htmlspecialchars($medical_opinion); ?></textarea>
        </div>
        <div class="form-group">
            <label for="recommendations">Medical Officer’s Recommendations</label>
            <textarea id="recommendations" name="recommendations" rows="4"><?php echo htmlspecialchars($recommendations); ?></textarea>
        </div>
        <div class="form-group">
            <label for="fit_for_duty">Is Applicant Fit for Duty? *</label>
            <select id="fit_for_duty" name="fit_for_duty">
                <option value="" <?php if ($fit_for_duty == '') echo 'selected'; ?>>Select</option>
                <option value="Yes" <?php if ($fit_for_duty == 'Yes') echo 'selected'; ?>>Yes</option>
                <option value="No" <?php if ($fit_for_duty == 'No') echo 'selected'; ?>>No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="absence_period">If Not, Period of Absence from Duty Recommended</label>
            <input type="text" id="absence_period" name="absence_period" value="<?php echo htmlspecialchars($absence_period); ?>">
        </div>
        <div class="form-group">
            <label for="signature">Medical Officer’s Signature *</label>
            <canvas id="signature-pad" class="signature-pad"></canvas>
            <input type="hidden" id="medical_officer_signature" name="medical_officer_signature" value="<?php echo htmlspecialchars($medical_officer_signature); ?>">
            <button type="button" class="clear-signature" onclick="signaturePad.clear()">Clear Signature</button>
        </div>
        <div class="form-group">
            <label for="submission_date">Date *</label>
            <input type="date" id="submission_date" name="submission_date" value="<?php echo htmlspecialchars($submission_date); ?>" readonly>
        </div>
        <button type="submit">Submit</button>
    </form>

     <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
        <?php if ($submitted_data): ?>
            <button class="pdf-button" onclick="generatePDF()">Generate PDF</button>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        try {
            // Initialize SignaturePad
            const canvas = document.getElementById('signature-pad');
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)'
            });

            // Adjust canvas size to fit container
            canvas.width = canvas.offsetWidth;
            canvas.height = 150;

            // Update hidden input with signature data on form submission
            document.querySelector('form').addEventListener('submit', function(e) {
                const dataUrl = signaturePad.toDataURL('image/png');
                document.getElementById('medical_officer_signature').value = dataUrl;
                console.log('Signature data:', dataUrl); // Debugging
            });

            // Clear signature button functionality
            document.querySelector('.clear-signature').addEventListener('click', function() {
                signaturePad.clear();
                document.getElementById('medical_officer_signature').value = '';
                console.log('Signature cleared'); // Debugging
            });

            // Load existing signature if present (e.g., after validation error)
            <?php if ($medical_officer_signature && strpos($medical_officer_signature, 'data:image/png;base64,iVBORw0KGgo=') !== 0): ?>
                signaturePad.fromDataURL('<?php echo $medical_officer_signature; ?>');
                console.log('Loaded existing signature'); // Debugging
            <?php endif; ?>

            // PDF generation function
            function generatePDF() {
                try {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();
                    
                    // Set font and add header
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(16);
                    doc.text('University Medical Centre', 105, 10, null, null, 'center');
                    doc.setFontSize(14);
                    doc.text('Medical Certificate', 105, 20, null, null, 'center');
                    doc.setLineWidth(0.5);
                    doc.line(10, 25, 200, 25); // Horizontal line

                    // Add form data
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(12);
                    let y = 35;
                    const data = <?php echo json_encode($submitted_data); ?>;
                    if (data) {
                        doc.text(`Certificate Number: ${data.certificate_number}`, 10, y);
                        y += 10;
                        doc.text(`Name of Patient: ${data.patient_name}`, 10, y);
                        y += 10;
                        doc.text(`Usual Address of Residence:`, 10, y);
                        y += 5;
                        doc.text(data.address, 10, y, { maxWidth: 190 });
                        y += 15;
                        doc.text(`Where Employed: ${data.employment}`, 10, y);
                        y += 10;
                        doc.text(`Signs and Symptoms: ${data.signs_symptoms}`, 10, y, { maxWidth: 190 });
                        y += 15;
                        doc.text(`Medical Officer’s Opinion: ${data.medical_opinion}`, 10, y, { maxWidth: 190 });
                        y += 15;
                        doc.text(`Recommendations: ${data.recommendations}`, 10, y, { maxWidth: 190 });
                        y += 15;
                        doc.text(`Fit for Duty: ${data.fit_for_duty}`, 10, y);
                        y += 10;
                        doc.text(`Period of Absence: ${data.absence_period}`, 10, y);
                        y += 10;
                        doc.text(`Submission Date: ${data.submission_date}`, 10, y);
                        y += 10;
                        doc.text('Medical Officer’s Signature:', 10, y);
                        y += 5;
                        if (data.medical_officer_signature && data.medical_officer_signature !== 'data:image/png;base64,iVBORw0KGgo=') {
                            doc.addImage(data.medical_officer_signature, 'PNG', 10, y, 50, 20);
                        }
                    }

                    // Save the PDF
                    doc.save(`medical_certificate_${data.certificate_number}.pdf`);
                    console.log('PDF generated successfully');
                } catch (error) {
                    console.error('PDF generation failed:', error);
                    alert('Failed to generate PDF. Please check the console for errors.');
                }
            }
        } catch (error) {
            console.error('SignaturePad initialization failed:', error);
            alert('SignaturePad failed to initialize. Please check the console for errors.');
        }
    </script>
</body>
</html>
<?php
// Close database connection
$conn = null;
?>