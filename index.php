<?php
session_start();
include 'includes/db_connect.php';
include 'includes/header.php';

// Fetch latest books for the new section
$latest_books = [];
if ($conn) { // Ensure connection is established
    $sql_latest_books = "SELECT id, title, author, quantity FROM books ORDER BY created_at DESC LIMIT 3";
    $result_latest_books = $conn->query($sql_latest_books);
    if ($result_latest_books) {
        if ($result_latest_books->num_rows > 0) {
            while ($row = $result_latest_books->fetch_assoc()) {
                $latest_books[] = $row;
            }
        } else {
            // Handle error if query fails (or no books found)
            error_log("No latest books found or query failed: " . $conn->error);
        }
    }
}
?>

<style>
    :root {
        --primary-color: #007bff;
        --secondary-color: #6c757d;
        --accent-color-1: #ffc107;
        /* Yellow */
        --accent-color-2: #28a745;
        /* Green */
        --dark-bg: #343a40;
        --light-bg: #f8f9fa;
        --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, #6610f2 100%);
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        /* A more modern font stack */
        background-color: var(--light-bg);
    }

    /* Hero Section Enhancements */
    .hero-section {
        background-image: var(--gradient-primary);
        color: white;
        min-height: 70vh;
        /* Make it even taller */
        display: flex;
        align-items: center;
        justify-content: center;
        text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.4);
        /* Stronger text shadow */
        position: relative;
        overflow: hidden;
        /* Hide overflow for particles/decorations */
        border-bottom-left-radius: 50px;
        /* Custom rounded bottom corners */
        border-bottom-right-radius: 50px;
        animation: fadeIn 1.8s ease-out;
        /* Slower fade in */
    }

    .hero-title {
        font-size: 4.5rem;
        /* Much larger title */
        font-weight: 800;
        /* Extra bold */
        line-height: 1.1;
    }

    .hero-lead {
        font-size: 1.6rem;
        max-width: 800px;
        line-height: 1.6;
        animation: slideInUp 1.5s ease-out;
        /* Animate lead text */
    }

    .hero-cta-buttons {
        margin-top: 3rem;
        animation: fadeIn 2s ease-out;
    }

    .btn-hero-primary {
        background-color: var(--accent-color-1) !important;
        border-color: var(--accent-color-1) !important;
        color: var(--dark-bg) !important;
        font-weight: bold;
        padding: 0.8rem 2.5rem;
        font-size: 1.2rem;
        border-radius: 50px;
        /* Pill button */
        transition: all 0.4s ease;
    }

    .btn-hero-primary:hover {
        background-color: #e0a800 !important;
        border-color: #e0a800 !important;
        transform: translateY(-5px) scale(1.02);
        /* More pronounced lift */
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2) !important;
    }

    .btn-hero-secondary {
        border-color: rgba(255, 255, 255, 0.7) !important;
        color: rgba(255, 255, 255, 0.9) !important;
        padding: 0.8rem 2.5rem;
        font-size: 1.2rem;
        border-radius: 50px;
        /* Pill button */
        transition: all 0.4s ease;
    }

    .btn-hero-secondary:hover {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2) !important;
    }

    /* Section Styles */
    .section-title {
        font-size: 3rem;
        /* Larger section titles */
        font-weight: 700;
        color: var(--dark-bg);
        margin-bottom: 2.5rem;
        text-align: center;
        position: relative;
    }

    .section-title::after {
        content: '';
        display: block;
        width: 80px;
        height: 5px;
        background-color: var(--primary-color);
        margin: 15px auto 0;
        border-radius: 5px;
    }

    /* Card Feature Enhancements */
    .card-feature {
        border-radius: 1.5rem;
        /* Even more rounded corners */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .1) !important;
        /* Default shadow */
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        /* Smooth cubic-bezier transition */
        overflow: hidden;
    }

    .card-feature:hover {
        transform: translateY(-15px) scale(1.02);
        /* More pronounced lift and slight scale */
        box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, .25) !important;
        /* Much stronger shadow on hover */
        border-color: var(--primary-color) !important;
        /* Highlight border on hover */
    }

    .card-body .display-3 {
        color: var(--primary-color);
        /* Default icon color */
    }

    .card-body .text-success {
        /* Override for success icon */
        color: var(--accent-color-2) !important;
    }

    .card-body .text-info {
        /* Override for info icon */
        color: #17a2b8 !important;
        /* Default Bootstrap info */
    }

    .card-title.fw-bold {
        color: var(--dark-bg);
    }

    /* Latest Books Section */
    .latest-books-card {
        border-left: 5px solid var(--primary-color);
        /* Left border highlight */
        transition: all 0.3s ease;
    }

    .latest-books-card:hover {
        border-left-color: var(--accent-color-1);
        /* Change border color on hover */
    }

    .latest-books-card .badge {
        font-size: 0.9rem;
        padding: 0.5em 0.7em;
        border-radius: 0.5rem;
    }


    /* General Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<header class="hero-section text-white p-5 mb-5 rounded-3">
    <div class="container text-center py-5">
        <h1 class="hero-title mb-4">Selamat Datang di <br> Perpustakaan Kita!</h1>
        <p class="hero-lead mb-5 mx-auto">
            Kelola koleksi buku dan anggota perpustakaan Anda dengan mudah dan efisien.
            Rasakan pengalaman manajemen perpustakaan modern yang intuitif dan responsif.
        </p>
        <div class="hero-cta-buttons d-grid gap-3 d-sm-flex justify-content-sm-center">
            <a class="btn btn-hero-primary btn-lg px-4 shadow-sm" href="books.php" role="button">
                <i class="bi bi-book-half me-2"></i> Jelajahi Koleksi Buku
            </a>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <a class="btn btn-hero-secondary btn-lg px-4 shadow-sm" href="dashboard.php" role="button">
                    <i class="bi bi-speedometer2 me-2"></i> Pergi ke Dashboard
                </a>
            <?php else: ?>
                <a class="btn btn-hero-secondary btn-lg px-4 shadow-sm" href="login.php" role="button">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Login Admin
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container my-5">
    <h2 class="section-title">Fitur Utama Kami</h2>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <div class="col">
            <div class="card h-100 card-feature bg-light border-0">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-collection-fill display-3 text-primary mb-3"></i>
                    <h5 class="card-title fw-bold">Manajemen Buku</h5>
                    <p class="card-text text-muted">Tambahkan, edit, dan hapus data buku dengan cepat dan terorganisir.</p>
                    <a href="books.php" class="btn btn-primary mt-3 rounded-pill px-4">Lihat Buku</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 card-feature bg-light border-0">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-person-bounding-box display-3 text-success mb-3"></i>
                    <h5 class="card-title fw-bold">Daftar Anggota</h5>
                    <p class="card-text text-muted">Kelola informasi anggota perpustakaan termasuk detail kontak dan riwayat peminjaman.</p>
                    <a href="members.php" class="btn btn-success mt-3 rounded-pill px-4">Lihat Anggota</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 card-feature bg-light border-0">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-journals display-3 text-info mb-3"></i>
                    <h5 class="card-title fw-bold">Transaksi Peminjaman</h5>
                    <p class="card-text text-muted">Catat dan lacak peminjaman serta pengembalian buku dengan riwayat lengkap.</p>
                    <a href="borrowings.php" class="btn btn-info mt-3 rounded-pill px-4">Lihat Peminjaman</a>
                </div>
            </div>
        </div>
    </div>

    <h2 class="section-title mt-5">Buku Terbaru Kami</h2>

    <?php if (empty($latest_books)): ?>
        <div class="alert alert-warning text-center my-4" role="alert">
            Belum ada buku terbaru yang ditambahkan.
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] === 'admin'): ?>
                Silakan <a href="add_book.php" class="alert-link">tambah buku baru</a>.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($latest_books as $book): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 latest-books-card bg-white">
                        <div class="card-body p-4">
                            <h5 class="card-title text-primary fw-bold"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="card-text text-muted mb-2">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="card-text small text-secondary">
                                Stok Tersedia:
                                <span class="badge <?php echo ($book['quantity'] > 0 ? 'bg-success' : 'bg-danger'); ?>">
                                    <?php echo htmlspecialchars($book['quantity']); ?>
                                </span>
                            </p>
                            <a href="books.php" class="btn btn-sm btn-outline-primary mt-3 rounded-pill">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php'; // Sertakan footer
?>