<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

// Fetch user's download history
$stmt = $pdo->prepare("
    SELECT dh.*, b.title, b.author, b.file_size, b.pages, c.name as category_name, c.slug as category_slug 
    FROM download_history dh 
    JOIN books b ON dh.book_id = b.id 
    JOIN categories c ON b.category_id = c.id 
    WHERE dh.user_id = ? 
    ORDER BY dh.downloaded_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$downloads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Downloads - E-Library Management System</title>
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
                <li><a href="dashboard.php">Browse</a></li>
                <li><a href="my_downloads.php" class="active">My Downloads</a></li>
            </ul>
            <div class="nav-actions">
                <span style="color: var(--text-secondary); font-size: 0.9rem;">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
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
                    <div class="user-avatar"><?php echo getInitials($_SESSION['user_name']); ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        <div class="user-role">Student</div>
                    </div>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Navigation</div>
                <ul class="sidebar-nav">
                    <li><a href="dashboard.php"><i class="fas fa-compass"></i> Browse Books</a></li>
                    <li><a href="my_downloads.php" class="active"><i class="fas fa-download"></i> My Downloads</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>📥 My Downloads</h1>
                <p>Track all the books you've downloaded</p>
            </div>

            <?php if (empty($downloads)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No downloads yet</h3>
                    <p>Start exploring our library and download books for your exam preparation.</p>
                    <a href="dashboard.php" class="btn btn-primary mt-2">
                        <i class="fas fa-compass"></i> Browse Books
                    </a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <div class="table-header">
                        <h3><i class="fas fa-clock-rotate-left"></i> Download History (<?php echo count($downloads); ?> records)</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Category</th>
                                <th>Author</th>
                                <th>Size</th>
                                <th>Downloaded On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($downloads as $dl): 
                                $badgeClass = ($dl['category_slug'] === 'upsc') ? 'badge-purple' : (($dl['category_slug'] === 'ssc') ? 'badge-pink' : (($dl['category_slug'] === 'rrb') ? 'badge-green' : 'badge-orange'));
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($dl['title']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($dl['category_name']); ?></span>
                                </td>
                                <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($dl['author']); ?></td>
                                <td style="color: var(--text-secondary);"><?php echo $dl['file_size']; ?></td>
                                <td style="color: var(--text-secondary);"><?php echo date('M d, Y h:i A', strtotime($dl['downloaded_at'])); ?></td>
                                <td>
                                    <a href="download.php?id=<?php echo $dl['book_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i> Re-download
                                    </a>
                                </td>
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
