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
    
    // Validate feedback - required for rejection
    if (empty($feedback)) {
        echo "Error: Feedback is required when rejecting a tasmik.";
        exit;
    }
    
    // Prepare the SQL statement to update the status and add feedback
    // Using 'repeated' status based on your existing badge setup
    $stmt = $conn->prepare("UPDATE tasmik SET status = 'repeated', feedback = ? WHERE tasmikid = ?");
    $stmt->bind_param("ss", $feedback, $tasmikid);

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
