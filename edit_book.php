<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/BookRepository.php';
require_once 'classes/CategoryRepository.php';

// Tambahkan USE statements di sini
use App\Classes\Database;
use App\Classes\BookRepository;
use App\Classes\CategoryRepository;

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Arahkan ke login jika tidak login atau bukan admin
    exit;
}

$error_message = '';
$success_message = '';
$book_data = []; // Untuk menyimpan data buku yang akan diedit

// Inisialisasi Database dan Repository
// Sekarang tidak perlu '\App\Classes\' karena sudah ada 'use' statement di atas
$database = Database::getInstance();
$bookRepository = new BookRepository($database);
$categoryRepository = new CategoryRepository($database);

// 1. Ambil ID buku dari URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $book_id = (int)$_GET['id'];

    // Ambil data buku yang akan diedit menggunakan BookRepository
    $book_data = $bookRepository->getBookById($book_id);

    if (empty($book_data)) { // Jika buku tidak ditemukan
        $error_message = "Buku tidak ditemukan.";
    }
} else {
    $error_message = "ID buku tidak diberikan.";
}

// 2. Ambil daftar kategori dari database menggunakan CategoryRepository untuk dropdown
$categories = $categoryRepository->getAllCategories();

// Inisialisasi form_data dengan data buku yang ada atau POST data (untuk "sticky form")
$form_data = $book_data; // Default to existing book data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_data = array_merge($form_data, $_POST); // Merge with POST data if form was submitted
}


// 3. Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_message)) { // Lanjutkan hanya jika tidak ada error dari GET
    // Ambil ID buku dari POST (untuk memastikan ID yang sama)
    $book_id_post = (int)$_POST['book_id'];

    // Jika ID dari GET dan POST tidak cocok, ada masalah
    if ($book_id_post !== $book_data['id']) {
        $error_message = "ID buku tidak cocok, operasi dibatalkan.";
    } else {
        // Ambil dan bersihkan data input
        $title = trim($form_data['title']);
        $author = trim($form_data['author']);
        $publisher = trim($form_data['publisher']);
        $publication_year = (int)$form_data['publication_year'];
        $isbn = trim($form_data['isbn']);
        $category_id = (int)$form_data['category_id'];
        $quantity = (int)$form_data['quantity'];

        // Validasi sederhana
        if (empty($title) || empty($author) || empty($isbn) || $quantity === '' || $quantity === null) {
            $error_message = "Judul, Penulis, ISBN, dan Kuantitas harus diisi.";
        } elseif (!filter_var($isbn, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^(978|979){1}[0-9]{9}[0-9X]{1}$/")))) {
            $error_message = "Format ISBN tidak valid. (Contoh: 9781234567890 atau 979123456789X)";
        } elseif ($publication_year < 1000 || $publication_year > date("Y") + 5) {
            $error_message = "Tahun terbit tidak valid.";
        } elseif ($quantity < 0) {
            $error_message = "Kuantitas tidak bisa kurang dari 0.";
        } else {
            // Cek duplikasi ISBN, kecuali untuk buku yang sedang diedit, menggunakan BookRepository
            if ($bookRepository->isIsbnExists($isbn, $book_id)) {
                $error_message = "ISBN ini sudah terdaftar untuk buku lain.";
            } else {
                // Perbarui buku menggunakan BookRepository
                if ($bookRepository->updateBook($book_id, $title, $author, $publisher, $publication_year, $isbn, $category_id, $quantity)) {
                    $success_message = "Buku <strong>" . htmlspecialchars($title) . "</strong> berhasil diperbarui!";
                    // Opsional: Muat ulang data buku setelah update agar form menunjukkan data terbaru
                    // Ini dilakukan dengan memperbarui $book_data agar form tetap "sticky" dengan data terbaru
                    $book_data = $bookRepository->getBookById($book_id);
                    // Atau, bisa juga redirect ke halaman daftar buku
                    // header("Location: books.php?message_type=success&message=" . urlencode("Buku berhasil diperbarui!"));
                    // exit;
                } else {
                    $error_message = "Terjadi kesalahan saat memperbarui buku.";
                }
            }
        }
    }
}
// Tidak perlu lagi $conn->close() karena koneksi dikelola oleh kelas Database Singleton.
include 'includes/header.php';
?>

<h2 class="mb-4">Edit Buku</h2>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error_message; ?>
    </div>
    <a href="books.php" class="btn btn-secondary mb-3">Kembali ke Daftar Buku</a>
<?php else: // Tampilkan form hanya jika tidak ada error awal 
?>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form action="edit_book.php?id=<?php echo htmlspecialchars($book_data['id']); ?>" method="POST">
        <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book_data['id']); ?>">

        <div class="mb-3">
            <label for="title" class="form-label">Judul Buku <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="author" class="form-label">Penulis <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($form_data['author'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="publisher" class="form-label">Penerbit</label>
            <input type="text" class="form-control" id="publisher" name="publisher" value="<?php echo htmlspecialchars($form_data['publisher'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label for="publication_year" class="form-label">Tahun Terbit</label>
            <input type="number" class="form-control" id="publication_year" name="publication_year" value="<?php echo htmlspecialchars($form_data['publication_year'] ?? date("Y")); ?>" min="1000" max="<?php echo date("Y") + 5; ?>">
        </div>
        <div class="mb-3">
            <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($form_data['isbn'] ?? ''); ?>" required pattern="^(978|979)[0-9]{9}[0-9X]{1}$" title="Format ISBN: 13 digit (misal: 9781234567890 atau 979123456789X)">
            <div class="form-text">Contoh: 9781234567890 (hanya angka dan X, 13 digit)</div>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Kategori</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">Pilih Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Kuantitas (Stok) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($form_data['quantity'] ?? 1); ?>" min="0" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Buku</button>
        <a href="books.php" class="btn btn-secondary">Kembali ke Daftar Buku</a>
    </form>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>