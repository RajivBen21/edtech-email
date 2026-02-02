<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';
$imported_count = 0;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error uploading file.';
    } elseif ($file['type'] !== 'text/csv' && !str_ends_with($file['name'], '.csv')) {
        $error = 'Please upload a CSV file.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle) {
            if (isset($_POST['has_header'])) {
                fgetcsv($handle);
            }
            
            $recorded_by = $_SESSION['full_name'];
            
            while (($data = fgetcsv($handle)) !== false) {
                if (empty($data[0]) && empty($data[1])) continue;
                
                $college_department = isset($data[0]) ? trim($data[0]) : '';
                $last_name = isset($data[1]) ? trim($data[1]) : '';
                $first_name = isset($data[2]) ? trim($data[2]) : '';
                $middle_name = isset($data[3]) ? trim($data[3]) : '';
                $email = isset($data[4]) ? trim($data[4]) : '';
                $password = isset($data[5]) ? trim($data[5]) : '';
                $record_date = isset($data[6]) ? trim($data[6]) : date('Y-m-d');
                $account_status = isset($data[7]) ? trim($data[7]) : 'Activate';
                
                if (!in_array($account_status, ['Activate', 'Deactivated'])) {
                    $account_status = 'Activate';
                }
                
                if (!empty($record_date) && strtotime($record_date)) {
                    $record_date = date('Y-m-d', strtotime($record_date));
                } else {
                    $record_date = date('Y-m-d');
                }
                
                if (!empty($last_name) && !empty($first_name)) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO email_records 
                            (college_department, last_name, first_name, middle_name, email, password, record_date, account_status, request_type, recorded_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'New', ?)
                        ");
                        $stmt->execute([
                            $college_department,
                            $last_name,
                            $first_name,
                            $middle_name,
                            $email,
                            $password,
                            $record_date,
                            $account_status,
                            $recorded_by
                        ]);
                        $imported_count++;
                    } catch (Exception $e) {
                        // Skip duplicate or error rows
                    }
                }
            }
            
            fclose($handle);
            $success = "Successfully imported $imported_count records!";
        } else {
            $error = 'Could not read the file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV - LDCU Email System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="records.php">All Records</a>
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Import Records from CSV</h3>
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
                            <th>Column 1</th>
                            <th>Column 2</th>
                            <th>Column 3</th>
                            <th>Column 4</th>
                            <th>Column 5</th>
                            <th>Column 6</th>
                            <th>Column 7</th>
                            <th>Column 8</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>college_department</td>
                            <td>last_name</td>
                            <td>first_name</td>
                            <td>middle_name</td>
                            <td>email</td>
                            <td>password</td>
                            <td>record_date</td>
                            <td>account_status</td>
                        </tr>
                        <tr style="color: #666; font-size: 12px;">
                            <td>College of Nursing</td>
                            <td>Dela Cruz</td>
                            <td>Juan</td>
                            <td>Santos</td>
                            <td>j.delacruz@ldcu.edu.ph</td>
                            <td>pass123</td>
                            <td>2026-01-15</td>
                            <td>Activate</td>
                        </tr>
                    </tbody>
                </table>
                <p style="font-size: 13px; color: #666;">
                    <strong>Note:</strong> account_status should be either "Activate" or "Deactivated"
                </p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">Select CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" 
                           accept=".csv" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="has_header" checked>
                        First row is header (skip first row)
                    </label>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Import Records</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
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