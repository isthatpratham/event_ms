<?php
session_start();
session_destroy(); // Destroy the session to log the user out
header("Location: ../organizerlogin.php"); // Redirect to login page after logout
exit();
?>
