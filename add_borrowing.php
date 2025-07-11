<?php
session_start();
// Hapus include 'includes/db_connect.php'; jika masih ada

// --- Memuat Kelas-Kelas OOP yang Diperlukan ---
require_once 'classes/Database.php';
require_once 'classes/BookRepository.php';
require_once 'classes/MemberRepository.php'; // Pastikan file ini ada di classes/
require_once 'classes/BorrowingRepository.php'; // Pastikan file ini ada di classes/

// --- Menggunakan Namespace dari Kelas-Kelas ---
use App\Classes\Database;
use App\Classes\BookRepository;
use App\Classes\MemberRepository;
use App\Classes\BorrowingRepository;

// --- Proteksi Halaman (Hanya Admin yang Bisa Mengakses) ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Arahkan ke halaman login jika tidak login atau bukan admin
    exit;
}

$error_message = '';
$success_message = '';

// --- Inisialisasi Repositories (Pengelola Data) ---
$database = Database::getInstance(); // Mendapatkan instance tunggal koneksi database
$bookRepository = new BookRepository($database); // Mengelola data buku
$memberRepository = new MemberRepository($database); // Mengelola data anggota
$borrowingRepository = new BorrowingRepository($database); // Mengelola transaksi peminjaman

// --- Mengambil Data untuk Dropdown dari Database ---

// Mengambil daftar buku yang tersedia (stok > 0) dan juga informasi gambar sampul
// PASTIKAN BookRepository::getAllBooks() mengembalikan kolom 'cover_image'
$books_available = $bookRepository->getAllBooks(); // Mengambil semua buku
$books_data_for_dropdown = []; // Data buku yang akan digunakan di dropdown dan JavaScript
foreach ($books_available as $book) {
    if ($book['quantity'] > 0) { // Hanya buku dengan stok > 0 yang bisa dipinjam
        $books_data_for_dropdown[] = $book; // Menyimpan data buku lengkap untuk looping HTML
    }
}

// Mengambil daftar anggota terdaftar
$members = $memberRepository->getAllMembers();

// --- Default Value untuk Form (Agar "Sticky" Jika Ada Validasi Gagal) ---
$default_borrow_date = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime('+7 days')); // Jatuh tempo default 7 hari

$form_book_id = $_POST['book_id'] ?? '';
$form_member_id = $_POST['member_id'] ?? '';
$form_borrow_date = $_POST['borrow_date'] ?? $default_borrow_date;
$form_due_date = $_POST['due_date'] ?? $default_due_date;


// --- Proses Form Saat Dikirim (POST Request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book_id = (int)$form_book_id; // Mengambil ID buku dari form
    $member_id = (int)$form_member_id; // Mengambil ID anggota dari form
    $borrow_date = $form_borrow_date; // Mengambil tanggal pinjam dari form
    $due_date = $form_due_date; // Mengambil tanggal jatuh tempo dari form

    // --- Validasi Input Form ---
    if (empty($book_id) || empty($member_id) || empty($borrow_date) || empty($due_date)) {
        $error_message = "Semua field bertanda * harus diisi.";
    } elseif ($borrow_date > $due_date) {
        $error_message = "Tanggal jatuh tempo tidak boleh sebelum tanggal peminjaman.";
    } else {
        // --- Mencatat Peminjaman Menggunakan BorrowingRepository ---
        // Metode addBorrowing() di BorrowingRepository sudah menangani pengecekan stok dan transaksi
        if ($borrowingRepository->addBorrowing($book_id, $member_id, $borrow_date, $due_date)) {
            $success_message = "Buku berhasil dipinjamkan!";
            // Redirect ke halaman daftar peminjaman dengan pesan sukses
            header("Location: borrowings.php?message_type=success&message=" . urlencode($success_message));
            exit;
        } else {
            // Jika addBorrowing() mengembalikan false, berarti ada error.
            // Pesan error spesifik bisa diambil dari log server yang di-generate oleh BorrowingRepository.
            $error_message = "Gagal mencatat peminjaman. Mungkin stok buku tidak cukup atau terjadi kesalahan internal. Mohon cek log server.";
        }
    }
}
// Tidak perlu lagi $conn->close() karena koneksi dikelola oleh kelas Database Singleton.

// --- Menyertakan Header HTML (Memuat Bootstrap dan CSS Kustom) ---
include 'includes/header.php';
?>

<style>
    /* Desain umum halaman */
    .page-title {
        font-size: 2.8rem;
        font-weight: 700;
        color: #343a40;
        margin-bottom: 2.5rem;
        display: flex;
        align-items: center;
        gap: 15px;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        padding-top: 1rem;
    }

    .page-title i {
        font-size: 1.2em;
        color: #6610f2;
        /* Purple color */
    }

    .form-card {
        background-color: #ffffff;
        border-radius: 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 3rem;
        margin-bottom: 3rem;
    }

    /* Styling Form Labels dan Inputs */
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.6rem;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-control,
    .form-select {
        border-radius: 0.75rem;
        padding: 0.9rem 1.2rem;
        font-size: 1.1rem;
        border: 2px solid #e0e0e0;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #6610f2;
        box-shadow: 0 0 0 0.3rem rgba(102, 16, 242, 0.25);
        background-color: #f6f0ff;
    }

    .form-text {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    .form-text.text-danger {
        font-weight: 500;
    }

    /* Styling Alert Messages */
    .alert-custom {
        border-radius: 0.75rem;
        font-size: 1.1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        animation: slideIn 0.6s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .alert-custom i {
        font-size: 1.2em;
    }

    .alert-danger {
        background-color: #fcebeb;
        color: #a0202a;
        border-color: #f5c6cb;
    }

    .alert-success {
        background-color: #e6faed;
        color: #1a6e2d;
        border-color: #c3e6cb;
    }

    /* Styling Buttons */
    .btn-primary {
        background: linear-gradient(45deg, #6610f2 0%, #510dc2 100%);
        /* Purple gradient */
        border: none;
        border-radius: 2.5rem;
        padding: 0.9rem 2.5rem;
        font-size: 1.15rem;
        font-weight: 600;
        transition: all 0.3s ease-in-out;
        box-shadow: 0 5px 15px rgba(102, 16, 242, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(45deg, #510dc2 0%, #420b99 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(102, 16, 242, 0.3);
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        border-radius: 2.5rem;
        padding: 0.9rem 2.5rem;
        font-size: 1.15rem;
        font-weight: 600;
        transition: all 0.3s ease-in-out;
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.2);
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
    }

    /* Styling untuk Required field asterisk */
    .text-danger {
        color: #dc3545 !important;
        font-weight: bold;
    }

    /* Keyframes Animasi */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Custom styles untuk pratinjau gambar sampul */
    .book-cover-preview {
        max-width: 150px;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-top: 15px;
        display: none;
        /* Tersembunyi secara default */
    }

    .book-cover-preview.show {
        display: block;
        /* Tampilkan saat dipilih */
    }
</style>

<div class="container py-4">
    <h2 class="page-title">
        <i class="bi bi-journal-plus"></i> Pinjamkan Buku Baru
    </h2>

    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <div class="form-card">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-custom" role="alert">
                        <i class="bi bi-x-circle-fill"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-custom" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form action="add_borrowing.php" method="POST">
                    <div class="mb-4">
                        <label for="book_id" class="form-label">Pilih Buku <span class="text-danger">*</span></label>
                        <select class="form-select" id="book_id" name="book_id" required <?php echo (empty($books_data_for_dropdown)) ? 'disabled' : ''; ?>>
                            <option value="">-- Pilih Buku Tersedia --</option>
                            <?php foreach ($books_data_for_dropdown as $book): ?>
                                <option value="<?php echo htmlspecialchars($book['id']); ?>"
                                    data-cover-image="<?php echo htmlspecialchars($book['cover_image'] ?? ''); ?>"
                                    data-book-title="<?php echo htmlspecialchars($book['title']); ?>"
                                    data-book-author="<?php echo htmlspecialchars($book['author']); ?>"
                                    <?php echo ((int)$form_book_id === (int)$book['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($book['title']) . " oleh " . htmlspecialchars($book['author']) . " (Stok: " . htmlspecialchars($book['quantity']) . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($books_data_for_dropdown)): ?>
                            <div class="form-text text-danger mt-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Tidak ada buku tersedia untuk dipinjam saat ini.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4 text-center" id="coverImageContainer" style="display: none;">
                        <img id="bookCoverPreview" src="" alt="Sampul Buku" class="img-fluid book-cover-preview">
                        <p class="small text-muted mt-2" id="bookTitleAuthor"></p>
                    </div>

                    <div class="mb-4">
                        <label for="member_id" class="form-label">Pilih Anggota <span class="text-danger">*</span></label>
                        <select class="form-select" id="member_id" name="member_id" required <?php echo (empty($members)) ? 'disabled' : ''; ?>>
                            <option value="">-- Pilih Anggota Terdaftar --</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo htmlspecialchars($member['id']); ?>"
                                    <?php echo ((int)$form_member_id === (int)$member['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($members)): ?>
                            <div class="form-text text-danger mt-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Belum ada anggota terdaftar untuk peminjaman.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="borrow_date" class="form-label">Tanggal Peminjaman <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="borrow_date" name="borrow_date" value="<?php echo htmlspecialchars($form_borrow_date); ?>" required>
                    </div>

                    <div class="mb-5">
                        <label for="due_date" class="form-label">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo htmlspecialchars($form_due_date); ?>" required>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="submit" class="btn btn-primary" <?php echo (empty($books_data_for_dropdown) || empty($members)) ? 'disabled' : ''; ?>>
                            <i class="bi bi-send-fill me-2"></i> Catat Peminjaman
                        </button>
                        <a href="borrowings.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i> Kembali ke Daftar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookSelect = document.getElementById('book_id');
        const bookCoverPreview = document.getElementById('bookCoverPreview');
        const coverImageContainer = document.getElementById('coverImageContainer');
        const bookTitleAuthorElement = document.getElementById('bookTitleAuthor'); // Elemen untuk menampilkan Judul & Penulis
        const defaultCoverPath = 'assets/images/default_cover.png'; // Pastikan path ini benar dan relatif dari add_borrowing.php

        // Fungsi untuk memperbarui tampilan gambar dan info buku
        function updateBookCoverAndInfo() {
            const selectedOption = bookSelect.options[bookSelect.selectedIndex];
            const coverImageFilename = selectedOption.dataset.coverImage;
            const bookTitle = selectedOption.dataset.bookTitle; // Ambil dari data-attribute
            const bookAuthor = selectedOption.dataset.bookAuthor; // Ambil dari data-attribute

            if (selectedOption.value === "") { // Jika opsi "-- Pilih Buku Tersedia --" yang dipilih
                coverImageContainer.style.display = 'none'; // Sembunyikan container
                bookCoverPreview.src = ''; // Bersihkan src
                bookTitleAuthorElement.textContent = ''; // Bersihkan teks
            } else {
                if (coverImageFilename) {
                    bookCoverPreview.src = 'assets/images/covers/' + coverImageFilename; // Path relatif dari add_borrowing.php
                    bookCoverPreview.alt = `Sampul ${bookTitle}`;
                } else {
                    bookCoverPreview.src = defaultCoverPath; // Gambar default jika tidak ada sampul
                    bookCoverPreview.alt = `Tidak ada sampul untuk ${bookTitle}`;
                }
                bookTitleAuthorElement.textContent = `${bookTitle} oleh ${bookAuthor}`;
                coverImageContainer.style.display = 'block'; // Tampilkan container
                bookCoverPreview.classList.add('show'); // Tambahkan kelas 'show'
            }
        }

        // Jalankan saat halaman dimuat jika ada buku yang sudah terpilih (misal dari POST request)
        // Ini memastikan gambar muncul jika form gagal validasi tapi buku sudah terpilih
        if (bookSelect.value) {
            updateBookCoverAndInfo();
        }

        // Tambahkan event listener saat pilihan buku berubah
        bookSelect.addEventListener('change', updateBookCoverAndInfo);
    });
</script>