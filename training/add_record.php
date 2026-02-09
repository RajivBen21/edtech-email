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
    $activity_type = $_POST['activity_type'] ?? 'Training';
    $theme_topic = trim($_POST['theme_topic']);
    $objectives = trim($_POST['objectives'] ?? '');
    $activity_schedule = trim($_POST['activity_schedule'] ?? '');
    $time_allocated = trim($_POST['time_allocated'] ?? '');
    $no_of_participants = !empty($_POST['no_of_participants']) ? (int)$_POST['no_of_participants'] : null;
    $activity_venue = trim($_POST['activity_venue'] ?? '');
    $record_date = $_POST['record_date'] ?: date('Y-m-d');
    $remarks = trim($_POST['remarks'] ?? '');
    $recorded_by = $_SESSION['full_name'] ?? $_SESSION['username'];
    
    $datetime_received = !empty($_POST['datetime_received']) ? $_POST['datetime_received'] : null;
    $datetime_processed = !empty($_POST['datetime_processed']) ? $_POST['datetime_processed'] : null;
    $datetime_accomplished = !empty($_POST['datetime_accomplished']) ? $_POST['datetime_accomplished'] : null;
    
    if (empty($college_department) || empty($theme_topic)) {
        $error = 'Please fill in all required fields (Department, Theme/Topic).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO training_records 
                (college_department, activity_type, theme_topic, objectives, activity_schedule, 
                 time_allocated, no_of_participants, activity_venue, record_date, recorded_by, remarks,
                 datetime_received, datetime_processed, datetime_accomplished)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $college_department, $activity_type, $theme_topic, $objectives, $activity_schedule,
                $time_allocated, $no_of_participants, $activity_venue, $record_date, $recorded_by, $remarks,
                $datetime_received, $datetime_processed, $datetime_accomplished
            ]);
            
            if (isset($_POST['save_and_add'])) {
                $success = 'Record added successfully! Add another record below.';
                $_POST = [];
            } else {
                header('Location: records.php?added=1');
                exit;
            }
            
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
    <title>Add Training Record - LDCU EdTech System</title>
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
        .activity-type-group {
            display: flex;
            gap: 25px;
            padding: 10px 0;
        }
        .activity-type-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 400;
        }
        .activity-type-group input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
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
            <a href="../dashboard.php?module=training">Dashboard</a>
            <a href="records.php" class="active">Training Records</a>
            <a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add New Training/Seminar/Workshop Record</h3>
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
                        <label for="record_date">Date *</label>
                        <input type="date" name="record_date" id="record_date" class="form-control" 
                               value="<?php echo $_POST['record_date'] ?? date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Type of Activity *</label>
                    <div class="activity-type-group">
                        <label>
                            <input type="radio" name="activity_type" value="Training" 
                                   <?php echo (!isset($_POST['activity_type']) || $_POST['activity_type'] == 'Training') ? 'checked' : ''; ?>>
                            Training
                        </label>
                        <label>
                            <input type="radio" name="activity_type" value="Seminar"
                                   <?php echo (isset($_POST['activity_type']) && $_POST['activity_type'] == 'Seminar') ? 'checked' : ''; ?>>
                            Seminar
                        </label>
                        <label>
                            <input type="radio" name="activity_type" value="Workshop"
                                   <?php echo (isset($_POST['activity_type']) && $_POST['activity_type'] == 'Workshop') ? 'checked' : ''; ?>>
                            Workshop
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="theme_topic">Theme or Topic *</label>
                    <input type="text" name="theme_topic" id="theme_topic" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['theme_topic'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="objectives">Objectives of the Activity</label>
                    <textarea name="objectives" id="objectives" class="form-control" rows="4"
                              placeholder="Enter the objectives of the activity..."><?php echo htmlspecialchars($_POST['objectives'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity_schedule">Activity Schedule</label>
                        <input type="text" name="activity_schedule" id="activity_schedule" class="form-control"
                               placeholder="e.g., March 15-17, 2026"
                               value="<?php echo htmlspecialchars($_POST['activity_schedule'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="time_allocated">Amount of Time Allocated</label>
                        <input type="text" name="time_allocated" id="time_allocated" class="form-control"
                               placeholder="e.g., 8 hours / 3 days"
                               value="<?php echo htmlspecialchars($_POST['time_allocated'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="no_of_participants">No. of Participants</label>
                        <input type="number" name="no_of_participants" id="no_of_participants" class="form-control"
                               min="1"
                               value="<?php echo htmlspecialchars($_POST['no_of_participants'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="activity_venue">Activity Venue</label>
                        <input type="text" name="activity_venue" id="activity_venue" class="form-control"
                               placeholder="e.g., AVR, Conference Room"
                               value="<?php echo htmlspecialchars($_POST['activity_venue'] ?? ''); ?>">
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