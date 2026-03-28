<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('../login.php');
}

$bookId = intval($_GET['id'] ?? 0);

if ($bookId <= 0) {
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("SELECT b.*, c.name as category_name, c.slug as category_slug FROM books b JOIN categories c ON b.category_id = c.id WHERE b.id = ? AND b.is_active = 1");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    redirect('dashboard.php');
}

// Get related books
$relatedStmt = $pdo->prepare("SELECT b.*, c.name as category_name, c.slug as category_slug FROM books b JOIN categories c ON b.category_id = c.id WHERE b.category_id = ? AND b.id != ? AND b.is_active = 1 ORDER BY b.downloads DESC LIMIT 4");
$relatedStmt->execute([$book['category_id'], $bookId]);
$relatedBooks = $relatedStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - E-Library Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="nav-brand">
                <span class="brand-icon"><i class="fas fa-book-open"></i></span>
                E-Library Management System
            </a>
            <div class="nav-actions">
                <a href="dashboard.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="../logout.php" class="btn btn-ghost btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    <li><a href="my_downloads.php"><i class="fas fa-download"></i> My Downloads</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content book-detail-page">
            <div class="page-header">
                <a href="dashboard.php?category=<?php echo $book['category_slug']; ?>" style="color: var(--primary-light); font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back to <?php echo htmlspecialchars($book['category_name']); ?> Books
                </a>
            </div>

            <div class="book-detail-card">
                <div class="book-detail-header">
                    <div class="book-detail-cover book-cover <?php echo $book['category_slug']; ?>">
                        <div class="book-cover-content">
                            <i class="fas fa-book-open" style="color: rgba(255,255,255,0.3); font-size: 3rem;"></i>
                            <br><br>
                            <span class="book-category-label"><?php echo htmlspecialchars($book['category_name']); ?></span>
                        </div>
                    </div>
                    <div class="book-detail-info">
                        <span class="badge badge-<?php echo ($book['category_slug'] === 'upsc') ? 'purple' : (($book['category_slug'] === 'ssc') ? 'pink' : (($book['category_slug'] === 'rrb') ? 'green' : 'orange')); ?>"><?php echo htmlspecialchars($book['category_name']); ?></span>
                        <h1 style="margin-top: 0.75rem;"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <div class="author">by <?php echo htmlspecialchars($book['author']); ?></div>
                        
                        <div class="book-detail-meta">
                            <div class="meta-item">
                                <span class="meta-label">Pages</span>
                                <span class="meta-value"><?php echo $book['pages']; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">File Size</span>
                                <span class="meta-value"><?php echo $book['file_size']; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Language</span>
                                <span class="meta-value"><?php echo htmlspecialchars($book['language']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Year</span>
                                <span class="meta-value"><?php echo $book['year_published']; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Downloads</span>
                                <span class="meta-value"><?php echo formatNumber($book['downloads']); ?></span>
                            </div>
                        </div>

                        <a href="download.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-download"></i> Download Book
                        </a>
                    </div>
                </div>
                <div class="book-description">
                    <h3>About this Book</h3>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
            </div>

            <?php if (!empty($relatedBooks)): ?>
            <div style="margin-top: 3rem;">
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; margin-bottom: 1.25rem;">More <?php echo htmlspecialchars($book['category_name']); ?> Books</h2>
                <div class="books-grid">
                    <?php foreach ($relatedBooks as $rb): ?>
                    <div class="book-card">
                        <div class="book-cover <?php echo $rb['category_slug']; ?>">
                            <div class="book-cover-content">
                                <i class="fas fa-book-open" style="color: rgba(255,255,255,0.3);"></i>
                                <br>
                                <span class="book-category-label"><?php echo htmlspecialchars($rb['category_name']); ?></span>
                            </div>
                        </div>
                        <div class="book-info">
                            <h3><?php echo htmlspecialchars($rb['title']); ?></h3>
                            <div class="book-author">by <?php echo htmlspecialchars($rb['author']); ?></div>
                            <div class="book-meta">
                                <span><i class="fas fa-file-lines"></i> <?php echo $rb['pages']; ?> pages</span>
                                <span><i class="fas fa-download"></i> <?php echo formatNumber($rb['downloads']); ?></span>
                            </div>
                            <div class="book-actions">
                                <a href="book_detail.php?id=<?php echo $rb['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Details</a>
                                <a href="download.php?id=<?php echo $rb['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
