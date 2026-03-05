<?php
require_once '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Department mapping
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
    'CIT' => 'College of Information Technology',
    'IA' => 'Internal Audit',
    'PP' => 'Physical Plant',
    'SophR' => 'Sophia Residence'
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

// Helper function to get employment status badge class
function getEmploymentStatusClass($status) {
    switch ($status) {
        case 'Full-time':
            return 'badge-approved';
        case 'Part-time':
            return 'badge-processing';
        case 'Probationary':
            return 'badge-pending';
        default:
            return 'badge-approved';
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'delete_single' && isset($_POST['record_id'])) {
        $record_id = (int)$_POST['record_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id = ?");
        $stmt->execute([$record_id]);
        $deleted_record = $stmt->fetch();
        
        if ($deleted_record) {
            $_SESSION['ws_deleted_records'] = [$deleted_record];
            $_SESSION['ws_delete_time'] = time();
            
            $stmt = $pdo->prepare("DELETE FROM workspace_license_records WHERE record_id = ?");
            $stmt->execute([$record_id]);
            
            header('Location: records.php?deleted=1&count=1');
            exit;
        }
    }
    
    if ($_POST['action'] == 'delete_multiple' && isset($_POST['selected_records'])) {
        $selected_ids = $_POST['selected_records'];
        $count = count($selected_ids);
        
        if ($count > 0) {
            $placeholders = str_repeat('?,', $count - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM workspace_license_records WHERE record_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            $deleted_records = $stmt->fetchAll();
            
            $_SESSION['ws_deleted_records'] = $deleted_records;
            $_SESSION['ws_delete_time'] = time();
            
            $stmt = $pdo->prepare("DELETE FROM workspace_license_records WHERE record_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            
            header('Location: records.php?deleted=1&count=' . $count);
            exit;
        }
    }
    
    if ($_POST['action'] == 'undo_delete' && isset($_SESSION['ws_deleted_records'])) {
        $deleted_records = $_SESSION['ws_deleted_records'];
        $restored_count = 0;
        
        foreach ($deleted_records as $record) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO workspace_license_records 
                    (record_id, college_department, last_name, first_name, middle_name, liceo_email,
                     employment_status, record_date, recorded_by, remarks, created_at,
                     datetime_received, datetime_processed, datetime_accomplished)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $record['record_id'],
                    $record['college_department'],
                    $record['last_name'],
                    $record['first_name'],
                    $record['middle_name'],
                    $record['liceo_email'],
                    $record['employment_status'],
                    $record['record_date'],
                    $record['recorded_by'],
                    $record['remarks'],
                    $record['created_at'],
                    $record['datetime_received'],
                    $record['datetime_processed'],
                    $record['datetime_accomplished']
                ]);
                $restored_count++;
            } catch (Exception $e) {
                // Record might already exist
            }
        }
        
        unset($_SESSION['ws_deleted_records']);
        unset($_SESSION['ws_delete_time']);
        
        header('Location: records.php?restored=' . $restored_count);
        exit;
    }
}

// Check if undo should be available
$show_undo = false;
if (isset($_SESSION['ws_deleted_records']) && isset($_SESSION['ws_delete_time'])) {
    $time_elapsed = time() - $_SESSION['ws_delete_time'];
    if ($time_elapsed <= 30) {
        $show_undo = true;
        $undo_time_remaining = 30 - $time_elapsed;
    } else {
        unset($_SESSION['ws_deleted_records']);
        unset($_SESSION['ws_delete_time']);
    }
}

// Get filter values
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_request_status = isset($_GET['request_status']) ? $_GET['request_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$records_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Messages
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
$sql = "SELECT * FROM workspace_license_records WHERE 1=1";
$params = [];

if (!empty($filter_department)) {
    $sql .= " AND college_department = ?";
    $params[] = $filter_department;
}

if (!empty($filter_status)) {
    $sql .= " AND employment_status = ?";
    $params[] = $filter_status;
}

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
    $sql .= " AND (last_name LIKE ? OR first_name LIKE ? OR liceo_email LIKE ? OR college_department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

// Get total count
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Add LIMIT
$offset = ($current_page - 1) * $records_per_page;
$sql .= " LIMIT $records_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get departments for filter
$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace License Records - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        #deleteBar {
            display: none;
            background: #fed7d7;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            align-items: center;
            gap: 15px;
            border: 1px solid #feb2b2;
        }
        #deleteBar.show {
            display: flex;
        }
        #deleteBar span {
            color: #c53030;
            font-weight: 600;
        }
        
        #selectionPanel {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            min-width: 320px;
            max-width: 400px;
            z-index: 9999;
            border: 2px solid #2b6cb0;
            font-family: 'Montserrat', sans-serif;
        }
        #selectionPanel.show {
            display: block;
        }
        
        #deleteModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        #deleteModal.active {
            display: flex;
        }
        
        .selection-panel-header {
            background: linear-gradient(135deg, #2b6cb0, #1a365d);
            color: white;
            padding: 12px 15px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .selection-panel-header .panel-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .selection-panel-header .panel-count {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .selection-panel-body {
            max-height: 350px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .selection-list {
            max-height: 280px;
            overflow-y: auto;
            padding: 10px;
        }
        .selection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #f7fafc;
            margin-bottom: 6px;
            border-radius: 8px;
            font-size: 13px;
            border-left: 3px solid #2b6cb0;
        }
        .selection-item:hover {
            background: #edf2f7;
        }
        .selection-item .item-info {
            flex: 1;
            min-width: 0;
        }
        .selection-item .item-name {
            font-weight: 600;
            color: #2d3748;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .selection-item .item-email {
            color: #718096;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .selection-item .remove-btn {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
            margin-left: 10px;
        }
        .selection-actions {
            padding: 12px;
            border-top: 1px solid #e2e8f0;
            background: #f7fafc;
            border-radius: 0 0 10px 10px;
        }
        .btn-icon {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            cursor: pointer;
            font-size: 14px;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .empty-selection {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-size: 13px;
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
        
        tr.selected-row {
            background-color: #ebf8ff !important;
        }
        tr.selected-row:hover {
            background-color: #bee3f8 !important;
        }
        
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
            z-index: 1001;
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
        }
        .undo-toast .close-toast {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
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
            <a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Google Workspace License Records (<?php echo $total_records; ?> total records)</h3>
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
                    <option value="">All Status</option>
                    <option value="Full-time" <?php echo $filter_status == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                    <option value="Part-time" <?php echo $filter_status == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                    <option value="Probationary" <?php echo $filter_status == 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
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
            <div id="deleteBar">
                <span>🗑️ <span id="deleteCount">0</span> record(s) selected</span>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteMultiple()">Delete Selected</button>
                <button type="button" class="btn btn-secondary" onclick="clearSelection()">Cancel</button>
            </div>
            
            <!-- Export Form -->
            <form method="POST" action="preview_export.php" id="exportForm">
                <div class="export-bar">
                    <label>Export Selected:</label>
                    <button type="submit" class="btn btn-success">Export to Word</button>
                    <span class="select-count"><span id="selectedCount">0</span> selected</span>
                </div>
            
                <!-- Records Table -->
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAll" title="Select All on This Page">
                                </th>
                                <th>#</th>
                                <th>Name</th>
                                <th>Liceo Email</th>
                                <th>Status</th>
                                <th>Request Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($current_page - 1) * $records_per_page + 1;
                            $count = $start_number; 
                            foreach ($records as $record): 
                                $request_status = getRequestStatus($record);
                                $employment_status = $record['employment_status'] ?? 'Full-time';
                                $status_class = getEmploymentStatusClass($employment_status);
                            ?>
                            <tr data-record-id="<?php echo $record['record_id']; ?>">
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="selected_records[]" 
                                           value="<?php echo $record['record_id']; ?>" 
                                           class="record-checkbox"
                                           data-name="<?php echo htmlspecialchars($record['last_name'] . ', ' . $record['first_name']); ?>"
                                           data-email="<?php echo htmlspecialchars($record['liceo_email']); ?>">
                                </td>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                                    <?php echo htmlspecialchars($record['first_name']); ?> 
                                    <?php echo htmlspecialchars($record['middle_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['liceo_email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo $employment_status; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $request_status['class']; ?>">
                                        <?php echo $request_status['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_record.php?id=<?php echo $record['record_id']; ?>" 
                                           class="action-btn edit">Edit</a>
                                        <button type="button" class="action-btn delete" 
                                                onclick="confirmDeleteSingle(<?php echo $record['record_id']; ?>, '<?php echo htmlspecialchars(addslashes($record['last_name'] . ', ' . $record['first_name'])); ?>')">
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

    <!-- Selection Panel -->
    <div id="selectionPanel">
        <div class="selection-panel-header">
            <div class="panel-title">
                📋 <span>Selected Records</span>
                <span class="panel-count" id="panelCount">0</span>
            </div>
            <button type="button" class="btn-icon" onclick="toggleSelectionPreview()">
                <span id="toggleIcon">▼</span>
            </button>
        </div>
        <div class="selection-panel-body" id="selectionPanelBody">
            <div class="selection-list" id="selectionList">
                <div class="empty-selection">No records selected</div>
            </div>
            <div class="selection-actions">
                <button type="button" class="btn btn-secondary" onclick="clearSelection()">Clear All</button>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal">
        <div class="modal">
            <h3>Confirm Delete</h3>
            <p id="deleteMessage">Are you sure you want to delete this record?</p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" action="" id="deleteForm" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Undo Toast -->
    <?php if ($show_undo && isset($_GET['deleted'])): ?>
    <div class="undo-toast" id="undoToast">
        <span><?php echo count($_SESSION['ws_deleted_records']); ?> record(s) deleted</span>
        <form method="POST" action="" style="display: inline;">
            <input type="hidden" name="action" value="undo_delete">
            <button type="submit" class="undo-btn">↩ Undo</button>
        </form>
        <span id="undoTimer"><?php echo $undo_time_remaining; ?>s</span>
        <button type="button" class="close-toast" onclick="document.getElementById('undoToast').style.display='none'">×</button>
    </div>
    <script>
        let timeRemaining = <?php echo $undo_time_remaining; ?>;
        const timerInterval = setInterval(function() {
            timeRemaining--;
            document.getElementById('undoTimer').textContent = timeRemaining + 's';
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                document.getElementById('undoToast').style.display = 'none';
            }
        }, 1000);
    </script>
    <?php endif; ?>

    <script>
        // Selection Manager (using different key for workspace module)
        const SelectionManager = {
            storageKey: 'wsSelectedRecords',
            detailsKey: 'wsSelectedRecordDetails',
            
            getSelectedIds() {
                const data = sessionStorage.getItem(this.storageKey);
                return data ? JSON.parse(data) : [];
            },
            
            getSelectedDetails() {
                const data = sessionStorage.getItem(this.detailsKey);
                return data ? JSON.parse(data) : {};
            },
            
            saveSelectedIds(ids) {
                sessionStorage.setItem(this.storageKey, JSON.stringify(ids));
            },
            
            saveSelectedDetails(details) {
                sessionStorage.setItem(this.detailsKey, JSON.stringify(details));
            },
            
            add(id, name, email) {
                const ids = this.getSelectedIds();
                const details = this.getSelectedDetails();
                if (!ids.includes(id)) {
                    ids.push(id);
                    this.saveSelectedIds(ids);
                }
                details[id] = { name: name, email: email };
                this.saveSelectedDetails(details);
            },
            
            remove(id) {
                let ids = this.getSelectedIds();
                let details = this.getSelectedDetails();
                ids = ids.filter(item => item !== id);
                delete details[id];
                this.saveSelectedIds(ids);
                this.saveSelectedDetails(details);
            },
            
            clear() {
                sessionStorage.removeItem(this.storageKey);
                sessionStorage.removeItem(this.detailsKey);
            },
            
            isSelected(id) {
                return this.getSelectedIds().includes(id);
            },
            
            count() {
                return this.getSelectedIds().length;
            }
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.record-checkbox').forEach(checkbox => {
                if (SelectionManager.isSelected(checkbox.value)) {
                    checkbox.checked = true;
                    const row = checkbox.closest('tr');
                    if (row) row.classList.add('selected-row');
                }
            });
            updateSelectionUI();
            updateSelectionPreview();
            updateSelectAllCheckbox();
        });

        // Select All
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.record-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
                const name = checkbox.getAttribute('data-name');
                const email = checkbox.getAttribute('data-email');
                const row = checkbox.closest('tr');
                if (this.checked) {
                    SelectionManager.add(checkbox.value, name, email);
                    if (row) row.classList.add('selected-row');
                } else {
                    SelectionManager.remove(checkbox.value);
                    if (row) row.classList.remove('selected-row');
                }
            });
            updateSelectionUI();
            updateSelectionPreview();
        });

        // Individual checkbox
        document.querySelectorAll('.record-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const name = this.getAttribute('data-name');
                const email = this.getAttribute('data-email');
                const row = this.closest('tr');
                if (this.checked) {
                    SelectionManager.add(this.value, name, email);
                    if (row) row.classList.add('selected-row');
                } else {
                    SelectionManager.remove(this.value);
                    if (row) row.classList.remove('selected-row');
                }
                updateSelectionUI();
                updateSelectionPreview();
                updateSelectAllCheckbox();
            });
        });

        function updateSelectionUI() {
            const totalSelected = SelectionManager.count();
            document.getElementById('selectedCount').textContent = totalSelected;
            document.getElementById('deleteCount').textContent = totalSelected;
            document.getElementById('panelCount').textContent = totalSelected;
            
            const deleteBar = document.getElementById('deleteBar');
            const panel = document.getElementById('selectionPanel');
            if (totalSelected > 0) {
                deleteBar.classList.add('show');
                panel.classList.add('show');
            } else {
                deleteBar.classList.remove('show');
                panel.classList.remove('show');
            }
        }

        function updateSelectAllCheckbox() {
            const all = document.querySelectorAll('.record-checkbox');
            const checked = document.querySelectorAll('.record-checkbox:checked');
            const selectAll = document.getElementById('selectAll');
            if (all.length > 0 && checked.length === all.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else if (checked.length > 0) {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
        }

        function updateSelectionPreview() {
            const details = SelectionManager.getSelectedDetails();
            const list = document.getElementById('selectionList');
            const count = SelectionManager.count();
            if (count === 0) {
                list.innerHTML = '<div class="empty-selection">No records selected</div>';
                return;
            }
            let html = '';
            for (const [id, record] of Object.entries(details)) {
                html += `<div class="selection-item"><div class="item-info"><div class="item-name">${record.name}</div><div class="item-email">${record.email || 'No email'}</div></div><button type="button" class="remove-btn" onclick="removeFromSelection('${id}')">×</button></div>`;
            }
            list.innerHTML = html;
        }

        function toggleSelectionPreview() {
            const body = document.getElementById('selectionPanelBody');
            const icon = document.getElementById('toggleIcon');
            if (body.style.display === 'none') {
                body.style.display = 'flex';
                icon.textContent = '▼';
            } else {
                body.style.display = 'none';
                icon.textContent = '▲';
            }
        }

        function removeFromSelection(id) {
            SelectionManager.remove(id);
            const checkbox = document.querySelector(`.record-checkbox[value="${id}"]`);
            if (checkbox) {
                checkbox.checked = false;
                const row = checkbox.closest('tr');
                if (row) row.classList.remove('selected-row');
            }
            updateSelectionUI();
            updateSelectionPreview();
            updateSelectAllCheckbox();
        }

        function clearSelection() {
            SelectionManager.clear();
            document.querySelectorAll('.record-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                const row = checkbox.closest('tr');
                if (row) row.classList.remove('selected-row');
            });
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateSelectionUI();
            updateSelectionPreview();
        }

        // Export form
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedIds = SelectionManager.getSelectedIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one record to export.');
                return;
            }
            this.querySelectorAll('input[name="selected_records[]"]').forEach(input => input.remove());
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_records[]';
                input.value = id;
                this.appendChild(input);
            });
            this.submit();
        });

        // Delete functions
        function confirmDeleteSingle(recordId, recordName) {
            document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete "' + recordName + '"?';
            document.getElementById('deleteForm').innerHTML = '<input type="hidden" name="action" value="delete_single"><input type="hidden" name="record_id" value="' + recordId + '"><button type="submit" class="btn btn-danger">Delete</button>';
            document.getElementById('deleteModal').classList.add('active');
        }

        function confirmDeleteMultiple() {
            const selectedIds = SelectionManager.getSelectedIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one record to delete.');
                return;
            }
            document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete ' + selectedIds.length + ' selected record(s)?';
            let formHtml = '<input type="hidden" name="action" value="delete_multiple">';
            selectedIds.forEach(id => { formHtml += '<input type="hidden" name="selected_records[]" value="' + id + '">'; });
            formHtml += '<button type="submit" class="btn btn-danger">Delete ' + selectedIds.length + ' Record(s)</button>';
            document.getElementById('deleteForm').innerHTML = formHtml;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });

        <?php if (isset($_GET['deleted']) || isset($_GET['restored'])): ?>
        SelectionManager.clear();
        <?php endif; ?>
    </script>
</body>
</html>