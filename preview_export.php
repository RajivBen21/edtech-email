<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if records are selected
if (!isset($_POST['selected_records']) || empty($_POST['selected_records'])) {
    header('Location: records.php?error=no_selection');
    exit;
}

$selected_ids = $_POST['selected_records'];
$request_type = isset($_POST['request_type']) ? $_POST['request_type'] : 'New';

// Get selected records from database
$placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM email_records WHERE record_id IN ($placeholders)");
$stmt->execute($selected_ids);
$records = $stmt->fetchAll();

$record_count = count($records);
$records_per_page = 5;
$total_pages = ceil($record_count / $records_per_page);

// Helper function to format datetime
function formatDateTime($datetime) {
    if (empty($datetime)) return '<span class="not-set">Not set</span>';
    return date('M d, Y h:i A', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Preview - LDCU Email System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .preview-info {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2b6cb0;
        }
        .preview-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .preview-info strong {
            color: #1a365d;
        }
        .preview-section {
            margin-bottom: 30px;
        }
        .preview-section h4 {
            color: #1a365d;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2b6cb0;
        }
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .preview-table th,
        .preview-table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .preview-table th {
            background: #2b6cb0;
            color: white;
            font-weight: 600;
        }
        .preview-table tbody tr:nth-child(even) {
            background: #f7fafc;
        }
        .preview-table tbody tr:hover {
            background: #ebf8ff;
        }
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .record-count {
            display: inline-block;
            background: #2b6cb0;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .request-type-badge {
            display: inline-block;
            background: #38a169;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        .page-info {
            display: inline-block;
            background: #718096;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .not-set {
            color: #a0aec0;
            font-style: italic;
        }
        .page-divider {
            border-top: 3px dashed #2b6cb0;
            margin: 30px 0;
            position: relative;
        }
        .page-divider-label {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 0 15px;
            color: #2b6cb0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
<div class="header">
       <a href="dashboard.php" class="header-left" style="text-decoration: none; color: inherit; cursor: pointer;">
        <a href="dashboard.php" style="cursor: pointer;"><img src="images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo"></a>
        <div>
            <h1>Educational Technology Center</h1>
            <div class="header-subtitle">Liceo de Cagayan University</div>
        </div>
    </a>
        <nav class="nav-menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="records.php">All Records</a>
    </nav>
    <div style="position: relative;">
        <button class="menu-btn" onclick="toggleMenu()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 12px; border-radius: 5px; cursor: pointer; margin-right: 20px;">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
        </button>
        <div id="menuDropdown" style="display: none; position: absolute; top: 50px; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-width: 200px; z-index: 1000;">
            <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1a365d; font-size: 13px;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
            <a href="change_password.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #2d3748; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f7fafc'" onmouseout="this.style.backgroundColor='transparent'">
                Change Password
            </a>
            <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #e53e3e; text-decoration: none; border-top: 1px solid #e2e8f0; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#fed7d7'" onmouseout="this.style.backgroundColor='transparent'">
                Logout
            </a>
        </div>
    </div>
</div>

    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Export Preview 
                    <span class="record-count"><?php echo $record_count; ?> record<?php echo $record_count > 1 ? 's' : ''; ?> selected</span>
                    <?php if ($total_pages > 1): ?>
                        <span class="page-info"><?php echo $total_pages; ?> page<?php echo $total_pages > 1 ? 's' : ''; ?> will be generated</span>
                    <?php endif; ?>
                </h3>
            </div>
            
            <div class="preview-info">
                <p><strong>Request Type:</strong> <span class="request-type-badge"><?php echo $request_type == 'New' ? 'New Email' : 'Activate Account'; ?></span></p>
                <p><strong>Export Date:</strong> <?php echo date('F d, Y'); ?></p>
                <?php if ($total_pages > 1): ?>
                    <p><strong>Note:</strong> Your export will contain <?php echo $total_pages; ?> pages (5 records per page).</p>
                <?php endif; ?>
            </div>
            
            <?php for ($page = 0; $page < $total_pages; $page++): ?>
                <?php 
                $page_records = array_slice($records, $page * $records_per_page, $records_per_page);
                $start_num = $page * $records_per_page;
                ?>
                
                <?php if ($page > 0): ?>
                    <div class="page-divider">
                        <span class="page-divider-label">Page <?php echo $page + 1; ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Table 1: Names and Departments -->
                <div class="preview-section">
                    <h4> Table 1: Request Form (Names & Departments) <?php if ($total_pages > 1): ?>- Page <?php echo $page + 1; ?><?php endif; ?></h4>
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>College/Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($page_records as $index => $record): ?>
                            <tr>
                                <td><?php echo $start_num + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['middle_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Table 2: Emails, Passwords, and Timestamps -->
                <div class="preview-section">
                    <h4> Table 2: Monitoring Form (Emails, Passwords & Status) <?php if ($total_pages > 1): ?>- Page <?php echo $page + 1; ?><?php endif; ?></h4>
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email Address</th>
                                <th>Password</th>
                                <th>Received</th>
                                <th>Processed</th>
                                <th>Accomplished</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($page_records as $index => $record): ?>
                            <tr>
                                <td><?php echo $start_num + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($record['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($record['password'] ?? ''); ?></td>
                                <td><?php echo formatDateTime($record['datetime_received'] ?? null); ?></td>
                                <td><?php echo formatDateTime($record['datetime_processed'] ?? null); ?></td>
                                <td><?php echo formatDateTime($record['datetime_accomplished'] ?? null); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endfor; ?>
            
            <!-- Export Button -->
            <form method="POST" action="export_word.php">
                <?php foreach ($selected_ids as $id): ?>
                    <input type="hidden" name="selected_records[]" value="<?php echo $id; ?>">
                <?php endforeach; ?>
                <input type="hidden" name="request_type" value="<?php echo htmlspecialchars($request_type); ?>">
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary"> Download Word Document</button>
                    <a href="records.php" class="btn btn-secondary">← Back to Records</a>
                </div>
            </form>
        </div>
    </div>
        <script>
        function toggleMenu() {
            const menu = document.getElementById('menuDropdown');
            if (menu.style.display === 'none') {
                menu.style.display = 'block';
            } else {
                menu.style.display = 'none';
            }
        }

        document.addEventListener('click', function(e) {
            const menu = document.getElementById('menuDropdown');
            const btn = document.querySelector('.menu-btn');
            if (menu && !menu.contains(e.target) && !btn.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    </script>
</body>
</html>