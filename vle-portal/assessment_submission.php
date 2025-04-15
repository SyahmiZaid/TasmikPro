<?php
$pageTitle = "Assessment Submission";
$breadcrumb = "Pages / VLE - Student Portal / Assessment Submission";
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

// Get assessment ID from URL parameter
$assessmentId = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($assessmentId)) {
    die("Assessment ID is required.");
}

// Get assessment details
$assessmentSql = "SELECT a.*, c.course_name, u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
                  FROM vle_assessments a
                  JOIN vle_courses c ON a.courseid = c.courseid
                  JOIN teacher t ON a.teacherid = t.teacherid
                  JOIN users u ON t.userid = u.userid
                  WHERE a.assessmentid = ?";
$assessmentStmt = $conn->prepare($assessmentSql);
$assessmentStmt->bind_param("s", $assessmentId);
$assessmentStmt->execute();
$assessmentResult = $assessmentStmt->get_result();

if ($assessmentResult->num_rows > 0) {
    $assessment = $assessmentResult->fetch_assoc();
} else {
    die("Assessment not found.");
}

// Handle file submission for exercise
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assessment['type'] === 'exercise') {
    $targetDir = "../uploads/"; // Directory to store uploaded files
    $fileName = basename($_FILES["submission_file"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    // Check if file is uploaded
    if (!empty($_FILES["submission_file"]["name"])) {
        // Allow only specific file formats
        $allowedTypes = array("pdf", "doc", "docx", "txt", "zip");
        if (in_array($fileType, $allowedTypes)) {
            // Upload file to server
            if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $targetFilePath)) {
                // Insert submission into the database
                $submissionSql = "INSERT INTO vle_assessment_submissions (submissionid, assessmentid, studentid, file_path, status) 
                                  VALUES (UUID(), ?, ?, ?, 'pending')";
                $submissionStmt = $conn->prepare($submissionSql);
                $submissionStmt->bind_param("sss", $assessmentId, $studentId, $targetFilePath);
                if ($submissionStmt->execute()) {
                    $successMessage = "Your submission has been uploaded successfully.";
                } else {
                    $errorMessage = "Failed to save your submission. Please try again.";
                }
            } else {
                $errorMessage = "There was an error uploading your file. Please try again.";
            }
        } else {
            $errorMessage = "Only PDF, DOC, DOCX, TXT, and ZIP files are allowed.";
        }
    } else {
        $errorMessage = "Please select a file to upload.";
    }
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($pageTitle); ?></h4>
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
            .btn-custom-back {
                background-color: #e0e0e0 !important; /* Silver background */
                color: #6c757d !important; /* Dark gray text */
                border-color: #d6d6d6 !important; /* Light gray border */
            }
            .btn-custom-back:hover {
                background-color: #d6d6d6 !important; /* Slightly darker silver */
                color: #5a6268 !important; /* Slightly darker gray text */
            }
        </style>

        <!-- Assessment Details -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header custom-header">
                        <h4 class="card-title mb-0"><i class="fas fa-book"></i> <?php echo htmlspecialchars($assessment['title']); ?></h4>
                        <p class="text-black-50 mb-0">Course: <?php echo htmlspecialchars($assessment['course_name']); ?></p>
                        <p class="text-black-50 mb-0">Instructor: <?php echo htmlspecialchars($assessment['teacher_firstname'] . ' ' . $assessment['teacher_lastname']); ?></p>
                    </div>
                    <div class="card-body">
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($assessment['description']); ?></p>
                        <p><strong>Due Date:</strong> <span class="badge bg-danger"><?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission Section -->
        <?php if ($assessment['type'] === 'tasmik' || $assessment['type'] === 'murajaah'): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header custom-header-submission">
                            <h4 class="card-title mb-0"><i class="fas fa-upload"></i> Add Submission</h4>
                        </div>
                        <div class="card-body text-center">
                            <p>Click the button below to add your recitation and form details.</p>
                            <a href="submit_tasmik.php?assessment_id=<?php echo htmlspecialchars($assessmentId); ?>" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane"></i> Add Submission
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($assessment['type'] === 'note'): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> This is a note. No submission is required.
            </div>
        <?php elseif ($assessment['type'] === 'exercise'): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header custom-header-submission">
                            <h4 class="card-title mb-0"><i class="fas fa-upload"></i> Submit Your Work</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($successMessage)): ?>
                                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $successMessage; ?></div>
                            <?php elseif (!empty($errorMessage)): ?>
                                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?></div>
                            <?php endif; ?>

                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="submission_file"><i class="fas fa-file-upload"></i> Upload File</label>
                                    <input type="file" name="submission_file" id="submission_file" class="form-control" required>
                                    <small class="form-text text-muted">Allowed file types: PDF, DOC, DOCX, TXT, ZIP</small>
                                </div>
                                <button type="submit" class="btn btn-success mt-3"><i class="fas fa-paper-plane"></i> Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="javascript:history.back()" class="btn btn-custom-back btn-lg">
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