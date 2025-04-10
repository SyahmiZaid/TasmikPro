<?php
include '../database/db_connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tasmikid = $_POST['tasmikid'];

    // Prepare the SQL statement to update the status
    $stmt = $conn->prepare("UPDATE tasmik SET status = 'rejected' WHERE tasmikid = ?");
    $stmt->bind_param("s", $tasmikid);

    // Execute the statement and check if it was successful
    if ($stmt->execute()) {
        echo "Tasmik rejected successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>