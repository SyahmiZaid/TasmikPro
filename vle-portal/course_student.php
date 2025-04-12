<?php
$pageTitle = "Course";
$breadcrumb = "Pages / VLE - Student Portal / Course";
include '../include/header.php';

// Get the logged-in user's ID
$userid = $_SESSION['userid'] ?? ''; // Assuming userid is stored in session

// Database connection
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the student ID using the user ID
$studentSql = "SELECT studentid FROM student WHERE userid = ?";
$studentStmt = $conn->prepare($studentSql);
$studentStmt->bind_param("s", $userid);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows > 0) {
    $studentRow = $studentResult->fetch_assoc();
    $studentId = $studentRow['studentid'];
} else {
    die("Student ID not found for the logged-in user.");
}

// Get course ID from URL parameter
$courseId = isset($_GET['courseid']) ? $_GET['courseid'] : '';

// Get course details
$courseSql = "SELECT c.course_name, u.firstname, u.lastname 
              FROM vle_courses c
              JOIN teacher t ON c.created_by = t.teacherid
              JOIN users u ON t.userid = u.userid
              WHERE c.courseid = ?";
$courseStmt = $conn->prepare($courseSql);
$courseStmt->bind_param("s", $courseId);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

if ($courseResult->num_rows > 0) {
    $course = $courseResult->fetch_assoc();
    $teacherName = $course['firstname'] . ' ' . $course['lastname'];
    $courseName = $course['course_name'];
} else {
    $teacherName = "Unknown Teacher";
    $courseName = "Course Not Found";
}

// Get assessments for this course created by the course's teacher
$assessmentSql = "SELECT a.* 
                  FROM vle_assessments a
                  JOIN vle_courses c ON a.courseid = c.courseid
                  WHERE a.courseid = ? AND c.created_by = a.teacherid
                  ORDER BY a.due_date DESC";
$assessmentStmt = $conn->prepare($assessmentSql);
$assessmentStmt->bind_param("s", $courseId);
$assessmentStmt->execute();
$assessmentResult = $assessmentStmt->get_result();

$assessments = [];
while ($row = $assessmentResult->fetch_assoc()) {
    $assessments[] = $row;
}
?>

<div class="container">
    <link rel="stylesheet" href="style.css" />
    <div class="page-inner">
        <!-- Banner section -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="custom-banner-container" style="height: 200px; margin-top: 0; padding-top: 0;">
                    <div class="custom-banner-bg"></div>
                    <div class="custom-banner-content col-md-8">
                        <h1 class="custom-banner-title"><?php echo htmlspecialchars($courseName); ?></h1>
                        <p class="custom-banner-subtitle">Instructor: <?php echo htmlspecialchars($teacherName); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">Course</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-grades-tab" data-bs-toggle="pill" href="#pills-grades" role="tab" aria-controls="pills-grades" aria-selected="false">Grades</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-announcements-tab" data-bs-toggle="pill" href="#pills-announcements" role="tab" aria-controls="pills-announcements" aria-selected="false">Announcements</a>
                </li>
            </ul>
            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                <!-- Course Assignments and Assessments -->
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                    <?php if (count($assessments) > 0): ?>
                        <?php foreach ($assessments as $assessment): ?>
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <h4 class="card-title mb-0 me-2" style="color: black">
                                                <?php echo htmlspecialchars($assessment['type'] == 'assignment' ? 'Assignment: ' : 'Quiz: '); ?>
                                                <?php echo htmlspecialchars($assessment['title']); ?>
                                            </h4>
                                            <span class="badge bg-<?php echo $assessment['status'] == 'published' ? 'success' : ($assessment['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p><?php echo htmlspecialchars($assessment['description']); ?></p>
                                        <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                                        <?php if ($assessment['type'] == 'quiz' && !empty($assessment['duration_minutes'])): ?>
                                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($assessment['duration_minutes']); ?> minutes</p>
                                        <?php endif; ?>
                                        <a href="assessment_submission.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="btn btn-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="alert alert-info" role="alert">
                                        No assessments found for this course.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Grades Tab -->
                <div class="tab-pane fade" id="pills-grades" role="tabpanel" aria-labelledby="pills-grades-tab">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0" style="color: black">Grades</h4>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get grades for the student
                                $gradesSql = "SELECT a.title, a.type, sub.submitted_at, sub.status, sub.score 
                                              FROM vle_assessments a
                                              JOIN vle_assessment_submissions sub ON a.assessmentid = sub.assessmentid
                                              WHERE a.courseid = ? AND sub.studentid = ?
                                              ORDER BY a.title";
                                $gradesStmt = $conn->prepare($gradesSql);
                                $gradesStmt->bind_param("ss", $courseId, $studentId);
                                $gradesStmt->execute();
                                $gradesResult = $gradesStmt->get_result();
                                ?>

                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Type</th>
                                            <th>Submitted</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($gradesResult->num_rows > 0): ?>
                                            <?php while ($grade = $gradesResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($grade['title']); ?></td>
                                                    <td><?php echo ucfirst(htmlspecialchars($grade['type'])); ?></td>
                                                    <td><?php echo date('F j, Y, g:i a', strtotime($grade['submitted_at'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $grade['status'] == 'graded' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst(htmlspecialchars($grade['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($grade['status'] == 'graded' && $grade['score'] !== null): ?>
                                                            <?php echo htmlspecialchars($grade['score']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not graded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No grades available for this course.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements Tab -->
                <div class="tab-pane fade" id="pills-announcements" role="tabpanel" aria-labelledby="pills-announcements-tab">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0" style="color: black">Announcements</h4>
                            </div>
                            <div class="card-body">
                                <?php
                                // Get announcements for the course
                                $announcementSql = "SELECT * FROM announcement WHERE target_audience = ? ORDER BY created_at DESC";
                                $announcementStmt = $conn->prepare($announcementSql);
                                $announcementStmt->bind_param("s", $courseName);
                                $announcementStmt->execute();
                                $announcementResult = $announcementStmt->get_result();

                                if ($announcementResult->num_rows > 0): ?>
                                    <?php while ($announcement = $announcementResult->fetch_assoc()): ?>
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                                                <p class="text-muted">Posted on: <?php echo date('F j, Y, g:i a', strtotime($announcement['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="alert alert-info" role="alert">
                                        No announcements found for this course.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Close database connection
$conn->close();
include '../include/footer.php';
?>