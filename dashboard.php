<?php
session_start();
include 'includes/db_connect.php';

// Proteksi halaman: cek apakah user sudah login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Hanya admin yang bisa mengakses dashboard ini (opsional, bisa disesuaikan)
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: index.php"); // Atau halaman lain untuk user non-admin
// //     exit;
// }

include 'includes/header.php'; // Pastikan header ini menyertakan Font Awesome dan Bootstrap 5 CSS

// Ambil data untuk kartu
$total_books = 0;
$result_books = $conn->query("SELECT COUNT(*) AS total_books FROM books");
if ($result_books) {
    $data_books = $result_books->fetch_assoc();
    $total_books = $data_books['total_books'];
}

$total_members = 0;
$result_members = $conn->query("SELECT COUNT(*) AS total_members FROM members");
if ($result_members) {
    $data_members = $result_members->fetch_assoc();
    $total_members = $data_members['total_members'];
}

$active_borrowings = 0;
$result_borrowings = $conn->query("SELECT COUNT(*) AS active_borrowings FROM borrowings WHERE status = 'borrowed'");
if ($result_borrowings) {
    $data_borrowings = $result_borrowings->fetch_assoc();
    $active_borrowings = $data_borrowings['active_borrowings'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Perpustakaan Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4C+0RkL1oQ5wS2t9i92t5J1n/w6L0/BfR0E01I+7j8f/2pW7d0w2b0pW2w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General Body Styles */
        body {
            background-color: #f0f4f8;
            /* Soft blue-gray for main background */
            font-family: 'Poppins', sans-serif;
            color: #344767;
            /* Darker, slightly muted text color */
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            /* Subtle background pattern */
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM2 34v-4H0v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0H0v4h-4v2h4v4h2V6h4V4h-4z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            background-repeat: repeat;
        }

        /* Jumbotron Hero Section */
        .jumbotron-hero {
            background: linear-gradient(145deg, #4CAF50 0%, #2E7D32 100%);
            /* Elegant green gradient */
            color: white;
            padding: 6rem 3rem;
            /* Generous padding */
            border-radius: 1rem;
            /* More pronounced rounded corners */
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
            /* Deeper, softer shadow */
            position: relative;
            overflow: hidden;
            width: 100%;
            /* Full width */
            text-align: center;
            margin-bottom: 3rem;
            /* Space below jumbotron */
            z-index: 1;
            /* Ensure it's above body background */
        }

        /* Jumbotron subtle pattern overlay */
        .jumbotron-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='10' height='10' viewBox='0 0 10 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'%3E%3Cpath d='M5 0h1L0 6V5zm-.173 1L6 2.173V0H5.827zm1.745 4L5 6H4.173L6 4.173zM1 6h1L6 2V1zm-.173-1L0 3.827V6h.173zM4.173 0L0 4.173V0h4.173z'/%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.15;
            /* More visible pattern on jumbotron */
            z-index: 0;
        }

        .jumbotron-hero .container {
            position: relative;
            z-index: 1;
            /* Bring content to front */
        }

        .jumbotron-hero h1 {
            font-size: 4rem;
            /* Significantly larger heading */
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.35);
            /* Deeper text shadow */
            margin-bottom: 1rem;
        }

        .jumbotron-hero .lead {
            font-size: 1.5rem;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .jumbotron-hero .btn {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            /* Smooth transition */
            border-radius: 0.75rem;
            /* Consistent button radius */
            padding: 1rem 2.5rem;
            /* Larger button padding */
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .jumbotron-hero .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.7);
            /* Slightly thicker border for outline */
            color: white;
            background-color: transparent;
        }

        .jumbotron-hero .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.15);
            /* Light fill on hover */
            transform: translateY(-3px);
            /* Subtle lift */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
            /* Enhanced shadow on hover */
            border-color: white;
            /* Solid white border on hover */
        }

        /* Dashboard Cards */
        .card-dashboard {
            border: none;
            /* No default border */
            border-radius: 1.25rem;
            /* More rounded corners for cards */
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            /* Soft, elegant shadow */
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            /* Slower, smoother transition for dramatic effect */
            background-color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-dashboard:hover {
            transform: translateY(-10px);
            /* More pronounced lift effect */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            /* Deeper shadow on hover */
        }

        .card-dashboard .card-body {
            padding: 3rem;
            /* Generous internal padding */
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .card-dashboard .card-title {
            font-size: 1.8rem;
            /* Larger card title */
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            /* Space between icon and text */
            color: #344767;
            /* Default card title color */
        }

        .card-dashboard .card-title i {
            font-size: 2em;
            /* Larger icon relative to text */
            opacity: 0.8;
            /* Slightly muted icon color */
        }

        /* Specific colors for card titles and icons */
        .card-dashboard .card-title.text-primary i {
            color: #007bff;
        }

        .card-dashboard .card-title.text-success i {
            color: #28a745;
        }

        .card-dashboard .card-title.text-info i {
            color: #17a2b8;
        }

        .card-dashboard .card-title.text-warning i {
            color: #ffc107;
        }

        .card-dashboard .card-title.text-danger i {
            color: #dc3545;
        }

        .card-dashboard .card-text {
            font-size: 1.15rem;
            /* Larger, more readable text */
            color: #6c757d;
            margin-bottom: 2rem;
            /* More space below text */
        }

        .card-dashboard .card-text strong {
            display: block;
            font-size: 4.5rem;
            /* Very large, impactful numbers */
            font-weight: 800;
            /* Extra bold */
            line-height: 1;
            margin-top: 0.75rem;
            /* Space above number */
            color: #007bff;
            /* Default number color (primary blue) */
        }

        /* Specific strong number colors */
        .card-dashboard .card-text strong.text-primary {
            color: #007bff;
        }

        .card-dashboard .card-text strong.text-success {
            color: #28a745;
        }

        .card-dashboard .card-text strong.text-info {
            color: #17a2b8;
        }

        .card-dashboard .card-text strong.text-warning {
            color: #ffc107;
        }

        .card-dashboard .card-text strong.text-danger {
            color: #dc3545;
        }

        /* Buttons within Cards */
        .card-dashboard .btn {
            padding: 0.8rem 2.2rem;
            /* Slightly larger, more tactile buttons */
            font-size: 1rem;
            border-radius: 2rem;
            /* Pill-shaped buttons */
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Subtle button shadow */
        }

        .card-dashboard .btn:hover {
            transform: translateY(-3px);
            /* Gentle lift on button hover */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            /* Enhanced button shadow on hover */
        }

        /* Specific button colors (ensuring text contrast for warning) */
        .card-dashboard .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .card-dashboard .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .card-dashboard .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .card-dashboard .btn-success:hover {
            background-color: #1e7e34;
            border-color: #1e7e34;
        }

        .card-dashboard .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }

        .card-dashboard .btn-info:hover {
            background-color: #117a8b;
            border-color: #117a8b;
        }

        .card-dashboard .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #343a40;
        }

        /* Text needs to be dark */
        .card-dashboard .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            color: #343a40;
        }

        .card-dashboard .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .card-dashboard .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .jumbotron-hero {
                padding: 4rem 1.5rem;
            }

            .jumbotron-hero h1 {
                font-size: 2.5rem;
            }

            .jumbotron-hero .lead {
                font-size: 1.1rem;
            }

            .card-dashboard .card-body {
                padding: 1.5rem;
            }

            .card-dashboard .card-title {
                font-size: 1.3rem;
            }

            .card-dashboard .card-title i {
                font-size: 1.8em;
            }

            .card-dashboard .card-text strong {
                font-size: 3.5rem;
            }

            .card-dashboard .btn {
                font-size: 0.9rem;
                padding: 0.6rem 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="jumbotron-hero text-center mb-5">
        <div class="container position-relative">
            <h1 class="mb-3">Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p class="lead mb-4">
                Dashboard administratif Anda untuk pengelolaan perpustakaan yang efisien dan menyeluruh.
            </p>
            <hr class="my-4 border-white opacity-50" style="border-width: 1px;">
            <p class="mb-4 lead" style="font-size: 1.15rem; font-weight: 300;">
                Pantau statistik utama, kelola aset, dan lacak aktivitas dengan cepat melalui panel interaktif di bawah.
            </p>
            <a class="btn btn-outline-light btn-lg mt-3 shadow-sm" href="logout.php" role="button">
                <i class="fas fa-power-off me-2"></i> Keluar dari Dashboard
            </a>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card card-dashboard">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-book-reader"></i> Manajemen Buku
                        </h5>
                        <p class="card-text">Total Buku Terdaftar:
                            <strong class="text-primary"><?php echo $total_books; ?></strong>
                        </p>
                        <a href="books.php" class="btn btn-primary">
                            <i class="fas fa-book me-2"></i> Lihat & Kelola Buku
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card card-dashboard">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <i class="fas fa-users-cog"></i> Manajemen Anggota
                        </h5>
                        <p class="card-text">Jumlah Anggota Aktif:
                            <strong class="text-success"><?php echo $total_members; ?></strong>
                        </p>
                        <a href="members.php" class="btn btn-success">
                            <i class="fas fa-user-friends me-2"></i> Lihat & Kelola Anggota
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card card-dashboard">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <i class="fas fa-exchange-alt"></i> Peminjaman Aktif
                        </h5>
                        <p class="card-text">Peminjaman Sedang Berlangsung:
                            <strong class="text-info"><?php echo $active_borrowings; ?></strong>
                        </p>
                        <a href="borrowings.php" class="btn btn-info">
                            <i class="fas fa-clipboard-list me-2"></i> Lacak Peminjaman
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card card-dashboard">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <i class="fas fa-chart-line"></i> Laporan & Statistik
                        </h5>
                        <p class="card-text">Analisis Data Perpustakaan Anda.</p>
                        <a href="reports.php" class="btn btn-warning">
                            <i class="fas fa-chart-bar me-2"></i> Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card card-dashboard">
                    <div class="card-body">
                        <h5 class="card-title text-danger">
                            <i class="fas fa-cogs"></i> Pengaturan Sistem
                        </h5>
                        <p class="card-text">Konfigurasi Aplikasi Perpustakaan.</p>
                        <a href="settings.php" class="btn btn-danger">
                            <i class="fas fa-sliders-h me-2"></i> Sesuaikan Pengaturan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>

<?php
$conn->close(); // Tutup koneksi di akhir halaman
include 'includes/footer.php';
?>