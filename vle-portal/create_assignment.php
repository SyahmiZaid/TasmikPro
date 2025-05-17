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
    $openDate = !empty($_POST['open_date']) ? $_POST['open_date'] : null;
    $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $allowResubmission = isset($_POST['allow_resubmission']) ? 1 : 0;
    $status = $_POST['status'];
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

    // Automatically set status to "draft" if open date is in the future
    if (!empty($openDate) && strtotime($openDate) > time()) {
        $status = 'draft';
    }

    // Generate assessment ID based on the course, date, and number of assessments
    $date = date('Y-m-d'); // Current date
    $month = date('m'); // Current month

    // Count the number of assessments created for the course on the current day
    $sql = "SELECT COUNT(*) AS assessment_count FROM vle_assessments WHERE courseid = ? AND DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $courseId, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $assessmentCount = $row['assessment_count'] + 1; // Increment count for the new assessment

    // Generate the assessment ID
    $assessmentId = sprintf("ASS%s%s%02d%02d", substr($courseId, -3), $month, date('d'), $assessmentCount);

    // Insert assignment into database
    $sql = "INSERT INTO vle_assessments (assessmentid, courseid, teacherid, title, description, type, open_date, due_date, attachment_path, allow_resubmission, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssis", $assessmentId, $courseId, $teacherId, $title, $description, $type, $openDate, $dueDate, $attachmentPath, $allowResubmission, $status);

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
        </div>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        </div>

        <!-- Assignment Creation Form -->
        <div class="card" style="margin-top: -50px;">
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
                        <select name="type" id="type" class="form-control" required onchange="toggleFields()">
                            <option value="exercise">Exercise</option>
                            <option value="note">Note</option>
                            <option value="tasmik">Tasmik</option>
                            <option value="murajaah">Murajaah</option>
                        </select>
                    </div>
                    <div class="form-group" id="open-date-group">
                        <label for="open_date">Open Date</label>
                        <input type="datetime-local" name="open_date" id="open_date" class="form-control">
                    </div>
                    <div class="form-group" id="due-date-group">
                        <label for="due_date">Due Date</label>
                        <input type="datetime-local" name="due_date" id="due_date" class="form-control">
                    </div>
                    <div class="form-group" id="attachment-group">
                        <label for="attachment">Attachment (Optional)</label>
                        <input type="file" name="attachment" id="attachment" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="allow_resubmission">Allow Resubmission</label>
                        <input type="checkbox" name="allow_resubmission" id="allow_resubmission">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="d-flex mt-4" style="padding-left: 10px;">
                        <a href="course.php?courseid=<?php echo htmlspecialchars($courseId); ?>" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Create Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleFields() {
        const type = document.getElementById('type').value;
        const dueDateGroup = document.getElementById('due-date-group');
        const openDateGroup = document.getElementById('open-date-group');
        const attachmentGroup = document.getElementById('attachment-group');

        if (type === 'note') {
            dueDateGroup.style.display = 'none';
            openDateGroup.style.display = 'none';
            attachmentGroup.style.display = 'block';
        } else if (type === 'exercise' || type === 'tasmik' || type === 'murajaah') {
            dueDateGroup.style.display = 'block';
            openDateGroup.style.display = 'block';
            attachmentGroup.style.display = 'block';
        }
    }

    // Initialize fields on page load
    document.addEventListener('DOMContentLoaded', toggleFields);
</script>

<?php include '../include/footer.php'; ?>