<?php

namespace App\Classes;

class MemberRepository
{
    private $conn;

    public function __construct(Database $db)
    {
        $this->conn = $db->getConnection();
    }

    public function getAllMembers($search_query = "")
    {
        $members = [];
        $where_clause = "";
        $params = [];
        $types = "";

        if (!empty($search_query)) {
            $where_clause = " WHERE name LIKE ? OR phone_number LIKE ? OR email LIKE ?";
            $params = ["%{$search_query}%", "%{$search_query}%", "%{$search_query}%"];
            $types = "sss";
        }

        $sql = "SELECT id, name, address, phone_number, email, registered_at FROM members" . $where_clause . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            error_log("Error preparing statement for getAllMembers: " . $this->conn->error);
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $members[] = $row;
            }
        }
        $stmt->close();
        return $members;
    }

    public function getMemberById($id)
    {
        $sql = "SELECT id, name, address, phone_number, email FROM members WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing getMemberById: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $member = $result->fetch_assoc();
        $stmt->close();
        return $member;
    }

    public function isEmailOrPhoneExists($email, $phone_number, $exclude_member_id = null)
    {
        $sql = "SELECT id FROM members WHERE email = ? OR phone_number = ?";
        $types = "ss";
        $params = [$email, $phone_number];
        if ($exclude_member_id !== null) {
            $sql .= " AND id != ?";
            $types .= "i";
            $params[] = $exclude_member_id;
        }
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing isEmailOrPhoneExists: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function addMember($name, $address, $phone_number, $email)
    {
        $sql = "INSERT INTO members (name, address, phone_number, email) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing addMember: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("ssss", $name, $address, $phone_number, $email);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateMember($id, $name, $address, $phone_number, $email)
    {
        $sql = "UPDATE members SET name = ?, address = ?, phone_number = ?, email = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing updateMember: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("ssssi", $name, $address, $phone_number, $email, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function deleteMember($id)
    {
        $sql = "DELETE FROM members WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing deleteMember: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Mengambil jumlah total anggota. (METHOD BARU UNTUK REPORTS.PHP)
     * @return int Jumlah total anggota.
     */
    public function getTotalMembers()
    {
        $sql = "SELECT COUNT(*) AS total FROM members";
        $result = $this->conn->query($sql);
        if ($result === false) {
            error_log("Error getting total members: " . $this->conn->error);
            return 0;
        }
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
}
