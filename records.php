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

// Get filter values
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Check for errors
$error = '';
if (isset($_GET['error']) && $_GET['error'] == 'no_selection') {
    $error = 'Please select at least one record to export.';
}

// Build query
$sql = "SELECT * FROM email_records WHERE 1=1";
$params = [];

if (!empty($filter_department)) {
    // Find the acronym for this department
    $acronym = array_search($filter_department, $department_mapping);
    
    if ($acronym !== false) {
        // Search for both full name AND acronym
        $sql .= " AND (college_department = ? OR college_department = ?)";
        $params[] = $filter_department;
        $params[] = $acronym;
    } else {
        // Just search for the selected value
        $sql .= " AND college_department = ?";
        $params[] = $filter_department;
    }
}

if (!empty($filter_status)) {
    $sql .= " AND account_status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $sql .= " AND (last_name LIKE ? OR first_name LIKE ? OR email LIKE ? OR college_department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get departments for filter dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

// Get total counts
$total_records = count($records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Records - LDCU Email System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .export-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .export-bar label {
            font-weight: 600;
        }
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        .checkbox-cell input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .select-count {
            background: #C41E3A;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        .dept-badge {
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #495057;
        }
    </style>
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
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">All Email Records (<?php echo $total_records; ?> records)</h3>
                <a href="add_record.php" class="btn btn-primary">+ Add New Record</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
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
                    <option value="Active" <?php echo $filter_status == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Deactivated" <?php echo $filter_status == 'Deactivated' ? 'selected' : ''; ?>>Deactivated</option>
                </select>
                
                <input type="text" name="search" placeholder="Search name, email, or department..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="records.php" class="btn btn-secondary">Reset</a>
            </form>
            
            <?php if (count($records) > 0): ?>
            
            <!-- Export Form -->
            <form method="POST" action="export_word.php" id="exportForm">
                <div class="export-bar">
                    <label>Export Selected:</label>
                    <select name="request_type" class="form-control" style="width: auto;">
                        <option value="New">New Email</option>
                        <option value="Activate">Activate</option>
                    </select>
                    <button type="submit" class="btn btn-success">📄 Export to Word</button>
                    <span class="select-count"><span id="selectedCount">0</span> selected (max 5)</span>
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
                                <th>Password</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1; 
                            foreach ($records as $record): 
                                // Get full department name if acronym
                                $dept_display = $record['college_department'];
                                $dept_full = isset($department_mapping[$dept_display]) ? $department_mapping[$dept_display] : '';
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
                                <td><?php echo htmlspecialchars($record['password']); ?></td>
                                <td><?php echo $record['record_date'] ? date('M d, Y', strtotime($record['record_date'])) : '-'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $record['account_status'] == 'Active' ? 'approved' : 'rejected'; ?>">
                                        <?php echo $record['account_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit_record.php?id=<?php echo $record['record_id']; ?>" 
                                           class="action-btn edit">Edit</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records found. <a href="add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Select All checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.record-checkbox');
            const maxSelect = 5;
            let count = 0;
            
            checkboxes.forEach(checkbox => {
                if (this.checked && count < maxSelect) {
                    checkbox.checked = true;
                    count++;
                } else if (!this.checked) {
                    checkbox.checked = false;
                }
            });
            
            updateCount();
        });

        // Individual checkbox change
        document.querySelectorAll('.record-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = document.querySelectorAll('.record-checkbox:checked');
                
                // Limit to 5
                if (checked.length > 5) {
                    this.checked = false;
                    alert('You can only select up to 5 records at a time (template has 5 rows).');
                }
                
                updateCount();
            });
        });

        // Update selected count
        function updateCount() {
            const checked = document.querySelectorAll('.record-checkbox:checked');
            document.getElementById('selectedCount').textContent = checked.length;
        }
    </script>
</body>
</html>