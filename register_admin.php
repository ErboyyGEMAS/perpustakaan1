<?php
session_start();
include 'includes/db_connect.php'; // Pastikan koneksi database sudah benar

// Cek apakah sudah ada admin terdaftar. Jika ada, mungkin tidak perlu halaman register ini lagi.
// Ini adalah logika sederhana untuk mencegah pendaftaran admin berulang kali.
$stmt_check_admin = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt_check_admin->execute();
$stmt_check_admin->bind_result($admin_count);
$stmt_check_admin->fetch();
$stmt_check_admin->close();

if ($admin_count > 0) {
    // Jika sudah ada admin, arahkan ke halaman login
    // Anda bisa menambahkan pesan bahwa admin sudah terdaftar
    header("Location: login.php?message=admin_exists");
    exit;
}

$error_message = '';
$success_message = '';

// Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua field harus diisi.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter.";
    } else {
        // Hash password sebelum disimpan
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Masukkan user baru ke database dengan role 'admin'
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $success_message = "Akun admin berhasil dibuat! Silakan login.";
            // Setelah berhasil, Anda bisa mengarahkan ke halaman login
            // header("Location: login.php?message=registration_success");
            // exit;
        } else {
            $error_message = "Gagal membuat akun admin. Username mungkin sudah ada.";
        }
        $stmt->close();
    }
}
$conn->close(); // Tutup koneksi setelah selesai
include 'includes/header.php'; // Sertakan header
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Daftar Akun Admin Baru</h2>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?> <a href="login.php" class="alert-link">Login Sekarang</a>
                    </div>
                <?php endif; ?>

                <form action="register_admin.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Minimal 6 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Daftar Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php'; // Sertakan footer
?>