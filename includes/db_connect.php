<?php
// Konfigurasi koneksi database
$host = "localhost"; // Host database, default untuk XAMPP
$user = "root";      // Username database, default untuk XAMPP
$pass = "";          // Password database, default kosong untuk XAMPP
$db_name = "db_perpustakaan"; // Nama database yang sudah Anda buat di phpMyAdmin

// Membuat koneksi ke database menggunakan MySQLi
$conn = new mysqli($host, $user, $pass, $db_name);

// Memeriksa apakah koneksi berhasil
if ($conn->connect_error) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
    // Untuk pengembangan, die() bisa digunakan. Untuk produksi, gunakan logging error.
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset ke utf8mb4 untuk dukungan karakter yang lebih luas
$conn->set_charset("utf8mb4");

// Opsional: Untuk verifikasi awal bahwa koneksi berhasil
// echo "Koneksi database berhasil!";
