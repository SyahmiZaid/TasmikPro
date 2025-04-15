<?php
require_once '../database/db_connection.php';

// Initialize variables
$assessmentId = isset($_GET['assessment_id']) ? $conn->real_escape_string($_GET['assessment_id']) : '';
$courseId = isset($_GET['courseid']) ? $conn->real_escape_string($_GET['courseid']) : '';
$errorMessage = '';
$successMessage = '';

// Check if assessment ID is provided
if (!empty($assessmentId)) {
    // Start a transaction
    $conn->begin_transaction();
    try {
        // Delete the assessment
        $deleteSql = "DELETE FROM vle_assessments WHERE assessmentid = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("s", $assessmentId);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();
        $successMessage = "Assessment deleted successfully.";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $errorMessage = "Error occurred while deleting the assessment: " . $e->getMessage();
    }
} else {
    $errorMessage = "Invalid assessment ID.";
}

// Redirect back to the course page with a success or error message
if (!empty($successMessage)) {
    header("Location: course.php?courseid=$courseId&success=" . urlencode($successMessage));
} else {
    header("Location: course.php?courseid=$courseId&error=" . urlencode($errorMessage));
}
exit;
?>