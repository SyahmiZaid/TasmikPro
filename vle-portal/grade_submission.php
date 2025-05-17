<?php
// Start output buffering at the very beginning
ob_start();

// Set up page variables
$pageTitle = "Grade Submission";
$breadcrumb = "Pages / VLE - Teacher Portal / Course / View Assessment / Grade Submission";

// Database connection first (before any output)
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get submission ID from URL parameter
$submissionId = isset($_GET['submissionid']) ? $_GET['submissionid'] : '';
if (empty($submissionId)) {
    die("Submission ID is required.");
}

// Process AJAX requests BEFORE including header (to avoid "headers already sent" error)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // For AJAX requests, clear any buffered output
    ob_clean();
    
    // Set JSON content type
    header('Content-Type: application/json');
    
    $score = $_POST['score'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $status = 'graded';

    // Validate score
    if (!is_numeric($score) || $score < 0 || $score > 100) {
        echo json_encode(['success' => false, 'message' => 'Score must be a number between 0 and 100']);
        exit;
    }

    // Update submission with grade
    $updateSql = "UPDATE vle_assessment_submissions 
                  SET score = ?, feedback = ?, status = ?, graded_at = NOW() 
                  WHERE submissionid = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("dsss", $score, $feedback, $status, $submissionId);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to grade submission: ' . $conn->error]);
    }
    
    // End output buffer and exit for AJAX requests
    ob_end_flush();
    exit;
}

// Now it's safe to include the header
include '../include/header.php';

// Get the logged-in user's ID
$userid = $_SESSION['userid'] ?? '';

// Regular form submission (fallback for non-JS browsers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    $score = $_POST['score'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $status = 'graded';
    
    // Validate score
    if (!is_numeric($score) || $score < 0 || $score > 100) {
        $error = "Score must be a number between 0 and 100";
    } else {
        // Update submission with grade
        $updateSql = "UPDATE vle_assessment_submissions 
                      SET score = ?, feedback = ?, status = ?, graded_at = NOW() 
                      WHERE submissionid = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("dsss", $score, $feedback, $status, $submissionId);
        
        if ($updateStmt->execute()) {
            $success = "Submission graded successfully!";
        } else {
            $error = "Failed to grade submission: " . $conn->error;
        }
    }
}

// Get submission details
$submissionSql = "SELECT s.*, a.title as assessment_title, a.assessmentid, a.type,
                        u.firstname, u.lastname, c.course_name
                  FROM vle_assessment_submissions s
                  JOIN vle_assessments a ON s.assessmentid = a.assessmentid
                  JOIN student st ON s.studentid = st.studentid
                  JOIN users u ON st.userid = u.userid
                  JOIN vle_courses c ON a.courseid = c.courseid
                  WHERE s.submissionid = ?";
$submissionStmt = $conn->prepare($submissionSql);
$submissionStmt->bind_param("s", $submissionId);
$submissionStmt->execute();
$submissionResult = $submissionStmt->get_result();

if ($submissionResult->num_rows === 0) {
    die("Submission not found.");
}

$submission = $submissionResult->fetch_assoc();
?>

<!-- Include SweetAlert2 library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($pageTitle); ?></h4>
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
            
            .custom-header-grading {
                background-color: rgb(255, 223, 186) !important;
                color: #8b4513 !important;
                border-color: rgb(222, 184, 135) !important;
                font-size: 1.25rem !important;
                padding: 1rem !important;
            }
            
            .card {
                box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
                border-radius: 0.5rem !important;
                border: none !important;
                transition: all 0.2s !important;
            }
            
            .card:hover {
                box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.25) !important;
            }
            
            .btn-info {
                background-color: #5bc0de !important;
                border-color: #46b8da !important;
            }
            
            .student-info-box {
                background-color: #f8f9fa;
                border-radius: 0.5rem;
                padding: 1.25rem;
                border-left: 4px solid #0056b3;
                margin-bottom: 1.5rem;
            }
            
            .submission-info-box {
                background-color: #f8f9fa;
                border-radius: 0.5rem;
                padding: 1.25rem;
                border-left: 4px solid #28a745;
                margin-bottom: 1.5rem;
            }
            
            .grading-form {
                background-color: #fff;
                border-radius: 0.5rem;
                padding: 1.5rem;
                border: 1px solid #e3e6f0;
            }
            
            .btn-primary {
                background-color: #4e73df !important;
                border-color: #4e73df !important;
            }
            
            .btn-primary:hover {
                background-color: #2e59d9 !important;
                border-color: #2653d4 !important;
            }
            
            .btn-success {
                background-color: #28a745 !important;
                border-color: #28a745 !important;
            }
            
            .btn-success:hover {
                background-color: #218838 !important;
                border-color: #1e7e34 !important;
            }
            
            .file-info {
                background-color: #f8f9fa;
                border-radius: 0.25rem;
                padding: 0.5rem;
                margin-top: 0.5rem;
                font-size: 0.875rem;
                border: 1px solid #e3e6f0;
            }
        </style>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header custom-header">
                        <h4 class="card-title mb-0">
                            <?php
                            // Set icon based on assessment type
                            $icon = '';
                            switch ($submission['type']) {
                                case 'exercise':
                                    $icon = '<i class="fas fa-clipboard-list me-2" style="color: #007bff;"></i>';
                                    break;
                                case 'tasmik':
                                    $icon = '<i class="fas fa-book-reader me-2" style="color: #28a745;"></i>';
                                    break;
                                case 'murajaah':
                                    $icon = '<i class="fas fa-book me-2" style="color: #17a2b8;"></i>';
                                    break;
                                default:
                                    $icon = '<i class="fas fa-file-alt me-2"></i>';
                                    break;
                            }
                            echo $icon;
                            ?>
                            <?php echo htmlspecialchars($submission['assessment_title']); ?>
                        </h4>
                        <p class="text-muted mb-0">Course: <?php echo htmlspecialchars($submission['course_name']); ?></p>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="student-info-box">
                                    <h5><i class="fas fa-user-graduate me-2" style="color: #0056b3;"></i>Student Information</h5>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($submission['firstname'] . ' ' . $submission['lastname']); ?></p>
                                    <p><strong>Submitted:</strong> <?php echo date('F j, Y, g:i a', strtotime($submission['submitted_at'])); ?></p>
                                    <p class="mb-0"><strong>Status:</strong> 
                                        <span class="badge bg-<?php echo $submission['status'] == 'graded' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($submission['status'])); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="submission-info-box">
                                    <h5><i class="fas fa-file-upload me-2" style="color: #28a745;"></i>Submission Details</h5>
                                    <?php if (!empty($submission['file_path'])): ?>
                                        <div class="mb-3">
                                            <div class="btn-group">
                                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                                   target="_blank" class="btn btn-info">
                                                    <i class="fas fa-eye me-1"></i> View File
                                                </a>
                                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                                   download class="btn btn-success">
                                                    <i class="fas fa-download me-1"></i> Download File
                                                </a>
                                            </div>
                                            <div class="file-info mt-2">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?php 
                                                $fileInfo = pathinfo($submission['file_path']);
                                                $fileExt = strtoupper($fileInfo['extension'] ?? '');
                                                // Simplified file size handling to avoid errors
                                                echo "File type: {$fileExt}";
                                                ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No file submitted.</p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($submission['comments'])): ?>
                                        <div>
                                            <h6><i class="fas fa-comment-alt me-1"></i> Student Comments:</h6>
                                            <div class="p-3 bg-white rounded border">
                                                <?php echo nl2br(htmlspecialchars($submission['comments'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header custom-header-grading">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-pen-fancy me-2"></i> Grading Form
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="gradeForm" method="post" action="">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="score" class="form-label"><strong>Score (0-100):</strong></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="score" name="score" 
                                                           min="0" max="100" step="1" required
                                                           value="<?php echo htmlspecialchars($submission['score'] ?? ''); ?>">
                                                    <span class="input-group-text">points</span>
                                                </div>
                                                <small class="text-muted">Enter a score between 0 and 100</small>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group">
                                                <label for="feedback" class="form-label"><strong>Feedback to Student:</strong></label>
                                                <textarea class="form-control" id="feedback" name="feedback" 
                                                          rows="5" placeholder="Provide constructive feedback to the student..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                                <small class="text-muted">This feedback will be visible to the student</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 d-flex justify-content-between">
                                        <a href="manage_submission.php?assessment_id=<?php echo $submission['assessmentid']; ?>" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i> Back to Submissions
                                        </a>
                                        <button type="button" id="submitGrade" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Grade
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript for SweetAlert confirmation and form submission -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const submitButton = document.getElementById('submitGrade');
        const gradeForm = document.getElementById('gradeForm');
        
        submitButton.addEventListener('click', function() {
            // Validate form
            if (!gradeForm.checkValidity()) {
                gradeForm.reportValidity();
                return;
            }
            
            // Get form values
            const score = document.getElementById('score').value;
            const feedback = document.getElementById('feedback').value;
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Confirm Grading',
                text: 'Are you sure you want to save this grade?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4e73df',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request if confirmed
                    const formData = new FormData(gradeForm);
                    formData.append('ajax', '1');
                    
                    // Show loading state
                    Swal.fire({
                        title: 'Saving grade...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Server returned status ' + response.status);
                        }
                        return response.text();
                    })
                    .then(text => {
                        // Extract JSON part if needed
                        let jsonText = text;
                        // Look for JSON in the response if there's HTML mixed in
                        if (text.includes('{"success":')) {
                            const start = text.indexOf('{"success":');
                            const end = text.lastIndexOf('}') + 1;
                            if (start >= 0 && end > 0) {
                                jsonText = text.substring(start, end);
                            }
                        }
                        
                        try {
                            return JSON.parse(jsonText);
                        } catch (e) {
                            console.error("Failed to parse JSON response:", jsonText);
                            throw new Error("Invalid server response");
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'The submission has been graded successfully.',
                                icon: 'success',
                                confirmButtonColor: '#4e73df'
                            }).then(() => {
                                // Redirect back to the submissions page
                                window.location.href = 'manage_submission.php?assessment_id=<?php echo $submission['assessmentid']; ?>';
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Something went wrong.',
                                icon: 'error',
                                confirmButtonColor: '#4e73df'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred: ' + error.message,
                            icon: 'error',
                            confirmButtonColor: '#4e73df'
                        });
                    });
                }
            });
        });
    });
</script>

<?php
// Close database connection
$conn->close();
include '../include/footer.php';

// End output buffering
ob_end_flush();
?>