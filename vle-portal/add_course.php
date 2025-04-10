<?php
$pageTitle = "Add Course";
$breadcrumb = "Pages / <a href='../student/index.php' class='no-link-style'>Dashboard</a> / Add Course";
include '../include/header.php';

// Database connection
require_once '../database/db_connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$courseName = $description = "";
$successMessage = $errorMessage = "";

// Get the current user ID from the session
if (isset($_SESSION['userid'])) {
    $userId = $_SESSION['userid'];

    // Fetch the teacherid for the logged-in user
    $teacherQuery = "SELECT teacherid FROM teacher WHERE userid = ?";
    $stmt = $conn->prepare($teacherQuery);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $createdBy = $row['teacherid']; // Use teacherid as created_by
    } else {
        $errorMessage = "User is not associated with a teacher account.";
        $createdBy = null;
    }

    $stmt->close();
} else {
    $errorMessage = "User not logged in.";
    $createdBy = null;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $courseName = trim($_POST['course_name']);
    $description = trim($_POST['description']);

    // Validate inputs
    if (empty($courseName) || empty($description) || empty($createdBy)) {
        $errorMessage = "All fields are required.";
    } else {
        // Generate unique course ID
        $course_prefix = "CRS";
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(courseid, 4) AS UNSIGNED)) AS max_id FROM vle_courses");
        $stmt->execute();
        $stmt->bind_result($max_id);
        $stmt->fetch();
        $stmt->close();
        $new_id = $max_id + 1;
        $courseId = $course_prefix . str_pad($new_id, 3, '0', STR_PAD_LEFT);

        // Insert course into the database
        $sql = "INSERT INTO vle_courses (courseid, course_name, description, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $courseId, $courseName, $description, $createdBy);

        if ($stmt->execute()) {
            $successMessage = "Course added successfully!";
            $courseName = $description = ""; // Clear form fields
        } else {
            $errorMessage = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        </div>

        <!-- Add Course Form -->
        <div class="col-md-8">
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="course_name" class="form-label">Course Name</label>
                    <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($courseName); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Course</button>
            </form>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>