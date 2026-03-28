<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
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

    if (empty($title) || empty($author) || $categoryId <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        $filePath = '';
        $fileSize = '';

        // Handle file upload
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_DIR . 'books/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['book_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['book_file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/books/' . $fileName;
                $bytes = filesize($targetPath);
                if ($bytes >= 1048576) {
                    $fileSize = round($bytes / 1048576, 1) . ' MB';
                } else {
                    $fileSize = round($bytes / 1024, 1) . ' KB';
                }
            } else {
                $error = 'Failed to upload file.';
            }
        } else {
            // No file uploaded - set a placeholder path
            $filePath = 'uploads/books/' . strtolower(str_replace(' ', '_', $title)) . '.pdf';
            $fileSize = 'N/A';
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, description, category_id, file_path, file_size, pages, language, year_published, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $author, $description, $categoryId, $filePath, $fileSize, $pages, $language, $yearPublished, $_SESSION['user_id']]);

            flashMessage('admin', 'Book "' . $title . '" added successfully!', 'success');
            redirect('manage_books.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - Admin - E-Library Management System</title>
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
                <li><a href="add_book.php" class="active">Add Book</a></li>
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
                    <li><a href="add_book.php" class="active"><i class="fas fa-plus-circle"></i> Add New Book</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>➕ Add New Book</h1>
                <p>Upload a new book to the library for students to download</p>
            </div>

            <div class="auth-card" style="max-width: 700px; animation: none;">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-circle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="addBookForm">
                    <div class="form-group">
                        <label for="title">Book Title *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Enter book title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="author">Author *</label>
                        <input type="text" id="author" name="author" class="form-control" placeholder="Enter author name" value="<?php echo htmlspecialchars($author ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Exam Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($categoryId) && $categoryId == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Brief description of the book..." rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="pages">Pages</label>
                            <input type="number" id="pages" name="pages" class="form-control" placeholder="0" value="<?php echo $pages ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" name="language" class="form-control">
                                <option value="English">English</option>
                                <option value="Hindi">Hindi</option>
                                <option value="Tamil">Tamil</option>
                                <option value="Telugu">Telugu</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year_published">Year</label>
                            <input type="number" id="year_published" name="year_published" class="form-control" placeholder="2025" value="<?php echo $yearPublished ?? date('Y'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Upload Book File (PDF)</label>
                        <div class="file-upload" id="fileUpload">
                            <input type="file" name="book_file" accept=".pdf,.epub,.doc,.docx" id="bookFile">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload or drag and drop</p>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">PDF, EPUB, DOC up to 50MB</p>
                            <div class="file-name" id="fileName" style="display: none;"></div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                            <i class="fas fa-plus-circle"></i> Add Book
                        </button>
                        <a href="manage_books.php" class="btn btn-outline btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // File upload preview
        document.getElementById('bookFile').addEventListener('change', function(e) {
            const fileName = document.getElementById('fileName');
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
                fileName.style.display = 'block';
            } else {
                fileName.style.display = 'none';
            }
        });
    </script>
</body>
</html>
