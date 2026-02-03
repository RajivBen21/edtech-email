<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: records.php');
    exit;
}

$record_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: records.php?error=not_found');
    exit;
}

$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

function formatDateTimeForInput($datetime) {
    if (empty($datetime)) return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}

function formatDateTimeForDisplay($datetime) {
    if (empty($datetime)) return '<span style="color: #a0aec0; font-style: italic;">Not set</span>';
    return date('M d, Y g:i A', strtotime($datetime));
}

function getCurrentStatus($record) {
    if (!empty($record['datetime_accomplished'])) return 'accomplished';
    if (!empty($record['datetime_processed'])) return 'processing';
    if (!empty($record['datetime_received'])) return 'received';
    return 'pending';
}
$current_status = getCurrentStatus($record);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $college_department = trim($_POST['college_department']);
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $liceo_email = trim($_POST['liceo_email'] ?? '');
    $employment_status = $_POST['employment_status'] ?? 'Full-time';
    $record_date = $_POST['record_date'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');
    
    $datetime_received = !empty($_POST['datetime_received']) ? $_POST['datetime_received'] : null;
    $datetime_processed = !empty($_POST['datetime_processed']) ? $_POST['datetime_processed'] : null;
    $datetime_accomplished = !empty($_POST['datetime_accomplished']) ? $_POST['datetime_accomplished'] : null;
    
    if (empty($record_date)) $record_date = null;
    
    if (empty($college_department) || empty($last_name) || empty($first_name)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE workspace_license_records SET
                    college_department = ?, last_name = ?, first_name = ?, middle_name = ?,
                    liceo_email = ?, employment_status = ?, record_date = ?, remarks = ?,
                    datetime_received = ?, datetime_processed = ?, datetime_accomplished = ?
                WHERE record_id = ?
            ");
            $stmt->execute([
                $college_department, $last_name, $first_name, $middle_name,
                $liceo_email, $employment_status, $record_date, $remarks,
                $datetime_received, $datetime_processed, $datetime_accomplished,
                $record_id
            ]);
            
            $success = 'Record updated successfully!';
            
            $stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id = ?");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch();
            $current_status = getCurrentStatus($record);
            
        } catch (Exception $e) {
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
    <title>Edit Workspace Record - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .status-badge { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-received { background: #c6f6d5; color: #276749; }
        .status-processing { background: #bee3f8; color: #2b6cb0; }
        .status-accomplished { background: #c6f6d5; color: #276749; }
        
        .status-timeline { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; margin-bottom: 20px; }
        .timeline-step { display: flex; flex-direction: column; align-items: center; flex: 1; }
        .timeline-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; margin-bottom: 8px; background: #cbd5e0; }
        .timeline-step.completed .timeline-icon { box-shadow: 0 0 0 4px rgba(72, 187, 120, 0.2); background: #48bb78; }
        .timeline-label { font-size: 12px; font-weight: 600; color: #4a5568; }
        .timeline-date { font-size: 11px; color: #718096; margin-top: 4px; text-align: center; }
        .timeline-connector { flex: 0.5; height: 3px; background: #cbd5e0; margin: 0 10px; margin-bottom: 30px; }
        .timeline-connector.completed { background: #48bb78; }
        
        .datetime-input-group { display: flex; gap: 10px; align-items: center; }
        .datetime-input-group input { flex: 1; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
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
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Edit Workspace License Record #<?php echo $record_id; ?></h3>
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
            
            <form method="POST" action="">
                <!-- Department -->
                <div class="form-group">
                    <label for="college_department">College/Department *</label>
                    <select name="college_department" id="college_department" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                    <?php echo $record['college_department'] == $dept['department_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_code'] . ' - ' . $dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Name Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" required
                               value="<?php echo htmlspecialchars($record['last_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" required
                               value="<?php echo htmlspecialchars($record['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-control"
                               value="<?php echo htmlspecialchars($record['middle_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Email and Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="liceo_email">Liceo Email Address</label>
                        <input type="email" name="liceo_email" id="liceo_email" class="form-control"
                               value="<?php echo htmlspecialchars($record['liceo_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="employment_status">Status</label>
                        <select name="employment_status" id="employment_status" class="form-control">
                            <option value="Full-time" <?php echo ($record['employment_status'] ?? 'Full-time') == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo $record['employment_status'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="Probationary" <?php echo $record['employment_status'] == 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="record_date">Record Date</label>
                        <input type="date" name="record_date" id="record_date" class="form-control"
                               value="<?php echo $record['record_date'] ?? ''; ?>">
                    </div>
                </div>
                
                <!-- Date/Time Tracking Section -->
                <div class="card-header" style="margin-top: 20px; margin-bottom: 15px; padding: 0;">
                    <h3 class="card-title" style="font-size: 16px;">Request Status Tracking</h3>
                </div>
                
                <!-- Status Timeline -->
                <div class="status-timeline">
                    <div class="timeline-step <?php echo !empty($record['datetime_received']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon">1</div>
                        <div class="timeline-label">Received</div>
                        <div class="timeline-date"><?php echo formatDateTimeForDisplay($record['datetime_received']); ?></div>
                    </div>
                    <div class="timeline-connector <?php echo !empty($record['datetime_processed']) ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo !empty($record['datetime_processed']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon">2</div>
                        <div class="timeline-label">Processed</div>
                        <div class="timeline-date"><?php echo formatDateTimeForDisplay($record['datetime_processed']); ?></div>
                    </div>
                    <div class="timeline-connector <?php echo !empty($record['datetime_accomplished']) ? 'completed' : ''; ?>"></div>
                    <div class="timeline-step <?php echo !empty($record['datetime_accomplished']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon">3</div>
                        <div class="timeline-label">Accomplished</div>
                        <div class="timeline-date"><?php echo formatDateTimeForDisplay($record['datetime_accomplished']); ?></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="datetime_received">Date/Time Received</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_received" id="datetime_received" class="form-control"
                                   value="<?php echo formatDateTimeForInput($record['datetime_received']); ?>">
                            <button type="button" class="btn btn-sm btn-now" onclick="setCurrentDateTime('datetime_received')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_received')">Clear</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="datetime_processed">Date/Time Processed</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_processed" id="datetime_processed" class="form-control"
                                   value="<?php echo formatDateTimeForInput($record['datetime_processed']); ?>">
                            <button type="button" class="btn btn-sm btn-now" onclick="setCurrentDateTime('datetime_processed')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_processed')">Clear</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="datetime_accomplished">Date/Time Accomplished</label>
                        <div class="datetime-input-group">
                            <input type="datetime-local" name="datetime_accomplished" id="datetime_accomplished" class="form-control"
                                   value="<?php echo formatDateTimeForInput($record['datetime_accomplished']); ?>">
                            <button type="button" class="btn btn-sm btn-now" onclick="setCurrentDateTime('datetime_accomplished')">Now</button>
                            <button type="button" class="btn btn-sm btn-clear" onclick="clearDateTime('datetime_accomplished')">Clear</button>
                        </div>
                    </div>
                </div>
                
                <!-- Remarks -->
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></textarea>
                </div>
                
                <!-- Record Info -->
                <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 13px; color: #666;">
                    <strong>Record Info:</strong><br>
                    Created: <?php echo date('M d, Y g:i A', strtotime($record['created_at'])); ?><br>
                    Recorded By: <?php echo htmlspecialchars($record['recorded_by'] ?? 'N/A'); ?>
                </div>
                
                <!-- Submit Buttons -->
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="records.php" class="btn btn-secondary">← Back to Records</a>
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