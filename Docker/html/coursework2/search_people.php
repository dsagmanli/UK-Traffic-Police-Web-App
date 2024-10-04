<?php
require("db.inc.php"); // taken from module material
session_start();

// Checks user login
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$searchResults = []; // store results

// Validation of search - modified from: https://www.w3schools.com/php/php_form_validation.asp
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = validate($_POST['search_term']);

    // search by name - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $names = $conn->prepare("SELECT * FROM People WHERE LOWER(People_name) LIKE LOWER(?)");
    $searchTermNames = '%' . $searchTerm . '%';
    $names->bind_param("s", $searchTermNames);
    $names->execute();
    $resultNames = $names->get_result();

    // search by driver's licence numbers - modified from: https://www.w3schools.com/php/php_mysql_prepared_statements.asp
    $licence = $conn->prepare("SELECT * FROM People WHERE LOWER(People_licence) LIKE LOWER(?)");
    $searchTermLicence = '%' . $searchTerm . '%';
    $licence->bind_param("s", $searchTermLicence);
    $licence->execute();
    $resultLicence = $licence->get_result();

    // log search
    $logEvent = "Search People";
    $logDetails = "User {$username} searched for a person with '{$searchTerm}'.";
    logEvent($logEvent, $logDetails, $username);

    //store the results - modified from: https://www.w3schools.com/php/func_mysqli_fetch_all.asp
    $searchResults = array_merge($resultNames->fetch_all(MYSQLI_ASSOC), $resultLicence->fetch_all(MYSQLI_ASSOC));

    $names->close();
    $licence->close();
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

// pagination - modified from: https://www.javatpoint.com/php-pagination
$resultsPerPage = 10;
$totalResults = count($searchResults);
$totalPages = ceil($totalResults / $resultsPerPage);
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($currentPage - 1) * $resultsPerPage;
$searchResults = array_slice($searchResults, $start, $resultsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head> <!-- adapted from: https://www.w3schools.com/tags/tag_meta.asp -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police: Search People</title>
    <link rel="stylesheet" href="css/css2.css"> 
</head>
<body>
    <h2>Search People</h2>
    <form action="search_people.php" method="post">
        <label for="search_term">Search by Name or Driver's Licence:</label>
        <input type="text" name="search_term" required>
        <button type="submit">Search</button>
    </form>

    <?php
    // search results
    if (!empty($searchResults)) {
        echo "<h3>Search Results:</h3>";
        echo "<table border='1' style='width: 100%;'>";
        echo "<tr><th>Name</th><th>Driver's Licence</th><th>Address</th></tr>";

        foreach ($searchResults as $person) {
            echo "<tr>";
            echo "<td>{$person['People_name']}</td>";
            echo "<td>{$person['People_licence']}</td>";
            echo "<td>{$person['People_address']}</td>";
            echo "</tr>";
        }

        echo "</table>";

        // pagination - modified from: https://www.javatpoint.com/php-pagination
        echo "<div class='pagination'>";
        for ($i = 1; $i <= $totalPages; $i++) {
            // make current page bold
            $style = ($i == $currentPage) ? 'font-weight: bold;' : '';
            echo "<a href='search_people.php?page=$i' style='$style'>$i</a> ";
        }
        echo "</div>";
    } else {
        echo "<p>No results found for the given search term.</p>";
    }
    ?>

    <br>
    <a href="main_menu.php">Back to Main Menu</a>
</body>
</html>