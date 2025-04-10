<?php
$pageTitle = "Edit Assessment";
$breadcrumb = "Pages / VLE - Teacher Portal / Course / View Assessment / Edit Assessment";
include '../include/header.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'teacher') {
    echo "<div class='alert alert-danger text-center'>Access denied. You must be logged in as a teacher.</div>";
}

// Fetch teacherid using userid from session
$userid = $_SESSION['userid'];
$query = "SELECT teacherid FROM teacher WHERE userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    echo "<div class='alert alert-danger text-center' style='margin-top: 40px;'>Teacher record not found.</div>";
    exit;
}

$_SESSION['teacherid'] = $teacher['teacherid']; // Store teacherid in session

// Initialize variables
$errorMsg = "";
$successMsg = "";
$assessment = [];

// Fetch assessment details from the database
if (isset($_GET['id'])) {
    $assessmentId = htmlspecialchars($_GET['id']);
    $query = "SELECT * FROM vle_assessments WHERE assessmentid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $assessmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();

    if (!$assessment) {
        echo "<div class='alert alert-danger text-center' style='margin-top: 40px;'>Assessment not found.</div>";
        exit;
    }

    // echo "<div class='alert alert-info' style='margin-top: 80px;'>Assessment Teacher ID: " . $assessment['teacherid'] . "<br>Your Teacher ID: " . $_SESSION['teacherid'] . "</div>";

    // Check if current teacher is the owner of this assessment
    if ((string)$assessment['teacherid'] != (string)$_SESSION['teacherid']) {
        echo "<div class='alert alert-danger text-center' style='margin-top: 40px;'>You don't have permission to edit this assessment.</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger text-center' style='margin-top: 40px;'>No assessment ID provided.</div>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $dueDate = $_POST['due_date'];
    $status = $_POST['status'];
    $allowResubmission = isset($_POST['allow_resubmission']) ? 1 : 0;
    $openDate = !empty($_POST['open_date']) ? $_POST['open_date'] : null;
    $durationMinutes = !empty($_POST['duration_minutes']) ? $_POST['duration_minutes'] : null;

    // Validate form inputs
    if (empty($title) || empty($description) || empty($dueDate)) {
        $errorMsg = "Please fill in all required fields.";
    } else {
        // Handle file upload if a new file is uploaded or remove the current attachment
        $attachmentPath = $assessment['attachment_path']; // Default to existing path

        if (isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == 1) {
            // Remove the current attachment
            if (!empty($attachmentPath) && file_exists($attachmentPath)) {
                unlink($attachmentPath); // Delete the file from the server
            }
            $attachmentPath = null; // Clear the attachment path in the database
        } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $targetDir = "../uploads/assessments/";

            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['attachment']['name']);
            $targetFile = $targetDir . $fileName;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            // Check file size (limit to 10MB)
            if ($_FILES['attachment']['size'] > 10000000) {
                $errorMsg = "File is too large. Maximum size is 10MB.";
            }
            // Allow certain file formats
            elseif (!in_array($fileType, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'])) {
                $errorMsg = "Sorry, only PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, and TXT files are allowed.";
            }
            // Try to upload file
            elseif (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                $attachmentPath = $targetFile;
            } else {
                $errorMsg = "There was an error uploading your file.";
            }
        }

        // If no errors, update the assessment in database
        if (empty($errorMsg)) {
            $query = "UPDATE vle_assessments SET 
                      title = ?, 
                      description = ?, 
                      type = ?, 
                      due_date = ?, 
                      open_date = ?, 
                      duration_minutes = ?, 
                      attachment_path = ?, 
                      allow_resubmission = ?, 
                      status = ? 
                      WHERE assessmentid = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "sssssisiss",
                $title,
                $description,
                $type,
                $dueDate,
                $openDate,
                $durationMinutes,
                $attachmentPath,
                $allowResubmission,
                $status,
                $assessmentId
            );

            if ($stmt->execute()) {
                $successMsg = "Assessment updated successfully!";
            } else {
                $errorMsg = "Error updating assessment: " . $conn->error;
            }
        }
    }
}
?>

<div class="container-fluid" style="margin-top: 80px;">
    <div class="row">
        <div class="col-12">
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo $errorMsg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> <?php echo $successMsg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <meta http-equiv="refresh" content="0.8;url=view_assessment.php?id=<?php echo $assessmentId; ?>">
            <?php endif; ?>

            <!-- Edit Assessment Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-edit me-2"></i>
                        <h5 class="mb-0">Edit Assessment</h5>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Basic Info Section -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">Basic Information</h6>
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($assessment['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($assessment['description']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Assessment Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="assignment" <?php echo ($assessment['type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                                        <option value="quiz" <?php echo ($assessment['type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Section -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">Schedule</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="open_date" class="form-label">Open Date</label>
                                    <input type="datetime-local" class="form-control" id="open_date" name="open_date"
                                        value="<?php echo !empty($assessment['open_date']) ? date('Y-m-d\TH:i', strtotime($assessment['open_date'])) : ''; ?>">
                                    <small class="text-muted">When students can first see this assessment</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="due_date" name="due_date"
                                        value="<?php echo date('Y-m-d\TH:i', strtotime($assessment['due_date'])); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="duration_minutes" class="form-label">Duration (Minutes)</label>
                                    <input type="number" class="form-control" id="duration_minutes" name="duration_minutes"
                                        value="<?php echo htmlspecialchars($assessment['duration_minutes'] ?? ''); ?>" min="0">
                                    <small class="text-muted">For quizzes only (leave blank for unlimited)</small>
                                </div>
                            </div>
                        </div>

                        <!-- Submission Options -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">Submission Options</h6>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="allow_resubmission" name="allow_resubmission"
                                    <?php echo $assessment['allow_resubmission'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_resubmission">
                                    Allow students to resubmit before the due date
                                </label>
                            </div>
                        </div>

                        <!-- Attachment Section -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">Attachment</h6>
                            <?php if (!empty($assessment['attachment_path'])): ?>
                                <div class="mb-3">
                                    <p class="mb-2">Current Attachment:</p>
                                    <div class="d-flex align-items-center p-3 bg-light rounded">
                                        <i class="fas fa-file me-3 text-primary"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo basename($assessment['attachment_path']); ?></h6>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($assessment['attachment_path']); ?>" class="btn btn-sm btn-outline-primary me-2" download>
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="remove_attachment" name="remove_attachment" value="1">
                                    <label class="form-check-label" for="remove_attachment">
                                        Remove current attachment
                                    </label>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="attachment" class="form-label">Upload New Attachment</label>
                                <input type="file" class="form-control" id="attachment" name="attachment">
                                <small class="text-muted">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT (max 10MB)</small>
                            </div>
                        </div>

                        <!-- Status Section -->
                        <div class="mb-4">
                            <h6 class="text-primary mb-3">Status</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Publication Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?php echo ($assessment['status'] == 'draft') ? 'selected' : ''; ?>>Draft (hidden from students)</option>
                                        <option value="published" <?php echo ($assessment['status'] == 'published') ? 'selected' : ''; ?>>Published (visible to students)</option>
                                        <option value="closed" <?php echo ($assessment['status'] == 'closed') ? 'selected' : ''; ?>>Closed (no more submissions)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex mt-4">
                            <a href="view_assessment.php?id=<?php echo $assessmentId; ?>" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show relevant fields based on assessment type
    document.getElementById('type').addEventListener('change', function() {
        var durationsField = document.getElementById('duration_minutes');
        var durationRow = durationsField.closest('.row');

        if (this.value === 'quiz') {
            durationRow.style.display = 'flex';
        } else {
            durationRow.style.display = 'none';
        }
    });

    // Trigger the change event on page load
    document.addEventListener('DOMContentLoaded', function() {
        var typeSelect = document.getElementById('type');
        var event = new Event('change');
        typeSelect.dispatchEvent(event);
    });
</script>

<?php include '../include/footer.php'; ?>