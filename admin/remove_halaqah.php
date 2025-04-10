<?php
require_once '../database/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $halaqah_id = $_POST['halaqahid'];

    // Validate input
    if (empty($halaqah_id)) {
        echo "Invalid halaqah ID.";
        exit;
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Check if the halaqah exists
        $stmt = $conn->prepare("SELECT halaqahid FROM halaqah WHERE halaqahid = ?");
        $stmt->bind_param("s", $halaqah_id);
        $stmt->execute();
        $stmt->bind_result($existing_halaqah_id);
        $stmt->fetch();
        $stmt->close();

        if (empty($existing_halaqah_id)) {
            throw new Exception("Halaqah not found.");
        }

        // Delete references from the teacher table
        $stmt = $conn->prepare("UPDATE teacher SET halaqahid = NULL WHERE halaqahid = ?");
        $stmt->bind_param("s", $halaqah_id);
        $stmt->execute();
        $stmt->close();

        // Delete references from the student table
        $stmt = $conn->prepare("UPDATE student SET halaqahid = NULL WHERE halaqahid = ?");
        $stmt->bind_param("s", $halaqah_id);
        $stmt->execute();
        $stmt->close();

        // Delete from the halaqah table
        $stmt = $conn->prepare("DELETE FROM halaqah WHERE halaqahid = ?");
        $stmt->bind_param("s", $halaqah_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        echo "Halaqah removed successfully.";

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
} else {
    echo "Invalid request method.";
}
?>