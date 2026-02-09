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
$stmt = $pdo->prepare("SELECT * FROM training_records WHERE record_id IN ($placeholders) ORDER BY college_department, theme_topic");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

if (empty($records)) {
    header('Location: records.php?error=no_records');
    exit;
}

$grouped_records = [];
foreach ($records as $record) {
    $dept = $record['college_department'];
    if (!isset($grouped_records[$dept])) {
        $grouped_records[$dept] = [];
    }
    $grouped_records[$dept][] = $record;
}

$templatePath = '../templates/training_template.docx';

if (!file_exists($templatePath)) {
    die('Template file not found: ' . $templatePath);
}

$first_dept = array_key_first($grouped_records);
$dept_records = $grouped_records[$first_dept];

$first_record = $dept_records[0];
$record_date = $first_record['record_date'] ?? null;
$activity_type = $first_record['activity_type'] ?? 'Training';

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
    
    // Activity type checkboxes
    $templateProcessor->setValue('check_training', $activity_type == 'Training' ? 'X' : '');
    $templateProcessor->setValue('check_seminar', $activity_type == 'Seminar' ? 'X' : '');
    $templateProcessor->setValue('check_workshop', $activity_type == 'Workshop' ? 'X' : '');
    
    // Main content fields
    $templateProcessor->setValue('theme_topic', $first_record['theme_topic'] ?? '');
    $templateProcessor->setValue('objectives', $first_record['objectives'] ?? '');
    $templateProcessor->setValue('activity_schedule', $first_record['activity_schedule'] ?? '');
    $templateProcessor->setValue('time_allocated', $first_record['time_allocated'] ?? '');
    $templateProcessor->setValue('no_of_participants', $first_record['no_of_participants'] ?? '');
    $templateProcessor->setValue('activity_venue', $first_record['activity_venue'] ?? '');
    
    // Remarks field
    $templateProcessor->setValue('remarks', $first_record['remarks'] ?? '');
    
    // Generate filename
    $safeDept = preg_replace('/[^a-zA-Z0-9]/', '_', $first_dept);
    $filename = 'Training_' . $safeDept . '_' . date('Y-m-d_His') . '.docx';
    
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