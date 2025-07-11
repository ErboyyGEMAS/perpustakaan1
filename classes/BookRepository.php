<?php

namespace App\Classes;

use mysqli_stmt;

class BookRepository
{
    private $conn;

    public function __construct(Database $db)
    {
        $this->conn = $db->getConnection();
    }

    public function getAllBooks($search_query = "")
    {
        $books = [];
        $where_clause = "";
        $params = [];
        $types = "";

        if (!empty($search_query)) {
            $where_clause = " WHERE b.title LIKE ? OR b.author LIKE ? OR c.category_name LIKE ?";
            $params = ["%{$search_query}%", "%{$search_query}%", "%{$search_query}%"];
            $types = "sss";
        }

        $sql = "SELECT b.id, b.title, b.author, b.publisher, b.publication_year, b.isbn, b.quantity, b.cover_image, c.category_name
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id" . $where_clause . " ORDER BY b.title ASC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for getAllBooks: " . $this->conn->error);
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
        }
        $stmt->close();
        return $books;
    }

    public function getBookById($id)
    {
        $sql = "SELECT id, title, author, publisher, publication_year, isbn, category_id, quantity, cover_image FROM books WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for getBookById: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $stmt->close();
        return $book;
    }

    public function isIsbnExists($isbn, $exclude_book_id = null)
    {
        $sql = "SELECT id FROM books WHERE isbn = ?";
        $types = "s";
        $params = [$isbn];
        if ($exclude_book_id !== null) {
            $sql .= " AND id != ?";
            $types .= "i";
            $params[] = $exclude_book_id;
        }
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for isIsbnExists: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function addBook($title, $author, $publisher, $publication_year, $isbn, $category_id, $quantity, $cover_image = null)
    {
        $sql = "INSERT INTO books (title, author, publisher, publication_year, isbn, category_id, quantity, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for addBook: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("sssisiss", $title, $author, $publisher, $publication_year, $isbn, $category_id, $quantity, $cover_image);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateBook($id, $title, $author, $publisher, $publication_year, $isbn, $category_id, $quantity, $cover_image = null)
    {
        $sql = "UPDATE books SET title = ?, author = ?, publisher = ?, publication_year = ?, isbn = ?, category_id = ?, quantity = ?, cover_image = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for updateBook: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("sssisiiis", $title, $author, $publisher, $publication_year, $isbn, $category_id, $quantity, $cover_image, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function deleteBook($id)
    {
        $cover_filename = $this->getCoverImageFilename($id);
        $sql = "DELETE FROM books WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for deleteBook: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        if ($success && $cover_filename) {
            $this->deleteCoverImageFile($cover_filename);
        }
        return $success;
    }

    public function updateBookQuantity($book_id, $change_by)
    {
        $sql = "UPDATE books SET quantity = quantity + ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for updateBookQuantity: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("ii", $change_by, $book_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getCoverImageFilename($id)
    {
        $sql = "SELECT cover_image FROM books WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing statement for getCoverImageFilename: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['cover_image'] : null;
    }

    public function deleteCoverImageFile($filename)
    {
        if (empty($filename)) {
            return true;
        }
        $filepath = __DIR__ . '/../assets/images/covers/' . $filename;
        $real_filepath = realpath($filepath);
        $base_dir = realpath(__DIR__ . '/../assets/images/covers/');
        if ($real_filepath && $base_dir && strpos($real_filepath, $base_dir) === 0 && file_exists($real_filepath) && is_file($real_filepath)) {
            return unlink($real_filepath);
        }
        return true;
    }

    /**
     * Mengambil jumlah total buku. (METHOD BARU UNTUK REPORTS.PHP)
     * @return int Jumlah total buku.
     */
    public function getTotalBooks()
    {
        $sql = "SELECT COUNT(*) AS total FROM books";
        $result = $this->conn->query($sql);
        if ($result === false) {
            error_log("Error getting total books: " . $this->conn->error);
            return 0;
        }
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
}
