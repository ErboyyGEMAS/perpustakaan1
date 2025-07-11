<?php

namespace App\Classes;

use mysqli;

class Database
{
    private static $instance = null;
    private $conn;

    // Konfigurasi koneksi database Anda (INI YANG HARUS DIUBAH UNTUK HOSTING)
    private $host = "HOST_DATABASE_ANDA"; // <--- INI PENTING! DAPATKAN DARI PENYEDIA HOSTING
    private $user = "ertd3715_erga";      // <--- USERNAME DATABASE DARI HOSTING ANDA
    private $pass = "erga213_";           // <--- PASSWORD DATABASE DARI HOSTING ANDA
    private $db_name = "ertd3715_db_perpustakaan"; // <--- INI JUGA PENTING! DAPATKAN DARI PENYEDIA HOSTING


    private function __construct()
    {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);

        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4"); // Mengatur charset untuk dukungan karakter penuh
    }

    // Metode statis publik untuk mendapatkan instance tunggal dari kelas Database
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Metode publik untuk mendapatkan objek koneksi mysqli
    public function getConnection()
    {
        return $this->conn;
    }

    // Mencegah kloning instance (bagian dari pola Singleton)
    private function __clone() {}

    // Mencegah unserialisasi instance (bagian dari pola Singleton)
    public function __wakeup() {}
}
