<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$deleteId]);
    flashMessage('admin', 'Book deleted successfully!', 'success');
    redirect('manage_books.php');
}

// Handle toggle active status
if (isset($_GET['toggle'])) {
    $toggleId = intval($_GET['toggle']);
    $stmt = $pdo->prepare("UPDATE books SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$toggleId]);
    flashMessage('admin', 'Book status updated successfully!', 'success');
    redirect('manage_books.php');
}

// Get filters
$categoryFilter = $_GET['category'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Build query
$sql = "SELECT b.*, c.name as category_name, c.slug as category_slug FROM books b JOIN categories c ON b.category_id = c.id WHERE 1=1";
$params = [];

if ($categoryFilter !== 'all') {
    $sql .= " AND c.slug = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Admin - E-Library Management System</title>
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
                <li><a href="manage_books.php" class="active">Books</a></li>
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
                    <li><a href="manage_books.php" class="active"><i class="fas fa-book"></i> Manage Books</a></li>
                    <li><a href="add_book.php"><i class="fas fa-plus-circle"></i> Add New Book</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <div class="flex-between mb-3">
                <div class="page-header" style="margin-bottom: 0;">
                    <h1>📖 Manage Books</h1>
                    <p>Add, edit, or remove books from the library</p>
                </div>
                <a href="add_book.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Book
                </a>
            </div>

            <?php if ($flash = flashMessage('admin')): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <form method="GET" action="" style="width: 100%;">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                        <input type="text" name="search" placeholder="Search books..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </form>
                </div>
                <div class="filter-group">
                    <a href="manage_books.php?category=all" class="filter-chip <?php echo $categoryFilter === 'all' ? 'active' : ''; ?>">All</a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="manage_books.php?category=<?php echo $cat['slug']; ?>" class="filter-chip <?php echo $categoryFilter === $cat['slug'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Books Table -->
            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No books found</h3>
                    <p>Add your first book to get started.</p>
                    <a href="add_book.php" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> Add Book</a>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <div class="table-header">
                    <h3>All Books (<?php echo count($books); ?>)</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Downloads</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): 
                            $badgeClass = ($book['category_slug'] === 'upsc') ? 'badge-purple' : (($book['category_slug'] === 'ssc') ? 'badge-pink' : (($book['category_slug'] === 'rrb') ? 'badge-green' : 'badge-orange'));
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $book['file_size']; ?> · <?php echo $book['pages']; ?> pages</div>
                                </div>
                            </td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($book['category_name']); ?></span></td>
                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><strong><?php echo formatNumber($book['downloads']); ?></strong></td>
                            <td>
                                <?php if ($book['is_active']): ?>
                                    <span class="badge badge-green">Active</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(239,68,68,0.15); color: #F87171;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="manage_books.php?toggle=<?php echo $book['id']; ?>" class="btn btn-ghost btn-sm" title="Toggle Status">
                                        <i class="fas fa-<?php echo $book['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                    </a>
                                    <a href="manage_books.php?delete=<?php echo $book['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this book?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
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
