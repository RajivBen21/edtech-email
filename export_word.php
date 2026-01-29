<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

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

// Get selected records from database (no limit now - we handle multiple pages)
$placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id IN ($placeholders)");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

$total_records = count($records);
$records_per_page = 5;
$total_pages = ceil($total_records / $records_per_page);

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

// If only 1 page (5 or fewer records), use the simple template method
if ($total_pages <= 1) {
    $templateProcessor = new TemplateProcessor($template_path);
    
    // Set the date
    $templateProcessor->setValue('${DATE}', date('F d, Y'));
    
    // Set request type
    try {
        $templateProcessor->setValue('${REQTYPE}', $request_type);
    } catch (Exception $e) {}
    
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
            $templateProcessor->setValue('${RECEIVED' . $i . '}', formatDateTimeForExport($record['datetime_received']));
            $templateProcessor->setValue('${PROCESSED' . $i . '}', formatDateTimeForExport($record['datetime_processed']));
            $templateProcessor->setValue('${ACCOMPLISHED' . $i . '}', formatDateTimeForExport($record['datetime_accomplished']));
        } else {
            $templateProcessor->setValue('${LNAME' . $i . '}', '');
            $templateProcessor->setValue('${FNAME' . $i . '}', '');
            $templateProcessor->setValue('${MNAME' . $i . '}', '');
            $templateProcessor->setValue('${DEPT' . $i . '}', '');
            $templateProcessor->setValue('${EMAIL' . $i . '}', '');
            $templateProcessor->setValue('${PASS' . $i . '}', '');
            $templateProcessor->setValue('${RECEIVED' . $i . '}', '');
            $templateProcessor->setValue('${PROCESSED' . $i . '}', '');
            $templateProcessor->setValue('${ACCOMPLISHED' . $i . '}', '');
        }
    }
    
    $filename = 'Email_Request_' . date('Y-m-d_His') . '.docx';
    $temp_file = tempnam(sys_get_temp_dir(), 'word_');
    $templateProcessor->saveAs($temp_file);
    
} else {
    // Multiple pages needed - create merged document
    $mergedDocument = new PhpWord();
    
    for ($page = 0; $page < $total_pages; $page++) {
        $templateProcessor = new TemplateProcessor($template_path);
        
        // Set the date
        $templateProcessor->setValue('${DATE}', date('F d, Y'));
        
        // Set request type
        try {
            $templateProcessor->setValue('${REQTYPE}', $request_type);
        } catch (Exception $e) {}
        
        // Get records for this page
        $page_records = array_slice($records, $page * $records_per_page, $records_per_page);
        
        // Fill in the records for this page
        for ($i = 1; $i <= 5; $i++) {
            if (isset($page_records[$i - 1])) {
                $record = $page_records[$i - 1];
                $templateProcessor->setValue('${LNAME' . $i . '}', $record['last_name']);
                $templateProcessor->setValue('${FNAME' . $i . '}', $record['first_name']);
                $templateProcessor->setValue('${MNAME' . $i . '}', $record['middle_name'] ?? '');
                $templateProcessor->setValue('${DEPT' . $i . '}', $record['college_department']);
                $templateProcessor->setValue('${EMAIL' . $i . '}', $record['email'] ?? '');
                $templateProcessor->setValue('${PASS' . $i . '}', $record['password'] ?? '');
                $templateProcessor->setValue('${RECEIVED' . $i . '}', formatDateTimeForExport($record['datetime_received']));
                $templateProcessor->setValue('${PROCESSED' . $i . '}', formatDateTimeForExport($record['datetime_processed']));
                $templateProcessor->setValue('${ACCOMPLISHED' . $i . '}', formatDateTimeForExport($record['datetime_accomplished']));
            } else {
                $templateProcessor->setValue('${LNAME' . $i . '}', '');
                $templateProcessor->setValue('${FNAME' . $i . '}', '');
                $templateProcessor->setValue('${MNAME' . $i . '}', '');
                $templateProcessor->setValue('${DEPT' . $i . '}', '');
                $templateProcessor->setValue('${EMAIL' . $i . '}', '');
                $templateProcessor->setValue('${PASS' . $i . '}', '');
                $templateProcessor->setValue('${RECEIVED' . $i . '}', '');
                $templateProcessor->setValue('${PROCESSED' . $i . '}', '');
                $templateProcessor->setValue('${ACCOMPLISHED' . $i . '}', '');
            }
        }
        
        // Save this page to a temp file
        $page_temp = tempnam(sys_get_temp_dir(), 'page_');
        $templateProcessor->saveAs($page_temp);
        
        // Read the temp file and add to merged document
        $source = IOFactory::load($page_temp);
        
        foreach ($source->getSections() as $section) {
            $newSection = $mergedDocument->addSection();
            
            foreach ($section->getElements() as $element) {
                $newSection->addElement($element);
            }
        }
        
        // Clean up page temp file
        unlink($page_temp);
        
        // Add page break between pages (except for last page)
        if ($page < $total_pages - 1) {
            $mergedDocument->addSection(['breakType' => 'nextPage']);
        }
    }
    
    $filename = 'Email_Request_' . $total_records . '_records_' . date('Y-m-d_His') . '.docx';
    $temp_file = tempnam(sys_get_temp_dir(), 'word_');
    $objWriter = IOFactory::createWriter($mergedDocument, 'Word2007');
    $objWriter->save($temp_file);
}

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