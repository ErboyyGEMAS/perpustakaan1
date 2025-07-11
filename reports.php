<?php
session_start();
// Memuat Kelas-Kelas OOP yang Diperlukan
require_once 'classes/Database.php';
require_once 'classes/BookRepository.php';
require_once 'classes/MemberRepository.php';
require_once 'classes/BorrowingRepository.php';

// Menggunakan Namespace dari Kelas-Kelas
use App\Classes\Database;
use App\Classes\BookRepository;
use App\Classes\MemberRepository;
use App\Classes\BorrowingRepository;

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Arahkan ke login jika tidak login atau bukan admin
    exit;
}

// Inisialisasi Repositories (Pengelola Data)
$database = Database::getInstance();
$bookRepository = new BookRepository($database);
$memberRepository = new MemberRepository($database);
$borrowingRepository = new BorrowingRepository($database);

// --- Ambil Data untuk Laporan dari Repositories ---
$total_books = $bookRepository->getTotalBooks();
$total_members = $memberRepository->getTotalMembers();
$borrowed_books_count = $borrowingRepository->getBorrowingCountByStatus('borrowed');
$overdue_books_count = $borrowingRepository->getBorrowingCountByStatus('overdue');
$total_borrowings = $borrowingRepository->getBorrowingCountByStatus(); // Semua transaksi peminjaman (tanpa filter status)

// Ambil daftar buku yang sedang dipinjam (status 'borrowed')
$current_borrowings = $borrowingRepository->getAllBorrowings("", "borrowed");
// Ambil daftar buku yang terlambat dikembalikan (status 'overdue')
$overdue_borrowings = $borrowingRepository->getAllBorrowings("", "overdue");

// Menyertakan Header HTML (Memuat Bootstrap dan CSS Kustom)
include 'includes/header.php';
?>

<style>
    /* Styling untuk kartu laporan */
    .report-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        /* Transisi untuk efek hover */
        border-radius: 1rem;
        /* Sudut membulat */
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 1.5rem;
        /* Jarak antar kartu */
    }

    .report-card:hover {
        transform: translateY(-5px);
        /* Sedikit terangkat saat dihover */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15) !important;
        /* Bayangan lebih kuat */
    }

    .report-card .display-4 {
        font-weight: 700;
        /* Angka lebih tebal */
    }

    .report-card .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #555;
        /* Warna teks judul kartu */
    }

    .report-icon {
        font-size: 3rem;
        /* Ukuran ikon */
        margin-bottom: 1rem;
        color: var(--bs-primary);
        /* Menggunakan warna primer Bootstrap */
    }

    /* Mengatur warna ikon menjadi putih di dalam kartu berwarna */
    .report-card.bg-primary .report-icon,
    .report-card.bg-success .report-icon,
    .report-card.bg-info .report-icon,
    .report-card.bg-danger .report-icon,
    .report-card.bg-secondary .report-icon {
        color: white;
    }
</style>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-bar-chart-line-fill me-2"></i> Laporan & Statistik Perpustakaan</h2>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <div class="col">
            <div class="card report-card bg-primary text-white shadow">
                <div class="card-body">
                    <i class="bi bi-book-fill report-icon"></i>
                    <h3 class="display-4"><?php echo $total_books; ?></h3>
                    <p class="card-title text-white">Total Buku</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card report-card bg-success text-white shadow">
                <div class="card-body">
                    <i class="bi bi-people-fill report-icon"></i>
                    <h3 class="display-4"><?php echo $total_members; ?></h3>
                    <p class="card-title text-white">Total Anggota</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card report-card bg-info text-white shadow">
                <div class="card-body">
                    <i class="bi bi-journal-check report-icon"></i>
                    <h3 class="display-4"><?php echo $borrowed_books_count; ?></h3>
                    <p class="card-title text-white">Buku Sedang Dipinjam</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card report-card bg-danger text-white shadow">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle-fill report-icon"></i>
                    <h3 class="display-4"><?php echo $overdue_books_count; ?></h3>
                    <p class="card-title text-white">Buku Terlambat</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card report-card bg-secondary text-white shadow">
                <div class="card-body">
                    <i class="bi bi-arrow-left-right report-icon"></i>
                    <h3 class="display-4"><?php echo $total_borrowings; ?></h3>
                    <p class="card-title text-white">Total Transaksi Peminjaman</p>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-4"><i class="bi bi-book-half me-2"></i> Buku yang Sedang Dipinjam</h3>
    <?php if (empty($current_borrowings)): ?>
        <div class="alert alert-info" role="alert">Tidak ada buku yang sedang dipinjam saat ini.</div>
    <?php else: ?>
        <div class="table-responsive mb-5">
            <table class="table table-hover table-striped">
                <thead class="table-info">
                    <tr>
                        <th>ID Peminjaman</th>
                        <th>Judul Buku</th>
                        <th>Peminjam</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_borrowings as $borrow): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($borrow['id']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($borrow['borrow_date'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($borrow['due_date'])); ?></td>
                            <td><span class="badge bg-warning text-dark"><?php echo ucfirst($borrow['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h3 class="mb-4"><i class="bi bi-clock-history me-2"></i> Buku yang Terlambat Dikembalikan</h3>
    <?php if (empty($overdue_borrowings)): ?>
        <div class="alert alert-success" role="alert">Tidak ada buku yang terlambat dikembalikan.</div>
    <?php else: ?>
        <div class="table-responsive mb-5">
            <table class="table table-hover table-striped">
                <thead class="table-danger">
                    <tr>
                        <th>ID Peminjaman</th>
                        <th>Judul Buku</th>
                        <th>Peminjam</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdue_borrowings as $borrow): ?>
                        <tr class="table-danger">
                            <td><?php echo htmlspecialchars($borrow['id']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($borrow['borrow_date'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($borrow['due_date'])); ?></td>
                            <td><span class="badge bg-danger"><?php echo ucfirst($borrow['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>