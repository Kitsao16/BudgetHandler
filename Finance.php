<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$servername = "localhost";
$username = "root";
$password = ""; // Use your MySQL password if set
$dbname = "client_accounts";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user's name from the database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT first_name, last_name FROM clients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
    }
    $stmt->close();
}

// Handle setting total budget
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_budget'])) {
    $_SESSION['total_budget'] = test_input($_POST["total_budget"]);
    $_SESSION['remaining_budget'] = $_SESSION['total_budget']; // Initialize remaining budget
    header("Location: Finance.php");
    exit();
}

// Handle adding budget item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $budget_name = test_input($_POST["budget_name"]);
    $item = test_input($_POST["item"]);
    $amount = test_input($_POST["amount"]);

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        $_SESSION['error'] = "Invalid amount. Please enter a positive number.";
    } elseif ($_SESSION['remaining_budget'] - $amount < 0) {
        $_SESSION['error'] = "Insufficient budget. Please reset or increase your budget.";
    } else {
        $_SESSION['remaining_budget'] -= $amount;

        // Insert budget item into the database
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO budget_items (user_id, item, amount, budget_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $user_id, $item, $amount, $budget_name);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Item added successfully!";
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    header("Location: Finance.php");
    exit();
}

// Handle creating budget with a name (after items have been added)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_budget'])) {
    if (!empty($_POST["budget_name"])) {
        $_SESSION['budget_name'] = test_input($_POST["budget_name"]);
        $_SESSION['message'] = "Budget '{$_SESSION['budget_name']}' created!";
    } else {
        $_SESSION['error'] = "Please enter a budget name.";
    }
    header("Location: Budget.php");
    exit();
}

// Function to sanitize input
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Finance Budget</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .welcome-message {
            text-align: center;
            background-color: #e7f3fe;
            padding: 10px;
            border: 1px solid #b3d8fd;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row mt-4">
        <div class="col-12 text-center">
            <div class="logo">
                <p>LOGO</p>
            </div>
            <div class="user-name">
                <?php
                if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                    echo "<p>Welcome, " . htmlspecialchars($_SESSION['first_name']) . " " . htmlspecialchars($_SESSION['last_name']) . "!</p>";
                }
                ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <?php
            if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                echo "<div class='welcome-message'>Welcome, " . htmlspecialchars($_SESSION['first_name']) . " " . htmlspecialchars($_SESSION['last_name']) . "!</div>";
            }
            ?>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h2>Enter Your Total Budget</h2>
            <div class="box">
                <form action="Finance.php" method="post">
                    <div class="form-group">
                        <label for="total_budget">Total Budget (Ksh):</label>
                        <input type="number" class="form-control" id="total_budget" name="total_budget" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" name="set_budget">Set Budget</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h2>Budget Entry</h2>
            <div class="box">
                <?php
                if (isset($_SESSION['total_budget'])) {
                    echo "<p>Total Budget: Ksh " . number_format($_SESSION['total_budget'], 2) . "/=</p>";
                    echo "<p>Remaining Budget: Ksh " . number_format($_SESSION['remaining_budget'], 2) . "/=</p>";
                }
                if (isset($_SESSION['error'])) {
                    echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['message'])) {
                    echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['message']) . "</div>";
                    unset($_SESSION['message']);
                }
                ?>
            </div>

            <div class="box <?php echo (isset($_SESSION['remaining_budget']) && $_SESSION['remaining_budget'] <= 0) ? 'disabled' : ''; ?>">
                <form action="Finance.php" method="post">
                    <div class="form-group">
                        <label for="budget_name">Budget Name:</label>
                        <input type="text" class="form-control" id="budget_name" name="budget_name" required>
                    </div>
                    <div class="form-group">
                        <label for="item">Item:</label>
                        <input type="text" class="form-control" id="item" name="item" required <?php echo (isset($_SESSION['remaining_budget']) && $_SESSION['remaining_budget'] <= 0) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (Ksh):</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required <?php echo (isset($_SESSION['remaining_budget']) && $_SESSION['remaining_budget'] <= 0) ? 'disabled' : ''; ?>>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" name="add_item" <?php echo (isset($_SESSION['remaining_budget']) && $_SESSION['remaining_budget'] <= 0) ? 'disabled' : ''; ?>>Add Item</button>
                </form>

                <?php
                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                    $sql = "SELECT item, amount, budget_name FROM budget_items WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        echo "<h2>Budget Items</h2>";
                        echo "<table class='table'>";
                        echo "<thead><tr><th>Budget Name</th><th>Item</th><th>Amount (Ksh)</th></tr></thead><tbody>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['budget_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['item']) . "</td>";
                            echo "<td>Ksh " . number_format($row['amount'], 2) . "/=</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    } else {
                        echo "<p>No budget items added yet.</p>";
                    }

                    $stmt->close();
                }
                ?>
            </div>

            <!-- Create Budget button outside the main form -->
            <div class="box">
                <form action="Finance.php" method="post">
                    <div class="form-group">
                        <label for="create_budget_name">Budget Name:</label>
                        <input type="text" class="form-control" id="create_budget_name" name="budget_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" name="create_budget">Create Budget</button>
                </form>
            </div>

            <!-- Back to Budget page button -->
            <div class="row">
                <div class="col-12">
                    <a href="Budget.php" class="btn btn-secondary btn-block">Back to Budget Page</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
