<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks user login
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'daniels') {
    header("Location: index.html");
    exit();
}

$message = ""; // for success/error message

// validation function - modified from: https://www.w3schools.com/php/php_form_validation.asp
function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validation of adding new accounts - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $newUsername = validate($_POST['new_username']);
    $newPassword = validate($_POST['new_password']);

    // check whether username exists - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $checkUsername = $conn->prepare("SELECT * FROM Users WHERE username = ?");
    $checkUsername->bind_param("s", $newUsername);
    $checkUsername->execute();
    $checkUsername->store_result();

    if ($checkUsername->num_rows > 0) {
        $message = "Username already exists. Please choose a different username.";
    } else {
        // insert new user in users table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $addUser = $conn->prepare("INSERT INTO Users (username, password) VALUES (?, ?)");
        $addUser->bind_param("ss", $newUsername, $newPassword);

        if ($addUser->execute()) {
            $message = "User added successfully.";
            // log new user add
            $logEvent = "Add User";
            $logDetails = "Admin added a user: '{$newUsername}'.";
            logEvent($logEvent, $logDetails, $_SESSION['username']);
        } else {
            $message = "Error adding user.";
        }

        $addUser->close();
    }

    $checkUsername->close();
}

// Validation of associating or editing fines - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manage_fine'])) {
    $incidentID = validate($_POST['incident_id']);
    $fineAmount = validate($_POST['fine_amount']);
    $finePoints = validate($_POST['fine_points']);

    // check whether Incident_ID already has fines - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $checkFines = $conn->prepare("SELECT * FROM Fines WHERE Incident_ID = ?");
    $checkFines->bind_param("s", $incidentID);
    $checkFines->execute();
    $checkFines->store_result();

    if ($checkFines->num_rows > 0) {
        // show existing fines - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $getFinesInfo = $conn->prepare("SELECT Fine_Amount, Fine_Points, Offence_Description
                                           FROM Fines
                                           JOIN Incident ON Fines.Incident_ID = Incident.Incident_ID
                                           JOIN Offence ON Incident.Offence_ID = Offence.Offence_ID
                                           WHERE Fines.Incident_ID = ?");
        $getFinesInfo->bind_param("s", $incidentID);
        $getFinesInfo->execute();
        $getFinesInfo->bind_result($existingFineAmount, $existingFinePoints, $offenceDescription);
        $getFinesInfo->fetch(); // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
        $getFinesInfo->close();

        // check Offence_ID associated with Incident_ID - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $checkOffence = $conn->prepare("SELECT Offence_ID FROM Incident WHERE Incident_ID = ?");
        $checkOffence->bind_param("s", $incidentID);
        $checkOffence->execute();
        $checkOffence->bind_result($offenceID);
        $checkOffence->fetch(); // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
        $checkOffence->close();

        $getMaxValues = $conn->prepare("SELECT Offence_maxFine, Offence_maxPoints FROM Offence WHERE Offence_ID = ?");
        $getMaxValues->bind_param("s", $offenceID);
        $getMaxValues->execute();
        $getMaxValues->bind_result($maxFine, $maxPoints);
        $getMaxValues->fetch();
        $getMaxValues->close();

        if ($fineAmount < 0 || $fineAmount > $maxFine || $finePoints < 0 || $finePoints > $maxPoints) {
            $message = "Invalid values for Fine Amount or Fine Points.";
        } else {
            // update existing fine in Fines table
            $editFine = $conn->prepare("UPDATE Fines SET Fine_Amount = ?, Fine_Points = ? WHERE Incident_ID = ?");
            $editFine->bind_param("sss", $fineAmount, $finePoints, $incidentID);

            if ($editFine->execute()) {
                $message = "Fine updated successfully.";
                // log edit fine
                $logEvent = "Edit Fine";
                $logDetails = "Admin edited a fine with Incident ID: {$incidentID}, Fine Amount: {$fineAmount}, Fine Points: {$finePoints}.";
                logEvent($logEvent, $logDetails, $_SESSION['username']);
            } else {
                $message = "Error updating fine.";
            }

            $editFine->close();
        }
    } else {
        // check Offence_ID associated with Incident_ID - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $checkOffence = $conn->prepare("SELECT Offence_ID FROM Incident WHERE Incident_ID = ?");
        $checkOffence->bind_param("s", $incidentID);
        $checkOffence->execute();
        $checkOffence->bind_result($offenceID);
        $checkOffence->fetch();
        $checkOffence->close();

        $getMaxValues = $conn->prepare("SELECT Offence_maxFine, Offence_maxPoints FROM Offence WHERE Offence_ID = ?");
        $getMaxValues->bind_param("s", $offenceID);
        $getMaxValues->execute();
        $getMaxValues->bind_result($maxFine, $maxPoints);
        $getMaxValues->fetch();
        $getMaxValues->close();

        if ($fineAmount < 0 || $fineAmount > $maxFine || $finePoints < 0 || $finePoints > $maxPoints) {
            $message = "Invalid values for Fine Amount or Fine Points.";
        } else {
            // insert fine in Fines table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
            $addFine = $conn->prepare("INSERT INTO Fines (Fine_Amount, Fine_Points, Incident_ID)
                                           VALUES (?, ?, ?)");
            $addFine->bind_param("sss", $fineAmount, $finePoints, $incidentID);

            if ($addFine->execute()) {
                $message = "Fine added successfully.";
                // log add fine
                $logEvent = "Add Fine";
                $logDetails = "Admin added a fine with Incident ID: {$incidentID}, Fine Amount: {$fineAmount}, Fine Points: {$finePoints}.";
                logEvent($logEvent, $logDetails, $_SESSION['username']);
            } else {
                $message = "Error adding fine.";
            }

            $addFine->close();
        }
    }

    $checkFines->close();
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
    <title>Police: Admin Panel</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <div class="admin-panel-container">
        <h2>Admin Panel</h2>

        <?php
        // success/error message
        if (!empty($message)) {
            echo "<p>{$message}</p>";
            echo "<script>alert('$message')</script>"; // JavaScript to display message
        }
        ?>

        <div class="add-user-section">
            <h3>Add New User</h3>
            <form action="admin_panel.php" method="post">
                <label for="new_username">New Username:</label>
                <input type="text" name="new_username" required>

                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" required>

                <button type="submit" name="add_user">Add User</button>
            </form>
        </div>

        <div class="manage-fine-section">
            <h3>Manage Fine</h3> <!-- fine adding and editing are both done here. If fines associated -> edit, if no fine -> add fine -->
            <form action="admin_panel.php" method="post">
                <label for="incident_id">Incident ID:</label>
                <input type="text" name="incident_id" required>

                <label for="fine_amount">Fine Amount:</label>
                <input type="number" name="fine_amount" required>

                <label for="fine_points">Fine Points:</label>
                <input type="number" name="fine_points" required>

                <button type="submit" name="manage_fine">Manage Fine</button>
            </form>
        </div>

        <br>
        <a href="main_menu.php">Back to Main Menu</a>
    </div>
</body>
</html>