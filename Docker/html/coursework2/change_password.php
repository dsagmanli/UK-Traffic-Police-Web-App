<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks user login
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$errorMessage = ""; // error 
$successMessage = ""; // success
$updateStmt = null; 

// Validation of password change - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = validate($_POST['current_password']);
    $newPassword = validate($_POST['new_password']);

    // compare current and new passwords
    if (!empty($currentPassword) && !empty($newPassword)) {
        // check if current password correct - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $currentPassword);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // change password - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $updateStmt->bind_param("ss", $newPassword, $username);
            $updateStmt->execute();

            $successMessage = "Password changed successfully!";
        } else {
            $errorMessage = "Incorrect current password. Please try again.";
        }

        $stmt->close();
    } else {
        $errorMessage = "Please enter both current and new passwords.";
    }

    // close $updateStmt if not null
    if ($updateStmt !== null) {
        $updateStmt->close();
    }
}

// validation function - modified from: https://www.w3schools.com/php/php_form_validation.asp
function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head> <!-- adapted from: https://www.w3schools.com/tags/tag_meta.asp -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police: Change Password</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Change Password</h2>
    <?php
    // success message if password changed 
    if (!empty($successMessage)) {
        echo "<p style='color: green;'>$successMessage</p>";
        echo "<script>alert('$successMessage')</script>"; // JavaScript to display message
        echo "<a href='main_menu.php'>Back to Main Menu</a>";
    } else {
        // error message
        echo "<p style='color: red;'>$errorMessage</p>";
        echo "<script>alert('$errorMessage')</script>"; // JavaScript to display message
        ?>
        <form action="change_password.php" method="post">
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" required>
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>
            <button type="submit">Change Password</button>
        </form>
        <br>
        <a href="main_menu.php">Back to Main Menu</a>
        <?php
    }
    ?>
</body>
</html>