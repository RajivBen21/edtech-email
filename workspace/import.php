<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error = '';
$imported_count = 0;
$skipped_count = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error uploading file.';
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $error = 'Please upload a CSV file.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle !== false) {
            $row_number = 0;
            $has_header = isset($_POST['has_header']);
            
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row_number++;
                
                if ($row_number == 1 && $has_header) {
                    continue;
                }
                
                // Expected: college_department, last_name, first_name, middle_name, liceo_email, employment_status
                if (count($data) >= 3) {
                    $college_department = trim($data[0] ?? '');
                    $last_name = trim($data[1] ?? '');
                    $first_name = trim($data[2] ?? '');
                    $middle_name = trim($data[3] ?? '');
                    $liceo_email = trim($data[4] ?? '');
                    $employment_status = trim($data[5] ?? 'Full-time');
                    
                    if (empty($college_department) || empty($last_name) || empty($first_name)) {
                        $skipped_count++;
                        continue;
                    }
                    
                    if (!in_array($employment_status, ['Full-time', 'Part-time'])) {
                        $employment_status = 'Full-time';
                    }
                    
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO workspace_license_records 
                            (college_department, last_name, first_name, middle_name, liceo_email, 
                             employment_status, record_date, recorded_by)
                            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)
                        ");
                        $stmt->execute([
                            $college_department, $last_name, $first_name, $middle_name, 
                            $liceo_email, $employment_status, $_SESSION['username']
                        ]);
                        $imported_count++;
                    } catch (Exception $e) {
                        $skipped_count++;
                    }
                } else {
                    $skipped_count++;
                }
            }
            
            fclose($handle);
            
            if ($imported_count > 0) {
                $success = "Successfully imported $imported_count record(s).";
                if ($skipped_count > 0) {
                    $success .= " Skipped $skipped_count row(s).";
                }
            } else {
                $error = "No records imported. Check your CSV format.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Workspace Records - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
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
            <a href="../dashboard.php?module=workspace">Dashboard</a>
            <a href="records.php" class="active">Workspace Records</a>
            <a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Import Workspace License Records from CSV</h3>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px; color: #1a365d;">📋 CSV File Format</h4>
                <p style="margin-bottom: 10px;">Your CSV file should have columns in this order:</p>
                <table class="data-table" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th>college_department</th>
                            <th>last_name</th>
                            <th>first_name</th>
                            <th>middle_name</th>
                            <th>liceo_email</th>
                            <th>employment_status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>College of Nursing</td>
                            <td>Dela Cruz</td>
                            <td>Juan</td>
                            <td>Santos</td>
                            <td>jdelacruz@liceo.edu.ph</td>
                            <td>Full-time</td>
                        </tr>
                    </tbody>
                </table>
                <p style="font-size: 13px; color: #666;">
                    <strong>Note:</strong> employment_status should be either "Full-time" or "Part-time"
                </p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">Select CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="has_header" checked>
                        First row is header (skip first row)
                    </label>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Import Records</button>
                    <a href="records.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
        <?php if ($imported_count > 0): ?>
        <div class="card">
            <p style="text-align: center;">
                <a href="records.php" class="btn btn-success">View Imported Records</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>