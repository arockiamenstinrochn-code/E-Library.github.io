<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Fetch all customers
$users = $pdo->query("
    SELECT u.*, 
           COUNT(dh.id) as download_count,
           MAX(dh.downloaded_at) as last_download
    FROM users u 
    LEFT JOIN download_history dh ON u.id = dh.user_id 
    WHERE u.role = 'customer' 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin - E-Library Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-brand">
                <span class="brand-icon"><i class="fas fa-book-open"></i></span>
                E-Library Management System
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_books.php">Books</a></li>
                <li><a href="add_book.php">Add Book</a></li>
            </ul>
            <div class="nav-actions">
                <a href="../logout.php" class="btn btn-ghost btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-user">
                    <div class="user-avatar" style="background: linear-gradient(135deg, #FF6584, #FF8E9E);"><?php echo getInitials($_SESSION['user_name']); ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Admin Panel</div>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php"><i class="fas fa-gauge-high"></i> Dashboard</a></li>
                    <li><a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                    <li><a href="add_book.php"><i class="fas fa-plus-circle"></i> Add New Book</a></li>
                    <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>👥 Registered Students</h1>
                <p>View all registered students on the platform</p>
            </div>

            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No students yet</h3>
                    <p>Students will appear here once they register.</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <div class="table-header">
                    <h3>All Students (<?php echo count($users); ?>)</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Downloads</th>
                            <th>Last Active</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.75rem;"><?php echo getInitials($user['full_name']); ?></div>
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                </div>
                            </td>
                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge badge-purple"><?php echo $user['download_count']; ?> downloads</span></td>
                            <td style="color: var(--text-secondary);">
                                <?php echo $user['last_download'] ? date('M d, Y', strtotime($user['last_download'])) : 'Never'; ?>
                            </td>
                            <td style="color: var(--text-secondary);"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
