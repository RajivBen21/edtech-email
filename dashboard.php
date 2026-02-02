<?php
require_once 'includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get statistics
$stats = [];

// Total records
$stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records");
$stats['total'] = $stmt->fetch()['count'];

// Activate accounts
$stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Active'");
$stats['activate'] = $stmt->fetch()['count'];

// Deactivated accounts
$stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE account_status = 'Deactivated'");
$stats['deactivated'] = $stmt->fetch()['count'];

// Today's records
$stmt = $pdo->query("SELECT COUNT(*) as count FROM email_records WHERE DATE(created_at) = CURDATE()");
$stats['today'] = $stmt->fetch()['count'];

// Get recent records
$stmt = $pdo->query("SELECT * FROM email_records ORDER BY created_at DESC LIMIT 10");
$recent_records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LDCU Email System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
        <a href="dashboard.php">Dashboard</a>
        <a href="records.php">All Records</a>
        <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
    </nav>
</div>

    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Message -->
        <div class="card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Role: <?php echo $_SESSION['role']; ?></p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['activate']; ?></div>
                <div class="stat-label">Activate Accounts</div>
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

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="add_record.php" class="btn btn-primary">+ Add New Record</a>
                <a href="records.php" class="btn btn-secondary">View All Records</a>
                <a href="import.php" class="btn btn-secondary">Import from CSV</a>
            </div>
        </div>

        <!-- Recent Records -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Records</h3>
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
                                    <?php echo $record['account_status'] == 'Active' ? 'Activated' : $record['account_status']; ?>
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
                    or <a href="import.php">Import from CSV</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>