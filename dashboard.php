<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get current module (default to 'email')
$current_module = isset($_GET['module']) ? $_GET['module'] : 'email';

// Get statistics based on module
$stats = [];
$recent_records = [];

if ($current_module == 'email') {
    // Email Activation stats
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records");
    $stats['total'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Active'");
    $stats['active'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Deactivated'");
    $stats['deactivated'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['count'];

    // Get recent records
    $stmt = $pdo->query("SELECT * FROM email_records ORDER BY created_at DESC LIMIT 10");
    $recent_records = $stmt->fetchAll();
    
} elseif ($current_module == 'workspace') {
    // Google Workspace License stats
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM workspace_license_records");
    $stats['total'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM workspace_license_records WHERE employment_status = 'Full-time'");
    $stats['fulltime'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM workspace_license_records WHERE employment_status = 'Part-time'");
    $stats['parttime'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM workspace_license_records WHERE DATE(created_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['count'];

    // Get recent records
    $stmt = $pdo->query("SELECT * FROM workspace_license_records ORDER BY created_at DESC LIMIT 10");
    $recent_records = $stmt->fetchAll();
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
        /* Module Tabs */
        .module-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .module-tab {
            padding: 20px 25px;
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
            min-width: 160px;
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
        
        .module-tab .tab-icon {
            font-size: 28px;
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

        .welcome-card {
            background: linear-gradient(135deg, #1a365d, #2b6cb0);
            color: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 25px;
        }
        
        .welcome-card h2 {
            margin-bottom: 5px;
            font-size: 24px;
        }
        
        .welcome-card p {
            opacity: 0.9;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <img src="images/tech-ed-logo.png" alt="EdTech Logo" class="header-logo">
            <div>
                <h1>Educational Technology Center</h1>
                <div class="header-subtitle">Liceo de Cagayan University</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Message -->
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Role: <?php echo $_SESSION['role'] ?? 'Staff'; ?></p>
        </div>

        <!-- Module Tabs -->
        <div class="module-tabs">
            <a href="?module=email" class="module-tab <?php echo $current_module == 'email' ? 'active' : ''; ?>">
                <span class="tab-icon"></span>
                <span class="tab-label">Email Account<br>Activation/Deactivation</span>
            </a>
            <a href="?module=workspace" class="module-tab <?php echo $current_module == 'workspace' ? 'active' : ''; ?>">
                <span class="tab-icon"></span>
                <span class="tab-label">Google Workspace<br>Account License</span>
            </a>
            <div class="module-tab coming-soon">
                <span class="tab-icon"></span>
                <span class="tab-label">Module 3</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </div>
            <div class="module-tab coming-soon">
                <span class="tab-icon"></span>
                <span class="tab-label">Module 4</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </div>
            <div class="module-tab coming-soon">
                <span class="tab-icon"></span>
                <span class="tab-label">Module 5</span>
                <span class="coming-soon-badge">Coming Soon</span>
            </div>
        </div>

        <?php if ($current_module == 'email'): ?>
        <!-- ===== EMAIL ACTIVATION MODULE ===== -->
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active']; ?></div>
                <div class="stat-label">Active Accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['deactivated']; ?></div>
                <div class="stat-label">Deactivated</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Added Today</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions - Email Activation</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="add_record.php" class="btn btn-primary">+ Add New Record</a>
                <a href="records.php" class="btn btn-secondary">View All Records</a>
                <a href="import.php" class="btn btn-secondary">Import from CSV</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Email Records</h3>
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
                            <td><?php echo htmlspecialchars($record['college_department']); ?></td>
                            <td><?php echo htmlspecialchars($record['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $record['account_status'] == 'Active' ? 'approved' : 'rejected'; ?>">
                                    <?php echo $record['account_status'] == 'Active' ? 'Activated' : 'Deactivated'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="records.php">View All Records →</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records yet. <a href="add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php elseif ($current_module == 'workspace'): ?>
        <!-- ===== GOOGLE WORKSPACE LICENSE MODULE ===== -->
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['fulltime']; ?></div>
                <div class="stat-label">Full-time</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['parttime']; ?></div>
                <div class="stat-label">Part-time</div>
            </div>
            <div class="stat-card">
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
                                <span class="badge badge-<?php echo $record['employment_status'] == 'Full-time' ? 'approved' : 'processing'; ?>">
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
                <a href="workspace/records.php">View All Records →</a>
            </p>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No records yet. <a href="workspace/add_record.php">Add your first record!</a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>