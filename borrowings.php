<?php
session_start();
include 'includes/db_connect.php';

// Proteksi halaman: hanya user yang login yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Hanya admin yang bisa mengelola peminjaman
$isAdmin = ($_SESSION['role'] === 'admin');

// Inisialisasi variabel pencarian
$search_query = "";
$status_filter = ""; // Filter berdasarkan status peminjaman (borrowed, returned, overdue)
$where_clause = "";
$params = [];
$types = "";

// Tangani filter status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $allowed_statuses = ['borrowed', 'returned', 'overdue'];
    if (in_array($_GET['status'], $allowed_statuses)) {
        $status_filter = $_GET['status'];
        $where_clause .= ($where_clause ? " AND " : " WHERE ") . "b.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
}

// Tangani pencarian (judul buku atau nama anggota)
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $search_pattern = "%{$search_query}%";
    $where_clause .= ($where_clause ? " AND " : " WHERE ") . "(book.title LIKE ? OR member.name LIKE ?)";
    $params[] = $search_pattern;
    $params[] = $search_pattern;
    $types .= "ss";
}

// Ambil data peminjaman dari database
// Join dengan tabel books dan members untuk mendapatkan detail nama
$sql = "SELECT b.id, book.title AS book_title, member.name AS member_name,
               b.borrow_date, b.due_date, b.return_date, b.status
        FROM borrowings b
        JOIN books book ON b.book_id = book.id
        JOIN members member ON b.member_id = member.id";

$sql .= $where_clause; // Tambahkan klausa WHERE jika ada filter/pencarian
$sql .= " ORDER BY b.borrow_date DESC"; // Urutkan berdasarkan tanggal pinjam terbaru

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$borrowings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $borrowings[] = $row;
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
        color: #17a2b8;
        /* Info blue for borrowing icon */
    }

    .filter-search-container {
        padding: 1.5rem;
        background-color: #f8f9fa;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
    }

    .form-control-search,
    .form-select-filter {
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        border: 1px solid #ced4da;
        transition: all 0.3s ease;
    }

    .form-control-search:focus,
    .form-select-filter:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    }

    .btn-action-filter {
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-add-borrowing {
        border-radius: 0.5rem;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        transition: all 0.3s ease;
    }

    .btn-add-borrowing:hover {
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
        background-color: #17a2b8;
        /* Info blue header */
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
        background-color: #e0f7fa;
        /* Light cyan on hover */
    }

    .table-custom tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    /* Status Badges in Table */
    .badge-status {
        padding: 0.5em 0.8em;
        border-radius: 0.4rem;
        font-weight: 600;
        font-size: 0.9em;
        display: inline-flex;
        /* To align icon and text */
        align-items: center;
        gap: 5px;
    }

    .badge-status.bg-warning-light {
        background-color: #fff3cd;
        /* Light yellow */
        color: #856404;
        /* Dark yellow text */
    }

    .badge-status.bg-success-light {
        background-color: #d4edda;
        /* Light green */
        color: #155724;
        /* Dark green text */
    }

    .badge-status.bg-danger-light {
        background-color: #f8d7da;
        /* Light red */
        color: #721c24;
        /* Dark red text */
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

    .btn-info-return {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-info-return:hover {
        background-color: #138496;
        border-color: #117a8b;
    }

    /* Alert for no borrowings */
    .alert-info-custom {
        background-color: #e0f7fa;
        /* Light cyan */
        color: #17a2b8;
        /* Info blue text */
        border: 1px solid #b2ebf2;
        border-radius: 0.75rem;
        padding: 2rem;
        text-align: center;
        font-size: 1.2rem;
        margin-top: 3rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .alert-info-custom .alert-link {
        color: #138496;
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
        <i class="fas fa-handshake"></i> Manajemen Peminjaman Buku
    </h2>

    <div class="row mb-4 align-items-center filter-search-container">
        <div class="col-md-9 col-lg-9">
            <form action="" method="GET" class="d-flex flex-wrap align-items-center">
                <div class="input-group flex-grow-1 me-2 mb-2 mb-md-0">
                    <input type="text" name="search" class="form-control form-control-search" placeholder="Cari judul buku atau nama anggota..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <select name="status" class="form-select form-select-filter me-2 mb-2 mb-md-0" style="width: auto; min-width: 150px;">
                    <option value="">Semua Status</option>
                    <option value="borrowed" <?php echo ($status_filter == 'borrowed' ? 'selected' : ''); ?>>Dipinjam</option>
                    <option value="returned" <?php echo ($status_filter == 'returned' ? 'selected' : ''); ?>>Dikembalikan</option>
                    <option value="overdue" <?php echo ($status_filter == 'overdue' ? 'selected' : ''); ?>>Terlambat</option>
                </select>
                <button type="submit" class="btn btn-primary btn-action-filter me-2 mb-2 mb-md-0">
                    <i class="fas fa-filter me-2"></i> Filter / Cari
                </button>
                <?php if (!empty($search_query) || !empty($status_filter)): ?>
                    <a href="borrowings.php" class="btn btn-outline-secondary btn-action-filter">
                        <i class="fas fa-redo me-2"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($isAdmin): ?>
            <div class="col-md-3 col-lg-3 text-md-end mt-3 mt-md-0">
                <a href="add_borrowing.php" class="btn btn-success btn-add-borrowing">
                    <i class="fas fa-book-medical me-2"></i> Pinjamkan Buku Baru
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($borrowings)): ?>
        <div class="alert alert-info-custom" role="alert">
            <p><i class="fas fa-info-circle me-2"></i> Belum ada transaksi peminjaman yang tercatat atau tidak ditemukan dengan pencarian/filter Anda.</p>
            <?php if ($isAdmin): ?>
                <p>Silakan <a href="add_borrowing.php" class="alert-link"><i class="fas fa-book-medical me-2"></i>pinjamkan buku baru</a> untuk memulai.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-custom">
                    <thead>
                        <tr>
                            <th scope="col">#ID</th>
                            <th scope="col">Judul Buku</th>
                            <th scope="col">Nama Anggota</th>
                            <th scope="col">Tgl Pinjam</th>
                            <th scope="col">Tgl Jatuh Tempo</th>
                            <th scope="col">Tgl Kembali</th>
                            <th scope="col">Status</th>
                            <?php if ($isAdmin): ?>
                                <th scope="col">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowings as $borrow):
                            $status_class = '';
                            $status_text = '';
                            $due_date_obj = new DateTime($borrow['due_date']);
                            $current_date_obj = new DateTime();
                            $return_date_obj = $borrow['return_date'] ? new DateTime($borrow['return_date']) : null;

                            // Determine status and class
                            if ($borrow['status'] == 'borrowed') {
                                if ($current_date_obj > $due_date_obj) {
                                    $borrow['status'] = 'overdue'; // Update status if overdue
                                }
                            }

                            switch ($borrow['status']) {
                                case 'borrowed':
                                    $status_class = 'bg-warning-light';
                                    $status_text = '<i class="fas fa-clock"></i> Dipinjam';
                                    break;
                                case 'returned':
                                    $status_class = 'bg-success-light';
                                    $status_text = '<i class="fas fa-check-circle"></i> Dikembalikan';
                                    break;
                                case 'overdue':
                                    $status_class = 'bg-danger-light';
                                    $status_text = '<i class="fas fa-exclamation-triangle"></i> Terlambat';
                                    break;
                                default:
                                    $status_class = 'bg-secondary';
                                    $status_text = ucfirst($borrow['status']);
                                    break;
                            }
                        ?>
                            <tr class="<?php echo ($borrow['status'] == 'overdue' ? 'table-danger' : ''); ?>">
                                <td><?php echo htmlspecialchars($borrow['id']); ?></td>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                                <td class="text-success"><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                                <td class="text-muted small"><?php echo date('d M Y', strtotime($borrow['borrow_date'])); ?></td>
                                <td class="text-danger fw-semibold"><?php echo date('d M Y', strtotime($borrow['due_date'])); ?></td>
                                <td class="text-info"><?php echo $borrow['return_date'] ? date('d M Y', strtotime($borrow['return_date'])) : '<span class="text-muted fst-italic">Belum Kembali</span>'; ?></td>
                                <td>
                                    <span class="badge badge-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <?php if ($borrow['status'] == 'borrowed' || $borrow['status'] == 'overdue'): ?>
                                            <a href="return_book.php?id=<?php echo $borrow['id']; ?>" class="btn btn-info btn-sm btn-action me-2 btn-info-return" title="Tandai Dikembalikan" onclick="return confirm('Apakah buku ini sudah dikembalikan?');">
                                                <i class="fas fa-undo"></i> Kembalikan
                                            </a>
                                        <?php endif; ?>
                                        <a href="edit_borrowing.php?id=<?php echo $borrow['id']; ?>" class="btn btn-warning btn-sm btn-action me-2" title="Edit Transaksi">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_borrowing.php?id=<?php echo $borrow['id']; ?>" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi peminjaman ini? Ini tidak dapat dibatalkan!');" title="Hapus Transaksi">
                                            <i class="fas fa-trash-alt"></i> Hapus
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