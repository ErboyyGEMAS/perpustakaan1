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

// Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan data input
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $phone_number = $conn->real_escape_string(trim($_POST['phone_number'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));

    // Validasi sederhana
    if (empty($name) || empty($phone_number) || empty($email)) {
        $error_message = "Nama, Nomor Telepon, dan Email harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } elseif (!preg_match("/^[0-9\-\(\)\s\+]+$/", $phone_number)) { // Validasi nomor telepon sederhana
        $error_message = "Format nomor telepon tidak valid. Hanya angka, +, -, (, ) dan spasi yang diizinkan.";
    } else {
        // Cek duplikasi email atau nomor telepon
        $check_duplicate_sql = "SELECT id FROM members WHERE email = ? OR phone_number = ?";
        $stmt_check_duplicate = $conn->prepare($check_duplicate_sql);
        $stmt_check_duplicate->bind_param("ss", $email, $phone_number);
        $stmt_check_duplicate->execute();
        $stmt_check_duplicate->store_result();
        if ($stmt_check_duplicate->num_rows > 0) {
            $error_message = "Email atau Nomor Telepon ini sudah terdaftar untuk anggota lain.";
        } else {
            // Prepared statement untuk insert data
            $sql = "INSERT INTO members (name, address, phone_number, email) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // 'ssss' artinya: string, string, string, string
            $stmt->bind_param("ssss", $name, $address, $phone_number, $email);

            if ($stmt->execute()) {
                $success_message = "Anggota <strong>" . htmlspecialchars($name) . "</strong> berhasil ditambahkan!";
                // Opsional: kosongkan form setelah sukses
                $_POST = array(); // Clear form fields
            } else {
                $error_message = "Terjadi kesalahan saat menambahkan anggota: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check_duplicate->close();
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
        color: #fd7e14;
        /* Orange color for member icon */
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
        border-color: #fd7e14;
        /* Orange border on focus */
        box-shadow: 0 0 0 0.3rem rgba(253, 126, 20, 0.25);
        /* Broader, softer focus shadow */
        background-color: #fff9ed;
        /* Very light orange background on focus */
    }

    .form-text {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 0.5rem;
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
        background: linear-gradient(45deg, #28a745 0%, #218838 100%);
        /* Green gradient for primary button */
        border: none;
        border-radius: 2.5rem;
        padding: 0.9rem 2.5rem;
        font-size: 1.15rem;
        font-weight: 600;
        transition: all 0.3s ease-in-out;
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(45deg, #218838 0%, #1e7e34 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
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

<div class="container py-4">
    <h2 class="page-title">
        <i class="fas fa-user-plus"></i> Tambah Anggota Baru
    </h2>

    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            <div class="form-card">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-custom" role="alert">
                        <i class="fas fa-times-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-custom" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form action="add_member.php" method="POST">
                    <div class="mb-4">
                        <label for="name" class="form-label">Nama Anggota <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Nama lengkap anggota">
                    </div>
                    <div class="mb-4">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Alamat lengkap anggota"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="phone_number" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>" required placeholder="Contoh: +6281234567890">
                        <div class="form-text">Gunakan format internasional jika memungkinkan (misal: +62...).</div>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Alamat email anggota (misal: nama@contoh.com)">
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> Simpan Anggota
                        </button>
                        <a href="members.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>