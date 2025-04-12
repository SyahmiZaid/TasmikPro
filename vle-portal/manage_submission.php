<?php
$pageTitle = "Manage Submissions";
$breadcrumb = "Pages / VLE - Teacher Portal / Course / View Assessment / Manage Submissions";
include '../include/header.php';

// Get the logged-in user's ID
$userid = $_SESSION['userid'] ?? ''; // Assuming userid is stored in session

// Database connection
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the teacher ID using the user ID
$teacherSql = "SELECT teacherid FROM teacher WHERE userid = ?";
$teacherStmt = $conn->prepare($teacherSql);
$teacherStmt->bind_param("s", $userid);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();

if ($teacherResult->num_rows > 0) {
    $teacherRow = $teacherResult->fetch_assoc();
    $teacherId = $teacherRow['teacherid'];
} else {
    die("Teacher ID not found for the logged-in user.");
}

// Get assessment ID from URL parameter
$assessmentId = isset($_GET['assessment_id']) ? $_GET['assessment_id'] : '';
if (empty($assessmentId)) {
    die("Assessment ID is required.");
}

// Get assessment details
$assessmentSql = "SELECT a.title, c.course_name, a.due_date 
                  FROM vle_assessments a
                  JOIN vle_courses c ON a.courseid = c.courseid
                  WHERE a.assessmentid = ? AND a.teacherid = ?";
$assessmentStmt = $conn->prepare($assessmentSql);
$assessmentStmt->bind_param("ss", $assessmentId, $teacherId);
$assessmentStmt->execute();
$assessmentResult = $assessmentStmt->get_result();

if ($assessmentResult->num_rows > 0) {
    $assessment = $assessmentResult->fetch_assoc();
    $assessmentTitle = $assessment['title'];
    $courseName = $assessment['course_name'];
    $dueDate = $assessment['due_date']; // Fetch the due date
} else {
    die("Assessment not found or you do not have permission to view it.");
}

// Get student submissions for the assessment
$submissionSql = "SELECT sub.submissionid, sub.file_path, sub.status, sub.submitted_at, sub.score, 
                         s.studentid, u.firstname, u.lastname 
                  FROM vle_assessment_submissions sub
                  JOIN student s ON sub.studentid = s.studentid
                  JOIN users u ON s.userid = u.userid
                  WHERE sub.assessmentid = ?
                  ORDER BY sub.submitted_at DESC";
$submissionStmt = $conn->prepare($submissionSql);
$submissionStmt->bind_param("s", $assessmentId);
$submissionStmt->execute();
$submissionResult = $submissionStmt->get_result();
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($pageTitle); ?></h4>
        </div>

        <style>
            .custom-header {
                background-color: rgb(198, 225, 255) !important;
                color: #0056b3 !important;
                border-color: rgb(104, 137, 184) !important;
                font-size: 1.25rem !important;
                padding: 1rem !important;
            }
            .custom-header-submission {
                background-color: rgb(173, 235, 173) !important;
                color: #004d00 !important;
                border-color: rgb(120, 200, 120) !important;
                font-size: 1.25rem !important;
                padding: 1rem !important;
            }
        </style>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header custom-header">
                        <h4 class="card-title mb-0"><i class="fas fa-book"></i> <?php echo htmlspecialchars($assessmentTitle); ?></h4>
                        <p class="text-black-50 mb-0">Course: <?php echo htmlspecialchars($courseName); ?></p>
                    </div>
                    <div class="card-body">
                        <p>Below is the list of submissions for this assessment. You can view, grade, and manage each submission.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header custom-header-submission">
                        <h4 class="card-title mb-0"><i class="fas fa-file-alt"></i> Student Submissions</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($submissionResult->num_rows > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Submitted At</th>
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Submission Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($submission = $submissionResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($submission['firstname'] . ' ' . $submission['lastname']); ?></td>
                                            <td><?php echo date('F j, Y, g:i a', strtotime($submission['submitted_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $submission['status'] == 'graded' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($submission['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($submission['status'] == 'graded' && $submission['score'] !== null): ?>
                                                    <?php echo htmlspecialchars($submission['score']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not graded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $submittedAt = strtotime($submission['submitted_at']);
                                                $dueDateTimestamp = strtotime($dueDate);

                                                if ($submittedAt > $dueDateTimestamp): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">On Time</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="grade_submission.php?submissionid=<?php echo htmlspecialchars($submission['submissionid']); ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-pen"></i> Grade
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                No submissions found for this assessment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="javascript:history.back()" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php
// Close database connection
$conn->close();
include '../include/footer.php';
?>