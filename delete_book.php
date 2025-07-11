<?php
session_start();
// Hapus include 'includes/db_connect.php'; jika masih ada
// Gunakan require_once untuk kelas-kelas OOP
require_once 'classes/Database.php';
require_once 'classes/BookRepository.php';

// Tambahkan USE statements untuk mengenali kelas dari namespace App\Classes
use App\Classes\Database;
use App\Classes\BookRepository;

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = 'danger'; // Default ke danger

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $book_id = (int)$_GET['id'];

    // Inisialisasi Database dan BookRepository
    // Pastikan ini adalah cara Anda menginisialisasi objek
    $database = Database::getInstance();
    $bookRepository = new BookRepository($database);

    // H Hapus buku menggunakan BookRepository (INI PENTING: TIDAK ADA LAGI $conn->prepare DI SINI)
    if ($bookRepository->deleteBook($book_id)) {
        $message = "Buku berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus buku atau buku tidak ditemukan.";
    }
} else {
    $message = "ID buku tidak diberikan.";
    $message_type = 'danger';
}

// Tidak perlu lagi $conn->close() karena koneksi dikelola oleh kelas Database Singleton.

// Redirect kembali ke halaman books.php dengan pesan
header("Location: books.php?message_type=" . $message_type . "&message=" . urlencode($message));
exit;
