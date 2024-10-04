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

// incidentID in url check
if (isset($_GET['incidentID'])) {
    $incidentID = validate($_GET['incidentID']);

    // Validation of editing report - modified from: https://www.w3schools.com/php/php_form_validation.asp
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $peopleLicence = validate($_POST['people_licence']);
        $vehicleLicence = validate($_POST['vehicle_licence']);
        $incidentDate = validate($_POST['incident_date']);
        $incidentReport = validate($_POST['incident_report']);
        $offenceDescription = validate($_POST['offence_description']);

        // get Offence_ID using selected offence description 
        $getOffenceID = $conn->prepare("SELECT Offence_ID FROM Offence WHERE Offence_Description = ?");
        $getOffenceID->bind_param("s", $offenceDescription);
        $getOffenceID->execute();
        $getOffenceID->bind_result($offenceID);
        $getOffenceID->fetch(); // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
        $getOffenceID->close();

        // update report in Incident table - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $editReport = $conn->prepare("UPDATE Incident
                                          SET People_ID = (SELECT People_ID FROM People WHERE LOWER(People_licence) = LOWER(?)),
                                              Vehicle_ID = (SELECT Vehicle_ID FROM Vehicle WHERE LOWER(Vehicle_licence) = LOWER(?)),
                                              Incident_Date = ?,
                                              Incident_Report = ?,
                                              Offence_ID = ?
                                          WHERE Incident_ID = ?");
        $editReport->bind_param("sssssi", $peopleLicence, $vehicleLicence, $incidentDate, $incidentReport, $offenceID, $incidentID);

        if ($editReport->execute()) {
            $message = "Report edited successfully.";
        } else {
            $message = "Error editing report.";
        }
        // log report edit
        $logEvent = "Edit Report";
        $logDetails = "User {$username} edited a report with Incident ID: {$incidentID}.";
        logEvent($logEvent, $logDetails, $username);
        $editReport->close();
    }

    // get details of selected report - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $getReportDetails = $conn->prepare("SELECT Incident_Date, People_licence, Vehicle_licence, Incident_Report, Offence_Description
                                           FROM Incident
                                           JOIN People ON Incident.People_ID = People.People_ID
                                           JOIN Vehicle ON Incident.Vehicle_ID = Vehicle.Vehicle_ID
                                           JOIN Offence ON Incident.Offence_ID = Offence.Offence_ID
                                           WHERE Incident_ID = ?");
    $getReportDetails->bind_param("i", $incidentID);
    $getReportDetails->execute();
    $getReportDetails->bind_result($incidentDate, $peopleLicence, $vehicleLicence, $incidentReport, $offenceDescription);
    $getReportDetails->fetch(); // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
    $getReportDetails->close();
} else {
    // if no incidentID redirect to search_report.php
    header("Location: search_report.php");
    exit();
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
    <title>Police: Edit Report</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Edit Report</h2>

    <?php
    // Incident_ID
    echo "<p>Incident ID: {$incidentID}</p>";

    // success/error message
    if (!empty($message)) {
        echo "<p>{$message}</p>";
        echo "<script>alert('$message')</script>"; // JavaScript to display message
    }
    ?>

    <form action="edit_report.php?incidentID=<?php echo $incidentID; ?>" method="post">
        <label for="people_licence">Driving Licence Number:</label>
        <input type="text" name="people_licence" value="<?php echo $peopleLicence; ?>" required>

        <label for="vehicle_licence">Licence Plate Number:</label>
        <input type="text" name="vehicle_licence" value="<?php echo $vehicleLicence; ?>" required>

        <label for="incident_date">Incident Date (YYYY-MM-DD):</label>
        <input type="text" name="incident_date" value="<?php echo $incidentDate; ?>" placeholder="YYYY-MM-DD" required>

        <label for="incident_report">Incident Report (Max 500 characters):</label>
        <textarea name="incident_report" rows="4" maxlength="500" required><?php echo $incidentReport; ?></textarea>

        <label for="offence_description">Offence Description:</label>
        <select name="offence_description" required>
            <?php
            // get offence descriptions
            $getOffences = $conn->prepare("SELECT Offence_Description FROM Offence");
            $getOffences->execute();
            $getOffences->bind_result($offenceDesc);
            
            while ($getOffences->fetch()) { // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
                // choose selected description
                $selected = ($offenceDesc == $offenceDescription) ? "selected" : "";
                echo "<option value=\"{$offenceDesc}\" {$selected}>{$offenceDesc}</option>";
            }

            $getOffences->close();
            ?>
        </select>

        <button type="submit">Save Changes</button>
    </form>

    <br>
    <a href="search_report.php">Back to Search Report</a>
</body>
</html>