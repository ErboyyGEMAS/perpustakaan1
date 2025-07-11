<?php
session_start();
include 'includes/db_connect.php';

// Proteksi halaman: hanya user yang login yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Hanya admin yang bisa mengelola anggota (menambah, edit, hapus)
// Anggota biasa bisa melihat daftar saja
$isAdmin = ($_SESSION['role'] === 'admin');

// Inisialisasi variabel pencarian
$search_query = "";
$where_clause = "";
$params = [];
$types = "";

// Tangani pencarian
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $where_clause = " WHERE name LIKE ? OR phone_number LIKE ? OR email LIKE ?";
    $params = ["%{$search_query}%", "%{$search_query}%", "%{$search_query}%"];
    $types = "sss";
}

// Ambil data anggota dari database
$sql = "SELECT id, name, address, phone_number, email, registered_at FROM members";

$sql .= $where_clause;
$sql .= " ORDER BY name ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$members = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
}
$stmt->close();
$conn->close();

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
    }

    .page-title i {
        font-size: 1.2em;
        color: #28a745;
        /* Green color for member icon */
    }

    .search-form-container {
        padding: 1.5rem;
        background-color: #f8f9fa;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
    }

    .form-control-search {
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        border: 1px solid #ced4da;
        transition: all 0.3s ease;
    }

    .form-control-search:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    }

    .btn-search {
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-reset {
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-add-member {
        border-radius: 0.5rem;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        transition: all 0.3s ease;
    }

    .btn-add-member:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
    }

    /* Table Styling */
    .table-container {
        background-color: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-top: 2rem;
    }

    .table-custom {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-custom thead th {
        background-color: #28a745;
        /* Success green header */
        color: white;
        padding: 1.2rem 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-bottom: none;
    }

    .table-custom thead tr:first-child th:first-child {
        border-top-left-radius: 1rem;
    }

    .table-custom thead tr:first-child th:last-child {
        border-top-right-radius: 1rem;
    }

    .table-custom tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-custom tbody tr:hover {
        background-color: #e6faed;
        /* Light green on hover */
    }

    .table-custom tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    /* Action Buttons in Table */
    .btn-action {
        border-radius: 0.4rem;
        padding: 0.5rem 0.8rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-action.btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #343a40;
        /* Dark text on yellow */
    }

    .btn-action.btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
        color: #343a40;
    }

    /* Alert for no members */
    .alert-info-custom {
        background-color: #e6faed;
        /* Light green */
        color: #28a745;
        /* Green text */
        border: 1px solid #c3e6cb;
        border-radius: 0.75rem;
        padding: 2rem;
        text-align: center;
        font-size: 1.2rem;
        margin-top: 3rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .alert-info-custom .alert-link {
        color: #1e7e34;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .alert-info-custom .alert-link:hover {
        text-decoration: underline;
    }
</style>

<div class="container-fluid py-4">
    <h2 class="page-title">
        <i class="fas fa-users-line"></i> Daftar Anggota Perpustakaan
    </h2>

    <div class="row mb-4 align-items-center search-form-container">
        <div class="col-md-7 col-lg-8">
            <form action="" method="GET" class="d-flex flex-grow-1">
                <div class="input-group">
                    <input type="text" name="search" class="form-control form-control-search" placeholder="Cari nama, nomor telepon, atau email..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary btn-search">
                        <i class="fas fa-search me-2"></i> Cari
                    </button>
                    <?php if (!empty($search_query)): ?>
                        <a href="members.php" class="btn btn-outline-secondary btn-reset ms-2">
                            <i class="fas fa-redo me-2"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php if ($isAdmin): ?>
            <div class="col-md-5 col-lg-4 text-md-end mt-3 mt-md-0">
                <a href="add_member.php" class="btn btn-success btn-add-member">
                    <i class="fas fa-user-plus me-2"></i> Tambah Anggota Baru
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($members)): ?>
        <div class="alert alert-info-custom" role="alert">
            <p><i class="fas fa-info-circle me-2"></i> Belum ada anggota terdaftar atau tidak ditemukan dengan pencarian Anda.</p>
            <?php if ($isAdmin): ?>
                <p>Silakan <a href="add_member.php" class="alert-link"><i class="fas fa-user-plus me-2"></i>tambah anggota baru</a> untuk memulai.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-custom">
                    <thead>
                        <tr>
                            <th scope="col">#ID</th>
                            <th scope="col">Nama Lengkap</th>
                            <th scope="col">Alamat</th>
                            <th scope="col">Telepon</th>
                            <th scope="col">Email</th>
                            <th scope="col">Tgl Daftar</th>
                            <?php if ($isAdmin): ?>
                                <th scope="col">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['id']); ?></td>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($member['name']); ?></td>
                                <td><?php echo htmlspecialchars($member['address']); ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($member['phone_number']); ?></span></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($member['email']); ?> <i class="fas fa-external-link-alt fa-xs ms-1 text-muted"></i></a></td>
                                <td class="text-muted small"><?php echo date('d M Y, H:i', strtotime($member['registered_at'])); ?></td>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <a href="edit_member.php?id=<?php echo $member['id']; ?>" class="btn btn-warning btn-sm btn-action me-2" title="Edit Anggota">
                                            <i class="fas fa-user-edit"></i> Edit
                                        </a>
                                        <a href="delete_member.php?id=<?php echo $member['id']; ?>" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Apakah Anda yakin ingin menghapus anggota ini? Ini juga akan menghapus transaksi peminjaman yang terkait.');" title="Hapus Anggota">
                                            <i class="fas fa-user-minus"></i> Hapus
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>