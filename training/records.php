

<?php
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'delete_single' && isset($_POST['record_id'])) {
        $record_id = (int)$_POST['record_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM training_records WHERE record_id = ?");
        $stmt->execute([$record_id]);
        $deleted_record = $stmt->fetch();
        
        if ($deleted_record) {
            $_SESSION['tr_deleted_records'] = [$deleted_record];
            $_SESSION['tr_delete_time'] = time();
            
            $stmt = $pdo->prepare("DELETE FROM training_records WHERE record_id = ?");
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
            $stmt = $pdo->prepare("SELECT * FROM training_records WHERE record_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            $deleted_records = $stmt->fetchAll();
            
            $_SESSION['tr_deleted_records'] = $deleted_records;
            $_SESSION['tr_delete_time'] = time();
            
            $stmt = $pdo->prepare("DELETE FROM training_records WHERE record_id IN ($placeholders)");
            $stmt->execute($selected_ids);
            
            header('Location: records.php?deleted=1&count=' . $count);
            exit;
        }
    }
    
    if ($_POST['action'] == 'undo_delete' && isset($_SESSION['tr_deleted_records'])) {
        $deleted_records = $_SESSION['tr_deleted_records'];
        $restored_count = 0;
        
        foreach ($deleted_records as $record) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO training_records 
                    (record_id, college_department, activity_type, theme_topic, objectives, activity_schedule,
                     time_allocated, no_of_participants, activity_venue, record_date, recorded_by, remarks, created_at,
                     datetime_received, datetime_processed, datetime_accomplished)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $record['record_id'],
                    $record['college_department'],
                    $record['activity_type'],
                    $record['theme_topic'],
                    $record['objectives'],
                    $record['activity_schedule'],
                    $record['time_allocated'],
                    $record['no_of_participants'],
                    $record['activity_venue'],
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
            }
        }
        
        unset($_SESSION['tr_deleted_records']);
        unset($_SESSION['tr_delete_time']);
        
        header('Location: records.php?restored=' . $restored_count);
        exit;
    }
}

$show_undo = false;
if (isset($_SESSION['tr_deleted_records']) && isset($_SESSION['tr_delete_time'])) {
    $time_elapsed = time() - $_SESSION['tr_delete_time'];
    if ($time_elapsed <= 30) {
        $show_undo = true;
        $undo_time_remaining = 30 - $time_elapsed;
    } else {
        unset($_SESSION['tr_deleted_records']);
        unset($_SESSION['tr_delete_time']);
    }
}

$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_activity_type = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';
$filter_request_status = isset($_GET['request_status']) ? $_GET['request_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$records_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$error = '';
$success = '';

if (isset($_GET['error']) && $_GET['error'] == 'no_selection') {
    $error = 'Please select at least one record to export.';
}

if (isset($_GET['added'])) {
    $success = 'Record added successfully!';
}

if (isset($_GET['deleted']) && isset($_GET['count'])) {
    $count = (int)$_GET['count'];
    $success = $count . ' record' . ($count > 1 ? 's' : '') . ' deleted successfully.';
}

if (isset($_GET['restored'])) {
    $count = (int)$_GET['restored'];
    $success = $count . ' record' . ($count > 1 ? 's' : '') . ' restored successfully.';
}

$sql = "SELECT * FROM training_records WHERE 1=1";
$params = [];

if (!empty($filter_department)) {
    $sql .= " AND college_department = ?";
    $params[] = $filter_department;
}

if (!empty($filter_activity_type)) {
    $sql .= " AND activity_type = ?";
    $params[] = $filter_activity_type;
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
    $sql .= " AND (theme_topic LIKE ? OR objectives LIKE ? OR activity_venue LIKE ? OR college_department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

$offset = ($current_page - 1) * $records_per_page;
$sql .= " LIMIT $records_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Records - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
        }
        #selectionPanel.show { display: block; }
        
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
        #deleteModal.active { display: flex; }
        
        .selection-panel-header {
            background: linear-gradient(135deg, #2b6cb0, #1a365d);
            color: white;
            padding: 12px 15px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .selection-panel-header .panel-count {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
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
        .selection-item .item-info { flex: 1; min-width: 0; }
        .selection-item .item-name {
            font-weight: 600;
            color: #2d3748;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .selection-item .item-dept {
            color: #718096;
            font-size: 11px;
        }
        .selection-item .remove-btn {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
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
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .modal {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .modal h3 { color: #1a365d; margin-bottom: 15px; }
        .modal p { color: #666; margin-bottom: 25px; }
        .modal-buttons { display: flex; gap: 15px; justify-content: center; }
        
        tr.selected-row { background-color: #ebf8ff !important; }
        
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
            font-weight: 600;
        }
        .undo-toast .close-toast {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-received { background: #dbeafe; color: #1e40af; }
        .badge-processing { background: #fce7f3; color: #9d174d; }
        .badge-accomplished { background: #d1fae5; color: #065f46; }
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
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h3 class="card-title">Training/Seminar/Workshop Records</h3>
                <div style="display: flex; gap: 10px;">
                    <a href="add_record.php" class="btn btn-primary">+ Add New Record</a>
                </div>
            </div>
            
            <form method="GET" action="" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                        <label for="search" style="font-size: 12px;">Search</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Theme, venue, department..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group" style="margin: 0; min-width: 180px;">
                        <label for="department" style="font-size: 12px;">Department</label>
                        <select name="department" id="department" class="form-control">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                        <?php echo $filter_department == $dept['department_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0; min-width: 150px;">
                        <label for="activity_type" style="font-size: 12px;">Activity Type</label>
                        <select name="activity_type" id="activity_type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Training" <?php echo $filter_activity_type == 'Training' ? 'selected' : ''; ?>>Training</option>
                            <option value="Seminar" <?php echo $filter_activity_type == 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Workshop" <?php echo $filter_activity_type == 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0; min-width: 150px;">
                        <label for="request_status" style="font-size: 12px;">Status</label>
                        <select name="request_status" id="request_status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filter_request_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="received" <?php echo $filter_request_status == 'received' ? 'selected' : ''; ?>>Received</option>
                            <option value="processing" <?php echo $filter_request_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="accomplished" <?php echo $filter_request_status == 'accomplished' ? 'selected' : ''; ?>>Accomplished</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="records.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
            
            <div style="margin-bottom: 15px; color: #666;">
                Showing <?php echo count($records); ?> of <?php echo $total_records; ?> records
                <?php if ($total_pages > 1): ?>
                    (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                <?php endif; ?>
            </div>
            
            <?php if (count($records) > 0): ?>
            <form id="recordsForm" method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="">
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                                </th>
                                <th>#</th>
                                <th>Department</th>
                                <th>Theme/Topic</th>
                                <th>Activity Type</th>
                                <th>Schedule</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $num = $offset + 1;
                            foreach ($records as $record): 
                                $status = getRequestStatus($record);
                            ?>
                            <tr id="row-<?php echo $record['record_id']; ?>">
                                <td>
                                    <input type="checkbox" name="selected_records[]" 
                                           value="<?php echo $record['record_id']; ?>"
                                           class="record-checkbox"
                                           data-id="<?php echo $record['record_id']; ?>"
                                           data-theme="<?php echo htmlspecialchars($record['theme_topic']); ?>"
                                           data-dept="<?php echo htmlspecialchars($record['college_department']); ?>"
                                           onchange="updateSelection()">
                                </td>
                                <td><?php echo $num++; ?></td>
                                <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                                <td><?php echo htmlspecialchars(substr($record['theme_topic'], 0, 40)) . (strlen($record['theme_topic']) > 40 ? '...' : ''); ?></td>
                                <td>
                                    <?php 
                                    $typeClass = 'approved';
                                    if ($record['activity_type'] == 'Seminar') $typeClass = 'processing';
                                    if ($record['activity_type'] == 'Workshop') $typeClass = 'pending';
                                    ?>
                                    <span class="badge badge-<?php echo $typeClass; ?>">
                                        <?php echo $record['activity_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($record['activity_schedule'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($record['activity_venue'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $status['class']; ?>">
                                        <?php echo $status['label']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                <td>
                                    <a href="edit_record.php?id=<?php echo $record['record_id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                    <button type="button" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" 
                                            onclick="confirmDeleteSingle(<?php echo $record['record_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
                <?php if ($current_page > 1): ?>
                    <a href="?page=1<?php echo $filter_department ? '&department='.urlencode($filter_department) : ''; ?><?php echo $filter_activity_type ? '&activity_type='.urlencode($filter_activity_type) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-secondary" style="padding: 8px 12px;">First</a>
                    <a href="?page=<?php echo $current_page - 1; ?><?php echo $filter_department ? '&department='.urlencode($filter_department) : ''; ?><?php echo $filter_activity_type ? '&activity_type='.urlencode($filter_activity_type) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-secondary" style="padding: 8px 12px;">Prev</a>
                <?php endif; ?>
                
                <span style="padding: 8px 15px; background: #1a365d; color: white; border-radius: 5px;">
                    <?php echo $current_page; ?> / <?php echo $total_pages; ?>
                </span>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?><?php echo $filter_department ? '&department='.urlencode($filter_department) : ''; ?><?php echo $filter_activity_type ? '&activity_type='.urlencode($filter_activity_type) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-secondary" style="padding: 8px 12px;">Next</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo $filter_department ? '&department='.urlencode($filter_department) : ''; ?><?php echo $filter_activity_type ? '&activity_type='.urlencode($filter_activity_type) : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-secondary" style="padding: 8px 12px;">Last</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records found. <a href="add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="selectionPanel">
        <div class="selection-panel-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>Selected</span>
                <span class="panel-count" id="panelCount">0</span>
            </div>
            <button class="btn-icon" onclick="clearAllSelections()">Clear All</button>
        </div>
        <div class="selection-list" id="selectionList"></div>
        <div class="selection-actions">
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-success" style="flex: 1;" onclick="exportSelected()">Export to Word</button>
                <button type="button" class="btn btn-danger" style="flex: 1;" onclick="confirmDeleteMultiple()">Delete Selected</button>
            </div>
        </div>
    </div>
    
    <div id="deleteModal">
        <div class="modal">
            <h3>Confirm Delete</h3>
            <p id="deleteModalText">Are you sure you want to delete this record?</p>
            <div class="modal-buttons">
                <button class="btn btn-danger" onclick="executeDelete()">Yes, Delete</button>
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <?php if ($show_undo): ?>
    <div class="undo-toast" id="undoToast">
        <span>Records deleted. <span id="undoTimer"><?php echo $undo_time_remaining; ?></span>s to undo</span>
        <form method="POST" action="" style="display: inline;">
            <input type="hidden" name="action" value="undo_delete">
            <button type="submit" class="undo-btn">Undo</button>
        </form>
        <button class="close-toast" onclick="closeUndoToast()">x</button>
    </div>
    <script>
        let undoTime = <?php echo $undo_time_remaining; ?>;
        const undoInterval = setInterval(() => {
            undoTime--;
            document.getElementById('undoTimer').textContent = undoTime;
            if (undoTime <= 0) {
                clearInterval(undoInterval);
                document.getElementById('undoToast').style.display = 'none';
            }
        }, 1000);
        
        function closeUndoToast() {
            clearInterval(undoInterval);
            document.getElementById('undoToast').style.display = 'none';
        }
    </script>
    <?php endif; ?>
    
    <script>
        let selectedRecords = {};
        let deleteMode = '';
        let deleteId = null;
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.record-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateSelection();
        }
        
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.record-checkbox:checked');
            selectedRecords = {};
            
            checkboxes.forEach(cb => {
                selectedRecords[cb.dataset.id] = {
                    id: cb.dataset.id,
                    theme: cb.dataset.theme,
                    dept: cb.dataset.dept
                };
                document.getElementById('row-' + cb.dataset.id).classList.add('selected-row');
            });
            
            document.querySelectorAll('.record-checkbox:not(:checked)').forEach(cb => {
                document.getElementById('row-' + cb.dataset.id).classList.remove('selected-row');
            });
            
            updateSelectionPanel();
        }
        
        function updateSelectionPanel() {
            const panel = document.getElementById('selectionPanel');
            const list = document.getElementById('selectionList');
            const count = Object.keys(selectedRecords).length;
            
            document.getElementById('panelCount').textContent = count;
            
            if (count > 0) {
                panel.classList.add('show');
                let html = '';
                for (const id in selectedRecords) {
                    const record = selectedRecords[id];
                    html += `
                        <div class="selection-item">
                            <div class="item-info">
                                <div class="item-name">${record.theme}</div>
                                <div class="item-dept">${record.dept}</div>
                            </div>
                            <button class="remove-btn" onclick="removeSelection('${id}')">x</button>
                        </div>
                    `;
                }
                list.innerHTML = html;
            } else {
                panel.classList.remove('show');
            }
        }
        
        function removeSelection(id) {
            delete selectedRecords[id];
            const checkbox = document.querySelector(`.record-checkbox[data-id="${id}"]`);
            if (checkbox) {
                checkbox.checked = false;
                document.getElementById('row-' + id).classList.remove('selected-row');
            }
            updateSelectionPanel();
        }
        
        function clearAllSelections() {
            selectedRecords = {};
            document.querySelectorAll('.record-checkbox').forEach(cb => {
                cb.checked = false;
            });
            document.querySelectorAll('tr.selected-row').forEach(row => {
                row.classList.remove('selected-row');
            });
            document.getElementById('selectAll').checked = false;
            updateSelectionPanel();
        }
        
        function confirmDeleteSingle(id) {
            deleteMode = 'single';
            deleteId = id;
            document.getElementById('deleteModalText').textContent = 'Are you sure you want to delete this record?';
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function confirmDeleteMultiple() {
            const count = Object.keys(selectedRecords).length;
            if (count === 0) return;
            
            deleteMode = 'multiple';
            document.getElementById('deleteModalText').textContent = `Are you sure you want to delete ${count} selected record(s)?`;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteMode = '';
            deleteId = null;
        }
        
        function executeDelete() {
            const form = document.getElementById('recordsForm');
            
            if (deleteMode === 'single') {
                document.getElementById('formAction').value = 'delete_single';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'record_id';
                input.value = deleteId;
                form.appendChild(input);
            } else if (deleteMode === 'multiple') {
                document.getElementById('formAction').value = 'delete_multiple';
            }
            
            form.submit();
        }
        
        function exportSelected() {
            const count = Object.keys(selectedRecords).length;
            if (count === 0) return;
            
            const form = document.getElementById('recordsForm');
            form.action = 'preview_export.php';
            form.submit();
        }
    </script>
</body>
</html>