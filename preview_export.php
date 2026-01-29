<?php
require_once 'includes/db_connect.php';

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

$record_count = count($records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Preview - LDCU Email System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .preview-info {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2b6cb0;
        }
        .preview-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .preview-info strong {
            color: #1a365d;
        }
        .preview-section {
            margin-bottom: 30px;
        }
        .preview-section h4 {
            color: #1a365d;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2b6cb0;
        }
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .preview-table th,
        .preview-table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .preview-table th {
            background: #2b6cb0;
            color: white;
            font-weight: 600;
        }
        .preview-table tbody tr:nth-child(even) {
            background: #f7fafc;
        }
        .preview-table tbody tr:hover {
            background: #ebf8ff;
        }
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .record-count {
            display: inline-block;
            background: #2b6cb0;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .request-type-badge {
            display: inline-block;
            background: #38a169;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div>
                <h1>LDCU EdTech Email System</h1>
                <div class="header-subtitle">Educational Technology Center</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_record.php">Add Record</a>
            <a href="records.php" class="active">All Records</a>
            <a href="import.php">Import CSV</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Export Preview 
                    <span class="record-count"><?php echo $record_count; ?> record<?php echo $record_count > 1 ? 's' : ''; ?> selected</span>
                </h3>
            </div>
            
            <div class="preview-info">
                <p><strong>Request Type:</strong> <span class="request-type-badge"><?php echo $request_type == 'New' ? 'New Email' : 'Activate Account'; ?></span></p>
                <p><strong>Export Date:</strong> <?php echo date('F d, Y'); ?></p>
            </div>
            
            <!-- First Table Preview: Names and Departments -->
            <div class="preview-section">
                <h4>📋 Table 1: Request Form (Names & Departments)</h4>
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>College/Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $record_count; $i++): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($records[$i]['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($records[$i]['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($records[$i]['middle_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($records[$i]['college_department']); ?></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Second Table Preview: Emails and Passwords -->
            <div class="preview-section">
                <h4>📧 Table 2: Monitoring Form (Emails & Passwords)</h4>
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Email Address</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $record_count; $i++): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($records[$i]['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($records[$i]['password'] ?? ''); ?></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export Button -->
            <form method="POST" action="export_word.php">
                <?php foreach ($selected_ids as $id): ?>
                    <input type="hidden" name="selected_records[]" value="<?php echo $id; ?>">
                <?php endforeach; ?>
                <input type="hidden" name="request_type" value="<?php echo htmlspecialchars($request_type); ?>">
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">📥 Download Word Document</button>
                    <a href="records.php" class="btn btn-secondary">← Back to Records</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>