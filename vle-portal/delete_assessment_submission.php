<!-- filepath: c:\Users\Asus\OneDrive\Desktop\FYP\(4) Development\TasmikPro\vle-portal\delete_assessment_submission.php -->
<?php
require_once '../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionId = $_POST['submission_id'] ?? '';
    $assessmentId = $_POST['assessment_id'] ?? ''; // Get the assessment ID from the form

    if (!empty($submissionId) && !empty($assessmentId)) {
        // Delete the submission from the database
        $deleteSubmissionSql = "DELETE FROM vle_assessment_submissions WHERE submissionid = ?";
        $deleteSubmissionStmt = $conn->prepare($deleteSubmissionSql);
        $deleteSubmissionStmt->bind_param("s", $submissionId);

        if ($deleteSubmissionStmt->execute()) {

            // Update is_done to 0
            $updateIsDoneSql = "UPDATE vle_assessment_submissions 
                                SET is_done = 0 
                                WHERE assessmentid = ? AND studentid = ?";
            $updateIsDoneStmt = $conn->prepare($updateIsDoneSql);
            $updateIsDoneStmt->bind_param("ss", $assessmentId, $_SESSION['studentid']);
            $updateIsDoneStmt->execute();

            // Redirect to the assessment submission page with success message
            header("Location: assessment_submission.php?id=$assessmentId&message=Submission deleted successfully.");
            exit;
        } else {
            // Redirect with error message
            header("Location: assessment_submission.php?id=$assessmentId&error=Failed to delete submission.");
            exit;
        }
    } else {
        // Redirect with error message
        header("Location: assessment_submission.php?id=$assessmentId&error=Invalid submission ID.");
        exit;
    }
} else {
    // Redirect if accessed directly
    header("Location: assessment_submission.php");
    exit;
}
?>