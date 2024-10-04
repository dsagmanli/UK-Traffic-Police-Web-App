<?php
session_start();

$_SESSION = array(); // unset session variables

session_destroy(); // destroy session

header("Location: index.html"); // redirect to login
exit();
?>