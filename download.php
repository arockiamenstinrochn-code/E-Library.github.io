<?php
require_once '../../backend/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$bookId = intval($_GET['id'] ?? 0);

if ($bookId <= 0) {
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND is_active = 1");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    flashMessage('download', 'Book not found or unavailable.', 'error');
    redirect('dashboard.php');
}

// Record download
$insertDownload = $pdo->prepare("INSERT INTO download_history (user_id, book_id) VALUES (?, ?)");
$insertDownload->execute([$_SESSION['user_id'], $bookId]);

// Increment download count
$updateCount = $pdo->prepare("UPDATE books SET downloads = downloads + 1 WHERE id = ?");
$updateCount->execute([$bookId]);

// Check if actual file exists
$filePath = __DIR__ . '/../../backend/' . $book['file_path'];

if (file_exists($filePath)) {
    // Serve the file
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($book['file_path']) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit();
} else {
    // File doesn't exist - show message
    flashMessage('download', 'Download recorded! "' . $book['title'] . '" has been added to your download history. (Note: This is a demo - actual PDF file is not available yet. Admin can upload real files.)', 'success');
    redirect('dashboard.php');
}
?>
