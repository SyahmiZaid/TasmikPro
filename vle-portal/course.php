<?php
$pageTitle = "Course";
$breadcrumb = "Pages / VLE - Teacher Portal / Course";
include '../include/header.php';

// Get course ID from URL parameter
$courseId = isset($_GET['courseid']) ? $_GET['courseid'] : '';

// Database connection
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Get assessments for this course
$assessmentSql = "SELECT a.*, t.teacherid, u.firstname, u.lastname 
                 FROM vle_assessments a
                 JOIN teacher t ON a.teacherid = t.teacherid
                 JOIN users u ON t.userid = u.userid
                 WHERE a.courseid = ?
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
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        </div>
        <!-- Banner section -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="custom-banner-container" style="Height: 200px">
                    <div class="custom-banner-bg"></div>
                    <div class="custom-banner-content col-md-8">
                        <!-- Content updated to use dynamic data -->
                        <h1 class="custom-banner-title"><?php echo htmlspecialchars($courseName); ?></h1>
                        <p class="custom-banner-subtitle"><?php echo htmlspecialchars($teacherName); ?></p>
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
                    <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">Participants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#pills-contact" role="tab" aria-controls="pills-contact" aria-selected="false">Grades</a>
                </li>
            </ul>
            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                    <!-- Add Assignment Button -->
                    <div class="mb-3">
                        <a href="create_assignment.php?courseid=<?php echo htmlspecialchars($courseId); ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Assignment
                        </a>
                    </div>

                    <!-- Course Assignments and Assessments -->
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
                                        <a href="edit_assessment.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="text-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    <style>
                                        /* Custom style for the View Details button */
                                        .custom-view-details {
                                            background-color: #f0f8ff;
                                            /* Light white with a hint of blue */
                                            color: #007bff;
                                            /* Blue text color */
                                            border: 1px solid #d1e7ff;
                                            /* Light blue border */
                                        }

                                        .custom-view-details:hover {
                                            background-color:rgb(198, 225, 255);
                                            /* Slightly darker blue on hover */
                                            color: #0056b3;
                                            /* Darker blue text on hover */
                                            border-color:rgb(104, 137, 184);
                                            /* Darker border on hover */
                                        }
                                    </style>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p><?php echo htmlspecialchars($assessment['description']); ?></p>
                                                <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                                                <?php if ($assessment['type'] == 'quiz' && !empty($assessment['duration_minutes'])): ?>
                                                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($assessment['duration_minutes']); ?> minutes</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 d-flex justify-content-end align-items-end flex-column">
                                                <a href="view_assessment.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="btn custom-view-details mb-2">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
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

                <!-- Participants Tab -->
                <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0" style="color: black">Participants</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php
                                        // Get enrolled students
                                        $enrollmentSql = "SELECT s.studentid, u.firstname, u.lastname, u.email, 
                                                         s.form, s.class, e.enrolled_at 
                                                         FROM vle_enrollment e
                                                         JOIN student s ON e.studentid = s.studentid
                                                         JOIN users u ON s.userid = u.userid
                                                         WHERE e.courseid = ?
                                                         ORDER BY u.lastname, u.firstname";
                                        $enrollmentStmt = $conn->prepare($enrollmentSql);
                                        $enrollmentStmt->bind_param("s", $courseId);
                                        $enrollmentStmt->execute();
                                        $enrollmentResult = $enrollmentStmt->get_result();
                                        ?>

                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Email</th>
                                                    <th>Form/Class</th>
                                                    <th>Enrolled On</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($enrollmentResult->num_rows > 0): ?>
                                                    <?php while ($student = $enrollmentResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['form'] . ' ' . $student['class']); ?></td>
                                                            <td><?php echo date('F j, Y', strtotime($student['enrolled_at'])); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No students enrolled in this course</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades Tab -->
                <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0" style="color: black">Grades</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php
                                        // Get assessment submissions and grades
                                        $gradesSql = "SELECT a.title, a.type, s.studentid, u.firstname, u.lastname, 
                                                     sub.submitted_at, sub.status, sub.score 
                                                     FROM vle_assessments a
                                                     JOIN vle_assessment_submissions sub ON a.assessmentid = sub.assessmentid
                                                     JOIN student s ON sub.studentid = s.studentid
                                                     JOIN users u ON s.userid = u.userid
                                                     WHERE a.courseid = ?
                                                     ORDER BY a.title, u.lastname, u.firstname";
                                        $gradesStmt = $conn->prepare($gradesSql);
                                        $gradesStmt->bind_param("s", $courseId);
                                        $gradesStmt->execute();
                                        $gradesResult = $gradesStmt->get_result();
                                        ?>

                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Assessment</th>
                                                    <th>Type</th>
                                                    <th>Student</th>
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
                                                            <td><?php echo htmlspecialchars($grade['firstname'] . ' ' . $grade['lastname']); ?></td>
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
                                                        <td colspan="6" class="text-center">No submissions found for this course</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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