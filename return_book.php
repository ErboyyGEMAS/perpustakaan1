<?php
session_start();
include 'includes/db_connect.php';

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$borrowing_id = null;
$message = '';
$message_type = 'danger'; // Default ke danger

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $borrowing_id = (int)$_GET['id'];

    // Mulai transaksi database
    $conn->begin_transaction();

    try {
        // 1. Ambil data peminjaman untuk verifikasi dan mendapatkan book_id
        $stmt_get_borrowing = $conn->prepare("SELECT book_id, status FROM borrowings WHERE id = ? FOR UPDATE"); // Lock row
        $stmt_get_borrowing->bind_param("i", $borrowing_id);
        $stmt_get_borrowing->execute();
        $result_get_borrowing = $stmt_get_borrowing->get_result();

        if ($result_get_borrowing->num_rows === 0) {
            throw new Exception("Transaksi peminjaman tidak ditemukan.");
        }

        $borrowing_data = $result_get_borrowing->fetch_assoc();
        $book_id = $borrowing_data['book_id'];
        $current_status = $borrowing_data['status'];
        $stmt_get_borrowing->close();

        // Cek apakah buku sudah dikembalikan
        if ($current_status === 'returned') {
            throw new Exception("Buku ini sudah dikembalikan sebelumnya.");
        }

        // 2. Perbarui status peminjaman di tabel borrowings
        $sql_update_borrowing = "UPDATE borrowings SET return_date = CURDATE(), status = 'returned' WHERE id = ?";
        $stmt_update_borrowing = $conn->prepare($sql_update_borrowing);
        $stmt_update_borrowing->bind_param("i", $borrowing_id);

        if (!$stmt_update_borrowing->execute()) {
            throw new Exception("Gagal memperbarui status peminjaman: " . $stmt_update_borrowing->error);
        }
        $stmt_update_borrowing->close();

        // 3. Tambahkan kembali kuantitas buku di tabel books
        $sql_update_book_quantity = "UPDATE books SET quantity = quantity + 1 WHERE id = ?";
        $stmt_update_book_quantity = $conn->prepare($sql_update_book_quantity);
        $stmt_update_book_quantity->bind_param("i", $book_id);

        if (!$stmt_update_book_quantity->execute()) {
            throw new Exception("Gagal menambahkan stok buku: " . $stmt_update_book_quantity->error);
        }
        $stmt_update_book_quantity->close();

        // Jika semua berhasil, commit transaksi
        $conn->commit();
        $message = "Buku berhasil dikembalikan!";
        $message_type = 'success';
    } catch (Exception $e) {
        // Jika ada kesalahan, rollback transaksi
        $conn->rollback();
        $message = "Terjadi kesalahan saat mengembalikan buku: " . $e->getMessage();
        $message_type = 'danger';
    }
} else {
    $message = "ID peminjaman tidak diberikan.";
    $message_type = 'danger';
}

$conn->close();

// Redirect kembali ke halaman borrowings.php dengan pesan
header("Location: borrowings.php?message_type=" . $message_type . "&message=" . urlencode($message));
exit;
