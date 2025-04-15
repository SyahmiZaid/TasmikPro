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

// Get success or error messages from the URL
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
$errorMessage = isset($_GET['error']) ? $_GET['error'] : '';

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
                                                <?php echo ucfirst(htmlspecialchars($assessment['type'])); ?>: <?php echo htmlspecialchars($assessment['title']); ?>
                                            </h4>
                                            <span class="badge bg-<?php echo $assessment['status'] == 'published' ? 'success' : ($assessment['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <a href="edit_assessment.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="text-warning me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-link text-danger p-0" style="border: none; background: none;" onclick="confirmDelete('<?php echo htmlspecialchars($assessment['assessmentid']); ?>', '<?php echo htmlspecialchars($courseId); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p><?php echo htmlspecialchars($assessment['description']); ?></p>
                                                <?php if ($assessment['type'] !== 'note'): ?>
                                                    <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($assessment['type'] === 'exercise' && !empty($assessment['duration_minutes'])): ?>
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
                    <!-- Participants content here -->
                </div>

                <!-- Grades Tab -->
                <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
                    <!-- Grades content here -->
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

<!-- Include SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript for SweetAlert2 Confirmation -->
<script>
    function confirmDelete(assessmentId, courseId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete this assessment.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to delete_assessment.php with the assessment ID and course ID
                window.location.href = `delete_assessment.php?assessment_id=${encodeURIComponent(assessmentId)}&courseid=${encodeURIComponent(courseId)}`;
            }
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

    .btn-link i.fas.fa-trash {
        font-size: 1.2rem;
    }

    .btn-link i.fas.fa-trash:hover {
        color: #dc3545;
    }
</style>