<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/db_connect.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

$tempDir = realpath(__DIR__ . '/../temp');
if (!$tempDir) {
    $tempDir = __DIR__ . '/../temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
}
putenv('TMPDIR=' . $tempDir);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_POST['selected_records']) || empty($_POST['selected_records'])) {
    header('Location: records.php?error=no_selection');
    exit;
}

$selected_ids = $_POST['selected_records'];

$placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM retrieval_records WHERE record_id IN ($placeholders) ORDER BY college_department, last_name, first_name");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

if (empty($records)) {
    header('Location: records.php?error=no_records');
    exit;
}

// Group records by department
$grouped_records = [];
foreach ($records as $record) {
    $dept = $record['college_department'];
    if (!isset($grouped_records[$dept])) {
        $grouped_records[$dept] = [];
    }
    $grouped_records[$dept][] = $record;
}

$templatePath = '../templates/retrieval_template.docx';

if (!file_exists($templatePath)) {
    die('Template file not found: ' . $templatePath);
}

$first_dept = array_key_first($grouped_records);
$dept_records = $grouped_records[$first_dept];

$records_to_export = array_slice($dept_records, 0, 10);
$record_count = count($records_to_export);

$first_record = $records_to_export[0];
$record_date = $first_record['record_date'] ?? null;
$request_type = $first_record['request_type'] ?? 'Account';

if (!empty($record_date)) {
    $formatted_date = date('F d, Y', strtotime($record_date));
} else {
    $formatted_date = date('F d, Y');
}

try {
    $templateProcessor = new TemplateProcessor($templatePath);
    
    // Header fields
    $templateProcessor->setValue('college_department', $first_dept);
    $templateProcessor->setValue('date', $formatted_date);
    
    // Request type checkboxes
    $templateProcessor->setValue('check_account', $request_type == 'Account' ? 'X' : '');
    $templateProcessor->setValue('check_password', $request_type == 'Password' ? 'X' : '');
    
    // Clone table rows for first table (request form)
    $templateProcessor->cloneRow('student_employee_id', $record_count);
    
    // Fill first table data
    for ($i = 0; $i < $record_count; $i++) {
        $record = $records_to_export[$i];
        $rowNum = $i + 1;
        
        $templateProcessor->setValue('student_employee_id#' . $rowNum, $record['student_employee_id'] ?? '');
        $templateProcessor->setValue('last_name#' . $rowNum, $record['last_name'] ?? '');
        $templateProcessor->setValue('first_name#' . $rowNum, $record['first_name'] ?? '');
        $templateProcessor->setValue('middle_name#' . $rowNum, $record['middle_name'] ?? '');
    }
    
    // Clone table rows for second table (monitoring form)
    $templateProcessor->cloneRow('monitor_student_employee_id', $record_count);
    
    // Fill second table data
    for ($i = 0; $i < $record_count; $i++) {
        $record = $records_to_export[$i];
        $rowNum = $i + 1;
        
        $templateProcessor->setValue('monitor_student_employee_id#' . $rowNum, $record['student_employee_id'] ?? '');
        $templateProcessor->setValue('email_address#' . $rowNum, $record['email_address'] ?? '');
        $templateProcessor->setValue('password#' . $rowNum, $record['password'] ?? '');
    }
    
    // Date/Time tracking fields
    $templateProcessor->setValue('datetime_received', '______________');
    $templateProcessor->setValue('datetime_processed', '______________');
    $templateProcessor->setValue('datetime_accomplished', '______________');
    
    // Remarks field
    $templateProcessor->setValue('remarks', '');
    
    // Second form date
    $templateProcessor->setValue('date2', $formatted_date);
    
    // Generate filename
    $safeDept = preg_replace('/[^a-zA-Z0-9]/', '_', $first_dept);
    $filename = 'Retrieval_' . $safeDept . '_' . date('Y-m-d_His') . '.docx';
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Save to output
    $templateProcessor->saveAs('php://output');
    exit;
    
} catch (Exception $e) {
    die('Error generating document: ' . $e->getMessage());
}