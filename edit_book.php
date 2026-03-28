<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$bookId = intval($_GET['id'] ?? 0);
if ($bookId <= 0) {
    redirect('manage_books.php');
}

// Fetch book
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    flashMessage('admin', 'Book not found.', 'error');
    redirect('manage_books.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $author = sanitize($_POST['author'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $pages = intval($_POST['pages'] ?? 0);
    $language = sanitize($_POST['language'] ?? 'English');
    $yearPublished = intval($_POST['year_published'] ?? date('Y'));
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || empty($author) || $categoryId <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        $filePath = $book['file_path'];
        $fileSize = $book['file_size'];

        // Handle new file upload
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_DIR . 'books/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['book_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['book_file']['tmp_name'], $targetPath)) {
                // Delete old file if it exists
                $oldFile = __DIR__ . '/../../backend/' . $book['file_path'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
                $filePath = 'uploads/books/' . $fileName;
                $bytes = filesize($targetPath);
                if ($bytes >= 1048576) {
                    $fileSize = round($bytes / 1048576, 1) . ' MB';
                } else {
                    $fileSize = round($bytes / 1024, 1) . ' KB';
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, description=?, category_id=?, file_path=?, file_size=?, pages=?, language=?, year_published=?, is_active=? WHERE id=?");
        $stmt->execute([$title, $author, $description, $categoryId, $filePath, $fileSize, $pages, $language, $yearPublished, $isActive, $bookId]);

        flashMessage('admin', 'Book "' . $title . '" updated successfully!', 'success');
        redirect('manage_books.php');
    }
} else {
    // Pre-fill form
    $title = $book['title'];
    $author = $book['author'];
    $description = $book['description'];
    $categoryId = $book['category_id'];
    $pages = $book['pages'];
    $language = $book['language'];
    $yearPublished = $book['year_published'];
    $isActive = $book['is_active'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Admin - E-Library Management System</title>
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
            <div class="page-header">
                <a href="manage_books.php" style="color: var(--primary-light); font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Back to Books</a>
                <h1>✏️ Edit Book</h1>
                <p>Update book details and file</p>
            </div>

            <div class="auth-card" style="max-width: 700px; animation: none;">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Book Title *</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="author">Author *</label>
                        <input type="text" id="author" name="author" class="form-control" value="<?php echo htmlspecialchars($author); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Exam Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="pages">Pages</label>
                            <input type="number" id="pages" name="pages" class="form-control" value="<?php echo $pages; ?>">
                        </div>
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" name="language" class="form-control">
                                <option value="English" <?php echo ($language === 'English') ? 'selected' : ''; ?>>English</option>
                                <option value="Hindi" <?php echo ($language === 'Hindi') ? 'selected' : ''; ?>>Hindi</option>
                                <option value="Tamil" <?php echo ($language === 'Tamil') ? 'selected' : ''; ?>>Tamil</option>
                                <option value="Telugu" <?php echo ($language === 'Telugu') ? 'selected' : ''; ?>>Telugu</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year_published">Year</label>
                            <input type="number" id="year_published" name="year_published" class="form-control" value="<?php echo $yearPublished; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?php echo $isActive ? 'checked' : ''; ?> style="margin-right: 0.5rem;">
                            Book is active and visible to students
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Current File: <span style="color: var(--accent-green);"><?php echo htmlspecialchars(basename($book['file_path'])); ?></span> (<?php echo $book['file_size']; ?>)</label>
                        <div class="file-upload" id="fileUpload">
                            <input type="file" name="book_file" accept=".pdf,.epub,.doc,.docx" id="bookFile">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Upload new file to replace (optional)</p>
                            <div class="file-name" id="fileName" style="display: none;"></div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="manage_books.php" class="btn btn-outline btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('bookFile').addEventListener('change', function(e) {
            const fileName = document.getElementById('fileName');
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                fileName.style.display = 'block';
            }
        });
    </script>
</body>
</html>
