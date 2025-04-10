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
        // Check if the student exists
        $stmt = $conn->prepare("SELECT studentid FROM student WHERE studentid = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->bind_result($existing_student_id);
        $stmt->fetch();
        $stmt->close();

        if (empty($existing_student_id)) {
            throw new Exception("Student not found.");
        }

        // Remove the student from the halaqah by setting halaqahid to NULL
        $stmt = $conn->prepare("UPDATE student SET halaqahid = NULL WHERE studentid = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        echo "Student removed from halaqah successfully.";
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