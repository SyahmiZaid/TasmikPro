<?php
$pageTitle = "View Recording";
$breadcrumb = "Pages / View Recorded Sessions / View Recording";
include '../include/header.php';

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once '../database/db_connection.php';

// Get recording ID from URL
if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Recording ID not specified.</div>';
    include '../include/footer.php';
    exit();
}

$tasmikid = $_GET['id'];

// Get user role from session
$userid = $_SESSION['userid'];
$role = $_SESSION['role'];

// Prepare the query based on user role to ensure user has access to this recording
$authorized = false;
$recording = null;

if ($role === 'teacher') {
    // Get teacher ID
    $stmt = $conn->prepare("SELECT teacherid FROM teacher WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $stmt->close();
    
    if ($teacher) {
        $teacherid = $teacher['teacherid'];
        
        // Check if this recording belongs to one of this teacher's students
        $query = "SELECT t.*, 
                         CONCAT(su.firstname, ' ', su.lastname) AS student_name,
                         CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name
                  FROM tasmik t
                  JOIN student s ON t.studentid = s.studentid
                  JOIN teacher tc ON s.halaqahid = tc.halaqahid
                  JOIN users su ON s.userid = su.userid
                  JOIN users tu ON tc.userid = tu.userid
                  WHERE t.tasmikid = ? AND tc.teacherid = ? AND t.recording_path IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $tasmikid, $teacherid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $authorized = true;
            $recording = $result->fetch_assoc();
        }
        $stmt->close();
    }
} elseif ($role === 'student') {
    // Get student ID
    $stmt = $conn->prepare("SELECT studentid FROM student WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        $studentid = $student['studentid'];
        
        // Check if this recording belongs to this student
        $query = "SELECT t.*, 
                         CONCAT(su.firstname, ' ', su.lastname) AS student_name,
                         CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name
                  FROM tasmik t
                  JOIN student s ON t.studentid = s.studentid
                  JOIN teacher tc ON t.teacherid = tc.teacherid
                  JOIN users su ON s.userid = su.userid
                  JOIN users tu ON tc.userid = tu.userid
                  WHERE t.tasmikid = ? AND t.studentid = ? AND t.recording_path IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $tasmikid, $studentid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $authorized = true;
            $recording = $result->fetch_assoc();
        }
        $stmt->close();
    }
} elseif ($role === 'parent') {
    // Get parent ID
    $stmt = $conn->prepare("SELECT parentid FROM parent WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $parent = $result->fetch_assoc();
    $stmt->close();
    
    if ($parent) {
        $parentid = $parent['parentid'];
        
        // Check if this recording belongs to one of this parent's children
        $query = "SELECT t.*, 
                         CONCAT(su.firstname, ' ', su.lastname) AS student_name,
                         CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name
                  FROM tasmik t
                  JOIN student s ON t.studentid = s.studentid
                  JOIN teacher tc ON t.teacherid = tc.teacherid
                  JOIN users su ON s.userid = su.userid
                  JOIN users tu ON tc.userid = tu.userid
                  WHERE t.tasmikid = ? AND s.parentid = ? AND t.recording_path IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $tasmikid, $parentid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $authorized = true;
            $recording = $result->fetch_assoc();
        }
        $stmt->close();
    }
} elseif ($role === 'admin') {
    // Admin can access all recordings
    $query = "SELECT t.*, 
                     CONCAT(su.firstname, ' ', su.lastname) AS student_name,
                     CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name
              FROM tasmik t
              JOIN student s ON t.studentid = s.studentid
              JOIN teacher tc ON t.teacherid = tc.teacherid
              JOIN users su ON s.userid = su.userid
              JOIN users tu ON tc.userid = tu.userid
              WHERE t.tasmikid = ? AND t.recording_path IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $tasmikid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $authorized = true;
        $recording = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        
        <?php if (!$authorized || !$recording): ?>
            <div class="alert alert-danger">
                <p>You are not authorized to view this recording or the recording does not exist.</p>
                <a href="view_recorded_sessions.php" class="btn btn-danger mt-2">Back to Recordings</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Tasmik Recording Details</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Session Information</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Tasmik Date:</th>
                                            <td><?php echo date('F d, Y', strtotime($recording['tasmik_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Student:</th>
                                            <td><?php echo $recording['student_name']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Teacher:</th>
                                            <td><?php echo $recording['teacher_name']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Juzuk:</th>
                                            <td><?php echo $recording['juzuk']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Ayah Range:</th>
                                            <td><?php echo $recording['start_ayah'] . ' - ' . $recording['end_ayah']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Page Range:</th>
                                            <td><?php echo $recording['start_page'] . ' - ' . $recording['end_page']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Recording</h5>
                                    <div class="video-container mb-3">
                                        <video id="recordingPlayer" controls style="width: 100%; max-height: 500px; background-color: #000;">
                                            <source src="../recordings/<?php echo $recording['recording_path']; ?>" type="video/webm">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                    
                                    <div class="btn-group">
                                        <a href="../recordings/<?php echo $recording['recording_path']; ?>" download class="btn btn-primary">
                                            <i class="fas fa-download"></i> Download Recording
                                        </a>
                                        
                                        <a href="view_recorded_sessions.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Recordings
                                        </a>
                                        
                                        <?php if ($role === 'teacher'): ?>
                                        <a href="provide_feedback.php?tasmikid=<?php echo $tasmikid; ?>" class="btn btn-success">
                                            <i class="fas fa-comment"></i> Provide Feedback
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .video-container {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        overflow: hidden;
        background-color: #000;
    }
</style>

<?php include '../include/footer.php'; ?>