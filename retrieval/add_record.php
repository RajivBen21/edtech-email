<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error = '';

$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_department = trim($_POST['college_department']);
    $request_type = $_POST['request_type'] ?? 'Account';
    $student_employee_id = trim($_POST['student_employee_id'] ?? '');
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $email_address = trim($_POST['email_address'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $record_date = $_POST['record_date'] ?: date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');
    $recorded_by = $_SESSION['full_name'] ?? $_SESSION['username'];
    
    $datetime_received = !empty($_POST['datetime_received']) ? $_POST['datetime_received'] : null;
    $datetime_processed = !empty($_POST['datetime_processed']) ? $_POST['datetime_processed'] : null;
    $datetime_accomplished = !empty($_POST['datetime_accomplished']) ? $_POST['datetime_accomplished'] : null;
    
    if (empty($college_department) || empty($last_name) || empty($first_name)) {
        $error = 'Please fill in all required fields (Department, Last Name, First Name).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO retrieval_records 
                (college_department, request_type, student_employee_id, last_name, first_name, 
                 middle_name, email_address, password, record_date, recorded_by, remarks,
                 datetime_received, datetime_processed, datetime_accomplished)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $college_department, $request_type, $student_employee_id, $last_name, $first_name,
                $middle_name, $email_address, $password, $record_date, $recorded_by, $remarks,
                $datetime_received, $datetime_processed, $datetime_accomplished
            ]);
            
            $success = 'Record added successfully!';
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
    <title>Add Retrieval Record - LDCU EdTech System</title>
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
    <div class="header">
        <div class="header-left">
            <img src="../images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo">
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="../dashboard.php?module=retrieval">Dashboard</a>
            <a href="records.php" class="active">Retrieval Records</a>
            <a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add New Account/Password Retrieval Record</h3>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="college_department">College/Department *</label>
                        <select name="college_department" id="college_department" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                        <?php echo (isset($_POST['college_department']) && $_POST['college_department'] == $dept['department_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_code'] . ' - ' . $dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="request_type">Request Type *</label>
                        <select name="request_type" id="request_type" class="form-control" required>
                            <option value="Account" <?php echo (!isset($_POST['request_type']) || $_POST['request_type'] == 'Account') ? 'selected' : ''; ?>>Account</option>
                            <option value="Password" <?php echo (isset($_POST['request_type']) && $_POST['request_type'] == 'Password') ? 'selected' : ''; ?>>Password</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="record_date">Record Date</label>
                        <input type="date" name="record_date" id="record_date" class="form-control" 
                               value="<?php echo $_POST['record_date'] ?? date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="student_employee_id">Student/Employee ID No.</label>
                    <input type="text" name="student_employee_id" id="student_employee_id" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['student_employee_id'] ?? ''); ?>">
                </div>
                
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email_address">Email Address</label>
                        <input type="email" name="email_address" id="email_address" class="form-control"
                               placeholder="example@liceo.edu.ph"
                               value="<?php echo htmlspecialchars($_POST['email_address'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" name="password" id="password" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                    </div>
                </div>
                
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
                
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3"
                              placeholder="Any additional information or notes..."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Save Record</button>
                    <button type="submit" name="save_and_add" class="btn btn-success">Save and Add Another</button>
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