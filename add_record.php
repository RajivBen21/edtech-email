<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Get departments for dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_department = trim($_POST['college_department']);
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $record_date = $_POST['record_date'];
    $account_status = $_POST['account_status'];
    $request_type = $_POST['request_type'];
    $notes = trim($_POST['notes']);
    $recorded_by = $_SESSION['full_name'];
    
    // Validate required fields
    if (empty($college_department) || empty($last_name) || empty($first_name)) {
        $error = 'Please fill in all required fields (Department, Last Name, First Name).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO email_records 
                (college_department, last_name, first_name, middle_name, email, password, record_date, account_status, request_type, recorded_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $request_type,
                $recorded_by,
                $notes
            ]);
            
            $success = 'Record added successfully!';
            
            // Clear form by redirecting
            if (isset($_POST['save_and_new'])) {
                header('Location: add_record.php?success=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Error adding record: ' . $e->getMessage();
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success'])) {
    $success = 'Record added successfully! You can add another record.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Record - LDCU Email System</title>
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
                <h1>LDCU EdTech Email System</h1>
                <div class="header-subtitle">Educational Technology Center</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_record.php" class="active">Add Record</a>
            <a href="records.php">All Records</a>
            <a href="import.php">Import CSV</a>
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add New Email Record</h3>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Department -->
                <div class="form-group">
                    <label for="college_department">College/Department *</label>
                    <select name="college_department" id="college_department" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Name Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" 
                               placeholder="e.g., Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" 
                               placeholder="e.g., Juan" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" 
                               placeholder="e.g., Santos">
                    </div>
                </div>
                
                <!-- Email and Password -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               placeholder="e.g., juan.delacruz@ldcu.edu.ph">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" name="password" id="password" class="form-control" 
                               placeholder="Initial password">
                    </div>
                </div>
                
                <!-- Date, Status, Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="record_date">Record Date</label>
                        <input type="date" name="record_date" id="record_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="account_status">Account Status</label>
                        <select name="account_status" id="account_status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Deactivated">Deactivated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="request_type">Request Type</label>
                        <select name="request_type" id="request_type" class="form-control">
                            <option value="New">New Account</option>
                            <option value="Activate">Activate Account</option>
                        </select>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Notes / Remarks</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                              placeholder="Any additional notes..."></textarea>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" name="save" class="btn btn-primary">Save Record</button>
                    <button type="submit" name="save_and_new" class="btn btn-success">Save & Add Another</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>