<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks user login
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$message = ""; // success/error message
$reportResults = ""; // store report results

// Validation of search and edit - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = validate($_POST['search_term']);

    // check whether search term is in People or Vehicle - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $checkPerson = $conn->prepare("SELECT * FROM People WHERE LOWER(People_licence) = LOWER(?)");
    $checkPerson->bind_param("s", $searchTerm);
    $checkPerson->execute();
    $checkPerson->store_result();

    $checkVehicle = $conn->prepare("SELECT * FROM Vehicle WHERE LOWER(Vehicle_licence) = LOWER(?)");
    $checkVehicle->bind_param("s", $searchTerm);
    $checkVehicle->execute();
    $checkVehicle->store_result();

    if ($checkPerson->num_rows == 0 && $checkVehicle->num_rows == 0) {
        $message = "No matching records found.";
    } else {
        // get reports order by date - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
        $getReports = $conn->prepare("SELECT Incident_ID, Incident_Date, People_licence, Vehicle_licence, Incident_Report, Offence_Description
                                          FROM Incident
                                          JOIN People ON Incident.People_ID = People.People_ID
                                          JOIN Vehicle ON Incident.Vehicle_ID = Vehicle.Vehicle_ID
                                          JOIN Offence ON Incident.Offence_ID = Offence.Offence_ID
                                          WHERE LOWER(People_licence) = LOWER(?) OR LOWER(Vehicle_licence) = LOWER(?)
                                          ORDER BY Incident_Date DESC");

        $getReports->bind_param("ss", $searchTerm, $searchTerm);
        $getReports->execute();
        $getReports->bind_result($incidentID, $incidentDate, $peopleLicence, $vehicleLicence, $incidentReport, $offenceDescription);

        // Generate HTML for displaying report results in a table with an "Edit" button for each report
        $reportResults = "<table border='1' style='width: 100%;'>";
        $reportResults .= "<tr><th>Incident Date</th><th>Driving Licence Number</th><th>Licence Plate Number</th><th>Incident Report</th><th>Offence Description</th><th>Edit</th></tr>";
        
        while ($getReports->fetch()) { // modified from: https://www.phptutorial.net/php-pdo/php-fetch/#:~:text=The%20fetch()%20is%20a,row%20in%20the%20result%20set.
            $reportResults .= "<tr>";
            $reportResults .= "<td>{$incidentDate}</td>";
            $reportResults .= "<td>{$peopleLicence}</td>";
            $reportResults .= "<td>{$vehicleLicence}</td>";
            $reportResults .= "<td>{$incidentReport}</td>";
            $reportResults .= "<td>{$offenceDescription}</td>";
            $reportResults .= "<td><button onclick=\"editReport({$incidentID})\">Edit</button></td>";
            $reportResults .= "</tr>";
        }
        $reportResults .= "</table>";

        $getReports->close();
    }
    // log search
    $logEvent = "Search Report";
    $logDetails = "User {$username} searched for a report with '{$searchTerm}'.";
    logEvent($logEvent, $logDetails, $username);
    $checkPerson->close();
    $checkVehicle->close();
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
    <title>Police: Search Report</title>
    <link rel="stylesheet" href="css/css2.css"> 
    <script>
        // JS to edit report
        function editReport(incidentID) {
            // redirect to edit_report.php
            window.location.href = `edit_report.php?incidentID=${incidentID}`;
        }
    </script>
</head>
<body>
    <h2>Search Report</h2>

    <?php
    // success/error
    if (!empty($message)) {
        echo "<p>{$message}</p>";
        echo "<script>alert('$message')</script>"; // JavaScript to display message
    }
    ?>

    <form action="search_report.php" method="post">
        <label for="search_term">Search by Driving Licence Number or Licence Plate Number:</label>
        <input type="text" name="search_term" required>

        <button type="submit">Search</button>
    </form>

    <?php
    // report results
    echo $reportResults;
    ?>
    
    <br>
    <a href="main_menu.php">Back to Main Menu</a>
</body>
</html>