<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/CategoryRepository.php';

use App\Classes\Database;
use App\Classes\CategoryRepository;

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error_message = '';
$success_message = '';
$edit_category_id = null;
$edit_category_name = '';

$database = Database::getInstance();
$categoryRepository = new CategoryRepository($database);

// --- Proses Form Tambah/Edit Kategori ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name'] ?? '');
    $action = $_POST['action'] ?? ''; // 'add' or 'edit'
    $id = (int)($_POST['id'] ?? 0); // Only for edit action

    if (empty($category_name)) {
        $error_message = "Nama kategori tidak boleh kosong.";
    } elseif ($action === 'edit' && $id <= 0) {
        $error_message = "ID kategori tidak valid untuk diedit.";
    } else {
        if ($action === 'add') {
            // Cek duplikasi saat menambah
            if ($categoryRepository->isCategoryNameExists($category_name)) {
                $error_message = "Kategori dengan nama ini sudah ada.";
            } elseif ($categoryRepository->addCategory($category_name)) {
                $success_message = "Kategori '<strong>" . htmlspecialchars($category_name) . "</strong>' berhasil ditambahkan.";
            } else {
                $error_message = "Gagal menambahkan kategori.";
            }
        } elseif ($action === 'edit') {
            // Cek duplikasi saat mengedit (kecuali untuk dirinya sendiri)
            if ($categoryRepository->isCategoryNameExists($category_name, $id)) {
                $error_message = "Kategori dengan nama ini sudah ada.";
            } elseif ($categoryRepository->updateCategory($id, $category_name)) {
                $success_message = "Kategori '<strong>" . htmlspecialchars($category_name) . "</strong>' berhasil diperbarui.";
            } else {
                $error_message = "Gagal memperbarui kategori.";
            }
        }
    }
}

// --- Proses Aksi Hapus Kategori ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $id_to_delete = (int)$_GET['id'];
    if ($categoryRepository->deleteCategory($id_to_delete)) {
        $success_message = "Kategori berhasil dihapus.";
    } else {
        $error_message = "Gagal menghapus kategori.";
    }
    // Redirect untuk membersihkan parameter GET setelah aksi
    // Menggunakan ternary operator untuk memastikan pesan terisi bahkan jika deleteCategory() gagal
    header("Location: settings.php?message_type=" . (empty($error_message) ? 'success' : 'danger') . "&message=" . urlencode($success_message ?: $error_message));
    exit;
}

// --- Ambil Data Kategori untuk Edit Form (jika ada parameter edit di URL) ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $edit_category_id = (int)$_GET['id'];
    $category_to_edit = $categoryRepository->getCategoryById($edit_category_id);
    if ($category_to_edit) {
        $edit_category_name = $category_to_edit['category_name'];
    } else {
        $error_message = "Kategori tidak ditemukan untuk diedit.";
        $edit_category_id = null; // Reset jika tidak ditemukan
    }
}

// --- Ambil Semua Kategori untuk Tampilan Tabel ---
$categories = $categoryRepository->getAllCategories();

include 'includes/header.php';
?>

<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-gear-fill me-2"></i> Pengaturan Sistem</h2>

    <?php
    $display_message = '';
    $display_message_type = '';
    // Prioritaskan pesan dari GET (setelah redirect dari hapus)
    if (isset($_GET['message']) && isset($_GET['message_type'])) {
        $display_message = htmlspecialchars($_GET['message']);
        $display_message_type = htmlspecialchars($_GET['message_type']);
    }
    // Jika tidak ada pesan dari GET, cek pesan dari POST
    if (empty($display_message) && !empty($error_message)) {
        $display_message = $error_message;
        $display_message_type = 'danger';
    } elseif (empty($display_message) && !empty($success_message)) {
        $display_message = $success_message;
        $display_message_type = 'success';
    }

    if (!empty($display_message)): ?>
        <div class="alert alert-<?php echo $display_message_type; ?> alert-custom" role="alert">
            <i class="bi <?php echo ($display_message_type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'); ?> me-2"></i> <?php echo $display_message; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-tags-fill me-2"></i> <?php echo ($edit_category_id ? 'Edit Kategori' : 'Tambah Kategori Baru'); ?></h5>
        </div>
        <div class="card-body">
            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="<?php echo ($edit_category_id ? 'edit' : 'add'); ?>">
                <?php if ($edit_category_id): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_category_id); ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="category_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="category_name" name="category_name"
                        value="<?php echo htmlspecialchars($edit_category_name); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi <?php echo ($edit_category_id ? 'bi-pencil-square' : 'bi-plus-circle'); ?> me-2"></i>
                    <?php echo ($edit_category_id ? 'Update Kategori' : 'Tambah Kategori'); ?>
                </button>
                <?php if ($edit_category_id): ?>
                    <a href="settings.php" class="btn btn-secondary ms-2"><i class="bi bi-x-circle me-2"></i> Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <h3 class="mb-4"><i class="bi bi-list-nested me-2"></i> Daftar Kategori Buku</h3>
    <?php if (empty($categories)): ?>
        <div class="alert alert-info" role="alert">Belum ada kategori yang terdaftar.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['id']); ?></td>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td>
                                <a href="settings.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil me-1"></i> Edit</a>
                                <a href="settings.php?action=delete&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Buku yang terkait akan kehilangan kategorinya.');"><i class="bi bi-trash me-1"></i> Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>