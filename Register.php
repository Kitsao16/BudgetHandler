<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to get MySQLi connection
function getMysqli() {
    $servername = "127.0.0.1";
    $username = "root";
    $password = "admin@2016"; // Replace with your MySQL password
    $dbname = "client_accounts";

    // Create connection to the database
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if the connection to the database was successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

$conn = getMysqli();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $first_name = test_input($_POST["first_name"]);
    $last_name = test_input($_POST["last_name"]);
    $id_number = test_input($_POST["id_number"]);
    $remember_me = isset($_POST["remember_me"]); // Check if remember me is checked

    $errors = [];

    // Validate first name
    if (!validate_input($first_name, 'name')) {
        $errors[] = "Invalid first name. Only letters and spaces are allowed.";
    }

    // Validate last name
    if (!validate_input($last_name, 'name')) {
        $errors[] = "Invalid last name. Only letters and spaces are allowed.";
    }

    // Validate ID number
    if (!validate_input($id_number, 'id')) {
        $errors[] = "Invalid ID number. It must be between 4 to 10 digits.";
    }

    // Check if ID number is already in use
    if (!is_unique_id($conn, $id_number)) {
        $errors[] = "ID number is already in use.";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_id_number = password_hash($id_number, PASSWORD_DEFAULT);

        // Check the length of the hashed ID number
        if (strlen($hashed_id_number) > 255) {
            $errors[] = "Error: Hashed ID number is too long.";
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: Register.php?status=error");
            exit();
        }

        // Prepare and execute SQL statement to insert user into database
        $stmt = $conn->prepare("INSERT INTO clients (first_name, last_name, id_number) VALUES (?, ?, ?)");
        if ($stmt === false) {
            $_SESSION['error'] = "Error preparing statement: " . $conn->error;
            header("Location: Register.php?status=error");
            exit();
        }

        $stmt->bind_param("sss", $first_name, $last_name, $hashed_id_number);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful!";

            // If remember me is checked, set cookies with user ID
            if ($remember_me) {
                $expire = time() + (10 * 365 * 24 * 60 * 60); // Set cookie expiration to 10 years
                setcookie("remember_user_id", $conn->insert_id, $expire, '/');
                setcookie("remember_username", $first_name, $expire, '/');
            }

            // Clear any previous session data related to budgets
            unset($_SESSION['budget']);
            unset($_SESSION['total_budget']);
            unset($_SESSION['remaining_budget']);

            // Set user-specific session data
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['username'] = $first_name;

            $stmt->close();
            $conn->close();

            header("Location: Finance.php"); // Redirect to Finance.php after successful registration
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
            $stmt->close();
            $conn->close();
            header("Location: Register.php?status=error");
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        $conn->close();
        header("Location: Register.php?status=error");
    }
    exit();
}

function test_input($data): string {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

function validate_input($data, $type): bool {
    // Validation logic for name and id inputs
    if ($type === 'name') {
        return preg_match("/^[a-zA-Z ]*$/", $data);
    } elseif ($type === 'id') {
        return preg_match("/^\d{4,10}$/", $data);
    }
    return false;
}

function is_unique_id($conn, $id_number): bool {
    // Check if ID number already exists in database
    $stmt = $conn->prepare("SELECT id FROM clients WHERE id_number = ?");
    if ($stmt === false) {
        return false;
    }

    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $stmt->store_result();
    $count = $stmt->num_rows;
    $stmt->close();

    return $count === 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Register</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 400px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .form-box header {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .field {
            margin-bottom: 15px;
        }

        .field input[type="text"],
        .field input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .field input[type="checkbox"] {
            margin-right: 5px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .links {
            text-align: center;
            margin-top: 10px;
        }

        .links a {
            color: #007BFF;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }

            .form-box header {
                font-size: 20px;
                margin-bottom: 15px;
            }

            .field input[type="text"],
            .field input[type="password"] {
                padding: 8px;
                font-size: 14px;
            }

            .btn {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>

    <script>
        // Function to parse URL query parameters
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        // Check if status is success or error and show respective popup
        document.addEventListener('DOMContentLoaded', function () {
            const status = getQueryParam('status');
            if (status === 'success') {
                alert('Registration successful!');
            } else if (status === 'error') {
                alert('Registration failed. Please try again.');
            }
        });
    </script>
</head>
<body>
<div class="container">
    <div class="box form-box">
        <header>Sign Up</header>

        <!-- Registration Form -->
        <form action="Register.php" method="post">
            <div class="field input">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" autocomplete="off" required>
            </div>

            <div class="field input">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" autocomplete="off" required>
            </div>

            <div class="field input">
                <label for="id_number">ID:</label>
                <input type="password" id="id_number" name="id_number" autocomplete="off" required>
            </div>

            <div class="field">
                <label for="remember_me">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    Remember Me
                </label>
            </div>

            <div class="field">
                <input type="submit" class="btn" name="submit" value="Register">
            </div>

            <div class="links">
                Already a Registered client? <a href="Login.php">Sign In</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
