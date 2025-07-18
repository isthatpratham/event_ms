<?php
session_start();

// Check if organizer is logged in
if (!isset($_SESSION['organizer_loggedin']) || $_SESSION['organizer_loggedin'] !== true) {
    header("Location: ../organizerlogin.php");
    exit();
}

if (!isset($_GET['pt_id'])) {
    header("Location: manage_teams.php");
    exit();
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'eveflow_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pt_id = $_GET['pt_id'];

// Fetch certificate
$stmt = $conn->prepare("SELECT content, student_name, event_name FROM participations WHERE pt_id = ?");
$stmt->bind_param("i", $pt_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($content, $student_name, $event_name);
    $stmt->fetch();
    
    header("Content-type: application/pdf");
    header("Content-Disposition: attachment; filename=\"Certificate_".str_replace(' ', '_', $student_name)."_".str_replace(' ', '_', $event_name).".pdf\"");
    echo $content;
} else {
    $_SESSION['upload_message'] = "Certificate not found!";
    $_SESSION['upload_status'] = "danger";
    header("Location: manage_teams.php");
}

$stmt->close();
$conn->close();
?>