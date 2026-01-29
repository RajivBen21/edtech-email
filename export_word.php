<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Set custom temp directory
putenv('TMPDIR=' . __DIR__ . '/temp');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if records are selected
if (!isset($_POST['selected_records']) || empty($_POST['selected_records'])) {
    header('Location: records.php?error=no_selection');
    exit;
}

$selected_ids = $_POST['selected_records'];
$request_type = isset($_POST['request_type']) ? $_POST['request_type'] : 'New';

// Limit to 5 records (template has 5 rows)
if (count($selected_ids) > 5) {
    $selected_ids = array_slice($selected_ids, 0, 5);
}

// Get selected records from database
$placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id IN ($placeholders)");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

// Check if template exists
$template_path = 'templates/email_request_template.docx';
if (!file_exists($template_path)) {
    die('Template file not found. Please upload the template to: ' . $template_path);
}

// Load the template
$templateProcessor = new TemplateProcessor($template_path);

// Set the date
$templateProcessor->setValue('${DATE}', date('F d, Y'));

// Set request type (only if placeholder exists in template)
try {
    $templateProcessor->setValue('${REQTYPE}', $request_type);
} catch (Exception $e) {
    // Placeholder doesn't exist, skip it
}

// Fill in the records (up to 5)
for ($i = 1; $i <= 5; $i++) {
    if (isset($records[$i - 1])) {
        $record = $records[$i - 1];
        $templateProcessor->setValue('${LNAME' . $i . '}', $record['last_name']);
        $templateProcessor->setValue('${FNAME' . $i . '}', $record['first_name']);
        $templateProcessor->setValue('${MNAME' . $i . '}', $record['middle_name'] ?? '');
        $templateProcessor->setValue('${DEPT' . $i . '}', $record['college_department']);
        $templateProcessor->setValue('${EMAIL' . $i . '}', $record['email'] ?? '');
        $templateProcessor->setValue('${PASS' . $i . '}', $record['password'] ?? '');
    } else {
        // Empty row - clear placeholders
        $templateProcessor->setValue('${LNAME' . $i . '}', '');
        $templateProcessor->setValue('${FNAME' . $i . '}', '');
        $templateProcessor->setValue('${MNAME' . $i . '}', '');
        $templateProcessor->setValue('${DEPT' . $i . '}', '');
        $templateProcessor->setValue('${EMAIL' . $i . '}', '');
        $templateProcessor->setValue('${PASS' . $i . '}', '');
    }
}

// Generate filename
$filename = 'Email_Request_' . date('Y-m-d_His') . '.docx';

// Save to temporary file and download
$temp_file = tempnam(sys_get_temp_dir(), 'word_');
$templateProcessor->saveAs($temp_file);

// Send headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($temp_file));
header('Cache-Control: max-age=0');

// Output file
readfile($temp_file);

// Delete temp file
unlink($temp_file);
exit;
?>