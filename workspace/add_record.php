<?php
require_once '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
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
    $middle_name = trim($_POST['middle_name'] ?? '');
    $liceo_email = trim($_POST['liceo_email'] ?? '');
    $employment_status = $_POST['employment_status'] ?? 'Full-time';
    $record_date = $_POST['record_date'] ?: date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');
    $recorded_by = $_SESSION['full_name'] ?? $_SESSION['username'];
    
    // Date/Time tracking fields
    $datetime_received = !empty($_POST['datetime_received']) ? $_POST['datetime_received'] : null;
    $datetime_processed = !empty($_POST['datetime_processed']) ? $_POST['datetime_processed'] : null;
    $datetime_accomplished = !empty($_POST['datetime_accomplished']) ? $_POST['datetime_accomplished'] : null;
    
    // Validate required fields
    if (empty($college_department) || empty($last_name) || empty($first_name)) {
        $error = 'Please fill in all required fields (Department, Last Name, First Name).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO workspace_license_records 
                (college_department, last_name, first_name, middle_name, liceo_email, 
                 employment_status, record_date, recorded_by, remarks,
                 datetime_received, datetime_processed, datetime_accomplished)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $college_department, $last_name, $first_name, $middle_name, $liceo_email,
                $employment_status, $record_date, $recorded_by, $remarks,
                $datetime_received, $datetime_processed, $datetime_accomplished
            ]);
            
            $success = 'Record added successfully!';
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $error = 'Error adding record: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Workspace License Record - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .datetime-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .datetime-input-group input {
            flex: 1;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .btn-now { background: #48bb78; color: white; border: none; cursor: pointer; border-radius: 4px; }
        .btn-now:hover { background: #38a169; }
        .btn-clear { background: #e2e8f0; color: #4a5568; border: none; cursor: pointer; border-radius: 4px; }
        .btn-clear:hover { background: #cbd5e0; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <a href="../dashboard.php" style="cursor: pointer;"><img src="../images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo"></a>
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

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add New Google Workspace License Record</h3>
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
                                    <?php echo (isset($_POST['college_department']) && $_POST['college_department'] == $dept['department_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Name Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Email and Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="liceo_email">Liceo Email Address</label>
                        <input type="email" name="liceo_email" id="liceo_email" class="form-control"
                               placeholder="example@liceo.edu.ph"
                               value="<?php echo htmlspecialchars($_POST['liceo_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="employment_status">Status *</label>
                        <select name="employment_status" id="employment_status" class="form-control" required>
                            <option value="Full-time" <?php echo (!isset($_POST['employment_status']) || $_POST['employment_status'] == 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo (isset($_POST['employment_status']) && $_POST['employment_status'] == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                            <option value="Probationary" <?php echo (isset($_POST['employment_status']) && $_POST['employment_status'] == 'Probationary') ? 'selected' : ''; ?>>Probationary</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="record_date">Record Date</label>
                        <input type="date" name="record_date" id="record_date" class="form-control" 
                               value="<?php echo $_POST['record_date'] ?? date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <!-- Date/Time Tracking Section -->
                <div class="card-header" style="margin-top: 20px; margin-bottom: 15px; padding: 0;">
                    <h3 class="card-title" style="font-size: 16px;">Request Status Tracking</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="datetime_received">Date/Time Received</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_received" id="datetime_received" class="form-control"
                                   value="<?php echo $_POST['datetime_received'] ?? ''; ?>">
                            <button type="button" class="btn btn-sm btn-now" onclick="setCurrentDateTime('datetime_received')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_received')">Clear</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="datetime_processed">Date/Time Processed</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_processed" id="datetime_processed" class="form-control"
                                   value="<?php echo $_POST['datetime_processed'] ?? ''; ?>">
                            <button type="button" class="btn btn-sm btn-now" onclick="setCurrentDateTime('datetime_processed')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_processed')">Clear</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="datetime_accomplished">Date/Time Accomplished</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_accomplished" id="datetime_accomplished" class="form-control"
                                   value="<?php echo $_POST['datetime_accomplished'] ?? ''; ?>">
                            <button type="button" class="btn btn-sm btn-now" onclick="setCurrentDateTime('datetime_accomplished')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_accomplished')">Clear</button>
                        </div>
                    </div>
                </div>
                
                <!-- Remarks -->
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3"
                              placeholder="Any additional information or notes..."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                </div>
                
                <!-- Submit Buttons -->
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Save Record</button>
                    <button type="submit" name="save_and_add" class="btn btn-success">Save & Add Another</button>
                    <a href="records.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function setCurrentDateTime(fieldId) {
            const now = new Date();
            const offset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - offset)).toISOString().slice(0, 16);
            document.getElementById(fieldId).value = localISOTime;
        }
        
        function clearDateTime(fieldId) {
            document.getElementById(fieldId).value = '';
        }
    </script>
</body>
</html>