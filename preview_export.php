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
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .preview-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .preview-info strong {
            color: #8B0000;
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
            background: #8B0000;
            color: white;
            font-weight: 600;
        }
        .preview-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .preview-table tbody tr:hover {
            background: #fff5f5;
        }
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
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
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📄 Export Preview</h3>
            </div>
            
            <p style="color: #666; margin-bottom: 20px;">
                Please review the records below before exporting to Word document.
            </p>
            
            <div class="preview-info">
                <p><strong>Request Type:</strong> <?php echo htmlspecialchars($request_type); ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p>
                <p><strong>Total Records:</strong> <?php echo count($records); ?></p>
            </div>
            
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                            <?php echo htmlspecialchars($record['first_name']); ?> 
                            <?php echo htmlspecialchars($record['middle_name'] ?? ''); ?>
                        </td>
                        <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                        <td><?php echo htmlspecialchars($record['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($record['password'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="button-group">
                <form method="POST" action="export_word.php" style="display: inline;">
                    <?php foreach ($selected_ids as $id): ?>
                        <input type="hidden" name="selected_records[]" value="<?php echo $id; ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="request_type" value="<?php echo htmlspecialchars($request_type); ?>">
                    <button type="submit" class="btn btn-success">✅ Confirm & Download</button>
                </form>
                <a href="records.php" class="btn btn-secondary">← Cancel</a>
            </div>
        </div>
    </div>
</body>
</html>