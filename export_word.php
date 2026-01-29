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

// Get selected records from database
$placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id IN ($placeholders)");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

$total_records = count($records);
$records_per_page = 5;

// Check if template exists
$template_path = 'templates/email_request_template.docx';
if (!file_exists($template_path)) {
    die('Template file not found. Please upload the template to: ' . $template_path);
}

// Helper function to format datetime for export
function formatDateTimeForExport($datetime) {
    if (empty($datetime)) return '';
    return date('M d, Y h:i A', strtotime($datetime));
}

// Helper function to format date for export (date only)
function formatDateForExport($date) {
    if (empty($date)) return date('F d, Y'); // Fallback to today if no record date
    return date('F d, Y', strtotime($date));
}

// Helper function to create a group key based on all three timestamps (exact match)
function createGroupKey($record) {
    $received = $record['datetime_received'] ?? 'NULL';
    $processed = $record['datetime_processed'] ?? 'NULL';
    $accomplished = $record['datetime_accomplished'] ?? 'NULL';
    
    return md5($received . '|' . $processed . '|' . $accomplished);
}

// Helper function to create readable group label for filename
function createGroupLabel($record, $index) {
    $parts = [];
    
    if (!empty($record['datetime_accomplished'])) {
        $parts[] = 'Accomplished_' . date('M_d', strtotime($record['datetime_accomplished']));
    } elseif (!empty($record['datetime_processed'])) {
        $parts[] = 'Processing_' . date('M_d', strtotime($record['datetime_processed']));
    } elseif (!empty($record['datetime_received'])) {
        $parts[] = 'Received_' . date('M_d', strtotime($record['datetime_received']));
    } else {
        $parts[] = 'Pending';
    }
    
    return 'Batch_' . $index . '_' . implode('_', $parts);
}

// Function to fill template with records
function fillTemplate($template_path, $records, $request_type) {
    $templateProcessor = new TemplateProcessor($template_path);
    
    // Get the first record for shared fields
    $first_record = $records[0];
    
    // Set the date from the RECORD DATE (not export date)
    $record_date = formatDateForExport($first_record['record_date'] ?? null);
    $templateProcessor->setValue('${DATE}', $record_date);
    
    // Set request type
    try {
        $templateProcessor->setValue('${REQTYPE}', $request_type);
    } catch (Exception $e) {
        // Placeholder doesn't exist, skip it
    }
    
    // Set SHARED timestamp placeholders (only uses row 1 placeholders as per template)
    $templateProcessor->setValue('${RECEIVED1}', formatDateTimeForExport($first_record['datetime_received'] ?? null));
    $templateProcessor->setValue('${PROCESSED1}', formatDateTimeForExport($first_record['datetime_processed'] ?? null));
    $templateProcessor->setValue('${ACCOMPLISHED1}', formatDateTimeForExport($first_record['datetime_accomplished'] ?? null));
    
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
    
    return $templateProcessor;
}

// Group records by their EXACT timestamp combination
$grouped_records = [];
foreach ($records as $record) {
    $group_key = createGroupKey($record);
    if (!isset($grouped_records[$group_key])) {
        $grouped_records[$group_key] = [];
    }
    $grouped_records[$group_key][] = $record;
}

$total_groups = count($grouped_records);

// If only 1 group and 5 or fewer records, generate single file
if ($total_groups == 1 && $total_records <= 5) {
    $group_records = reset($grouped_records);
    $templateProcessor = fillTemplate($template_path, $group_records, $request_type);
    
    $filename = 'Email_Request_' . date('Y-m-d_His') . '.docx';
    $temp_file = tempnam(sys_get_temp_dir(), 'word_');
    $templateProcessor->saveAs($temp_file);
    
    // Send headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp_file));
    header('Cache-Control: max-age=0');
    
    readfile($temp_file);
    unlink($temp_file);
    exit;
    
} else {
    // Multiple groups or more than 5 records in one group - create ZIP
    $zip = new ZipArchive();
    $zip_filename = 'Email_Requests_' . $total_records . '_records_' . $total_groups . '_batches_' . date('Y-m-d_His') . '.zip';
    $zip_path = sys_get_temp_dir() . '/' . $zip_filename;
    
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Cannot create ZIP file');
    }
    
    $temp_files = []; // Track temp files for cleanup
    $batch_counter = 1;
    
    // Process each group (records with same timestamps)
    foreach ($grouped_records as $group_key => $group_records) {
        $group_total = count($group_records);
        $group_pages = ceil($group_total / $records_per_page);
        
        // Create label from first record in group
        $group_label = createGroupLabel($group_records[0], $batch_counter);
        
        // Generate Word files for this group (5 records per file)
        for ($page = 0; $page < $group_pages; $page++) {
            $page_records = array_slice($group_records, $page * $records_per_page, $records_per_page);
            
            $templateProcessor = fillTemplate($template_path, $page_records, $request_type);
            
            // Create descriptive filename
            if ($group_pages > 1) {
                $doc_filename = $group_label . '_Page_' . ($page + 1) . '_of_' . $group_pages . '.docx';
            } else {
                $doc_filename = $group_label . '.docx';
            }
            
            $temp_file = tempnam(sys_get_temp_dir(), 'word_');
            $templateProcessor->saveAs($temp_file);
            
            $zip->addFile($temp_file, $doc_filename);
            $temp_files[] = $temp_file;
        }
        
        $batch_counter++;
    }
    
    $zip->close();
    
    // Send ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($zip_path));
    header('Cache-Control: max-age=0');
    
    readfile($zip_path);
    
    // Clean up temp files
    foreach ($temp_files as $temp_file) {
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
    }
    unlink($zip_path);
    exit;
}
?>