<?php
// Function to sanitize input
function test_input($data): string {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

// Function to validate input based on type
function validate_input($data, $type): bool|int {
    return match ($type) {
        'name' => preg_match("/^[a-zA-Z-' ]*$/", $data),
        'id' => preg_match("/^\d{4,10}$/", $data),
        default => false,
    };
}

// Function to check if ID number is unique
function is_unique_id($conn, $id_number): bool {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE id_number = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $stmt->store_result();
    $is_unique = $stmt->num_rows === 0;
    $stmt->close();
    return $is_unique;
}

// Function to display session messages

