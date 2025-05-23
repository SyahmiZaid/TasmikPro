<!-- filepath: c:\Users\Asus\OneDrive\Desktop\FYP\(4) Development\TasmikPro\vle-portal\assessment_submission.php -->
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

// Check if the student has already submitted for this assessment
$existingSubmissionSql = "SELECT * FROM vle_assessment_submissions WHERE assessmentid = ? AND studentid = ?";
$existingSubmissionStmt = $conn->prepare($existingSubmissionSql);
$existingSubmissionStmt->bind_param("ss", $assessmentId, $studentId);
$existingSubmissionStmt->execute();
$existingSubmissionResult = $existingSubmissionStmt->get_result();
$existingSubmission = $existingSubmissionResult->fetch_assoc();

// Handle file submission for exercise
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $assessment['type'] === 'exercise') {
    if (isset($_POST['resubmit'])) {
        // Handle Resubmission
        $targetDir = "../uploads/";
        $fileName = basename($_FILES["submission_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        if (!empty($_FILES["submission_file"]["name"])) {
            $allowedTypes = array("pdf", "doc", "docx", "txt", "zip");
            if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $targetFilePath)) {
                // Update the existing submission
                $updateSubmissionSql = "UPDATE vle_assessment_submissions 
                                        SET file_path = ?, submitted_at = NOW(), resubmission_count = resubmission_count + 1, is_done = 1 
                                        WHERE submissionid = ?";
                $updateSubmissionStmt = $conn->prepare($updateSubmissionSql);
                $updateSubmissionStmt->bind_param("ss", $targetFilePath, $existingSubmission['submissionid']);
                if ($updateSubmissionStmt->execute()) {
                    $successMessage = "Your submission has been updated successfully.";
                } else {
                    $errorMessage = "Failed to update your submission. Please try again.";
                }
            } else {
                $errorMessage = "Only PDF, DOC, DOCX, TXT, and ZIP files are allowed.";
            }
        } else {
            $errorMessage = "Please select a file to upload.";
        }
    } else {
        // Handle New Submission
        $targetDir = "../uploads/";
        $fileName = basename($_FILES["submission_file"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        if (!empty($_FILES["submission_file"]["name"])) {
            $allowedTypes = array("pdf", "doc", "docx", "txt", "zip");
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $targetFilePath)) {
                    // Generate unique ID for exercise submission
                    $vle_prefix = "VLEE"; // VLE + E for Exercise
                    $date = date('ymd'); // Current date in YYMMDD format

                    // Get count of existing submissions
                    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM vle_assessment_submissions");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $vle_count = $row['count'] + 1;
                    $stmt->close();

                    // Create submission ID with format VLEE01230429 (for example)
                    $vle_submission_id = $vle_prefix . str_pad($vle_count, 2, '0', STR_PAD_LEFT) . $date;

                    // Insert submission into the database with custom ID
                    $submissionSql = "INSERT INTO vle_assessment_submissions (submissionid, assessmentid, studentid, file_path, status, is_done) 
                              VALUES (?, ?, ?, ?, 'pending', 1)";
                    $submissionStmt = $conn->prepare($submissionSql);
                    $submissionStmt->bind_param("ssss", $vle_submission_id, $assessmentId, $studentId, $targetFilePath);
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
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($pageTitle); ?></h4>
        </div>

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
                        <?php if ($assessment['type'] !== 'note'): ?>
                            <p><strong>Due Date:</strong> <span class="badge bg-danger"><?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></span></p>
                        <?php endif; ?>

                        <!-- Display attachment if available -->
                        <?php if (!empty($assessment['attachment_path'])): ?>
                            <div class="mt-4">
                                <p><strong>Attachment:</strong> <?php echo htmlspecialchars(basename($assessment['attachment_path'])); ?></p>
                                <a href="<?php echo htmlspecialchars($assessment['attachment_path']); ?>" class="btn btn-download btn-sm" download>
                                    <i class="fas fa-file-download"></i> Download Attachment
                                </a>
                            </div>
                        <?php endif; ?>

                        <style>
                            .btn-download {
                                background: linear-gradient(90deg, #4caf50, #81c784);
                                color: #fff;
                                border: none;
                                padding: 6px 12px;
                                /* Reduced padding for smaller size */
                                font-size: 0.9rem;
                                /* Smaller font size */
                                border-radius: 4px;
                                /* Slightly smaller border radius */
                                transition: all 0.3s ease;
                                text-decoration: none;
                                display: inline-block;
                            }

                            .btn-download:hover {
                                background: linear-gradient(90deg, #388e3c, #66bb6a);
                                color: #fff;
                                box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
                                /* Slightly smaller shadow */
                                text-decoration: none;
                            }

                            .btn-download i {
                                margin-right: 6px;
                                /* Adjusted spacing for smaller size */
                            }
                        </style>
                    </div>
                </div>
            </div>
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
                background-color: rgb(198, 255, 198) !important;
                /* Light green background */
                color: #007b00 !important;
                /* Dark green text */
                border-color: rgb(150, 200, 150) !important;
                /* Green border */
                font-size: 1.25rem !important;
                padding: 1rem !important;
            }
        </style>

        <!-- Submission Section -->
        <?php if ($assessment['type'] === 'tasmik' || $assessment['type'] === 'murajaah'): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header custom-header-submission">
                            <h4 class="card-title mb-0"><i class="fas fa-upload"></i> <?php echo $existingSubmission ? 'Submission Status' : 'Add Submission'; ?></h4>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($existingSubmission): ?>
                                <!-- Display Existing Submission -->
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> You have already submitted for this assessment.
                                    <br><strong>Submitted At:</strong> <?php echo date('F j, Y, g:i a', strtotime($existingSubmission['submitted_at'])); ?>
                                    <br><strong>Status:</strong> <?php echo ucfirst($existingSubmission['status']); ?>
                                </div>

                                <?php if (!empty($existingSubmission['file_path'])): ?>
                                    <p><strong>Audio Recording:</strong>
                                        <a href="<?php echo htmlspecialchars($existingSubmission['file_path']); ?>" target="_blank" class="btn btn-info btn-sm">
                                            <i class="fas fa-headphones"></i> Listen to Recording
                                        </a>
                                    </p>
                                <?php endif; ?>

                                <!-- Get tasmik details -->
                                <?php
                                $tasmikSQL = "SELECT * FROM tasmik WHERE tasmikid LIKE 'VLE%' AND studentid = ? ORDER BY submitted_at DESC LIMIT 1";
                                $tasmikStmt = $conn->prepare($tasmikSQL);
                                $tasmikStmt->bind_param("s", $studentId);
                                $tasmikStmt->execute();
                                $tasmikResult = $tasmikStmt->get_result();

                                if ($tasmikResult->num_rows > 0) {
                                    $tasmikDetails = $tasmikResult->fetch_assoc();
                                ?>
                                    <div class="mt-3 text-start">
                                        <h5>Submission Details:</h5>
                                        <ul class="list-group">
                                            <li class="list-group-item"><strong>Juzuk:</strong> <?php echo $tasmikDetails['juzuk']; ?></li>
                                            <li class="list-group-item"><strong>Pages:</strong> <?php echo $tasmikDetails['start_page']; ?> to <?php echo $tasmikDetails['end_page']; ?></li>
                                            <li class="list-group-item"><strong>Ayah:</strong> <?php echo $tasmikDetails['start_ayah']; ?> to <?php echo $tasmikDetails['end_ayah']; ?></li>
                                        </ul>
                                    </div>
                                <?php } ?>

                            <?php else: ?>
                                <p>Click the button below to add your recitation and form details.</p>
                                <a href="submit_tasmik_murajaah.php?type=<?php echo $assessment['type']; ?>&id=<?php echo htmlspecialchars($assessmentId); ?>" class="btn btn-success btn-lg">
                                    <i class="fas fa-paper-plane"></i> Add Submission
                                </a>
                            <?php endif; ?>
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

                            <?php if ($existingSubmission): ?>
                                <!-- Display Existing Submission -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> You have already submitted a file for this assessment.
                                    <br><strong>File:</strong> <a href="<?php echo htmlspecialchars($existingSubmission['file_path']); ?>" target="_blank">View Submission</a>
                                    <br><strong>Submitted At:</strong> <?php echo date('F j, Y, g:i a', strtotime($existingSubmission['submitted_at'])); ?>
                                </div>

                                <!-- Resubmit or Delete Options -->
                                <form id="deleteSubmissionForm" action="delete_assessment_submission.php" method="POST">
                                    <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($existingSubmission['submissionid']); ?>">
                                    <input type="hidden" name="assessment_id" value="<?php echo htmlspecialchars($assessmentId); ?>"> <!-- Pass assessment ID -->
                                    <button type="button" id="deleteSubmissionButton" class="btn btn-danger mt-3">
                                        <i class="fas fa-trash"></i> Delete Submission
                                    </button>
                                </form>

                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.getElementById('deleteSubmissionButton').addEventListener('click', function(e) {
                                        Swal.fire({
                                            title: 'Are you sure?',
                                            text: "You won't be able to revert this!",
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#d33',
                                            cancelButtonColor: '#3085d6',
                                            confirmButtonText: 'Yes, delete it!'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                document.getElementById('deleteSubmissionForm').submit();
                                            }
                                        });
                                    });
                                </script>
                            <?php else: ?>
                                <!-- New Submission Form -->
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="submission_file"><i class="fas fa-file-upload"></i> Upload File</label>
                                        <input type="file" name="submission_file" id="submission_file" class="form-control" required>
                                        <small class="form-text text-muted">Allowed file types: PDF, DOC, DOCX, TXT, ZIP</small>
                                    </div>
                                    <button type="submit" class="btn btn-success mt-3"><i class="fas fa-paper-plane"></i> Submit</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="course_student.php?courseid=<?php echo htmlspecialchars($assessment['courseid']); ?>" class="btn btn-custom-back btn-lg back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <style>
            .back-button {
                background-color: #f8f9fa;
                /* Default background color */
                color: #343a40;
                /* Default text color */
                border: 1px solid #ced4da;
                /* Default border color */
                transition: background-color 0.3s ease, color 0.3s ease;
                /* Smooth transition */
            }

            .back-button:hover {
                background-color: rgb(197, 197, 197);
                /* Slightly grey background on hover */
                color: #212529;
                /* Darker text color on hover */
                border-color: #adb5bd;
                /* Slightly darker border on hover */
            }
        </style>
    </div>
</div>

<?php
// Close database connection
$conn->close();
include '../include/footer.php';
?>