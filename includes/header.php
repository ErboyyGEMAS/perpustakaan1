<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Perpustakaan Kita - Kelola Buku dan Anggota</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4C+0RkL1oQ5wS2t9i92t5J1n/w6L0/BfR0E01I+7j8f/2pW7d0w2b0pW2w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin-top: 70px;
            /* Penting: agar konten tidak tertutup fixed navbar */
            background-color: #f0f4f8;
            /* Warna latar belakang umum yang bersih */
        }

        /* Navbar Styling */
        .navbar {
            /* Perpaduan warna biru gelap ke navy */
            background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            /* Bayangan yang lebih menonjol dan elegan */
            transition: all 0.3s ease;
            padding-top: 0.8rem;
            /* Padding vertikal sedikit lebih banyak */
            padding-bottom: 0.8rem;
        }

        .navbar-brand {
            font-weight: 700;
            /* Lebih tebal */
            font-size: 1.6rem;
            /* Ukuran lebih besar */
            letter-spacing: 1px;
            /* Jarak antar huruf */
            color: #ffffff !important;
            /* Putih bersih */
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            /* Bayangan teks yang halus */
            transition: transform 0.2s ease-out;
        }

        .navbar-brand:hover {
            transform: scale(1.02);
            /* Sedikit membesar saat hover */
        }

        .navbar-brand i {
            font-size: 1.2em;
            /* Icon sedikit lebih besar dari teks */
            margin-right: 10px;
            /* Jarak icon dari teks */
            color: #ecf0f1;
            /* Warna icon yang sedikit off-white */
        }

        /* Nav Links Styling */
        .navbar-nav .nav-item {
            margin-right: 15px;
            /* Jarak antar item menu lebih luas */
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.7) !important;
            /* Warna teks link default */
            font-weight: 500;
            /* Ketebalan sedang */
            padding: 0.75rem 1.2rem;
            /* Padding lebih nyaman */
            transition: all 0.3s ease-in-out;
            position: relative;
            border-radius: 0.4rem;
            /* Sudut membulat pada link */
            text-decoration: none;
            /* Pastikan tidak ada underline default */
        }

        .navbar-nav .nav-link:hover {
            color: #ffffff !important;
            /* Putih penuh saat hover */
            background-color: rgba(255, 255, 255, 0.1);
            /* Latar belakang transparan saat hover */
            transform: translateY(-2px);
            /* Efek mengangkat */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            /* Bayangan saat mengangkat */
        }

        /* Active Link Indicator */
        .navbar-nav .nav-link.active {
            color: #ffffff !important;
            font-weight: 600;
            /* Lebih tebal saat aktif */
            background-color: rgba(255, 255, 255, 0.15);
            /* Latar belakang lebih jelas saat aktif */
        }

        .navbar-nav .nav-link.active::after {
            content: '';
            position: absolute;
            width: 80%;
            /* Lebar garis bawah */
            height: 3px;
            /* Ketebalan garis */
            background: #3498db;
            /* Warna biru cerah untuk garis bawah */
            left: 10%;
            /* Tengah */
            bottom: -5px;
            /* Posisi di bawah teks */
            border-radius: 2px;
            transform: scaleX(1);
            /* Pastikan terlihat */
            transition: transform 0.4s ease-out;
            /* Animasi muncul */
        }

        /* Efek garis bawah saat hover (jika tidak aktif) */
        .navbar-nav .nav-link:not(.active)::after {
            content: '';
            position: absolute;
            width: 80%;
            height: 3px;
            background: #3498db;
            left: 10%;
            bottom: -5px;
            border-radius: 2px;
            transform: scaleX(0);
            /* Tersembunyi default */
            transition: transform 0.4s ease-out;
        }

        .navbar-nav .nav-link:not(.active):hover::after {
            transform: scaleX(1);
            /* Muncul saat hover */
        }

        /* Login/Logout Button Styling */
        .navbar-nav .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
            /* Border transparan */
            color: #fff;
            background-color: transparent;
            font-weight: 500;
            padding: 0.6rem 1.8rem;
            /* Padding tombol */
            border-radius: 2rem;
            /* Bentuk kapsul */
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            /* Bayangan tombol */
            margin-left: 15px;
            /* Jarak dari menu lain (untuk desktop) */
        }

        .navbar-nav .btn-outline-light:hover {
            background-color: #3498db;
            /* Isi warna biru saat hover */
            border-color: #3498db;
            color: white;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.4);
            /* Bayangan biru saat hover */
            transform: translateY(-2px);
            /* Sedikit mengangkat */
        }

        /* Navbar Toggler for Mobile */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
            font-size: 1.1rem;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.7%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Responsive Adjustments for small screens */
        @media (max-width: 991.98px) {

            /* breakpoint for lg (large) screens */
            .navbar-nav .nav-item {
                margin-right: 0;
                margin-bottom: 5px;
                /* Space between items in stacked view */
            }

            .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
                text-align: center;
                /* Center align links when stacked */
            }

            .navbar-nav .btn-outline-light {
                margin-left: 0;
                margin-top: 10px;
                /* Space for button when stacked */
                width: 100%;
                /* Full width button */
            }

            .navbar-nav .nav-link.active::after,
            .navbar-nav .nav-link:not(.active)::after {
                bottom: -2px;
                /* Adjust underline position for smaller screens */
                height: 2px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="../perpustakaan/index.php">
                <i class="fas fa-book-open me-2"></i> Perpustakaan Kita
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" aria-current="page" href="../perpustakaan/index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'books.php') ? 'active' : ''; ?>" href="../perpustakaan/books.php">Daftar Buku</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'members.php') ? 'active' : ''; ?>" href="../perpustakaan/members.php">Anggota</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'borrowings.php') ? 'active' : ''; ?>" href="../perpustakaan/borrowings.php">Peminjaman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>" href="../perpustakaan/reports.php">Laporan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>" href="../perpustakaan/settings.php">Pengaturan</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php
                    // PHP Logic for Login/Logout Button
                    // Bagian ini memerlukan session_start() di file utama sebelum include header.php
                    // Agar tidak ada error, saya akan asumsikan $loggedIn sudah didefinisikan atau set default.
                    $loggedIn = false; // Default value, agar tidak ada error Notice
                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                        $loggedIn = true;
                    }

                    if ($loggedIn) {
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link ' . ((basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '') . '" href="../perpustakaan/dashboard.php">Dashboard</a>';
                        echo '</li>';
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link btn btn-outline-light btn-sm ms-2" href="../perpustakaan/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>';
                        echo '</li>';
                    } else {
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link btn btn-outline-light btn-sm ms-2" href="../perpustakaan/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login Admin</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid main-content-wrapper">

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>