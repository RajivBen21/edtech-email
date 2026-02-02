<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../includes/db_connect.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Set custom temp directory
$tempDir = realpath(__DIR__ . '/../temp');
if (!$tempDir) {
    $tempDir = __DIR__ . '/../temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
}
putenv('TMPDIR=' . $tempDir);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if records are selected
if (!isset($_POST['selected_records']) || empty($_POST['selected_records'])) {
    header('Location: records.php?error=no_selection');
    exit;
}

$selected_ids = $_POST['selected_records'];

// Get selected records from database
$placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id IN ($placeholders) ORDER BY college_department, last_name, first_name");
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

// Template path
$templatePath = '../templates/workspace_license_template.docx';

// Check if template exists
if (!file_exists($templatePath)) {
    die('Template file not found: ' . $templatePath);
}

// Get first department's records
$first_dept = array_key_first($grouped_records);
$dept_records = $grouped_records[$first_dept];

// Limit to 10 records (template limitation)
$records_to_export = array_slice($dept_records, 0, 10);
$record_count = count($records_to_export);

// Get the record date from the first record (or use current date as fallback)
$first_record = $records_to_export[0];
$record_date = $first_record['record_date'] ?? null;

// Format the date - use record_date if available, otherwise use current date
if (!empty($record_date)) {
    $formatted_date = date('F d, Y', strtotime($record_date));
} else {
    $formatted_date = date('F d, Y');
}

try {
    // Load the template
    $templateProcessor = new TemplateProcessor($templatePath);
    
    // ===== HEADER FIELDS =====
    $templateProcessor->setValue('college_department', $first_dept);
    $templateProcessor->setValue('date', $formatted_date);
    
    // ===== CLONE TABLE ROWS =====
    $templateProcessor->cloneRow('last_name', $record_count);
    
    // ===== FILL TABLE DATA =====
    for ($i = 0; $i < $record_count; $i++) {
        $record = $records_to_export[$i];
        $rowNum = $i + 1;
        
        $lastName = $record['last_name'] ?? '';
        $firstName = trim(($record['first_name'] ?? '') . ' ' . ($record['middle_name'] ?? ''));
        $liceoEmail = $record['liceo_email'] ?? '';
        $employmentStatus = $record['employment_status'] ?? '';
        
        $templateProcessor->setValue('last_name#' . $rowNum, $lastName);
        $templateProcessor->setValue('first_name#' . $rowNum, $firstName);
        $templateProcessor->setValue('liceo_email#' . $rowNum, $liceoEmail);
        $templateProcessor->setValue('employment_status#' . $rowNum, $employmentStatus);
    }
    
    // ===== DATE/TIME TRACKING FIELDS =====
    $templateProcessor->setValue('datetime_received', '______________');
    $templateProcessor->setValue('datetime_processed', '______________');
    $templateProcessor->setValue('datetime_accomplished', '______________');
    
    // ===== REMARKS FIELD =====
    $templateProcessor->setValue('remarks', '');
    
    // Generate filename
    $safeDept = preg_replace('/[^a-zA-Z0-9]/', '_', $first_dept);
    $filename = 'Workspace_License_' . $safeDept . '_' . date('Y-m-d_His') . '.docx';
    
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