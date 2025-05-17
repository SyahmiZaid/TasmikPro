<?php
$pageTitle = "View Recorded Sessions";
$breadcrumb = "Pages / View Recorded Sessions";
include '../include/header.php';

// Check if the user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once '../database/db_connection.php';

// Get user role from session
$userid = $_SESSION['userid'];
$role = $_SESSION['role'];

// Prepare the query based on user role
$recordings = [];

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
        
        // Get recordings for this teacher's halaqah
        $query = "SELECT t.tasmikid, t.recording_path, t.tasmik_date, t.juzuk, 
                         t.start_page, t.end_page, t.start_ayah, t.end_ayah,
                         CONCAT(u.firstname, ' ', u.lastname) AS student_name 
                  FROM tasmik t
                  JOIN student s ON t.studentid = s.studentid
                  JOIN users u ON s.userid = u.userid
                  JOIN teacher tc ON s.halaqahid = tc.halaqahid
                  WHERE tc.teacherid = ? AND t.recording_path IS NOT NULL
                  ORDER BY t.tasmik_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $teacherid);
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
        
        // Get recordings for this student
        $query = "SELECT t.tasmikid, t.recording_path, t.tasmik_date, t.juzuk, 
                         t.start_page, t.end_page, t.start_ayah, t.end_ayah,
                         CONCAT(u.firstname, ' ', u.lastname) AS teacher_name 
                  FROM tasmik t
                  JOIN teacher tc ON t.teacherid = tc.teacherid
                  JOIN users u ON tc.userid = u.userid
                  WHERE t.studentid = ? AND t.recording_path IS NOT NULL
                  ORDER BY t.tasmik_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $studentid);
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
        
        // Get recordings for this parent's children
        $query = "SELECT t.tasmikid, t.recording_path, t.tasmik_date, t.juzuk, 
                         t.start_page, t.end_page, t.start_ayah, t.end_ayah,
                         CONCAT(su.firstname, ' ', su.lastname) AS student_name,
                         CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name 
                  FROM tasmik t
                  JOIN student s ON t.studentid = s.studentid
                  JOIN users su ON s.userid = su.userid
                  JOIN teacher tc ON t.teacherid = tc.teacherid
                  JOIN users tu ON tc.userid = tu.userid
                  WHERE s.parentid = ? AND t.recording_path IS NOT NULL
                  ORDER BY t.tasmik_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $parentid);
    }
} elseif ($role === 'admin') {
    // Admin can see all recordings
    $query = "SELECT t.tasmikid, t.recording_path, t.tasmik_date, t.juzuk, 
                     t.start_page, t.end_page, t.start_ayah, t.end_ayah,
                     CONCAT(su.firstname, ' ', su.lastname) AS student_name,
                     CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name 
              FROM tasmik t
              JOIN student s ON t.studentid = s.studentid
              JOIN users su ON s.userid = su.userid
              JOIN teacher tc ON t.teacherid = tc.teacherid
              JOIN users tu ON tc.userid = tu.userid
              WHERE t.recording_path IS NOT NULL
              ORDER BY t.tasmik_date DESC";
    $stmt = $conn->prepare($query);
}

// Execute the query and fetch the results
if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recordings[] = $row;
    }
    $stmt->close();
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Recorded Tasmik Sessions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recordings)): ?>
                            <div class="alert alert-info">
                                <p>No recorded sessions are available at this time.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <?php if ($role === 'teacher' || $role === 'admin' || $role === 'parent'): ?>
                                                <th>Student</th>
                                            <?php endif; ?>
                                            <?php if ($role === 'student' || $role === 'admin' || $role === 'parent'): ?>
                                                <th>Teacher</th>
                                            <?php endif; ?>
                                            <th>Juzuk</th>
                                            <th>Ayah Range</th>
                                            <th>Page Range</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recordings as $recording): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($recording['tasmik_date'])); ?></td>
                                                
                                                <?php if ($role === 'teacher' || $role === 'admin' || $role === 'parent'): ?>
                                                    <td><?php echo $recording['student_name'] ?? 'N/A'; ?></td>
                                                <?php endif; ?>
                                                
                                                <?php if ($role === 'student' || $role === 'admin' || $role === 'parent'): ?>
                                                    <td><?php echo $recording['teacher_name'] ?? 'N/A'; ?></td>
                                                <?php endif; ?>
                                                
                                                <td><?php echo $recording['juzuk']; ?></td>
                                                <td><?php echo $recording['start_ayah'] . ' - ' . $recording['end_ayah']; ?></td>
                                                <td><?php echo $recording['start_page'] . ' - ' . $recording['end_page']; ?></td>
                                                <td>
                                                    <a href="view_recording.php?id=<?php echo $recording['tasmikid']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-play-circle"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>