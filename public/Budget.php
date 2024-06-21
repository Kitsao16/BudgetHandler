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
error_reporting(E_ALL);
ini_set('display_errors', 1);





// Handle editing of budget item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_item_id'])) {
    $edit_item_id = $_POST['edit_item_id'];
    $edited_item = test_input($_POST['edited_item']);
    $edited_amount = test_input($_POST['edited_amount']);

    if (!is_numeric($edited_amount) || $edited_amount <= 0) {
        $_SESSION['error'] = "Invalid amount for editing. Please enter a positive number.";
    } else {
        // Prepare the update statement
        $stmt = $conn->prepare("UPDATE budget_items SET item = ?, amount = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sdii", $edited_item, $edited_amount, $edit_item_id, $_SESSION['user_id']);

        // Execute the update
        if ($stmt->execute()) {
            $_SESSION['message'] = "Item updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating item: " . $stmt->error;
        }

        $stmt->close();
    }

    header("Location: Budget.php");
    exit();
}

// Handle deletion of budget item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item_id'])) {
    $delete_item_id = $_POST['delete_item_id'];

    // Prepare the delete statement
    $stmt = $conn->prepare("DELETE FROM budget_items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_item_id, $_SESSION['user_id']);

    // Execute the delete
    if ($stmt->execute()) {
        $_SESSION['message'] = "Item deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting item: " . $stmt->error;
    }

    $stmt->close();

    header("Location: Budget.php");
    exit();
}

function test_input($data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

// Get the budget name from the database for display in the navigation bar
$budget_name = '';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT DISTINCT budget_name FROM budget_items WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($budget_name);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>My Budgets</title>
    <style>
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form {
            display: inline-block;
            margin-left: 10px;
        }
        .budget-group {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .budget-group-header {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .back-button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px 20px;
            text-align: center;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            table {
                width: 100%;
            }
            th, td {
                font-size: 14px;
                padding: 3px;
            }
            .budget-group-header {
                font-size: 16px;
            }
            .back-button {
                width: 100%;
            }
        }
        @media (max-width: 480px) {
            table {
                width: 100%;
            }
            th, td {
                font-size: 12px;
                padding: 2px;
            }
            .budget-group-header {
                font-size: 14px;
            }
            .back-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="nav">
    <div class="logo">
        <p><a href="#">LOGO</a></p>
    </div>
    <?php if ($budget_name): ?>
        <div class="budget-name">
            <p>Budget: <?php echo htmlspecialchars($budget_name); ?></p>
        </div>
    <?php endif; ?>
</div>
<div class="container">
    <div class="main-box">
        <h2>My Budgets</h2>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<p class='message'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['message'])) {
            echo "<p class='message' style='color: green'>" . $_SESSION['message'] . "</p>";
            unset($_SESSION['message']);
        }

        $user_id = $_SESSION['user_id'];
        $sql = "SELECT id, item, amount, budget_name, created_at FROM budget_items WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $budget_groups = [];
        while ($row = $result->fetch_assoc()) {
            $budget_groups[$row['budget_name']][] = $row;
        }

        foreach ($budget_groups as $budget_name => $items) {
            echo '<div class="budget-group">';
            echo '<div class="budget-group-header">' . htmlspecialchars($budget_name) . ' - ' . date('M d, Y', strtotime($items[0]['created_at'])) . '</div>';
            echo "<table>";
            echo "<tr><th>Item</th><th>Amount (Ksh)</th><th>Action</th></tr>";
            foreach ($items as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['item']) . "</td>";
                echo "<td>Ksh " . number_format($row['amount'], 2) . "/=</td>";
                echo "<td>";
                // Form for editing
                echo '<form action="Budget.php" method="post">';
                echo '<input type="hidden" name="edit_item_id" value="' . $row['id'] . '">';
                echo '<input type="text" name="edited_item" value="' . htmlspecialchars($row['item']) . '" required>';
                echo '<input type="number" step="0.01" name="edited_amount" value="' . $row['amount'] . '" required>';
                echo '<input type="submit" value="Edit" class="btn">';
                echo '</form>';

                // Form for deleting
                echo '<form action="Budget.php" method="post">';
                echo '<input type="hidden" name="delete_item_id" value="' . $row['id'] . '">';
                echo '<input type="submit" value="Delete" class="btn">';
                echo '</form>';
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>"; // Close budget group div
        }

        if ($result->num_rows == 0) {
            echo "<p>No budgets found.</p>";
        }

        $stmt->close();
        ?>

        <!-- Back to Finance page button -->
        <a href="Finance.php" class="back-button">Back to Finance Page</a>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>
