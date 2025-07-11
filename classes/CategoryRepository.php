<?php

namespace App\Classes;

class CategoryRepository
{
    private $conn;

    public function __construct(Database $db)
    {
        $this->conn = $db->getConnection();
    }

    /**
     * Mengambil semua kategori dari database.
     * @return array Array asosiatif dari data kategori.
     */
    public function getAllCategories()
    {
        $categories = [];
        $sql = "SELECT id, category_name FROM categories ORDER BY category_name ASC";
        $result = $this->conn->query($sql);

        if ($result === false) {
            error_log("Error fetching categories: " . $this->conn->error);
            return [];
        }

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }

    /**
     * Mengambil data kategori berdasarkan ID.
     * @param int $id ID kategori.
     * @return array|null Data kategori sebagai array asosiatif, atau null jika tidak ditemukan.
     */
    public function getCategoryById($id)
    {
        $sql = "SELECT id, category_name FROM categories WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing getCategoryById: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();
        return $category;
    }

    /**
     * Memeriksa apakah nama kategori sudah ada.
     * @param string $category_name Nama kategori yang akan diperiksa.
     * @param int|null $exclude_id ID kategori yang akan dikecualikan (untuk edit).
     * @return bool True jika nama kategori sudah ada, False jika tidak.
     */
    public function isCategoryNameExists($category_name, $exclude_id = null)
    {
        $sql = "SELECT id FROM categories WHERE category_name = ?";
        $types = "s";
        $params = [$category_name];

        if ($exclude_id !== null) {
            $sql .= " AND id != ?";
            $types .= "i";
            $params[] = $exclude_id;
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing isCategoryNameExists: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Menambahkan kategori baru.
     * @param string $category_name Nama kategori.
     * @return bool True jika berhasil, False jika gagal.
     */
    public function addCategory($category_name)
    {
        $sql = "INSERT INTO categories (category_name) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing addCategory: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("s", $category_name);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Memperbarui nama kategori.
     * @param int $id ID kategori.
     * @param string $category_name Nama kategori baru.
     * @return bool True jika berhasil, False jika gagal.
     */
    public function updateCategory($id, $category_name)
    {
        $sql = "UPDATE categories SET category_name = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing updateCategory: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("si", $category_name, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Menghapus kategori.
     * Catatan: Karena ada FOREIGN KEY di tabel books (category_id) dengan ON DELETE SET NULL,
     * buku-buku yang terkait dengan kategori ini akan memiliki category_id menjadi NULL.
     * @param int $id ID kategori.
     * @return bool True jika berhasil, False jika gagal.
     */
    public function deleteCategory($id)
    {
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing deleteCategory: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
