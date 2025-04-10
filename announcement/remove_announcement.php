<?php
require_once '../database/db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../authentication/signin.php");
    exit();
}

// Get the user's role
$role = $_SESSION['role'];

// Only allow admin and teacher to delete announcements
if ($role != 'admin' && $role != 'teacher') {
    header("Location: announcement.php");
    exit();
}

// Get the announcement ID from the URL
$announcement_id = $_GET['id'];

// Delete the announcement from the database
$stmt = $conn->prepare("DELETE FROM announcement WHERE announcementid = ?");
$stmt->bind_param("s", $announcement_id); // Use "s" for string

if ($stmt->execute()) {
    header("Location: announcement.php?message=Announcement deleted successfully.");
} else {
    header("Location: announcement.php?error=Error deleting announcement.");
}

$stmt->close();
$conn->close();
?>