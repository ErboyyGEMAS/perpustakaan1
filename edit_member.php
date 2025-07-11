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
$member_data = []; // Untuk menyimpan data anggota yang akan diedit

// 1. Ambil ID anggota dari URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $member_id = (int)$_GET['id'];

    // Ambil data anggota yang akan diedit
    $sql_get_member = "SELECT id, name, address, phone_number, email FROM members WHERE id = ?";
    $stmt_get_member = $conn->prepare($sql_get_member);
    $stmt_get_member->bind_param("i", $member_id);
    $stmt_get_member->execute();
    $result_get_member = $stmt_get_member->get_result();

    if ($result_get_member->num_rows === 1) {
        $member_data = $result_get_member->fetch_assoc();
    } else {
        $error_message = "Anggota tidak ditemukan.";
    }
    $stmt_get_member->close();
} else {
    $error_message = "ID anggota tidak diberikan.";
}

// 2. Proses form jika ada data POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_message)) { // Lanjutkan hanya jika tidak ada error dari GET
    // Ambil ID anggota dari POST (untuk memastikan ID yang sama)
    $member_id_post = (int)$_POST['member_id'];

    // Jika ID dari GET dan POST tidak cocok, ada masalah
    if ($member_id_post !== $member_data['id']) {
        $error_message = "ID anggota tidak cocok, operasi dibatalkan.";
    } else {
        // Ambil dan bersihkan data input
        $name = $conn->real_escape_string(trim($_POST['name']));
        $address = $conn->real_escape_string(trim($_POST['address']));
        $phone_number = $conn->real_escape_string(trim($_POST['phone_number']));
        $email = $conn->real_escape_string(trim($_POST['email']));

        // Validasi sederhana
        if (empty($name) || empty($phone_number) || empty($email)) {
            $error_message = "Nama, Nomor Telepon, dan Email harus diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Format email tidak valid.";
        } elseif (!preg_match("/^[0-9\-\(\)\s\+]+$/", $phone_number)) {
            $error_message = "Format nomor telepon tidak valid. Hanya angka, +, -, (, ) dan spasi yang diizinkan.";
        } else {
            // Cek duplikasi email atau nomor telepon, kecuali untuk anggota yang sedang diedit
            $check_duplicate_sql = "SELECT id FROM members WHERE (email = ? OR phone_number = ?) AND id != ?";
            $stmt_check_duplicate = $conn->prepare($check_duplicate_sql);
            $stmt_check_duplicate->bind_param("ssi", $email, $phone_number, $member_id);
            $stmt_check_duplicate->execute();
            $stmt_check_duplicate->store_result();
            if ($stmt_check_duplicate->num_rows > 0) {
                $error_message = "Email atau Nomor Telepon ini sudah terdaftar untuk anggota lain.";
            } else {
                // Prepared statement untuk update data
                $sql = "UPDATE members SET name = ?, address = ?, phone_number = ?, email = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                // 'ssssi' artinya: string, string, string, string, integer
                $stmt->bind_param("ssssi", $name, $address, $phone_number, $email, $member_id);

                if ($stmt->execute()) {
                    $success_message = "Anggota <strong>" . htmlspecialchars($name) . "</strong> berhasil diperbarui!";
                    // Opsional: Muat ulang data anggota setelah update agar form menunjukkan data terbaru
                    $member_data['name'] = $name;
                    $member_data['address'] = $address;
                    $member_data['phone_number'] = $phone_number;
                    $member_data['email'] = $email;
                    // Atau, bisa juga redirect ke halaman daftar anggota
                    // header("Location: members.php?message_type=success&message=" . urlencode("Anggota berhasil diperbarui!"));
                    // exit;
                } else {
                    $error_message = "Terjadi kesalahan saat memperbarui anggota: " . $stmt->error;
                }
            }
            $stmt_check_duplicate->close();
        }
    }
}
$conn->close(); // Tutup koneksi di akhir halaman
include 'includes/header.php';
?>

<h2 class="mb-4">Edit Anggota</h2>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error_message; ?>
    </div>
    <a href="members.php" class="btn btn-secondary mb-3">Kembali ke Daftar Anggota</a>
<?php else: // Tampilkan form hanya jika tidak ada error awal 
?>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <form action="edit_member.php?id=<?php echo htmlspecialchars($member_data['id']); ?>" method="POST">
        <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member_data['id']); ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Nama Anggota <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($member_data['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Alamat</label>
            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($member_data['address'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="phone_number" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($member_data['phone_number'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member_data['email'] ?? ''); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Anggota</button>
        <a href="members.php" class="btn btn-secondary">Kembali ke Daftar Anggota</a>
    </form>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>