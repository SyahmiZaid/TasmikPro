<?php
require_once '../database/db_connection.php';

$assessmentId = $_POST['assessment_id'] ?? null;
$studentId = $_POST['student_id'] ?? null;
$toggleState = $_POST['toggle_state'] ?? null; // 1 = Mark as Done, 0 = Undo

if (!$assessmentId || !$studentId || $toggleState === null) {
    echo 'Missing parameters';
    exit;
}

$sql = "UPDATE vle_assessment_submissions SET is_done = ? WHERE assessmentid = ? AND studentid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $toggleState, $assessmentId, $studentId);

if ($stmt->execute()) {
    echo 'Success';
} else {
    echo 'Error: ' . $stmt->error;
}
?>