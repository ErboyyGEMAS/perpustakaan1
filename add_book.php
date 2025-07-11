<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/BookRepository.php';
require_once 'classes/CategoryRepository.php';

use App\Classes\Database;
use App\Classes\BookRepository;
use App\Classes\CategoryRepository;

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error_message = '';
$success_message = '';

$database = Database::getInstance();
$bookRepository = new BookRepository($database);
$categoryRepository = new CategoryRepository($database);

$categories = $categoryRepository->getAllCategories();

// Default values for form (pre-fill)
$form_data = [
    'title' => $_POST['title'] ?? '',
    'author' => $_POST['author'] ?? '',
    'publisher' => $_POST['publisher'] ?? '',
    'publication_year' => $_POST['publication_year'] ?? date("Y"),
    'isbn' => $_POST['isbn'] ?? '',
    'category_id' => $_POST['category_id'] ?? '',
    'quantity' => $_POST['quantity'] ?? 1
];

// Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_data = array_merge($form_data, $_POST);

    $title = trim($form_data['title']);
    $author = trim($form_data['author']);
    $publisher = trim($form_data['publisher']);
    $publication_year = (int)$form_data['publication_year'];
    $isbn = trim($form_data['isbn']);
    $category_id = (int)$form_data['category_id'];
    $quantity = (int)$form_data['quantity'];

    $cover_image_filename = null; // Inisialisasi nama file gambar

    // --- LOGIKA UPLOAD GAMBAR ---
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['cover_image']['tmp_name'];
        $file_name = $_FILES['cover_image']['name'];
        $file_size = $_FILES['cover_image']['size'];
        $file_type = $_FILES['cover_image']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Tentukan direktori upload
        $upload_dir = __DIR__ . '/assets/images/covers/'; // __DIR__ adalah direktori file PHP saat ini

        // Validasi ekstensi file
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_ext)) {
            $error_message = "Hanya file JPG, JPEG, PNG, dan GIF yang diizinkan.";
        }
        // Validasi ukuran file (misal: maksimal 5MB)
        elseif ($file_size > 5 * 1024 * 1024) { // 5MB
            $error_message = "Ukuran file gambar maksimal 5MB.";
        } else {
            // Generate nama file unik untuk mencegah konflik
            $cover_image_filename = uniqid('cover_', true) . '.' . $file_ext;
            $destination_path = $upload_dir . $cover_image_filename;

            // Pindahkan file dari direktori temporer ke lokasi tujuan
            if (!move_uploaded_file($file_tmp_name, $destination_path)) {
                $error_message = "Gagal mengupload gambar sampul. Coba lagi.";
                $cover_image_filename = null; // Set null jika gagal upload
            }
        }
    } elseif (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Tangani error upload lainnya (selain UPLOAD_ERR_OK dan UPLOAD_ERR_NO_FILE)
        $error_message = "Terjadi kesalahan saat upload gambar: Kode " . $_FILES['cover_image']['error'];
    }
    // --- AKHIR LOGIKA UPLOAD GAMBAR ---

    // Lanjutkan validasi data buku hanya jika tidak ada error dari upload gambar
    if (empty($error_message)) {
        if (empty($title) || empty($author) || empty($isbn) || $quantity === '' || $quantity === null) {
            $error_message = "Judul, Penulis, ISBN, dan Kuantitas harus diisi.";
        } elseif (!filter_var($isbn, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^(978|979){1}[0-9]{9}[0-9X]{1}$/")))) {
            $error_message = "Format ISBN tidak valid. (Contoh: 9781234567890 atau 979123456789X)";
        } elseif ($publication_year < 1000 || $publication_year > date("Y") + 5) {
            $error_message = "Tahun terbit tidak valid.";
        } elseif ($quantity < 0) {
            $error_message = "Kuantitas tidak bisa kurang dari 0.";
        } else {
            // Cek duplikasi ISBN menggunakan Repository
            if ($bookRepository->isIsbnExists($isbn)) {
                $error_message = "ISBN ini sudah terdaftar dalam database.";
            } else {
                // Tambahkan buku menggunakan Repository, sertakan nama file gambar
                if ($bookRepository->addBook($title, $author, $publisher, $publication_year, $isbn, $category_id, $quantity, $cover_image_filename)) {
                    $success_message = "Buku <strong>" . htmlspecialchars($title) . "</strong> berhasil ditambahkan!";
                    // Reset form data for a new entry
                    $form_data = [
                        'title' => '',
                        'author' => '',
                        'publisher' => '',
                        'publication_year' => date("Y"),
                        'isbn' => '',
                        'category_id' => '',
                        'quantity' => 1
                    ];
                } else {
                    // Jika gagal menyimpan ke DB, hapus file yang mungkin sudah terupload
                    if ($cover_image_filename && file_exists($upload_dir . $cover_image_filename)) {
                        unlink($upload_dir . $cover_image_filename);
                    }
                    $error_message = "Terjadi kesalahan saat menambahkan buku ke database.";
                }
            }
        }
    }
}
include 'includes/header.php';
?>

<h2 class="mb-4">Tambah Buku Baru</h2>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<form action="add_book.php" method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="title" class="form-label">Judul Buku <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="author" class="form-label">Penulis <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($form_data['author']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="publisher" class="form-label">Penerbit</label>
        <input type="text" class="form-control" id="publisher" name="publisher" value="<?php echo htmlspecialchars($form_data['publisher']); ?>">
    </div>
    <div class="mb-3">
        <label for="publication_year" class="form-label">Tahun Terbit</label>
        <input type="number" class="form-control" id="publication_year" name="publication_year" value="<?php echo htmlspecialchars($form_data['publication_year']); ?>" min="1000" max="<?php echo date("Y") + 5; ?>">
    </div>
    <div class="mb-3">
        <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($form_data['isbn']); ?>" required pattern="^(978|979)[0-9]{9}[0-9X]{1}$" title="Format ISBN: 13 digit (misal: 9781234567890 atau 979123456789X)">
        <div class="form-text">Contoh: 9781234567890 (hanya angka dan X, 13 digit)</div>
    </div>
    <div class="mb-3">
        <label for="category_id" class="form-label">Kategori</label>
        <select class="form-select" id="category_id" name="category_id">
            <option value="">Pilih Kategori</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($form_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="quantity" class="form-label">Kuantitas (Stok) <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo htmlspecialchars($form_data['quantity']); ?>" min="0" required>
    </div>

    <div class="mb-3">
        <label for="cover_image" class="form-label">Gambar Sampul</label>
        <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/jpeg, image/png, image/gif">
        <div class="form-text">Upload file gambar (JPG, PNG, GIF) maksimal 5MB.</div>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Buku</button>
    <a href="books.php" class="btn btn-secondary">Kembali ke Daftar Buku</a>
</form>

<?php include 'includes/footer.php'; ?>