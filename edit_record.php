<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: records.php');
    exit;
}

$record_id = (int)$_GET['id'];

// Get the record
$stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

// If record not found, redirect
if (!$record) {
    header('Location: records.php?error=not_found');
    exit;
}

// Get departments for dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_department = trim($_POST['college_department']);
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $record_date = $_POST['record_date'] ?? null;
    $account_status = $_POST['account_status'];
    $request_type = $_POST['request_type'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Handle empty date
    if (empty($record_date)) {
        $record_date = null;
    }
    
    // Validate required fields
    if (empty($college_department) || empty($last_name) || empty($first_name)) {
        $error = 'Please fill in all required fields (Department, Last Name, First Name).';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE email_records SET
                    college_department = :college_department,
                    last_name = :last_name,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    email = :email,
                    password = :password,
                    record_date = :record_date,
                    account_status = :account_status,
                    request_type = :request_type,
                    notes = :notes
                WHERE record_id = :record_id
            ");
            
            $stmt->execute([
                ':college_department' => $college_department,
                ':last_name' => $last_name,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':email' => $email,
                ':password' => $password,
                ':record_date' => $record_date,
                ':account_status' => $account_status,
                ':request_type' => $request_type,
                ':notes' => $notes,
                ':record_id' => $record_id
            ]);
            
            $success = 'Record updated successfully!';
            
            // Refresh the record data
            $stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id = ?");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch();
            
        } catch (PDOException $e) {
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
    <title>Edit Record - LDCU Email System</title>
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
                <h3 class="card-title">Edit Email Record #<?php echo $record_id; ?></h3>
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
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                    <?php echo $record['college_department'] == $dept['department_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php 
                        $found = false;
                        foreach ($departments as $dept) {
                            if ($dept['department_name'] == $record['college_department']) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found && !empty($record['college_department'])): ?>
                            <option value="<?php echo htmlspecialchars($record['college_department']); ?>" selected>
                                <?php echo htmlspecialchars($record['college_department']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Name Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($record['last_name']); ?>"
                               placeholder="e.g., Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($record['first_name']); ?>"
                               placeholder="e.g., Juan" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" 
                               value="<?php echo htmlspecialchars($record['middle_name'] ?? ''); ?>"
                               placeholder="e.g., Santos">
                    </div>
                </div>
                
                <!-- Email and Password -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               value="<?php echo htmlspecialchars($record['email'] ?? ''); ?>"
                               placeholder="e.g., juan.delacruz@ldcu.edu.ph">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" name="password" id="password" class="form-control" 
                               value="<?php echo htmlspecialchars($record['password'] ?? ''); ?>"
                               placeholder="Initial password">
                    </div>
                </div>
                
                <!-- Date, Status, Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="record_date">Record Date</label>
                        <input type="date" name="record_date" id="record_date" class="form-control" 
                               value="<?php echo $record['record_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="account_status">Account Status</label>
                        <select name="account_status" id="account_status" class="form-control">
                            <option value="Active" <?php echo $record['account_status'] == 'Active' ? 'selected' : ''; ?>>Activate</option>
                            <option value="Deactivated" <?php echo $record['account_status'] == 'Deactivated' ? 'selected' : ''; ?>>Deactivated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="request_type">Request Type</label>
                        <select name="request_type" id="request_type" class="form-control">
                            <option value="New" <?php echo $record['request_type'] == 'New' ? 'selected' : ''; ?>>New Account</option>
                            <option value="Activate" <?php echo $record['request_type'] == 'Activate' ? 'selected' : ''; ?>>Activate Account</option>
                        </select>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Notes / Remarks</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                              placeholder="Any additional notes..."><?php echo htmlspecialchars($record['notes'] ?? ''); ?></textarea>
                </div>
                
                <!-- Record Info -->
                <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2b6cb0;">
                    <p style="margin: 0; font-size: 13px; color: #666;">
                        <strong>Recorded by:</strong> <?php echo htmlspecialchars($record['recorded_by'] ?? 'N/A'); ?> | 
                        <strong>Created:</strong> <?php echo $record['created_at'] ? date('M d, Y h:i A', strtotime($record['created_at'])) : 'N/A'; ?>
                    </p>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Record</button>
                    <a href="records.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>