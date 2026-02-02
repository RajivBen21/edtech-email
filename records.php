<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Department mapping (acronym => full name)
$department_mapping = [
    'GO-PDSA' => 'Guidance Office & PDSA SHS-RNP',
    'CN' => 'College of Nursing',
    'CMLS' => 'College of Medical Laboratory Science',
    'CAS' => 'College of Arts and Sciences',
    'JHS' => 'Junior High School',
    'CP' => 'College of Pharmacy',
    'CL' => 'College of Law',
    'LP' => 'Liceo Press',
    'QA' => 'Quality Assurance',
    'HR' => 'Human Resources',
    'REG' => 'Registrar',
    'SHS' => 'Senior High School',
    'STE' => 'School of Teacher Education',
    'SBMA' => 'School of Business Management and Accountancy',
    'CCJ' => 'College of Criminal Justice',
    'DIAG' => 'Diagnostic',
    'CMTD' => 'College of Music, Theatre and Dance',
    'CRS' => 'College of Rehabilitation Science',
    'CEng' => 'College of Engineering',
    'AF' => 'Accounting and Finance',
    'GS' => 'Grade School',
    'ETEEAP' => 'Expanded Tertiary Education Equivalency and Accreditation Program',
    'LIB' => 'Library',
    'RPO' => 'Research Publication Office',
    'MDC' => 'Medical/Dental Clinic',
    'CRT' => 'College of Radiologic Technology',
    'LC' => 'La Castilla',
    'CMed' => 'College of Medicine',
    'LN' => 'Liceo Net',
    'IA' => 'Internal Audit',
    'EAA' => 'Existing Account for Activation',
    'PP' => 'Physical Plant',
    'PHK' => 'PhilHealth Konsulta',
    'GUID' => 'Guidance'
];

// Helper function to get request status
function getRequestStatus($record) {
    if (!empty($record['datetime_accomplished'])) {
        return ['status' => 'accomplished', 'label' => 'Accomplished', 'class' => 'badge-accomplished'];
    } elseif (!empty($record['datetime_processed'])) {
        return ['status' => 'processing', 'label' => 'Processing', 'class' => 'badge-processing'];
    } elseif (!empty($record['datetime_received'])) {
        return ['status' => 'received', 'label' => 'Received', 'class' => 'badge-received'];
    }
    return ['status' => 'pending', 'label' => 'Pending', 'class' => 'badge-pending'];
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Delete single record
    if ($_POST['action'] == 'delete_single' && isset($_POST['record_id'])) {
        $record_id = (int)$_POST['record_id'];
        
        // Get record data before deleting (for undo)
        $stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id = ?");
        $stmt->execute([$record_id]);
        $deleted_record = $stmt->fetch();
        
        if ($deleted_record) {
            // Store in session for undo
            $_SESSION['deleted_records'] = [$deleted_record];
            $_SESSION['delete_time'] = time();
            
            // Delete the record
            $stmt = $pdo->prepare("DELETE FROM email_records WHERE record_id = ?");
            $stmt->execute([$record_id]);
            
            header('Location: records.php?deleted=1&count=1');
            exit;
        }
    }
    
    // Delete multiple records
    if ($_POST['action'] == 'delete_multiple' && isset($_POST['selected_records'])) {
        $selected_ids = $_POST['selected_records'];
        $count = count($selected_ids);
        
        if ($count > 0) {
            // Get records data before deleting (for undo)
            $placeholders = str_repeat('?,', $count - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            $deleted_records = $stmt->fetchAll();
            
            // Store in session for undo
            $_SESSION['deleted_records'] = $deleted_records;
            $_SESSION['delete_time'] = time();
            
            // Delete the records
            $stmt = $pdo->prepare("DELETE FROM email_records WHERE record_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            
            header('Location: records.php?deleted=1&count=' . $count);
            exit;
        }
    }
    
    // Undo delete
    if ($_POST['action'] == 'undo_delete' && isset($_SESSION['deleted_records'])) {
        $deleted_records = $_SESSION['deleted_records'];
        $restored_count = 0;
        
        foreach ($deleted_records as $record) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO email_records 
                    (record_id, college_department, last_name, first_name, middle_name, email, password, 
                     record_date, account_status, request_type, recorded_by, notes, created_at,
                     datetime_received, datetime_processed, datetime_accomplished)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $record['record_id'],
                    $record['college_department'],
                    $record['last_name'],
                    $record['first_name'],
                    $record['middle_name'],
                    $record['email'],
                    $record['password'],
                    $record['record_date'],
                    $record['account_status'],
                    $record['request_type'],
                    $record['recorded_by'],
                    $record['notes'],
                    $record['created_at'],
                    $record['datetime_received'],
                    $record['datetime_processed'],
                    $record['datetime_accomplished']
                ]);
                $restored_count++;
            } catch (Exception $e) {
                // Record might already exist or other error
            }
        }
        
        // Clear session
        unset($_SESSION['deleted_records']);
        unset($_SESSION['delete_time']);
        
        header('Location: records.php?restored=' . $restored_count);
        exit;
    }
}

// Check if undo should still be available (within 30 seconds)
$show_undo = false;
if (isset($_SESSION['deleted_records']) && isset($_SESSION['delete_time'])) {
    $time_elapsed = time() - $_SESSION['delete_time'];
    if ($time_elapsed <= 30) {
        $show_undo = true;
        $undo_time_remaining = 30 - $time_elapsed;
    } else {
        // Clear expired undo data
        unset($_SESSION['deleted_records']);
        unset($_SESSION['delete_time']);
    }
}

// Get filter values
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_request_status = isset($_GET['request_status']) ? $_GET['request_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
$records_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Check for messages
$error = '';
$success = '';

if (isset($_GET['error']) && $_GET['error'] == 'no_selection') {
    $error = 'Please select at least one record to export.';
}

if (isset($_GET['deleted']) && isset($_GET['count'])) {
    $count = (int)$_GET['count'];
    $success = $count . ' record' . ($count > 1 ? 's' : '') . ' deleted successfully.';
}

if (isset($_GET['restored'])) {
    $count = (int)$_GET['restored'];
    $success = $count . ' record' . ($count > 1 ? 's' : '') . ' restored successfully.';
}

// Build query
$sql = "SELECT * FROM email_records WHERE 1=1";
$params = [];

if (!empty($filter_department)) {
    $acronym = array_search($filter_department, $department_mapping);
    
    if ($acronym !== false) {
        $sql .= " AND (college_department = ? OR college_department = ?)";
        $params[] = $filter_department;
        $params[] = $acronym;
    } else {
        $sql .= " AND college_department = ?";
        $params[] = $filter_department;
    }
}

if (!empty($filter_status)) {
    $sql .= " AND account_status = ?";
    $params[] = $filter_status;
}

// Filter by request status
if (!empty($filter_request_status)) {
    switch ($filter_request_status) {
        case 'pending':
            $sql .= " AND datetime_received IS NULL";
            break;
        case 'received':
            $sql .= " AND datetime_received IS NOT NULL AND datetime_processed IS NULL";
            break;
        case 'processing':
            $sql .= " AND datetime_processed IS NOT NULL AND datetime_accomplished IS NULL";
            break;
        case 'accomplished':
            $sql .= " AND datetime_accomplished IS NOT NULL";
            break;
    }
}

if (!empty($search)) {
    $sql .= " AND (last_name LIKE ? OR first_name LIKE ? OR email LIKE ? OR college_department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

// Get total count for pagination
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Add LIMIT for pagination
$offset = ($current_page - 1) * $records_per_page;
$sql .= " LIMIT $records_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get departments for filter dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Records - LDCU Email System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Delete button styles */
        .action-btn.delete {
            background: #e53e3e;
            color: white;
        }
        .action-btn.delete:hover {
            background: #c53030;
        }
        
        /* Undo toast notification */
        .undo-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #1a365d;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
        
        .undo-toast .undo-message {
            font-size: 14px;
        }
        
        .undo-toast .undo-btn {
            background: #4299e1;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .undo-toast .undo-btn:hover {
            background: #3182ce;
        }
        
        .undo-toast .undo-timer {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .undo-toast .close-toast {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
            padding: 0 5px;
        }
        
        .undo-toast .close-toast:hover {
            opacity: 1;
        }
        
        /* Delete confirmation modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal h3 {
            color: #1a365d;
            margin-bottom: 15px;
        }
        
        .modal p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        /* Delete selected bar */
        .delete-bar {
            background: #fed7d7;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
            align-items: center;
            gap: 15px;
            border: 1px solid #feb2b2;
        }
        
        .delete-bar.show {
            display: flex;
        }
        
        .delete-bar span {
            color: #c53030;
            font-weight: 600;
        }
    </style>
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
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">All Email Records (<?php echo $total_records; ?> total records)</h3>
                <a href="add_record.php" class="btn btn-primary">+ Add New Record</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <form method="GET" action="" class="search-bar">
                <select name="department" class="form-control" style="width: auto;">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                <?php echo $filter_department == $dept['department_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department_code'] . ' - ' . $dept['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status" class="form-control" style="width: auto;">
                    <option value="">All Account Status</option>
                    <option value="Active" <?php echo $filter_status == 'Active' ? 'selected' : ''; ?>>Activated</option>   
                    <option value="Deactivated" <?php echo $filter_status == 'Deactivated' ? 'selected' : ''; ?>>Deactivated</option>
                </select>
                
                <select name="request_status" class="form-control" style="width: auto;">
                    <option value="">All Request Status</option>
                    <option value="pending" <?php echo $filter_request_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="received" <?php echo $filter_request_status == 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="processing" <?php echo $filter_request_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="accomplished" <?php echo $filter_request_status == 'accomplished' ? 'selected' : ''; ?>>Accomplished</option>
                </select>
                
                <input type="text" name="search" placeholder="Search name, email, or department..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="records.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <?php if (count($records) > 0): ?>
            
            <!-- Delete Selected Bar -->
            <div class="delete-bar" id="deleteBar">
                <span>🗑️ <span id="deleteCount">0</span> record(s) selected</span>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteMultiple()">Delete Selected</button>
                <button type="button" class="btn btn-secondary" onclick="clearSelection()">Cancel</button>
            </div>
            
            <!-- Export Form -->
            <form method="POST" action="preview_export.php" id="exportForm">
                <div class="export-bar">
                    <label>Export Selected:</label>
                    <select name="request_type" class="form-control" style="width: auto;">
                        <option value="New">New Email</option>
                        <option value="Activate">Activate</option>
                    </select>
                    <button type="submit" class="btn btn-success"> Export to Word</button>
                    <span class="select-count"><span id="selectedCount">0</span> selected</span>
                </div>
            
                <!-- Records Table -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAll" title="Select All">
                                </th>
                                <th>#</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Request Status</th>
                                <th>Account Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($current_page - 1) * $records_per_page + 1;
                            $count = $start_number; 
                            foreach ($records as $record): 
                                $dept_display = $record['college_department'];
                                $dept_full = isset($department_mapping[$dept_display]) ? $department_mapping[$dept_display] : '';
                                $request_status = getRequestStatus($record);
                            ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="selected_records[]" 
                                           value="<?php echo $record['record_id']; ?>" 
                                           class="record-checkbox">
                                </td>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                                    <?php echo htmlspecialchars($record['first_name']); ?> 
                                    <?php echo htmlspecialchars($record['middle_name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($dept_display); ?>
                                    <?php if ($dept_full): ?>
                                        <br><span class="dept-badge"><?php echo htmlspecialchars($dept_full); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $request_status['class']; ?>">
                                        <?php echo $request_status['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $record['account_status'] == 'Active' ? 'approved' : 'rejected'; ?>">
                                        <?php echo $record['account_status'] == 'Active' ? 'Activated' : 'Deactivated'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_record.php?id=<?php echo $record['record_id']; ?>" 
                                           class="action-btn edit">Edit</a>
                                        <button type="button" class="action-btn delete" 
                                                onclick="confirmDeleteSingle(<?php echo $record['record_id']; ?>, '<?php echo htmlspecialchars($record['last_name'] . ', ' . $record['first_name']); ?>')">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $query_params = [];
                if ($filter_department) $query_params['department'] = $filter_department;
                if ($filter_status) $query_params['status'] = $filter_status;
                if ($filter_request_status) $query_params['request_status'] = $filter_request_status;
                if ($search) $query_params['search'] = $search;
                $query_string = http_build_query($query_params);
                $query_string = $query_string ? '&' . $query_string : '';
                ?>
                
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?><?php echo $query_string; ?>" class="btn btn-secondary">← Previous</a>
                <?php endif; ?>
                
                <div class="page-numbers">
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?page=1<?php echo $query_string; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                </div>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?><?php echo $query_string; ?>" class="btn btn-secondary">Next →</a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Showing <?php echo $start_number; ?>-<?php echo min($start_number + $records_per_page - 1, $total_records); ?> of <?php echo $total_records; ?> records
                </span>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records found. <a href="add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3>Confirm Delete</h3>
            <p id="deleteMessage">Are you sure you want to delete this record?</p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" action="" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="action" id="deleteAction" value="delete_single">
                    <input type="hidden" name="record_id" id="deleteRecordId" value="">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Undo Toast -->
    <?php if ($show_undo && isset($_GET['deleted'])): ?>
    <div class="undo-toast" id="undoToast">
        <span class="undo-message">
            <?php echo count($_SESSION['deleted_records']); ?> record(s) deleted
        </span>
        <form method="POST" action="" style="display: inline;">
            <input type="hidden" name="action" value="undo_delete">
            <button type="submit" class="undo-btn">↩ Undo</button>
        </form>
        <span class="undo-timer" id="undoTimer"><?php echo $undo_time_remaining; ?>s</span>
        <button type="button" class="close-toast" onclick="closeUndoToast()">×</button>
    </div>
    
    <script>
        // Countdown timer for undo
        let timeRemaining = <?php echo $undo_time_remaining; ?>;
        const timerInterval = setInterval(function() {
            timeRemaining--;
            document.getElementById('undoTimer').textContent = timeRemaining + 's';
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                closeUndoToast();
            }
        }, 1000);
        
        function closeUndoToast() {
            const toast = document.getElementById('undoToast');
            if (toast) {
                toast.style.display = 'none';
            }
        }
    </script>
    <?php endif; ?>

    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.record-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateCount();
        });

        // Individual checkbox change
        document.querySelectorAll('.record-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateCount();
            });
        });

        // Update selected count and show/hide delete bar
        function updateCount() {
            const checked = document.querySelectorAll('.record-checkbox:checked');
            const count = checked.length;
            
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('deleteCount').textContent = count;
            
            const deleteBar = document.getElementById('deleteBar');
            if (count > 0) {
                deleteBar.classList.add('show');
            } else {
                deleteBar.classList.remove('show');
            }
        }

        // Clear selection
        function clearSelection() {
            document.querySelectorAll('.record-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
            updateCount();
        }

        // Confirm delete single record
        function confirmDeleteSingle(recordId, recordName) {
            document.getElementById('deleteMessage').textContent = 
                'Are you sure you want to delete "' + recordName + '"?';
            document.getElementById('deleteAction').value = 'delete_single';
            document.getElementById('deleteRecordId').value = recordId;
            document.getElementById('deleteForm').innerHTML = 
                '<input type="hidden" name="action" value="delete_single">' +
                '<input type="hidden" name="record_id" value="' + recordId + '">' +
                '<button type="submit" class="btn btn-danger">Delete</button>';
            document.getElementById('deleteModal').classList.add('active');
        }

        // Confirm delete multiple records
        function confirmDeleteMultiple() {
            const checked = document.querySelectorAll('.record-checkbox:checked');
            const count = checked.length;
            
            if (count === 0) {
                alert('Please select at least one record to delete.');
                return;
            }
            
            document.getElementById('deleteMessage').textContent = 
                'Are you sure you want to delete ' + count + ' selected record(s)?';
            
            // Build form with selected IDs
            let formHtml = '<input type="hidden" name="action" value="delete_multiple">';
            checked.forEach(checkbox => {
                formHtml += '<input type="hidden" name="selected_records[]" value="' + checkbox.value + '">';
            });
            formHtml += '<button type="submit" class="btn btn-danger">Delete ' + count + ' Record(s)</button>';
            
            document.getElementById('deleteForm').innerHTML = formHtml;
            document.getElementById('deleteModal').classList.add('active');
        }

        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>