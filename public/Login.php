<?php
session_start();

require 'vendor/autoload.php';
require_once 'src/Database.php';
require_once 'src/User.php';
use User\BudgetHandler\Database;


// Create a Database object
$db = new Database();
$conn = $db->connect();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $first_name = test_input($_POST["first_name"]);
    $last_name = test_input($_POST["last_name"]);
    $id_number = test_input($_POST["id_number"]);
    $remember_me = isset($_POST['remember_me']); // Check if remember me is checked

    // Prepare and execute SQL statement to check credentials
    $stmt = $conn->prepare("SELECT id, first_name, last_name, id_number FROM clients WHERE first_name = ? AND last_name = ?");
    if ($stmt === false) {
        $_SESSION['error'] = "Error preparing statement: " . $conn->error;
        header("Location: Login.php?status=error");
        exit();
    }

    $stmt->bind_param("ss", $first_name, $last_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_first_name, $db_last_name, $db_hashed_id);
        $stmt->fetch();

        // Verify hashed ID
        if (password_verify($id_number, $db_hashed_id)) {
            // Clear previous session data related to budgets
            unset($_SESSION['total_budget']);
            unset($_SESSION['remaining_budget']);

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $db_first_name; // Store other user details as needed
            $_SESSION['message'] = "Login successful!";

            // Set "Remember Me" cookies if the checkbox is checked
            if ($remember_me) {
                $expire = time() + (10 * 365 * 24 * 60 * 60); // Set cookie expiration to 10 years
                setcookie("remember_user_id", $user_id, $expire, '/', '', true, true); // HttpOnly and Secure flags
                setcookie("remember_first_name", $db_first_name, $expire, '/', '', true, true);
                setcookie("remember_last_name", $db_last_name, $expire, '/', '', true, true);
            }

            $stmt->close();
            $conn->close();

            // Redirect to the finance page
            header("Location: Finance.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid credentials. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Invalid credentials. Please try again.";
    }

    $stmt->close();
    $conn->close();
    header("Location: Login.php?status=error");
    exit();
}

function test_input($data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>

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
                alert('Login successful!');
            } else if (status === 'error') {
                alert('Login failed. Please try again.');
            }
        });
    </script>
</head>
<body>
<div class="container">
    <div class="box form-box">
        <header>Sign In</header>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<p style='color:red'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['message'])) {
            echo "<p style='color:green'>" . $_SESSION['message'] . "</p>";
            unset($_SESSION['message']);
        }
        ?>

        <form action="Login.php" method="post">
            <div class="field input">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_COOKIE['remember_first_name'] ?? ''); ?>" required>
            </div>

            <div class="field input">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_COOKIE['remember_last_name'] ?? ''); ?>" required>
            </div>

            <div class="field input">
                <label for="id_number">ID:</label>
                <input type="password" id="id_number" name="id_number" required>
            </div>

            <div class="field">
                <label for="remember_me">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    Remember Me
                </label>
            </div>

            <div class="field">
                <input type="submit" class="btn" name="submit" value="Login">
            </div>

            <div class="links">
                Not a registered client? <a href="Register.php">Sign Up</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
