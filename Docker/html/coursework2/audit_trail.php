<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks admin login
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'daniels') {
    header("Location: index.html");
    exit();
}

// validation function - modified from: https://www.w3schools.com/php/php_form_validation.asp
function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validation of search and filter - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = validate($_POST['search_term']);
    $filterEvent = validate($_POST['filter_event']);
}

// query for searching
$sql = "SELECT timestamp, username, event, details FROM Audit";

// adding WHERE conditions
$conditions = array();
if (!empty($searchTerm)) {
    $conditions[] = "username LIKE '%$searchTerm%'";
}
if (!empty($filterEvent)) {
    $conditions[] = "event = '$filterEvent'";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions); // modified from: https://www.w3schools.com/php/func_string_implode.asp
}

// order by timestamp
$sql .= " ORDER BY timestamp DESC";

$result = $conn->query($sql);

// pagination - modified from: https://www.javatpoint.com/php-pagination
$resultsPerPage = 10;
$totalResults = $result->num_rows;
$totalPages = ceil($totalResults / $resultsPerPage);
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($currentPage - 1) * $resultsPerPage;

// modify SQL for pagination
$sql .= " LIMIT $start, $resultsPerPage";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head> <!-- adapted from: https://www.w3schools.com/tags/tag_meta.asp -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police: Audit Trail</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Audit Trail</h2>

    <!-- Form -->
    <form action="audit_trail.php" method="post">
        <label for="search_term">Search by Username:</label>
        <input type="text" name="search_term">

        <label for="filter_event">Filter by Event:</label>
        <select name="filter_event">
            <option value="">All Events</option>
            <option value="Login">Login</option>
            <option value="Add Person">Add Person</option>
            <option value="Add Report">Add Report</option>
            <option value="Add Vehicle">Add Vehicle</option>
            <option value="Search People">Search People</option>
            <option value="Search Vehicle">Search Vehicle</option>
            <option value="Search Report">Search Report</option>
            <option value="Edit Report">Edit Report</option>
            <option value="Add User">Add User</option>
            <option value="Add Fine">Add Fine</option>
            <option value="Edit Fine">Edit Fine</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <!-- search results -->
    <?php
    if (isset($result) && $result->num_rows > 0) {
        echo "<table border='1' style='width: 100%;'>";
        echo "<tr><th style='width: 20%;'>Timestamp</th><th style='width: 20%;'>Username</th><th style='width: 20%;'>Event</th><th style='width: 40%;'>Details</th></tr>";

        // display results in range - modified from: https://www.w3schools.com/php/func_mysqli_fetch_assoc.asp
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['timestamp']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['event']}</td>";
            echo "<td>{$row['details']}</td>";
            echo "</tr>";
        }

        echo "</table>";

        // pagination - modified from: https://www.javatpoint.com/php-pagination
        echo "<div class='pagination'>";
        for ($i = 1; $i <= $totalPages; $i++) {
            // make current page bold
            $style = ($i == $currentPage) ? 'font-weight: bold;' : '';
            echo "<a href='audit_trail.php?page=$i' style='$style'>$i</a> ";
        }
        echo "</div>";
    } elseif (isset($result) && $result->num_rows === 0) {
        echo "<p>No results found.</p>";
    }
    ?>
    
    <br>
    <a href="main_menu.php">Back to Main Menu</a>
</body>
</html>