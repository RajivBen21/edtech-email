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
$stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id IN ($placeholders) ORDER BY college_department, last_name, first_name");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

if (empty($records)) {
    header('Location: records.php?error=no_records');
    exit;
}

$record_count = count($records);

// Group records by department
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
    <title>Export Preview - Workspace License</title>
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
        .form-type-badge {
            background: #38a169;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        .warning-box {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .warning-box h4 {
            color: #b45309;
            margin-bottom: 8px;
        }
        .warning-box p, .warning-box li {
            font-size: 14px;
            color: #92400e;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="../images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo">
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="../dashboard.php?module=workspace">Dashboard</a>
            <a href="records.php" class="active">Workspace Records</a>
            <a href="../logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Export Preview - Google Workspace Account License
                    <span class="record-count"><?php echo $record_count; ?> record<?php echo $record_count > 1 ? 's' : ''; ?> selected</span>
                </h3>
            </div>
            
            <!-- Preview Stats -->
            <div class="preview-stats">
                <div class="preview-stat">
                    <div class="preview-stat-number"><?php echo $record_count; ?></div>
                    <div class="preview-stat-label">Total Records</div>
                </div>
                <div class="preview-stat">
                    <div class="preview-stat-number"><?php echo $total_departments; ?></div>
                    <div class="preview-stat-label">Department(s)</div>
                </div>
                <div class="preview-stat">
                    <div class="preview-stat-number"><?php echo ceil($record_count / 10); ?></div>
                    <div class="preview-stat-label">Page(s)</div>
                </div>
            </div>
            
            <div class="preview-info">
                <p><strong>Form Type:</strong> <span class="form-type-badge">Google Workspace Account License</span></p>
                <p><strong>Export Date:</strong> <?php echo date('F d, Y'); ?></p>
                <p><strong>Exported By:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            
            <?php if ($total_departments > 1): ?>
            <div class="warning-box">
                <h4>⚠️ Multiple Departments Selected</h4>
                <p>You have selected records from <strong><?php echo $total_departments; ?></strong> different departments. The export will generate a document for the first department only. To export records from other departments, please select them separately.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($record_count > 10): ?>
            <div class="warning-box">
                <h4>⚠️ More Than 10 Records</h4>
                <p>You have selected <strong><?php echo $record_count; ?></strong> records. The template supports up to 10 records per page. Only the first 10 records will be exported.</p>
            </div>
            <?php endif; ?>
            
            <!-- Preview by Department -->
            <?php foreach ($grouped_records as $dept => $dept_records): ?>
            <div class="dept-section">
                <div class="dept-header">
                    <span><?php echo htmlspecialchars($dept); ?></span>
                    <span class="dept-count"><?php echo count($dept_records); ?> record(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="preview-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Liceo Email Address</th>
                                <th style="width: 150px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $num = 1; foreach ($dept_records as $record): ?>
                            <tr>
                                <td><?php echo $num++; ?></td>
                                <td><?php echo htmlspecialchars($record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . ($record['middle_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($record['liceo_email'] ?? ''); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $record['employment_status'] == 'Full-time' ? 'approved' : 'processing'; ?>">
                                        <?php echo htmlspecialchars($record['employment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Export Form -->
            <form method="POST" action="export_word.php">
                <?php foreach ($selected_ids as $id): ?>
                    <input type="hidden" name="selected_records[]" value="<?php echo htmlspecialchars($id); ?>">
                <?php endforeach; ?>
                
                <div class="button-group" style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="padding: 15px 30px; font-size: 16px;">
                        Download Word Document
                    </button>
                    <a href="records.php" class="btn btn-secondary" style="padding: 15px 30px; font-size: 16px;">
                        ← Back to Records
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>