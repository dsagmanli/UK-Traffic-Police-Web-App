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

// Validation of adding new vehicle - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = validate($_POST['type']);
    $colour = validate($_POST['colour']);
    $vehicleLicence = validate($_POST['vehicle_licence']);
    $peopleLicence = validate($_POST['people_licence']);

    // check whether the licence plate exceeds 7 characters
    if (strlen($vehicleLicence) > 7) {
        $message = "The licence plate cannot be longer than 7 characters.";
    } elseif (strpos($vehicleLicence, ' ') !== false) {
        $message = "The licence plate cannot contain whitespaces.";
    } else {
        // check whether vehicle with given licence plate already exists - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $checkVehicle = $conn->prepare("SELECT Vehicle_ID FROM Vehicle WHERE Vehicle_licence = ?");
        $checkVehicle->bind_param("s", $vehicleLicence);
        $checkVehicle->execute();
        $checkVehicle->store_result();

        if ($checkVehicle->num_rows > 0) {
            $message = "A vehicle with the same licence plate number already exists.";
        } else {
            // check whether owner with given People_licence exists - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
            $checkOwner = $conn->prepare("SELECT People_ID FROM People WHERE People_licence = ?");
            $checkOwner->bind_param("s", $peopleLicence);
            $checkOwner->execute();
            $checkOwner->store_result();

            if ($checkOwner->num_rows == 0) {
                $message = "The owner is not in the database. Please add them first.";
            } else {
                // Add vehicle to Vehicle table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
                $addVehicle = $conn->prepare("INSERT INTO Vehicle (Vehicle_ID, Vehicle_type, Vehicle_colour, Vehicle_licence)
                                                 SELECT COALESCE(MAX(Vehicle_ID), 0) + 1, ?, ?, ?
                                                 FROM Vehicle");
                $addVehicle->bind_param("sss", $type, $colour, $vehicleLicence);

                // check successful vehicle insertion before inserting into ownership
                if ($addVehicle->execute()) {
                    // Associate vehicle to the owner in Ownership table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
                    $associateOwner = $conn->prepare("INSERT INTO Ownership (People_ID, Vehicle_ID)
                                                          VALUES ((SELECT People_ID FROM People WHERE LOWER(People_licence) LIKE LOWER(?)),
                                                                  (SELECT Vehicle_ID FROM Vehicle WHERE Vehicle_licence LIKE LOWER(?)))");
                    $associateOwner->bind_param("ss", $peopleLicence, $vehicleLicence);

                    // check insert into ownership success
                    if (!$associateOwner->execute()) {
                        $message = "Error adding ownership information. Vehicle not added.";
                    } else {
                        $message = "Vehicle added successfully.";
                        // log new vehicle add
                        $logEvent = "Add Vehicle";
                        $logDetails = "User {$username} added a vehicle: Plate Number {$vehicleLicence}.";
                        logEvent($logEvent, $logDetails, $username);
                        $associateOwner->close();
                    }
                } else {
                    $message = "Error adding vehicle.";
                }

                $checkOwner->close();
            }
        }

        $checkVehicle->close();
    }
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
    <title>Police: Add Vehicle</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Add Vehicle</h2>

    <?php
    // success/error message
    if (!empty($message)) {
        echo "<p>{$message}</p>";
        echo "<script>alert('$message')</script>"; // JavaScript to display message
    }
    ?>

    <form action="add_vehicle.php" method="post">
        <label for="type">Vehicle Make and Model:</label>
        <input type="text" name="type" required>

        <label for="colour">Vehicle Colour:</label>
        <input type="text" name="colour" required>

        <label for="vehicle_licence">Vehicle Licence:</label>
        <input type="text" name="vehicle_licence" maxlength="7" required>

        <label for="people_licence">Owner's Licence:</label>
        <input type="text" name="people_licence" required>

        <button type="submit">Add Vehicle</button>
    </form>

    <br>
    <a href="main_menu.php">Back to Main Menu</a>
</body>
</html>