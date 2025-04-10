<?php
require_once '../database/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['studentid'];

    // Validate input
    if (empty($student_id)) {
        echo "Invalid student ID.";
        exit;
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Fetch the userid associated with the studentid
        $stmt = $conn->prepare("SELECT userid FROM student WHERE studentid = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->bind_result($userid);
        $stmt->fetch();
        $stmt->close();

        if (empty($userid)) {
            throw new Exception("Student not found.");
        }

        // Delete from the student table
        $stmt = $conn->prepare("DELETE FROM student WHERE studentid = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->close();

        // Delete from the users table
        $stmt = $conn->prepare("DELETE FROM users WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        echo "Student removed successfully.";
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