<?php
$pageTitle = "Create Assignment";
$breadcrumb = "Pages / <a href='course.php?courseid=" . htmlspecialchars($_GET['courseid']) . "' class='no-link-style'>Course</a> / Create Assignment";
include '../include/header.php';

// Database connection
require_once '../database/db_connection.php';

// Get course ID from URL parameter
$courseId = isset($_GET['courseid']) ? $_GET['courseid'] : '';

// Retrieve userid from session
$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : ''; // Retrieve userid from session

// Check if userid is valid
if (empty($userid)) {
    die("<div class='alert alert-danger'>Error: User ID is missing or invalid. Please log in again.</div>");
}

// Retrieve teacherid using userid
$sql = "SELECT teacherid FROM teacher WHERE userid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $teacherId = $row['teacherid'];
} else {
    die("<div class='alert alert-danger'>Error: No teacher record found for the current user.</div>");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $dueDate = $_POST['due_date'];
    $attachmentPath = null;

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/assessments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
        }
        $attachmentPath = $uploadDir . basename($_FILES['attachment']['name']);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $attachmentPath);
    }

    // Insert assignment into database
    $assessmentId = uniqid('assess_');
    $sql = "INSERT INTO vle_assessments (assessmentid, courseid, teacherid, title, description, type, due_date, attachment_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $assessmentId, $courseId, $teacherId, $title, $description, $type, $dueDate, $attachmentPath);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Assignment created successfully! Redirecting to the course page...</div>";
        echo "<script>setTimeout(function() { window.location.href = 'course.php?courseid=" . htmlspecialchars($courseId) . "'; }, 3000);</script>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        </div>

        <!-- Assignment Creation Form -->
        <div class="card">
            <div class="card-header">
                <h4>Create Assignment</h4>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="assignment">Assignment</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input type="datetime-local" name="due_date" id="due_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="attachment">Attachment (Optional)</label>
                        <input type="file" name="attachment" id="attachment" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>