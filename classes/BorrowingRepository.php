<?php

namespace App\Classes;

class BorrowingRepository
{
    private $conn;
    private $db_instance; // Menyimpan instance Database untuk mengakses BookRepository

    public function __construct(Database $db)
    {
        $this->db_instance = $db;
        $this->conn = $db->getConnection();
    }

    public function addBorrowing($book_id, $member_id, $borrow_date, $due_date)
    {
        $this->conn->begin_transaction();
        try {
            $stmt_check_stock = $this->conn->prepare("SELECT quantity FROM books WHERE id = ? FOR UPDATE");
            if ($stmt_check_stock === false) {
                throw new \Exception("Prepare check_stock failed: " . $this->conn->error);
            }
            $stmt_check_stock->bind_param("i", $book_id);
            $stmt_check_stock->execute();
            $result_stock = $stmt_check_stock->get_result();
            $book_stock = $result_stock->fetch_assoc();
            $stmt_check_stock->close();

            if (!$book_stock || $book_stock['quantity'] <= 0) {
                throw new \Exception("Buku tidak tersedia atau stok habis.");
            }

            $sql_insert_borrow = "INSERT INTO borrowings (book_id, member_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')";
            $stmt_insert_borrow = $this->conn->prepare($sql_insert_borrow);
            if ($stmt_insert_borrow === false) {
                throw new \Exception("Prepare insert_borrow failed: " . $this->conn->error);
            }
            $stmt_insert_borrow->bind_param("iiss", $book_id, $member_id, $borrow_date, $due_date);
            if (!$stmt_insert_borrow->execute()) {
                throw new \Exception("Gagal mencatat peminjaman: " . $stmt_insert_borrow->error);
            }
            $stmt_insert_borrow->close();

            $bookRepository = new BookRepository($this->db_instance);
            if (!$bookRepository->updateBookQuantity($book_id, -1)) {
                throw new \Exception("Gagal mengurangi stok buku.");
            }

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Borrowing transaction failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAllBorrowings($search_query = "", $status_filter = "")
    {
        $borrowings = [];
        $where_clause = "";
        $params = [];
        $types = "";

        if (!empty($status_filter)) {
            $allowed_statuses = ['borrowed', 'returned', 'overdue'];
            if (in_array($status_filter, $allowed_statuses)) {
                $where_clause .= ($where_clause ? " AND " : " WHERE ") . "b.status = ?";
                $params[] = $status_filter;
                $types .= "s";
            }
        }

        if (!empty($search_query)) {
            $search_pattern = "%{$search_query}%";
            $where_clause .= ($where_clause ? " AND " : " WHERE ") . "(book.title LIKE ? OR member.name LIKE ?)";
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $types .= "ss";
        }

        $sql = "SELECT b.id, book.title AS book_title, member.name AS member_name,
                       b.borrow_date, b.due_date, b.return_date, b.status
                FROM borrowings b
                JOIN books book ON b.book_id = book.id
                JOIN members member ON b.member_id = member.id" . $where_clause . " ORDER BY b.borrow_date DESC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing getAllBorrowings: " . $this->conn->error);
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $borrowings[] = $row;
            }
        }
        $stmt->close();
        return $borrowings;
    }

    public function getBorrowingById($id)
    {
        $sql = "SELECT b.id, b.book_id, b.member_id, b.borrow_date, b.due_date, b.return_date, b.status,
                       book.title AS book_title, member.name AS member_name
                FROM borrowings b
                JOIN books book ON b.book_id = book.id
                JOIN members member ON b.member_id = member.id
                WHERE b.id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing getBorrowingById: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $borrowing = $result->fetch_assoc();
        $stmt->close();
        return $borrowing;
    }

    public function updateBorrowing($id, $borrow_date, $due_date, $new_status, $return_date, $book_id, $original_status)
    {
        $this->conn->begin_transaction();
        try {
            $return_date_clause = $return_date === null ? ", return_date = NULL" : ", return_date = ?";
            $sql = "UPDATE borrowings SET borrow_date = ?, due_date = ?, status = ?" . $return_date_clause . " WHERE id = ?";
            $types = "sssi";
            $params = [$borrow_date, $due_date, $new_status, $id];
            if ($return_date !== null) {
                array_splice($params, 3, 0, $return_date);
                $types = "ssssi";
            }

            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new \Exception("Prepare updateBorrowing failed: " . $this->conn->error);
            }
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new \Exception("Execute updateBorrowing failed: " . $stmt->error);
            }
            $stmt->close();

            $bookRepository = new BookRepository($this->db_instance);
            if ($original_status !== 'returned' && $new_status === 'returned') {
                if (!$bookRepository->updateBookQuantity($book_id, 1)) {
                    throw new \Exception("Failed to increment book quantity.");
                }
            } elseif ($original_status === 'returned' && $new_status !== 'returned') {
                $current_book_qty = $bookRepository->getBookById($book_id)['quantity'] ?? 0;
                if ($current_book_qty <= 0) {
                    throw new \Exception("Stok buku tidak cukup untuk mengubah status menjadi 'dipinjam' kembali.");
                }
                if (!$bookRepository->updateBookQuantity($book_id, -1)) {
                    throw new \Exception("Failed to decrement book quantity.");
                }
            }

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Update borrowing transaction failed: " . $e->getMessage());
            return false;
        }
    }

    public function returnBook($borrowing_id, $book_id, $original_status)
    {
        $this->conn->begin_transaction();
        try {
            if ($original_status === 'returned') {
                throw new \Exception("Buku ini sudah dikembalikan sebelumnya.");
            }
            $sql_update_borrowing = "UPDATE borrowings SET return_date = CURDATE(), status = 'returned' WHERE id = ?";
            $stmt_update_borrowing = $this->conn->prepare($sql_update_borrowing);
            if ($stmt_update_borrowing === false) {
                throw new \Exception("Prepare returnBook update_borrowing failed: " . $this->conn->error);
            }
            $stmt_update_borrowing->bind_param("i", $borrowing_id);
            if (!$stmt_update_borrowing->execute()) {
                throw new \Exception("Execute returnBook update_borrowing failed: " . $stmt_update_borrowing->error);
            }
            $stmt_update_borrowing->close();

            $bookRepository = new BookRepository($this->db_instance);
            if (!$bookRepository->updateBookQuantity($book_id, 1)) {
                throw new \Exception("Gagal menambahkan stok buku saat pengembalian.");
            }

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Return book transaction failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBorrowing($id)
    {
        $this->conn->begin_transaction();
        try {
            $stmt_get_borrowing = $this->conn->prepare("SELECT book_id, status FROM borrowings WHERE id = ? FOR UPDATE");
            if ($stmt_get_borrowing === false) {
                throw new \Exception("Prepare deleteBorrowing get_borrowing failed: " . $this->conn->error);
            }
            $stmt_get_borrowing->bind_param("i", $id);
            $stmt_get_borrowing->execute();
            $result_get_borrowing = $stmt_get_borrowing->get_result();
            if ($result_get_borrowing->num_rows === 0) {
                throw new \Exception("Transaksi peminjaman tidak ditemukan.");
            }

            $borrowing_data = $result_get_borrowing->fetch_assoc();
            $book_id = $borrowing_data['book_id'];
            $current_status = $borrowing_data['status'];
            $stmt_get_borrowing->close();

            if ($current_status === 'borrowed' || $current_status === 'overdue') {
                $bookRepository = new BookRepository($this->db_instance);
                if (!$bookRepository->updateBookQuantity($book_id, 1)) {
                    throw new \Exception("Gagal menambahkan stok buku setelah penghapusan transaksi.");
                }
            }

            $sql_delete_borrowing = "DELETE FROM borrowings WHERE id = ?";
            $stmt_delete_borrowing = $this->conn->prepare($sql_delete_borrowing);
            if ($stmt_delete_borrowing === false) {
                throw new \Exception("Prepare deleteBorrowing failed: " . $this->conn->error);
            }
            $stmt_delete_borrowing->bind_param("i", $id);
            if (!$stmt_delete_borrowing->execute()) {
                throw new \Exception("Gagal menghapus transaksi peminjaman: " . $stmt_delete_borrowing->error);
            }
            $stmt_delete_borrowing->close();

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollback();
            error_log("Delete borrowing transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengambil jumlah transaksi peminjaman berdasarkan status. (METHOD UNTUK REPORTS.PHP)
     * @param string $status Filter status ('borrowed', 'returned', 'overdue', atau kosong untuk semua).
     * @return int Jumlah transaksi untuk status tertentu.
     */
    public function getBorrowingCountByStatus($status = '')
    {
        $sql = "SELECT COUNT(*) AS total FROM borrowings";
        $params = [];
        $types = "";

        if (!empty($status)) {
            $allowed_statuses = ['borrowed', 'returned', 'overdue'];
            if (in_array($status, $allowed_statuses)) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
                $types = "s";
            }
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing getBorrowingCountByStatus: " . $this->conn->error);
            return 0;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['total'];
    }
}
