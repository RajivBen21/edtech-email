<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: records.php');
    exit;
}

$record_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: records.php?error=not_found');
    exit;
}

$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

function formatDateTimeForInput($datetime) {
    if (empty($datetime)) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}

function formatDateTimeForDisplay($datetime) {
    if (empty($datetime)) return '<span style="color: #a0aec0; font-style: italic;">Not set</span>';
    return date('M d, Y g:i A', strtotime($datetime));
}

function getCurrentStatus($record) {
    if (!empty($record['datetime_accomplished'])) return 'accomplished';
    if (!empty($record['datetime_processed'])) return 'processing';
    if (!empty($record['datetime_received'])) return 'received';
    return 'pending';
}
$current_status = getCurrentStatus($record);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_department = trim($_POST['college_department']);
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $liceo_email = trim($_POST['liceo_email'] ?? '');
    $employment_status = $_POST['employment_status'];
    $record_date = $_POST['record_date'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');
    
    $datetime_received = !empty($_POST['datetime_received']) ? $_POST['datetime_received'] : null;
    $datetime_processed = !empty($_POST['datetime_processed']) ? $_POST['datetime_processed'] : null;
    $datetime_accomplished = !empty($_POST['datetime_accomplished']) ? $_POST['datetime_accomplished'] : null;
    
    if (empty($record_date)) $record_date = null;
    
    if (empty($college_department) || empty($last_name) || empty($first_name)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE workspace_license_records SET
                    college_department = ?, last_name = ?, first_name = ?, middle_name = ?,
                    liceo_email = ?, employment_status = ?, record_date = ?, remarks = ?,
                    datetime_received = ?, datetime_processed = ?, datetime_accomplished = ?
                WHERE record_id = ?
            ");
            $stmt->execute([
                $college_department, $last_name, $first_name, $middle_name,
                $liceo_email, $employment_status, $record_date, $remarks,
                $datetime_received, $datetime_processed, $datetime_accomplished,
                $record_id
            ]);
            
            $success = 'Record updated successfully!';
            
            $stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id = ?");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch();
            $current_status = getCurrentStatus($record);
            
        } catch (Exception $e) {
            $error = 'Error updating record: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Workspace Record - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .status-badge { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-received { background: #c6f6d5; color: #276749; }
        .status-processing { background: #bee3f8; color: #2b6cb0; }
        .status-accomplished { background: #c6f6d5; color: #276749; }
        
        .status-timeline { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; margin-bottom: 20px; }
        .timeline-step { display: flex; flex-direction: column; align-items: center; flex: 1; }
        .timeline-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; margin-bottom: 8px; background: #cbd5e0; }
        .timeline-step.completed .timeline-icon { box-shadow: 0 0 0 4px rgba(72, 187, 120, 0.2); }
        .timeline-icon.received { background: #48bb78; }
        .timeline-icon.processing { background: #4299e1; }
        .timeline-icon.accomplished { background: #38a169; }
        .timeline-step:not(.completed) .timeline-icon { background: #cbd5e0; }
        .timeline-label { font-size: 12px; font-weight: 600;