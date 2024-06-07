<?php
session_start();
require_once 'Database.php';
require_once 'User.php';

use BudgetHandler\User;

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $first_name = test_input($_POST["first_name"]);
    $last_name = test_input($_POST["last_name"]);
    $id_number = test_input($_POST["id_number"]);
    $remember_me = isset($_POST["remember_me"]);

    $user = new User();
    $result = $user->register($first_name, $last_name, $id_number, $remember_me);

    if ($result['success']) {
        $_SESSION['message'] = "Registration successful!";
        unset($_SESSION['budget']);
        unset($_SESSION['total_budget']);
        unset($_SESSION['remaining_budget']);
        header("Location: Finance.php");
        // Make sure to call exit after header redirection
    } else {
        $_SESSION['error'] = implode("<br>", $result['errors']);
        header("Location: Register.php?status=error");
        // Make sure to call exit after header redirection
    }
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
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

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
