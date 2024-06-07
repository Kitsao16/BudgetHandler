<?php
namespace BudgetHandler;

use mysqli;

class User {
    private ?mysqli $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function register($first_name, $last_name, $id_number, $remember_me): array
    {
        $errors = $this->validate($first_name, $last_name, $id_number);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $hashed_id_number = password_hash($id_number, PASSWORD_DEFAULT);

        if (strlen($hashed_id_number) > 255) {
            return ['success' => false, 'errors' => ['Hashed ID number is too long.']];
        }

        $stmt = $this->conn->prepare("INSERT INTO clients (first_name, last_name, id_number) VALUES (?, ?, ?)");

        if ($stmt === false) {
            return ['success' => false, 'errors' => ["Error preparing statement: " . $this->conn->error]];
        }

        $stmt->bind_param("sss", $first_name, $last_name, $hashed_id_number);

        if ($stmt->execute()) {
            if ($remember_me) {
                $expire = time() + (10 * 365 * 24 * 60 * 60);
                setcookie("remember_user_id", $this->conn->insert_id, $expire, '/');
                setcookie("remember_username", $first_name, $expire, '/');
            }

            $_SESSION['user_id'] = $this->conn->insert_id;
            $_SESSION['username'] = $first_name;

            $stmt->close();
            return ['success' => true];
        } else {
            $stmt->close();
            return ['success' => false, 'errors' => ["Error: " . $stmt->error]];
        }
    }

    private function validate($first_name, $last_name, $id_number): array
    {
        $errors = [];

        if (!preg_match("/^[a-zA-Z ]*$/", $first_name)) {
            $errors[] = "Invalid first name. Only letters and spaces are allowed.";
        }

        if (!preg_match("/^[a-zA-Z ]*$/", $last_name)) {
            $errors[] = "Invalid last name. Only letters and spaces are allowed.";
        }

        if (!preg_match("/^\d{4,10}$/", $id_number)) {
            $errors[] = "Invalid ID number. It must be between 4 to 10 digits.";
        }

        if (!$this->is_unique_id($id_number)) {
            $errors[] = "ID number is already in use.";
        }

        return $errors;
    }

    private function is_unique_id($id_number): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM clients WHERE id_number = ?");
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();

        return $count === 0;
    }
}

