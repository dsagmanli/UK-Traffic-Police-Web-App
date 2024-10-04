<!DOCTYPE html>
<html>
	<body>
		<h1>Form Test</h1>
<form method="POST">
 Name: <input type="text" name="name"><br/>
 Phone: <input type="text" name="phone"><br/>
 <input type="submit" value="Add Record">
</form>
<hr/> 

		<?php
		// MySQL database information
		$servername = "mariadb";
		$username = "root";
		$password = "rootpwd";
		$dbname = "labexercise8";

		$conn = mysqli_connect($servername, $username, $password, $dbname);
		
		if(mysqli_connect_errno())
		{
 			echo "Failed to connect to MySQL:".mysqli_connect_error();
 			die();
		}
		else
 			echo "MySQL connection OK<br/><br/>";
/*
if ($_POST['name']!="" && $_POST['phone']!="")
{
$sql = "INSERT INTO People(Name, PhoneNumber)
 VALUES ('".$_POST['name']."',".$_POST['phone'].");";
$result = mysqli_query($conn, $sql);
} 
*/
		// other code here!

if ($_GET['del']!="")
{
 $sql = "DELETE FROM People
 WHERE ID=".$_GET['del'].";";
 $result = mysqli_query($conn, $sql);
}

		// construct the SELECT query
		$sql = "SELECT * FROM People ORDER BY Name;";
		// send query to database
		$result = mysqli_query($conn, $sql);
		
		echo mysqli_num_rows($result)." rows<br/>";

if (mysqli_num_rows($result) > 0) {
    echo "<ul>";

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>";
        echo $row["Name"] . " (phone: " . $row["PhoneNumber"] . ") ";
$id = $row["ID"];
echo "<a href='?del=$id'>delete</a>"; 
        echo "</li>";
    }

    echo "</ul>";
} else {
    echo "Database is empty";
}
		mysqli_close($conn); 

		?> 
	</body>
</html>