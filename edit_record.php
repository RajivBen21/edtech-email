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

// Handle quick status update via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_update'])) {
    $field = $_POST['field'];
    $allowed_fields = ['datetime_received', 'datetime_processed', 'datetime_accomplished'];
    
    if (in_array($field, $allowed_fields)) {
        $stmt = $pdo->prepare("UPDATE email_records SET $field = NOW() WHERE record_id = ?");
        $stmt->execute([$record_id]);
        
        // Return the updated timestamp
        $stmt = $pdo->prepare("SELECT $field FROM email_records WHERE record_id = ?");
        $stmt->execute([$record_id]);
        $result = $stmt->fetch();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'value' => $result[$field]]);
        exit;
    }
}

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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['quick_update'])) {
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
    
    // Date/Time tracking fields
    $datetime_received = !empty($_POST['datetime_received']) ? $_POST['datetime_received'] : null;
    $datetime_processed = !empty($_POST['datetime_processed']) ? $_POST['datetime_processed'] : null;
    $datetime_accomplished = !empty($_POST['datetime_accomplished']) ? $_POST['datetime_accomplished'] : null;
    
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
                    notes = :notes,
                    datetime_received = :datetime_received,
                    datetime_processed = :datetime_processed,
                    datetime_accomplished = :datetime_accomplished
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
                ':datetime_received' => $datetime_received,
                ':datetime_processed' => $datetime_processed,
                ':datetime_accomplished' => $datetime_accomplished,
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

// Helper function to format datetime for input field
function formatDateTimeForInput($datetime) {
    if (empty($datetime)) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}

// Helper function to format datetime for display
function formatDateTimeForDisplay($datetime) {
    if (empty($datetime)) return 'Not set';
    return date('M d, Y h:i A', strtotime($datetime));
}

// Determine current status
function getRequestStatus($record) {
    if (!empty($record['datetime_accomplished'])) {
        return 'accomplished';
    } elseif (!empty($record['datetime_processed'])) {
        return 'processing';
    } elseif (!empty($record['datetime_received'])) {
        return 'received';
    }
    return 'pending';
}

$current_status = getRequestStatus($record);
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
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="records.php" class="active">All Records</a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Edit Email Record #<?php echo $record_id; ?></h3>
                <span class="status-badge status-<?php echo $current_status; ?>">
                    <?php echo ucfirst($current_status); ?>
                </span>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Quick Action Buttons -->
            <div class="quick-actions">
                <span class="quick-actions-label">Quick Actions:</span>
                <?php if (empty($record['datetime_received'])): ?>
                    <button type="button" class="btn btn-sm btn-received" onclick="quickUpdate('datetime_received')">
                        ✓ Mark as Received
                    </button>
                <?php endif; ?>
                <?php if (empty($record['datetime_processed'])): ?>
                    <button type="button" class="btn btn-sm btn-processing" onclick="quickUpdate('datetime_processed')">
                        ⚙ Mark as Processed
                    </button>
                <?php endif; ?>
                <?php if (empty($record['datetime_accomplished'])): ?>
                    <button type="button" class="btn btn-sm btn-accomplished" onclick="quickUpdate('datetime_accomplished')">
                        ★ Mark as Accomplished
                    </button>
                <?php endif; ?>
            </div>
            
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
                            <option value="Active" <?php echo $record['account_status'] == 'Active' ? 'selected' : ''; ?>>Activated</option>
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
                
                <!-- Date/Time Tracking Section -->
                <div class="card-header" style="margin-top: 20px; margin-bottom: 15px;">
                    <h3 class="card-title"> Request Status Tracking</h3>
                </div>
                
                <!-- Status Timeline -->
                <div class="status-timeline">
                    <div class="timeline-step <?php echo !empty($record['datetime_received']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon received">1</div>
                        <div class="timeline-label">Received</div>
                        <div class="timeline-date" id="display_received"><?php echo formatDateTimeForDisplay($record['datetime_received']); ?></div>
                    </div>
                    <div class="timeline-connector <?php echo !empty($record['datetime_processed']) ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo !empty($record['datetime_processed']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon processing">2</div>
                        <div class="timeline-label">Processed</div>
                        <div class="timeline-date" id="display_processed"><?php echo formatDateTimeForDisplay($record['datetime_processed']); ?></div>
                    </div>
                    <div class="timeline-connector <?php echo !empty($record['datetime_accomplished']) ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo !empty($record['datetime_accomplished']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon accomplished">3</div>
                        <div class="timeline-label">Accomplished</div>
                        <div class="timeline-date" id="display_accomplished"><?php echo formatDateTimeForDisplay($record['datetime_accomplished']); ?></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="datetime_received">Date/Time Received</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_received" id="datetime_received" class="form-control"
                                   value="<?php echo formatDateTimeForInput($record['datetime_received']); ?>">
                            <button type="button" class="btn btn-sm btn-received" onclick="setCurrentDateTime('datetime_received')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_received')">Clear</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="datetime_processed">Date/Time Processed</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_processed" id="datetime_processed" class="form-control"
                                   value="<?php echo formatDateTimeForInput($record['datetime_processed']); ?>">
                            <button type="button" class="btn btn-sm btn-processing" onclick="setCurrentDateTime('datetime_processed')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_processed')">Clear</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="datetime_accomplished">Date/Time Accomplished</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_accomplished" id="datetime_accomplished" class="form-control"
                                   value="<?php echo formatDateTimeForInput($record['datetime_accomplished']); ?>">
                            <button type="button" class="btn btn-sm btn-accomplished" onclick="setCurrentDateTime('datetime_accomplished')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_accomplished')">Clear</button>
                        </div>
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
    
    <script>
        function setCurrentDateTime(fieldId) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const dateTimeString = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById(fieldId).value = dateTimeString;
        }
        
        function clearDateTime(fieldId) {
            document.getElementById(fieldId).value = '';
        }
        
        function quickUpdate(field) {
            const formData = new FormData();
            formData.append('quick_update', '1');
            formData.append('field', field);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated data
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating record. Please try again.');
            });
        }
    </script>
</body>
</html>