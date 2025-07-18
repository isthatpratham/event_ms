<?php
session_start();
session_destroy(); // Destroy the session to log the user out
header("Location: adminlogin.php"); // Redirect to login page after logout
exit();
?>
