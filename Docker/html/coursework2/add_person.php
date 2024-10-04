<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks user login
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$message = ""; // for success/error message

// Validation of adding new person - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = validate($_POST['name']);
    $address = validate($_POST['address']);
    $licence = validate($_POST['licence']);

    // Checking whether a person with the same licence already exists - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $checkExisting = $conn->prepare("SELECT People_ID FROM People WHERE People_licence = ?");
    $checkExisting->bind_param("s", $licence);
    $checkExisting->execute();
    $checkExisting->store_result(); // modified from: https://www.w3schools.com/php/php_ref_mysqli.asp

    if ($checkExisting->num_rows > 0) {
        $message = "A person with the same licence exists.";
    } else {
        // Inserting new person into People table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $insertPerson = $conn->prepare("INSERT INTO People (People_ID, People_name, People_address, People_licence)
                                           SELECT COALESCE(MAX(People_ID), 0) + 1, ?, ?, ?
                                           FROM People");
        $insertPerson->bind_param("sss", $name, $address, $licence);
        $insertPerson->execute();
        $logEvent = "Add Person";
        $logDetails = "User {$username} added a person: Licence#: {$licence}.";
        logEvent($logEvent, $logDetails, $username);
        $insertPerson->close();

        $message = "Person added successfully.";
    }

    $checkExisting->close();
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
    <title>Police: Add Person</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Add Person</h2>

    <?php
    // success/error message
    if (!empty($message)) {
        echo "<p>{$message}</p>";
        echo "<script>alert('$message')</script>"; // JavaScript to display message
    }
    ?>

    <form action="add_person.php" method="post">
        <label for="name">Name:</label>
        <input type="text" name="name" required>

        <label for="address">Address:</label>
        <input type="text" name="address" required>

        <label for="licence">Licence:</label>
        <input type="text" name="licence" required>

        <button type="submit">Add Person</button>
    </form>

    <br>
    <a href="main_menu.php">Back to Main Menu</a>
</body>
</html>