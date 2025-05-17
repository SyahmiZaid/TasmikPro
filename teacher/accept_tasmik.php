<?php
include '../database/db_connection.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tasmikid = $_POST['tasmikid'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    
    // Validate tasmikid
    if (empty($tasmikid)) {
        echo "Error: Tasmik ID is required.";
        exit;
    }
    
    // Prepare the SQL statement to update the status and add feedback
    $stmt = $conn->prepare("UPDATE tasmik SET status = 'accepted', feedback = ? WHERE tasmikid = ?");
    $stmt->bind_param("ss", $feedback, $tasmikid);

    // Execute the statement and check if it was successful
    if ($stmt->execute()) {
        echo "Tasmik accepted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>