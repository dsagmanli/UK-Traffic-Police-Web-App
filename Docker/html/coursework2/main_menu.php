<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks user login
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head> <!-- adapted from: https://www.w3schools.com/tags/tag_meta.asp -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police: Main Menu</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Main Menu</h2>

    <p>Welcome, <?php echo $username; ?>!</p>

    <!-- link to pages -->
    <ul>
        <li><a href="add_person.php">Add Person</a></li>
        <li><a href="add_vehicle.php">Add Vehicle</a></li>
        <li><a href="add_report.php">Add Report</a></li>
        <li><a href="search_people.php">Search People</a></li>
        <li><a href="search_vehicle.php">Search Vehicle</a></li>
        <li><a href="search_report.php">Search Report</a></li>
        <?php
        // special links only for admin
        if ($username === 'daniels') {
            echo '<li><a href="admin_panel.php">Admin Panel</a></li>';
            echo '<li><a href="audit_trail.php">Audit Trail</a></li>';
        }
        ?>
        <li><a href="change_password.php">Change Password</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</body>
</html>