<?php
require_once '../includes/db_connect.php';

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

$record_count = count($records);

$grouped_records = [];
foreach ($records as $record) {
    $dept = $record['college_department'];
    if (!isset($grouped_records[$dept])) {
        $grouped_records[$dept] = [];
    }
    $grouped_records[$dept][] = $record;
}

$total_departments = count($grouped_records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Preview - Training</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .record-count {
            display: inline-block;
            background: #2b6cb0;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .dept-section {
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .dept-header {
            background: linear-gradient(135deg, #1a365d, #2b6cb0);
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dept-count {
            background: rgba(255,255,255,0.2);
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        .preview-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .preview-stat {
            background: #f7fafc;
            padding: 15px 25px;
            border-radius: 8px;
            border-left: 4px solid #2b6cb0;
        }
        .preview-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1a365d;
        }
        .preview-stat-label {
            font-size: 12px;
            color: #718096;
        }
        .info-box {
            background: #f0fdf4;
            border: 1px solid #22c55e;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            color: #166534;
            margin-bottom: 5px;
        }
        .info-box p, .info-box ul {
            color: #166534;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo">
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="../dashboard.php?module=training">Dashboard</a>
            <a href="records.php" class="active">Training Records</a>
            <a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Export Preview - Training/Seminar/Workshop<span class="record-count"><?php echo $record_count; ?> records</span></h3>
            </div>
            
            <div class="preview-stats">
                <div class="preview-stat">
                    <div class="preview-stat-number"><?php echo $record_count; ?></div>
                    <div class="preview-stat-label">Total Records</div>
                </div>
                <div class="preview-stat">
                    <div class="preview-stat-number"><?php echo $total_departments; ?></div>
                    <div class="preview-stat-label">Department(s)</div>
                </div>
            </div>
            
            <?php foreach ($grouped_records as $dept => $dept_records): ?>
            <div class="dept-section">
                <div class="dept-header">
                    <span><?php echo htmlspecialchars($dept); ?></span>
                    <span class="dept-count"><?php echo count($dept_records); ?> record(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Theme/Topic</th>
                                <th>Activity Type</th>
                                <th>Schedule</th>
                                <th>Venue</th>
                                <th>Participants</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $num = 1; foreach ($dept_records as $record): ?>
                            <tr>
                                <td><?php echo $num++; ?></td>
                                <td><?php echo htmlspecialchars($record['theme_topic']); ?></td>
                                <td>
                                    <?php 
                                    $typeClass = 'approved';
                                    if ($record['activity_type'] == 'Seminar') $typeClass = 'processing';
                                    if ($record['activity_type'] == 'Workshop') $typeClass = 'pending';
                                    ?>
                                    <span class="badge badge-<?php echo $typeClass; ?>">
                                        <?php echo $record['activity_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['activity_schedule'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['activity_venue'] ?? '-'); ?></td>
                                <td><?php echo $record['no_of_participants'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="info-box">
                <h4>Document Structure</h4>
                <p>The exported Word document will include:</p>
                <ul style="margin: 10px 0 0 20px;">
                    <li>University header with Office of VP for Academic Affairs</li>
                    <li>Department: <strong><?php echo htmlspecialchars(array_key_first($grouped_records)); ?></strong></li>
                    <li>Activity Type checkboxes (Training/Seminar/Workshop)</li>
                    <li>Theme or Topic</li>
                    <li>Objectives of the Activity</li>
                    <li>Activity Schedule, Time Allocated, Participants, Venue</li>
                    <li>Signature fields (Prepared by, Endorsed by, Received by, Recommended by, Approved by)</li>
                    <li>Request monitoring section with Status/Remarks</li>
                </ul>
            </div>
            
            <form method="POST" action="export_word.php">
                <?php foreach ($selected_ids as $id): ?>
                    <input type="hidden" name="selected_records[]" value="<?php echo htmlspecialchars($id); ?>">
                <?php endforeach; ?>
                
                <div class="button-group" style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">Export to Word Document</button>
                    <a href="records.php" class="btn btn-secondary">Back to Records</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>