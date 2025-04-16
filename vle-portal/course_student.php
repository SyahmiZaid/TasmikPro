<!-- filepath: c:\Users\Asus\OneDrive\Desktop\FYP\(4) Development\TasmikPro\vle-portal\course_student.php -->
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
                        <h1 class="custom-banner-title"><?php echo htmlspecialchars(ucwords($courseName)); ?></h1>
                        <p class="custom-banner-subtitle">Instructor: <?php echo htmlspecialchars(ucwords($teacherName)); ?></p>
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
                                            <!-- Add Icon Based on Assessment Type -->
                                            <?php
                                            $icon = '';
                                            switch ($assessment['type']) {
                                                case 'exercise':
                                                    $icon = '<i class="fas fa-clipboard-list me-2" style="color: #007bff;"></i>'; // Exercise icon
                                                    break;
                                                case 'note':
                                                    $icon = '<i class="fas fa-sticky-note me-2" style="color: #ffc107;"></i>'; // Note icon
                                                    break;
                                                case 'tasmik':
                                                    $icon = '<i class="fas fa-book-reader me-2" style="color: #28a745;"></i>'; // Tasmik icon
                                                    break;
                                                case 'murajaah':
                                                    $icon = '<i class="fas fa-book me-2" style="color: #17a2b8;"></i>'; // Murajaah icon
                                                    break;
                                                default:
                                                    $icon = '<i class="fas fa-question-circle me-2" style="color: #6c757d;"></i>'; // Default icon
                                                    break;
                                            }
                                            ?>
                                            <!-- Display Icon and Title -->
                                            <h4 class="card-title mb-0 me-2" style="color: black">
                                                <?php echo $icon; ?>
                                                <?php echo ucfirst(htmlspecialchars($assessment['type'])); ?>: <?php echo htmlspecialchars($assessment['title']); ?>
                                            </h4>
                                            <span class="badge bg-<?php echo $assessment['status'] == 'published' ? 'success' : ($assessment['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p><?php echo htmlspecialchars($assessment['description']); ?></p>
                                                <?php if ($assessment['type'] !== 'note'): ?>
                                                    <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 d-flex justify-content-end align-items-end flex-column">
                                                <?php
                                                // Check if the assessment is marked as done
                                                $isDoneSql = "SELECT is_done FROM vle_assessment_submissions WHERE assessmentid = ? AND studentid = ?";
                                                $isDoneStmt = $conn->prepare($isDoneSql);
                                                $isDoneStmt->bind_param("ss", $assessment['assessmentid'], $studentId);
                                                $isDoneStmt->execute();
                                                $isDoneResult = $isDoneStmt->get_result();
                                                $isDoneRow = $isDoneResult->fetch_assoc();
                                                $isDone = $isDoneRow['is_done'] ?? 0;
                                                ?>
                                                <form id="markAsDoneForm-<?php echo $assessment['assessmentid']; ?>" class="mt-2">
                                                    <input type="hidden" name="assessment_id" value="<?php echo htmlspecialchars($assessment['assessmentid']); ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentId); ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($courseId); ?>">
                                                    <button type="button" class="btn <?php echo $isDone ? 'btn-success' : 'btn-outline-success'; ?>" onclick="toggleMarkAsDone('<?php echo $assessment['assessmentid']; ?>')">
                                                        <?php echo $isDone ? '<i class="fas fa-check"></i> Done' : 'Mark as Done'; ?>
                                                    </button>
                                                </form>
                                                <a href="assessment_submission.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="btn custom-view-details mb-2" style="margin-top: 10px;">
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

                <!-- Grades Tab -->
                <div class="tab-pane fade" id="pills-grades" role="tabpanel" aria-labelledby="pills-grades-tab">
                    <!-- Grades content here -->
                </div>

                <!-- Announcements Tab -->
                <div class="tab-pane fade" id="pills-announcements" role="tabpanel" aria-labelledby="pills-announcements-tab">
                    <!-- Announcements content here -->
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

<script>
    function toggleMarkAsDone(assessmentId) {
        const form = document.getElementById(`markAsDoneForm-${assessmentId}`);
        const formData = new FormData(form);
        const button = form.querySelector('button');

        // Determine the current state (Done or Mark as Done)
        const isDone = button.classList.contains('btn-success');

        // Add a flag to the form data to indicate the desired state
        formData.append('toggle_state', isDone ? 0 : 1); // 1 = Mark as Done, 0 = Undo

        fetch('mark_as_done.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                } else {
                    throw new Error('Failed to toggle mark as done');
                }
            })
            .then(data => {
                if (data.trim() === 'Success') {
                    // Toggle the button state dynamically
                    if (isDone) {
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-success');
                        button.innerHTML = 'Mark as Done';
                    } else {
                        button.classList.remove('btn-outline-success');
                        button.classList.add('btn-success');
                        button.innerHTML = '<i class="fas fa-check"></i> Done';
                    }
                } else {
                    alert('Failed to toggle mark as done: ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
    }
</script>

<!-- Custom Styles -->
<style>
    .custom-view-details {
        background-color: #f0f8ff;
        color: #007bff;
        border: 1px solid #d1e7ff;
    }

    .custom-view-details:hover {
        background-color: rgb(198, 225, 255);
        color: #0056b3;
        border-color: rgb(104, 137, 184);
    }

    .btn-outline-success {
        background-color: #f8f9fa;
        color: #28a745;
        border: 1px solid #28a745;
    }

    .btn-outline-success:hover {
        background-color: #28a745;
        color: #fff;
    }

    .btn-success {
        background-color: #28a745;
        color: #fff;
        border: 1px solid #28a745;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
</style>