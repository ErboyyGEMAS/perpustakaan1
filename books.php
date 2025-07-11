<?php
session_start();
// Hapus include 'includes/db_connect.php'; jika masih ada
// Gunakan require_once untuk kelas-kelas OOP
require_once 'classes/Database.php';
require_once 'classes/BookRepository.php';
require_once 'classes/CategoryRepository.php'; // Diperlukan karena BookRepository::getAllBooks join ke categories

// Tambahkan USE statements untuk mengenali kelas dari namespace App\Classes
use App\Classes\Database;
use App\Classes\BookRepository;
use App\Classes\CategoryRepository; // Tambahkan ini juga

// Proteksi halaman: hanya user yang login yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Hanya admin yang bisa mengelola buku (menambah, edit, hapus)
$isAdmin = ($_SESSION['role'] === 'admin');

// Inisialisasi Database dan BookRepository
$database = Database::getInstance();
$bookRepository = new BookRepository($database);

// Inisialisasi variabel pencarian
$search_query = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
}

// Ambil data buku menggunakan Repository
$books = $bookRepository->getAllBooks($search_query);

include 'includes/header.php';
?>

<h2 class="mb-4">Daftar Buku</h2>

<div class="row mb-3">
    <div class="col-md-6">
        <form action="" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Cari judul, penulis, atau kategori..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-outline-primary">Cari</button>
            <?php if (!empty($search_query)): ?>
                <a href="books.php" class="btn btn-outline-secondary ms-2">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <?php if ($isAdmin): ?>
        <div class="col-md-6 text-end">
            <a href="add_book.php" class="btn btn-success">Tambah Buku Baru</a>
        </div>
    <?php endif; ?>
</div>

<?php
// Bagian ini adalah untuk menampilkan pesan dari operasi sebelumnya (misal: delete_book.php)
$display_message = '';
$display_message_type = '';
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $display_message = htmlspecialchars($_GET['message']);
    $display_message_type = htmlspecialchars($_GET['message_type']);
}
?>

<?php if (!empty($display_message)): ?>
    <div class="alert alert-<?php echo $display_message_type; ?>" role="alert">
        <?php echo $display_message; ?>
    </div>
<?php endif; ?>

<?php if (empty($books)): ?>
    <div class="alert alert-info" role="alert">
        Belum ada buku yang tersedia atau tidak ditemukan dengan pencarian Anda.
        <?php if ($isAdmin): ?>
            Silakan <a href="add_book.php" class="alert-link">tambah buku baru</a>.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Sampul</th>
                    <th>Judul</th>
                    <th>Penulis</th>
                    <th>Penerbit</th>
                    <th>Tahun Terbit</th>
                    <th>ISBN</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <?php if ($isAdmin): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book):
                    // Baris yang sudah diperbaiki menggunakan operator null coalescing (??):
                    $cover_image_path = 'assets/images/covers/' . htmlspecialchars($book['cover_image'] ?? '');
                    $default_image_path = 'assets/images/default_cover.png'; // Pastikan Anda punya default image ini
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['id']); ?></td>
                        <td>
                            <?php if (!empty($book['cover_image'] ?? null) && file_exists($cover_image_path)): ?> <img src="<?php echo $cover_image_path; ?>" alt="Sampul <?php echo htmlspecialchars($book['title']); ?>" class="img-thumbnail" style="width: 50px; height: auto;">
                            <?php else: ?>
                                <img src="<?php echo $default_image_path; ?>" alt="Tidak ada sampul" class="img-thumbnail" style="width: 50px; height: auto;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                        <td><?php echo htmlspecialchars($book['publication_year']); ?></td>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($book['quantity']); ?></td>
                        <?php if ($isAdmin): ?>
                            <td>
                                <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning me-1">Edit</a>
                                <a href="delete_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini?');">Hapus</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>