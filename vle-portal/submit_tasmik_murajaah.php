<?php
// Determine the assessment type from the query parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'tasmik'; // Default to 'tasmik' if not provided

// Get assessment ID from URL parameter
$assessmentId = isset($_GET['id']) ? $_GET['id'] : '';

// Set page title and breadcrumb dynamically
$pageTitle = ucfirst($type); // Capitalize the first letter
$breadcrumb = "Pages / VLE - Student Portal / Assessment Submission / Submit " . ucfirst($type);

include '../include/header.php';
require_once '../database/db_connection.php';

// Initialize variables to track submission status
$submission_error = false;
$error_message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the current user ID from the session
    $userid = $_SESSION['userid'];

    // Check if the student ID exists in the student table
    $stmt = $conn->prepare("SELECT studentid FROM student WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $submission_error = true;
        $error_message = "Student ID not found for the current user.";
    } else {
        $row = $result->fetch_assoc();
        $studentid = $row['studentid'];
        $stmt->close();

        // MODIFIED: Generate unique ID for the tasmik/murajaah table with VLE prefix
        $prefix = $type == 'tasmik' ? 'VLET' : 'VLEM'; // Use 'VLET' for VLE tasmik and 'VLEM' for VLE murajaah
        $date = date("ymd"); // Use 'ymd' to get last two digits of the year
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM tasmik"); // Always query tasmik table regardless of type
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'] + 1;
        $stmt->close();
        $tasmik_id = $prefix . str_pad($count, 2, '0', STR_PAD_LEFT) . $date;

        // Use the same ID for VLE submission to maintain consistency
        $vle_submission_id = $tasmik_id;

        // Get form data
        $submission_date = date("Y-m-d"); // Set today's date
        $juzuk = $_POST['juzuk'];
        $start_page = $_POST['startPage'];
        $end_page = $_POST['endPage'];
        $start_ayah = $_POST['startAyah'];
        $end_ayah = $_POST['endAyah'];
        $live_conference = "no"; // Set live_conference to "no" for VLE submissions
        $status = "pending"; // Set status to "pending" by default

        // Handle optional audio file upload
        $audio_file = null;
        if (isset($_FILES['audioFile']) && $_FILES['audioFile']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../uploads/audio/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['audioFile']['name'], PATHINFO_EXTENSION);
            $unique_filename = $tasmik_id . "_" . time() . "." . $file_extension;
            $audio_file_path = $target_dir . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['audioFile']['tmp_name'], $audio_file_path)) {
                $audio_file = $audio_file_path;
            } else {
                $submission_error = true;
                $error_message = "Error uploading audio file.";
            }
        }

        if (!$submission_error) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // 1. Insert into tasmik table (using correct column name 'tasmikid')
                $stmt = $conn->prepare("INSERT INTO tasmik (tasmikid, studentid, tasmik_date, juzuk, start_page, end_page, start_ayah, end_ayah, live_conference, status, submitted_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $stmt->bind_param("sssiiiiiss", $tasmik_id, $studentid, $submission_date, $juzuk, $start_page, $end_page, $start_ayah, $end_ayah, $live_conference, $status);
                $stmt->execute();
                
                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Failed to insert record into tasmik table.");
                }
                $stmt->close();

                // 2. Get an assessment ID related to this type of submission
                if (empty($assessmentId)) {
                    $stmt = $conn->prepare("SELECT assessmentid FROM vle_assessments WHERE type = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->bind_param("s", $type);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $assessmentId = $row['assessmentid'];
                    } else {
                        // If no assessment found, create a placeholder one
                        $assessmentId = "AUTO_" . $type . "_" . date("ymd");
                    }
                    $stmt->close();
                }

                // 3. Insert into VLE assessment submissions table
                $stmt = $conn->prepare("INSERT INTO vle_assessment_submissions (submissionid, assessmentid, studentid, file_path, status, submitted_at, is_done) 
                                    VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP, 1)");
                $stmt->bind_param("ssss", $vle_submission_id, $assessmentId, $studentid, $audio_file);
                $stmt->execute();
                
                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Failed to insert record into vle_assessment_submissions table.");
                }
                $stmt->close();

                // Commit transaction
                $conn->commit();
                $success = true;
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $submission_error = true;
                $error_message = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
            <hr>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><?php echo ucfirst($type); ?> Form</h4>
            </div>
            <div class="card-body">
                <?php if ($submission_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form action="submit_tasmik_murajaah.php?type=<?php echo $type; ?>&id=<?php echo htmlspecialchars($assessmentId); ?>" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="juzuk">Juzuk:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="juzukInput" aria-label="Text input with dropdown button" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-primary btn-border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Select Juzuk
                                </button>
                                <div class="dropdown-menu" style="max-height: 200px; overflow-y: auto;">
                                    <?php for ($i = 1; $i <= 30; $i++): ?>
                                        <a class="dropdown-item" href="#" onclick="document.getElementById('juzukInput').value='Juzuk <?php echo $i; ?>'; document.getElementById('juzuk').value='<?php echo $i; ?>';">Juzuk <?php echo $i; ?></a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="juzuk" name="juzuk" required>
                    </div>
                    <div class="form-group">
                        <label for="startPage">Start Page:</label>
                        <input type="number" class="form-control" id="startPage" name="startPage" min="0" max="604" required>
                    </div>
                    <div class="form-group">
                        <label for="endPage">End Page:</label>
                        <input type="number" class="form-control" id="endPage" name="endPage" min="0" max="604" required>
                    </div>
                    <div class="form-group">
                        <label for="startAyah">Start Ayah:</label>
                        <input type="number" class="form-control" id="startAyah" name="startAyah" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="endAyah">End Ayah:</label>
                        <input type="number" class="form-control" id="endAyah" name="endAyah" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="audioFile">Attach Audio File (Optional):</label>
                        <input type="file" class="form-control" id="audioFile" name="audioFile" accept="audio/*">
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-left: 10px;" id="submitBtn">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add SweetAlert library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include '../include/footer.php'; ?>

<script>
    function validateForm() {
        var startPage = document.getElementById('startPage').value;
        var endPage = document.getElementById('endPage').value;
        var startAyah = document.getElementById('startAyah').value;
        var endAyah = document.getElementById('endAyah').value;
        var juzuk = document.getElementById('juzuk').value;

        if (!juzuk) {
            Swal.fire({
                title: 'Error!',
                text: 'Please select a Juzuk.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }

        if (parseInt(endPage) < parseInt(startPage)) {
            Swal.fire({
                title: 'Error!',
                text: 'End Page cannot be less than Start Page.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }

        if (parseInt(endAyah) < parseInt(startAyah) && parseInt(endPage) <= parseInt(startPage)) {
            Swal.fire({
                title: 'Error!',
                text: 'End Ayah cannot be less than Start Ayah when on the same page.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }

        // Show loading state
        Swal.fire({
            title: 'Submitting...',
            text: 'Please wait while we process your submission',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        return true;
    }

    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            });
        }, 5000);
        
        <?php if ($success): ?>
        // Show success message and redirect to assessment submission page
        Swal.fire({
            title: 'Success!',
            text: 'Your <?php echo $type; ?> submission has been recorded successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            // Go back to the specific assessment submission page
            window.location.href = 'assessment_submission.php?id=<?php echo htmlspecialchars($assessmentId); ?>';
        });
        <?php endif; ?>
    });
</script>