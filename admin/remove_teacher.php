<?php
require_once '../database/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_POST['teacherid'];

    // Validate input
    if (empty($teacher_id)) {
        echo "Invalid teacher ID.";
        exit;
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Fetch the userid associated with the teacherid
        $stmt = $conn->prepare("SELECT userid FROM teacher WHERE teacherid = ?");
        $stmt->bind_param("s", $teacher_id);
        $stmt->execute();
        $stmt->bind_result($userid);
        $stmt->fetch();
        $stmt->close();

        if (empty($userid)) {
            throw new Exception("Teacher not found.");
        }

        // Delete from the teacher table
        $stmt = $conn->prepare("DELETE FROM teacher WHERE teacherid = ?");
        $stmt->bind_param("s", $teacher_id);
        $stmt->execute();
        $stmt->close();

        // Delete from the users table
        $stmt = $conn->prepare("DELETE FROM users WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        echo "Teacher removed successfully.";
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