<?php
session_start();
include 'includes/db_connect.php';

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Arahkan ke login jika tidak login atau bukan admin
    exit;
}

$error_message = '';
$success_message = '';
$borrowing_data = []; // Untuk menyimpan data peminjaman yang akan diedit
$book_id_for_update = null; // Akan digunakan untuk update stok
$original_status = null; // Untuk membandingkan status lama dan baru

// 1. Ambil ID peminjaman dari URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $borrowing_id = (int)$_GET['id'];

    // Ambil data peminjaman yang akan diedit, join dengan books dan members untuk display
    $sql_get_borrowing = "SELECT b.id, b.book_id, b.member_id, b.borrow_date, b.due_date, b.return_date, b.status,
                                 book.title AS book_title, book.author AS book_author, member.name AS member_name
                          FROM borrowings b
                          JOIN books book ON b.book_id = book.id
                          JOIN members member ON b.member_id = member.id
                          WHERE b.id = ?";
    $stmt_get_borrowing = $conn->prepare($sql_get_borrowing);
    $stmt_get_borrowing->bind_param("i", $borrowing_id);
    $stmt_get_borrowing->execute();
    $result_get_borrowing = $stmt_get_borrowing->get_result();

    if ($result_get_borrowing->num_rows === 1) {
        $borrowing_data = $result_get_borrowing->fetch_assoc();
        $book_id_for_update = $borrowing_data['book_id'];
        $original_status = $borrowing_data['status']; // Simpan status asli
    } else {
        $error_message = "Transaksi peminjaman tidak ditemukan.";
    }
    $stmt_get_borrowing->close();
} else {
    $error_message = "ID transaksi peminjaman tidak diberikan.";
}

// 2. Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_message) && !empty($borrowing_data)) { // Lanjutkan hanya jika tidak ada error dari GET & data ditemukan
    // Ambil ID peminjaman dari POST (untuk memastikan ID yang sama)
    $borrowing_id_post = (int)$_POST['borrowing_id'];

    // Jika ID dari GET dan POST tidak cocok, ada masalah
    if ($borrowing_id_post !== $borrowing_data['id']) {
        $error_message = "ID transaksi peminjaman tidak cocok, operasi dibatalkan.";
    } else {
        // Ambil dan bersihkan data input
        $borrow_date = $conn->real_escape_string(trim($_POST['borrow_date'] ?? ''));
        $due_date = $conn->real_escape_string(trim($_POST['due_date'] ?? ''));
        $new_status = $conn->real_escape_string(trim($_POST['status'] ?? ''));
        $return_date = (isset($_POST['return_date']) && !empty($_POST['return_date'])) ? $conn->real_escape_string(trim($_POST['return_date'])) : null; // Bisa null jika belum kembali

        // Validasi input
        if (empty($borrow_date) || empty($due_date) || empty($new_status)) {
            $error_message = "Tanggal Peminjaman, Jatuh Tempo, dan Status harus diisi.";
        } elseif (!in_array($new_status, ['borrowed', 'returned', 'overdue'])) {
            $error_message = "Status tidak valid.";
        } elseif ($borrow_date > $due_date) {
            $error_message = "Tanggal jatuh tempo tidak boleh sebelum tanggal peminjaman.";
        } elseif ($new_status === 'returned' && empty($return_date)) {
            $error_message = "Tanggal Kembali harus diisi jika status adalah 'Dikembalikan'.";
        }
        // Jika return_date ada, validasi agar tidak sebelum borrow_date atau setelah tanggal hari ini
        if (!empty($return_date)) {
            if ($return_date < $borrow_date) {
                $error_message = "Tanggal kembali tidak boleh sebelum tanggal peminjaman.";
            } elseif ($return_date > date('Y-m-d')) {
                $error_message = "Tanggal kembali tidak boleh di masa depan.";
            }
        }
        // Jika status bukan 'returned', pastikan return_date null
        if ($new_status !== 'returned' && !empty($return_date)) {
            $return_date = null; // Clear return_date if status is not 'returned'
        }


        if (empty($error_message)) {
            // Mulai transaksi database
            $conn->begin_transaction();

            try {
                // Tentukan query untuk return_date
                $return_date_clause = $return_date === null ? ", return_date = NULL" : ", return_date = ?";
                $types = "sssi"; // for borrow_date, due_date, new_status, borrowing_id
                $params = [$borrow_date, $due_date, $new_status, $borrowing_id_post];

                if ($return_date !== null) {
                    $types = "ssssi"; // for borrow_date, due_date, new_status, return_date, borrowing_id
                    array_splice($params, 3, 0, $return_date); // Insert return_date into params array at index 3
                }

                // Perbarui transaksi peminjaman
                $sql_update_borrowing = "UPDATE borrowings SET borrow_date = ?, due_date = ?, status = ?" . $return_date_clause . " WHERE id = ?";
                $stmt_update_borrowing = $conn->prepare($sql_update_borrowing);
                $stmt_update_borrowing->bind_param($types, ...$params);

                if (!$stmt_update_borrowing->execute()) {
                    throw new Exception("Gagal memperbarui transaksi peminjaman: " . $stmt_update_borrowing->error);
                }
                $stmt_update_borrowing->close();

                // Logic untuk update stok buku berdasarkan perubahan status
                if ($original_status !== 'returned' && $new_status === 'returned') {
                    // Jika status berubah dari 'dipinjam/terlambat' menjadi 'dikembalikan', tambahkan stok
                    $stmt_update_stock = $conn->prepare("UPDATE books SET quantity = quantity + 1 WHERE id = ?");
                    $stmt_update_stock->bind_param("i", $book_id_for_update);
                    if (!$stmt_update_stock->execute()) {
                        throw new Exception("Gagal menambahkan stok buku.");
                    }
                    $stmt_update_stock->close();
                } elseif ($original_status === 'returned' && ($new_status === 'borrowed' || $new_status === 'overdue')) {
                    // Jika status berubah dari 'dikembalikan' menjadi 'dipinjam/terlambat', kurangi stok
                    // Perlu cek stok agar tidak negatif
                    $stmt_check_book_quantity = $conn->prepare("SELECT quantity FROM books WHERE id = ? FOR UPDATE");
                    $stmt_check_book_quantity->bind_param("i", $book_id_for_update);
                    $stmt_check_book_quantity->execute();
                    $result_check_quantity = $stmt_check_book_quantity->get_result();
                    $current_book_qty = $result_check_quantity->fetch_assoc()['quantity'];
                    $stmt_check_book_quantity->close();

                    if ($current_book_qty <= 0) {
                        throw new Exception("Stok buku tidak cukup untuk mengubah status menjadi 'dipinjam' kembali.");
                    }

                    $stmt_update_stock = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");
                    $stmt_update_stock->bind_param("i", $book_id_for_update);
                    if (!$stmt_update_stock->execute()) {
                        throw new Exception("Gagal mengurangi stok buku.");
                    }
                    $stmt_update_stock->close();
                }

                // Commit transaksi jika semua berhasil
                $conn->commit();
                $success_message = "Transaksi peminjaman berhasil diperbarui!";
                // Redirect ke halaman daftar peminjaman dengan pesan sukses
                header("Location: borrowings.php?message_type=success&message=" . urlencode($success_message));
                exit;
            } catch (Exception $e) {
                // Rollback transaksi jika ada kesalahan
                $conn->rollback();
                $error_message = "Terjadi kesalahan: " . $e->getMessage();
                // Load kembali data asli untuk form jika ada error POST (opsional, bisa juga biarkan form terisi data yg di-submit)
                // $borrowing_data = $borrowing_data; // Re-use initial data
            }
        }
    }
}
$conn->close(); // Tutup koneksi di akhir halaman

include 'includes/header.php'; // Pastikan header ini memuat Bootstrap 5 & Font Awesome
?>

<style>
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
        /* Adjust to move title down slightly from navbar */
    }

    .page-title i {
        font-size: 1.2em;
        color: #ff8c00;
        /* Dark Orange for edit borrowing icon */
    }

    .form-card {
        background-color: #ffffff;
        border-radius: 1.25rem;
        /* More rounded corners */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        /* Deeper shadow */
        padding: 3rem;
        /* More internal padding */
        margin-bottom: 3rem;
        /* Space below the card */
    }

    /* Form Label and Input Styling */
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.6rem;
        font-size: 1.05rem;
        display: flex;
        /* For optional asterisk alignment */
        align-items: center;
        gap: 5px;
        /* Space between label text and asterisk */
    }

    .form-control,
    .form-select {
        border-radius: 0.75rem;
        /* Rounded input fields */
        padding: 0.9rem 1.2rem;
        /* Generous padding */
        font-size: 1.1rem;
        /* Larger font in inputs */
        border: 2px solid #e0e0e0;
        /* Thicker, softer border */
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        /* Smooth transition */
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #ff8c00;
        /* Dark Orange border on focus */
        box-shadow: 0 0 0 0.3rem rgba(255, 140, 0, 0.25);
        /* Broader, softer focus shadow */
        background-color: #fffaf0;
        /* Very light orange background on focus */
    }

    .form-text {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 0.5rem;
    }

    /* Readonly inputs for Book Title and Member Name */
    .form-control[readonly] {
        background-color: #e9ecef;
        /* Lighter background for disabled/readonly */
        opacity: 0.9;
        border-style: dashed;
        /* Dotted border for a distinct look */
        border-color: #adb5bd;
    }

    /* Alert Messages */
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

    /* Buttons */
    .btn-primary {
        background: linear-gradient(45deg, #007bff 0%, #0056b3 100%);
        /* Blue gradient for primary button */
        border: none;
        border-radius: 2.5rem;
        padding: 0.9rem 2.5rem;
        font-size: 1.15rem;
        font-weight: 600;
        transition: all 0.3s ease-in-out;
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(45deg, #0056b3 0%, #004085 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
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

    /* Required field asterisk styling */
    .text-danger {
        color: #dc3545 !important;
        font-weight: bold;
    }

    /* Animation for alerts */
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
</style>
</head>

<body>


    <div class="container py-4">
        <h2 class="page-title">
            <i class="fas fa-handshake-alt-slash"></i> Edit Transaksi Peminjaman
        </h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-custom" role="alert">
                <i class="fas fa-times-circle"></i> <?php echo $error_message; ?>
            </div>
            <a href="borrowings.php" class="btn btn-secondary mt-3 mb-3">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Peminjaman
            </a>
        <?php else: // Tampilkan form hanya jika tidak ada error awal dan borrowing_data ditemukan 
        ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-custom" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-9 col-lg-8">
                    <div class="form-card">
                        <form action="edit_borrowing.php?id=<?php echo htmlspecialchars($borrowing_data['id']); ?>" method="POST">
                            <input type="hidden" name="borrowing_id" value="<?php echo htmlspecialchars($borrowing_data['id']); ?>">

                            <div class="mb-4">
                                <label for="book_title" class="form-label">Judul Buku</label>
                                <input type="text" class="form-control" id="book_title" value="<?php echo htmlspecialchars($borrowing_data['book_title'] ?? '') . ' oleh ' . htmlspecialchars($borrowing_data['book_author'] ?? ''); ?>" disabled readonly>
                            </div>
                            <div class="mb-4">
                                <label for="member_name" class="form-label">Nama Anggota</label>
                                <input type="text" class="form-control" id="member_name" value="<?php echo htmlspecialchars($borrowing_data['member_name'] ?? ''); ?>" disabled readonly>
                            </div>

                            <div class="mb-4">
                                <label for="borrow_date" class="form-label">Tanggal Peminjaman <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="borrow_date" name="borrow_date" value="<?php echo htmlspecialchars($_POST['borrow_date'] ?? $borrowing_data['borrow_date']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="due_date" class="form-label">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo htmlspecialchars($_POST['due_date'] ?? $borrowing_data['due_date']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="borrowed" <?php echo (($_POST['status'] ?? $borrowing_data['status']) == 'borrowed') ? 'selected' : ''; ?>>Dipinjam</option>
                                    <option value="returned" <?php echo (($_POST['status'] ?? $borrowing_data['status']) == 'returned') ? 'selected' : ''; ?>>Dikembalikan</option>
                                    <option value="overdue" <?php echo (($_POST['status'] ?? $borrowing_data['status']) == 'overdue') ? 'selected' : ''; ?>>Terlambat</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="return_date" class="form-label">Tanggal Kembali</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo htmlspecialchars($_POST['return_date'] ?? $borrowing_data['return_date'] ?? ''); ?>">
                                <div class="form-text">Isi hanya jika status adalah 'Dikembalikan'. Secara otomatis terisi saat mengklik tombol 'Kembalikan' dari daftar.</div>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Update Transaksi
                                </button>
                                <a href="borrowings.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; // Sertakan footer di sini 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // JavaScript untuk mengelola field Tanggal Kembali berdasarkan Status
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status');
            const returnDateInput = document.getElementById('return_date');

            function toggleReturnDate() {
                if (statusSelect.value === 'returned') {
                    returnDateInput.removeAttribute('disabled');
                    returnDateInput.setAttribute('required', 'required');
                    // Set default to today's date if empty when status is changed to returned
                    if (!returnDateInput.value) {
                        const today = new Date();
                        const year = today.getFullYear();
                        const month = String(today.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
                        const day = String(today.getDate()).padStart(2, '0');
                        returnDateInput.value = `${year}-${month}-${day}`;
                    }
                } else {
                    returnDateInput.setAttribute('disabled', 'disabled');
                    returnDateInput.removeAttribute('required');
                    returnDateInput.value = ''; // Clear value if not 'returned'
                }
            }

            // Initial call on page load
            toggleReturnDate();

            // Add event listener for when status changes
            statusSelect.addEventListener('change', toggleReturnDate);
        });
    </script>
</body>

</html>