<?php
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$current_module = isset($_GET['module']) ? $_GET['module'] : 'activation';

$stats = [];
$recent_records = [];

if ($current_module == 'activation') {
    // Filter only Active records for Activation module
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Active'");
    $stats['total'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Active' AND DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT * FROM email_records WHERE account_status = 'Active' ORDER BY created_at DESC LIMIT 10");
    $recent_records = $stmt->fetchAll();
    
} elseif ($current_module == 'deactivation') {
    // Filter only Deactivated records for Deactivation module
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Deactivated'");
    $stats['total'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Deactivated' AND DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT * FROM email_records WHERE account_status = 'Deactivated' ORDER BY created_at DESC LIMIT 10");
    $recent_records = $stmt->fetchAll();
    
} elseif ($current_module == 'workspace') {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM workspace_license_records");
    $stats['total'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM workspace_license_records WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT * FROM workspace_license_records ORDER BY created_at DESC LIMIT 10");
    $recent_records = $stmt->fetchAll();
    
} elseif ($current_module == 'retrieval') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM retrieval_records");
        $stats['total'] = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM retrieval_records WHERE request_type = 'Account'");
        $stats['account'] = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM retrieval_records WHERE request_type = 'Password'");
        $stats['password'] = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM retrieval_records WHERE DATE(created_at) = CURDATE()");
        $stats['today'] = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT * FROM retrieval_records ORDER BY created_at DESC LIMIT 10");
        $recent_records = $stmt->fetchAll();
    } catch (Exception $e) {
        $stats['total'] = 0;
        $stats['account'] = 0;
        $stats['password'] = 0;
        $stats['today'] = 0;
        $recent_records = [];
    }
    
} elseif ($current_module == 'training') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_records");
        $stats['total'] = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_records WHERE DATE(created_at) = CURDATE()");
        $stats['today'] = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT * FROM training_records ORDER BY created_at DESC LIMIT 10");
        $recent_records = $stmt->fetchAll();
    } catch (Exception $e) {
        $stats['total'] = 0;
        $stats['today'] = 0;
        $recent_records = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LDCU EdTech System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .module-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .module-tab {
            flex: 1;
            padding: 20px 15px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #4a5568;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .module-tab:hover {
            border-color: #2b6cb0;
            background: #ebf8ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.15);
        }
        
        .module-tab.active {
            border-color: #2b6cb0;
            background: linear-gradient(135deg, #1a365d, #2b6cb0);
            color: white;
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }
        
        .module-tab .tab-label {
            font-size: 13px;
            line-height: 1.3;
        }
        
        .module-tab.coming-soon {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .coming-soon-badge {
            font-size: 9px;
            background: #718096;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .module-tabs {
                flex-wrap: wrap;
            }
            .module-tab {
                flex: 1 1 calc(50% - 10px);
                min-width: 140px;
            }
        }
    </style>
</head>
<body>
        <div class="header">
        <div class="header-left">
            <a href="dashboard.php" style="cursor: pointer;"><img src="images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo"></a>
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
                <nav class="nav-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="about.php">About Us</a>
            <button class="menu-btn" onclick="toggleMenu()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 12px; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
            </button>
            <div id="menuDropdown" style="display: none; position: absolute; top: 60px; right: 30px; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-width: 200px; z-index: 1000;">
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
        </nav>
    </div>
                                    <nav class="nav-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="about.php">About Us</a>
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
                    🔐 Change Password
                </a>
                <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #e53e3e; text-decoration: none; border-top: 1px solid #e2e8f0; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#fed7d7'" onmouseout="this.style.backgroundColor='transparent'">
                    🚪 Logout
                </a>
            </div>
        </div>
    </div>

        <div class="container" style="margin-top: 0 !important;">
        <div class="card" style="margin-bottom: 25px; margin-top: 0 !important;">
            <h2 style="margin-top: 0;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Role: <?php echo $_SESSION['role'] ?? 'Staff'; ?></p>
        </div>

        <div class="module-tabs">
            <a href="?module=activation" class="module-tab <?php echo $current_module == 'activation' ? 'active' : ''; ?>">
                <span class="tab-label">Email Account<br>Activation</span>
            </a>
            <a href="?module=deactivation" class="module-tab <?php echo $current_module == 'deactivation' ? 'active' : ''; ?>">
                <span class="tab-label">Email Account<br>Deactivation</span>
            </a>
            <a href="?module=workspace" class="module-tab <?php echo $current_module == 'workspace' ? 'active' : ''; ?>">
                <span class="tab-label">Google Workspace<br>Account License</span>
            </a>
            <a href="?module=retrieval" class="module-tab <?php echo $current_module == 'retrieval' ? 'active' : ''; ?>">
                <span class="tab-label">Google Workspace<br>Account/Password Retrieval</span>
            </a>
            <a href="?module=training" class="module-tab <?php echo $current_module == 'training' ? 'active' : ''; ?>">
                <span class="tab-label">Request for<br>Training/Seminar/Workshop</span>
            </a>
            
        </div>

        <?php if ($current_module == 'activation'): ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Active Accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Activated Today</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions - Email Activation</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="add_record.php?type=activation" class="btn btn-primary">+ Add New Activation</a>
                <a href="records.php?status=Active" class="btn btn-secondary">View All Activations</a>
                <a href="import.php" class="btn btn-secondary">Import from CSV</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activation Records</h3>
            </div>
            
            <?php if (count($recent_records) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Request Type</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                                <?php echo htmlspecialchars($record['first_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                            <td><?php echo htmlspecialchars($record['email']); ?></td>
                            <td>
                                <span class="badge badge-approved">
                                    <?php echo htmlspecialchars($record['request_type']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="records.php?status=Active">View All Activation Records</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No activation records yet. <a href="add_record.php?type=activation">Add your first activation!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php elseif ($current_module == 'deactivation'): ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Deactivated Accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Deactivated Today</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions - Email Deactivation</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="add_record.php?type=deactivation" class="btn btn-primary">+ Add New Deactivation</a>
                <a href="records.php?status=Deactivated" class="btn btn-secondary">View All Deactivations</a>
                <a href="import.php" class="btn btn-secondary">Import from CSV</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Deactivation Records</h3>
            </div>
            
            <?php if (count($recent_records) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Request Type</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                                <?php echo htmlspecialchars($record['first_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                            <td><?php echo htmlspecialchars($record['email']); ?></td>
                            <td>
                                <span class="badge badge-rejected">
                                    <?php echo htmlspecialchars($record['request_type']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="records.php?status=Deactivated">View All Deactivation Records</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No deactivation records yet. <a href="add_record.php?type=deactivation">Add your first deactivation!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php elseif ($current_module == 'workspace'): ?>
        
        <div class="stats-grid" style="display: flex; gap: 20px;">
            <div class="stat-card" style="flex: 1;">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card" style="flex: 1;">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Added Today</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions - Google Workspace License</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="workspace/add_record.php" class="btn btn-primary">+ Add New Record</a>
                <a href="workspace/records.php" class="btn btn-secondary">View All Records</a>
                <a href="workspace/import.php" class="btn btn-secondary">Import from CSV</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Workspace License Records</h3>
            </div>
            
            <?php if (count($recent_records) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Liceo Email</th>
                            <th>Status</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                                <?php echo htmlspecialchars($record['first_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['liceo_email']); ?></td>
                            <td>
                                <?php 
                                $statusClass = 'approved';
                                if ($record['employment_status'] == 'Part-time') $statusClass = 'processing';
                                if ($record['employment_status'] == 'Probationary') $statusClass = 'pending';
                                if ($record['employment_status'] == 'Full-time Probationary') $statusClass = 'pending';
                                ?>
                                <span class="badge badge-<?php echo $statusClass; ?>">
                                    <?php echo $record['employment_status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="workspace/records.php">View All Records</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records yet. <a href="workspace/add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php elseif ($current_module == 'retrieval'): ?>
        
        <div class="stats-grid" style="display: flex; gap: 20px;">
            <div class="stat-card" style="flex: 1;">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card" style="flex: 1;">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Added Today</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions - Account/Password Retrieval</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="retrieval/add_record.php" class="btn btn-primary">+ Add New Record</a>
                <a href="retrieval/records.php" class="btn btn-secondary">View All Records</a>
                <a href="retrieval/import.php" class="btn btn-secondary">Import from CSV</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Retrieval Records</h3>
            </div>
            
            <?php if (count($recent_records) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Student/Employee ID</th>
                            <th>Request Type</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['last_name']); ?></strong>, 
                                <?php echo htmlspecialchars($record['first_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['student_employee_id']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $record['request_type'] == 'Account' ? 'approved' : 'processing'; ?>">
                                    <?php echo $record['request_type']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="retrieval/records.php">View All Records</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records yet. <a href="retrieval/add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php elseif ($current_module == 'training'): ?>
        
        <div class="stats-grid" style="display: flex; gap: 20px;">
            <div class="stat-card" style="flex: 1;">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card" style="flex: 1;">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Added Today</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions - Training/Seminar/Workshop</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="training/add_record.php" class="btn btn-primary">+ Add New Record</a>
                <a href="training/records.php" class="btn btn-secondary">View All Records</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Training/Seminar/Workshop Records</h3>
            </div>
            
            <?php if (count($recent_records) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Theme/Topic</th>
                            <th>Activity Type</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach ($recent_records as $record): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                            <td><?php echo htmlspecialchars(substr($record['theme_topic'], 0, 50)) . (strlen($record['theme_topic']) > 50 ? '...' : ''); ?></td>
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
                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="training/records.php">View All Records</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records yet. <a href="training/add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
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
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    </script>
</body>
</html>