<?php
require("db.inc.php"); // taken from module material
session_start();

// Validation of login form - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = validate($_POST['username']);
    $password = validate($_POST['password']);

    // check username and password validity - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // valid login
        $_SESSION['username'] = $username;
        header("Location: main_menu.php");
        $logEvent = "Login";
        $logDetails = "User {$username} logged in.";
        logEvent($logEvent, $logDetails, $username);
        exit();
    } else {
        // error
        $errorMsg = "Incorrect username or password. Please try again.";
    }

    $stmt->close();
}

// validation function - modified from: https://www.w3schools.com/php/php_form_validation.asp
function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function logEvent($event, $details, $username) {
    global $conn; 

    // preventing SQL injection - modified from: https://www.w3schools.com/php/func_mysqli_real_escape_string.asp
    $event = mysqli_real_escape_string($conn, $event);
    $details = mysqli_real_escape_string($conn, $details);
    $username = mysqli_real_escape_string($conn, $username);

    // SQL query to insert log - modified from: https://www.w3schools.com/php/php_mysql_insert.asp
    $sql = "INSERT INTO Audit (event, details, username) VALUES ('$event', '$details', '$username')";
    mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head> <!-- adapted from: https://www.w3schools.com/tags/tag_meta.asp -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police: Login</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Login</h2>
    <?php
    // error
    if (isset($errorMsg)) {
        echo "<p style='color: red;'>$errorMsg</p>";
    }
    ?>
    <form action="login.php" method="post">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>