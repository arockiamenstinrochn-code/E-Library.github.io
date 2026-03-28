<?php require_once '../backend/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Library Management System - Access thousands of exam preparation books for UPSC, SSC, RRB, TNPSC and more. Download study materials for free.">
    <title>E-Library Management System - Your Gateway to Exam Success</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">
                <span class="brand-icon"><i class="fas fa-book-open"></i></span>
                E-Library Management System
            </a>
            <ul class="nav-links">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#categories">Categories</a></li>
                <li><a href="#books">Books</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-gauge-high"></i> Admin Panel
                        </a>
                    <?php else: ?>
                        <a href="customer/dashboard.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-gauge-high"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-ghost btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-sparkles"></i> India's Leading Exam Prep Library
            </div>
            <h1>Master Every Exam with <br><span class="gradient-text">Premium Study Materials</span></h1>
            <p>Access a vast collection of books and study materials for UPSC, SSC, RRB, TNPSC and more. Download, study, and succeed in your competitive examinations.</p>
            <div class="hero-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo isAdmin() ? 'admin/dashboard.php' : 'customer/dashboard.php'; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket"></i> Get Started Free
                    </a>
                    <a href="login.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-play"></i> Explore Now
                    </a>
                <?php endif; ?>
            </div>

            <?php
            // Fetch stats
            $totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
            $totalDownloads = $pdo->query("SELECT COALESCE(SUM(downloads), 0) FROM books")->fetchColumn();
            $totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            ?>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="stat-num"><?php echo $totalBooks; ?>+</div>
                    <div class="stat-label">Books Available</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-num"><?php echo $totalCategories; ?></div>
                    <div class="stat-label">Exam Categories</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-num"><?php echo formatNumber($totalDownloads); ?>+</div>
                    <div class="stat-label">Downloads</div>
                </div>
                <div class="hero-stat">
                    <div class="stat-num"><?php echo $totalUsers; ?>+</div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="section" id="categories">
        <div class="section-header">
            <h2>Exam Categories</h2>
            <p>Choose your exam and start preparing with the best study materials</p>
        </div>
        <div class="categories-grid">
            <?php
            $colors = ['purple', 'pink', 'green', 'orange'];
            $categories = $pdo->query("SELECT c.*, COUNT(b.id) as book_count FROM categories c LEFT JOIN books b ON c.id = b.category_id GROUP BY c.id")->fetchAll();
            foreach ($categories as $i => $cat):
                $color = $colors[$i % 4];
            ?>
            <a href="<?php echo isLoggedIn() ? 'customer/dashboard.php?category=' . $cat['slug'] : 'login.php'; ?>" class="category-card" data-color="<?php echo $color; ?>">
                <div class="category-icon <?php echo $color; ?>">
                    <i class="fas <?php echo $cat['icon']; ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                <p><?php echo htmlspecialchars($cat['description']); ?></p>
                <div class="category-count">
                    <i class="fas fa-book"></i> <?php echo $cat['book_count']; ?> Books Available
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Featured Books Section -->
    <section class="section" id="books">
        <div class="section-header">
            <h2>Featured Books</h2>
            <p>Most downloaded books by our students</p>
        </div>
        <div class="books-grid">
            <?php
            $featured = $pdo->query("SELECT b.*, c.name as category_name, c.slug as category_slug FROM books b JOIN categories c ON b.category_id = c.id WHERE b.is_active = 1 ORDER BY b.downloads DESC LIMIT 8")->fetchAll();
            foreach ($featured as $book):
            ?>
            <div class="book-card">
                <div class="book-cover <?php echo $book['category_slug']; ?>">
                    <div class="book-cover-content">
                        <i class="fas fa-book-open" style="color: rgba(255,255,255,0.3);"></i>
                        <br>
                        <span class="book-category-label"><?php echo htmlspecialchars($book['category_name']); ?></span>
                    </div>
                </div>
                <div class="book-info">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                    <div class="book-meta">
                        <span><i class="fas fa-file-lines"></i> <?php echo $book['pages']; ?> pages</span>
                        <span><i class="fas fa-download"></i> <?php echo formatNumber($book['downloads']); ?></span>
                        <span><i class="fas fa-hard-drive"></i> <?php echo $book['file_size']; ?></span>
                    </div>
                    <div class="book-actions">
                        <?php if (isLoggedIn()): ?>
                            <a href="customer/download.php?id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-download"></i> Download
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-lock"></i> Login to Download
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" id="about">
        <div class="section-header">
            <h2>Why Choose E-Library?</h2>
            <p>Everything you need to ace your competitive exams</p>
        </div>
        <div class="categories-grid">
            <div class="category-card" data-color="purple">
                <div class="category-icon purple">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Instant Downloads</h3>
                <p>Download any book instantly. No waiting, no restrictions. Study anytime, anywhere at your convenience.</p>
            </div>
            <div class="category-card" data-color="pink">
                <div class="category-icon pink">
                    <i class="fas fa-shield-check"></i>
                </div>
                <h3>Verified Content</h3>
                <p>All study materials are verified and curated by exam toppers and subject matter experts.</p>
            </div>
            <div class="category-card" data-color="green">
                <div class="category-icon green">
                    <i class="fas fa-arrows-rotate"></i>
                </div>
                <h3>Regular Updates</h3>
                <p>New books and updated content added regularly to keep you ahead of the competition.</p>
            </div>
            <div class="category-card" data-color="orange">
                <div class="category-icon orange">
                    <i class="fas fa-indian-rupee-sign"></i>
                </div>
                <h3>100% Free</h3>
                <p>All books and study materials available absolutely free. No hidden charges or subscriptions required.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> E-Library Management System. Built with <i class="fas fa-heart" style="color: var(--accent-pink);"></i> for Students.</p>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
