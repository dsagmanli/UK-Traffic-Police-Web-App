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

// Validation of adding new report - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $peopleLicence = validate($_POST['people_licence']);
    $vehicleLicence = validate($_POST['vehicle_licence']);
    $incidentDate = validate($_POST['incident_date']);
    $incidentReport = validate($_POST['incident_report']);
    $offenceDescription = validate($_POST['offence_description']);

    // Checking whether a person with the same licence already exists - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $checkPerson = $conn->prepare("SELECT * FROM People WHERE LOWER(People_licence) = LOWER(?)");
    $checkPerson->bind_param("s", $peopleLicence);
    $checkPerson->execute();
    $checkPerson->store_result();

    if ($checkPerson->num_rows == 0) {
        $message = "Person is not in the database. Please add them first.";
    } else {
        // Checking whether entered Vehicle_licence is in the database - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $checkVehicle = $conn->prepare("SELECT * FROM Vehicle WHERE LOWER(Vehicle_licence) = LOWER(?)");
        $checkVehicle->bind_param("s", $vehicleLicence);
        $checkVehicle->execute();
        $checkVehicle->store_result(); // modified from: https://www.w3schools.com/php/php_ref_mysqli.asp

        if ($checkVehicle->num_rows == 0) {
            $message = "Vehicle is not in the database. Please add it first.";
        } else {
            // Get Offence_ID based on the selected offence description - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
            $getOffenceID = $conn->prepare("SELECT Offence_ID FROM Offence WHERE Offence_Description = ?");
            $getOffenceID->bind_param("s", $offenceDescription);
            $getOffenceID->execute();
            $getOffenceID->bind_result($offenceID);
            $getOffenceID->fetch(); // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
            $getOffenceID->close();

            // insert new report to Incident table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
            $addReport = $conn->prepare("INSERT INTO Incident (Incident_ID, Vehicle_ID, People_ID, Incident_Date, Incident_Report, Offence_ID)
                                             SELECT 
                                                    (SELECT COALESCE(MAX(Incident_ID), 0) + 1 FROM Incident),
                                                    (SELECT Vehicle_ID FROM Vehicle WHERE LOWER(Vehicle_licence) = LOWER(?)),
                                                    (SELECT People_ID FROM People WHERE LOWER(People_licence) = LOWER(?)),
                                                    ?,
                                                    ?,
                                                    ?");
            $addReport->bind_param("sssss", $vehicleLicence, $peopleLicence, $incidentDate, $incidentReport, $offenceID);

            if ($addReport->execute()) {
                $message = "Report added successfully.";
                // log new report add
                $logEvent = "Add Report";
                $logDetails = "User {$username} added a report. Driving Licence#: {$peopleLicence}";
                logEvent($logEvent, $logDetails, $username);

            } else {
                $message = "Error adding report.";
            }

            $addReport->close();
        }

        $checkVehicle->close();
    }

    $checkPerson->close();
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
    <title>Police: Add Report</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Add Report</h2>

    <?php
    // success/error message
    if (!empty($message)) {
        echo "<p>{$message}</p>";
        echo "<script>alert('$message')</script>"; // JavaScript to display message
    }
    ?>

    <form action="add_report.php" method="post">
        <label for="people_licence">Driving Licence Number:</label>
        <input type="text" name="people_licence" required>

        <label for="vehicle_licence">Licence Plate Number:</label>
        <input type="text" name="vehicle_licence" required>

        <label for="incident_date">Incident Date (YYYY-MM-DD):</label>
        <input type="text" name="incident_date" placeholder="YYYY-MM-DD" required>

        <label for="incident_report">Incident Report (Max 500 characters):</label>
        <textarea name="incident_report" rows="4" maxlength="500" required></textarea>

        <label for="offence_description">Offence Description:</label>
        <select name="offence_description" required>
            <?php
            // get offence descriptions from Offence table
            $getOffences = $conn->prepare("SELECT Offence_Description FROM Offence");
            $getOffences->execute();
            $getOffences->bind_result($offenceDescription);
            
            while ($getOffences->fetch()) { // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
                echo "<option value=\"{$offenceDescription}\">{$offenceDescription}</option>";
            }

            $getOffences->close();
            ?>
        </select>

        <button type="submit">Add Report</button>
    </form>

    <br>
    <a href="main_menu.php">Back to Main Menu</a>
</body>
</html>