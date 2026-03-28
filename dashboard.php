<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Stats
$totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalDownloads = $pdo->query("SELECT COALESCE(SUM(downloads), 0) FROM books")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Recent downloads
$recentDownloads = $pdo->query("
    SELECT dh.*, u.full_name, b.title as book_title, c.name as category_name 
    FROM download_history dh 
    JOIN users u ON dh.user_id = u.id 
    JOIN books b ON dh.book_id = b.id 
    JOIN categories c ON b.category_id = c.id 
    ORDER BY dh.downloaded_at DESC LIMIT 10
")->fetchAll();

// Category stats
$categoryStats = $pdo->query("
    SELECT c.name, c.slug, COUNT(b.id) as book_count, COALESCE(SUM(b.downloads), 0) as total_downloads 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    ORDER BY total_downloads DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Library Management System</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_books.php">Books</a></li>
                <li><a href="add_book.php">Add Book</a></li>
            </ul>
            <div class="nav-actions">
                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                    <i class="fas fa-user-shield"></i> Admin
                </span>
                <a href="../logout.php" class="btn btn-ghost btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-gauge-high"></i> Dashboard</a></li>
                    <li><a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                    <li><a href="add_book.php"><i class="fas fa-plus-circle"></i> Add New Book</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                </ul>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Quick Links</div>
                <ul class="sidebar-nav">
                    <li><a href="../index.php"><i class="fas fa-globe"></i> View Site</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>⚡ Admin Dashboard</h1>
                <p>Overview of your e-library system</p>
            </div>

            <?php if ($flash = flashMessage('admin')): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-book"></i></div>
                    <div class="stat-details">
                        <h3>Total Books</h3>
                        <div class="stat-value"><?php echo $totalBooks; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pink"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h3>Students</h3>
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-download"></i></div>
                    <div class="stat-details">
                        <h3>Total Downloads</h3>
                        <div class="stat-value"><?php echo formatNumber($totalDownloads); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-folder"></i></div>
                    <div class="stat-details">
                        <h3>Categories</h3>
                        <div class="stat-value"><?php echo $totalCategories; ?></div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="table-wrapper mb-3">
                <div class="table-header">
                    <h3><i class="fas fa-chart-bar"></i> Category Breakdown</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Books</th>
                            <th>Total Downloads</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryStats as $cs): 
                            $badgeClass = ($cs['slug'] === 'upsc') ? 'badge-purple' : (($cs['slug'] === 'ssc') ? 'badge-pink' : (($cs['slug'] === 'rrb') ? 'badge-green' : 'badge-orange'));
                        ?>
                        <tr>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($cs['name']); ?></span></td>
                            <td><?php echo $cs['book_count']; ?></td>
                            <td><?php echo formatNumber($cs['total_downloads']); ?></td>
                            <td><a href="manage_books.php?category=<?php echo $cs['slug']; ?>" class="btn btn-outline btn-sm">View Books</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Downloads -->
            <div class="table-wrapper">
                <div class="table-header">
                    <h3><i class="fas fa-clock-rotate-left"></i> Recent Downloads</h3>
                </div>
                <?php if (empty($recentDownloads)): ?>
                    <div class="empty-state" style="padding: 2rem;">
                        <p>No downloads yet.</p>
                    </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Book</th>
                            <th>Category</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDownloads as $rd): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.7rem;"><?php echo getInitials($rd['full_name']); ?></div>
                                    <?php echo htmlspecialchars($rd['full_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($rd['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($rd['category_name']); ?></td>
                            <td style="color: var(--text-secondary);"><?php echo date('M d, Y h:i A', strtotime($rd['downloaded_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
