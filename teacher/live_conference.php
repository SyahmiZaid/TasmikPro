<?php
$pageTitle = "Live Conference";
$breadcrumb = "Pages / Live Conference";
include '../include/header.php';

// Check if the user is logged in
if (!isset($_SESSION['userid']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

$userid = $_SESSION['userid'];
$role = $_SESSION['role'];

// Database connection - using the correct path
require_once '../database/db_connection.php';

// Check if the connection is established
if (!isset($mysqli) || $mysqli === null) {
    // If your db_connection.php uses a different variable name, adjust accordingly
    if (isset($conn)) {
        $mysqli = $conn;
    } else {
        die("Database connection not established. Check your db_connection.php file.");
    }
}

// Function to generate a Zoom meeting link
function createZoomMeeting($mysqli, $tasmikid, $studentid, $meetingDate) {
    // In a real implementation, you would use Zoom API here
    // For now, we'll simulate it with a mock meeting link
    $meetingId = rand(10000000000, 99999999999);
    $password = substr(md5(uniqid()), 0, 8);
    $zoomLink = "https://zoom.us/j/$meetingId?pwd=$password";
    
    // Store meeting details in the zoom_meetings table
    // Since we don't have teacherid in tasmik table, we'll use userid of the current teacher
    $teacherid = $_SESSION['role'] === 'teacher' ? $_SESSION['teacherid'] : '';
    
    $stmt = $mysqli->prepare("INSERT INTO zoom_meetings (tasmikid, studentid, teacherid, meeting_id, password, meeting_link, scheduled_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $tasmikid, $studentid, $teacherid, $meetingId, $password, $zoomLink, $meetingDate);
    $stmt->execute();
    $stmt->close();
    
    return $zoomLink;
}

// Handle form submissions for scheduling or joining meetings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_meeting'])) {
        // Generate a unique tasmikid
        $tasmikid = 'TSM' . time();
        $studentid = $role === 'teacher' ? $_POST['studentid'] : $_SESSION['studentid'];
        $juzuk = $_POST['juzuk'];
        $start_page = $_POST['start_page'];
        $end_page = $_POST['end_page'];
        $start_ayah = $_POST['start_ayah'];
        $end_ayah = $_POST['end_ayah'];
        $meeting_date = $_POST['meeting_date'];
        
        // FIRST: Insert tasmik record
        $stmt = $mysqli->prepare("INSERT INTO tasmik (tasmikid, studentid, tasmik_date, juzuk, start_page, end_page, start_ayah, end_ayah, live_conference, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'yes', 'pending')");
        $stmt->bind_param("sssiiiis", $tasmikid, $studentid, $meeting_date, $juzuk, $start_page, $end_page, $start_ayah, $end_ayah);
        $stmt->execute();
        $stmt->close();
        
        // SECOND: Create Zoom meeting and insert into zoom_meetings
        $zoomLink = createZoomMeeting($mysqli, $tasmikid, $studentid, $meeting_date);
        
        $success_message = "Live conference scheduled successfully!";
    }
    
    if (isset($_POST['update_status'])) {
        $tasmikid = $_POST['tasmikid'];
        $status = $_POST['status'];
        
        $stmt = $mysqli->prepare("UPDATE tasmik SET status = ? WHERE tasmikid = ?");
        $stmt->bind_param("ss", $status, $tasmikid);
        $stmt->execute();
        $stmt->close();
        
        $success_message = "Tasmik status updated successfully!";
    }
}

// Fetch relevant data based on user role
$upcoming_meetings = [];
$past_meetings = [];

if ($role === 'student') {
    // Get student ID
    $stmt = $mysqli->prepare("SELECT studentid FROM student WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $studentid = $student['studentid'];
    $_SESSION['studentid'] = $studentid;
    $stmt->close();
    
    // Get upcoming and past meetings for this student
    $stmt = $mysqli->prepare("
        SELECT t.*, u.firstname, u.lastname, zm.meeting_link, zm.teacherid
        FROM tasmik t
        LEFT JOIN zoom_meetings zm ON t.tasmikid = zm.tasmikid
        LEFT JOIN teacher tc ON zm.teacherid = tc.teacherid
        LEFT JOIN users u ON tc.userid = u.userid
        WHERE t.studentid = ? AND t.live_conference = 'yes'
        ORDER BY t.tasmik_date DESC
    ");
    $stmt->bind_param("s", $studentid);
    
    // Get available teachers for scheduling
    $teachers_query = "
        SELECT t.teacherid, u.firstname, u.lastname
        FROM teacher t
        JOIN users u ON t.userid = u.userid
    ";
    $teachers_result = $mysqli->query($teachers_query);
    $teachers = [];
    while ($row = $teachers_result->fetch_assoc()) {
        $teachers[] = $row;
    }
} elseif ($role === 'teacher') {
    // Get teacher ID
    $stmt = $mysqli->prepare("SELECT teacherid FROM teacher WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $teacherid = $teacher['teacherid'];
    $_SESSION['teacherid'] = $teacherid;
    $stmt->close();
    
    // Get upcoming and past meetings for this teacher through zoom_meetings table
    $stmt = $mysqli->prepare("
        SELECT t.*, u.firstname, u.lastname, zm.meeting_link
        FROM tasmik t
        JOIN student s ON t.studentid = s.studentid
        JOIN users u ON s.userid = u.userid
        LEFT JOIN zoom_meetings zm ON t.tasmikid = zm.tasmikid
        WHERE zm.teacherid = ? AND t.live_conference = 'yes'
        ORDER BY t.tasmik_date DESC
    ");
    $stmt->bind_param("s", $teacherid);
    
    // Get students for this teacher
    $students_query = "
        SELECT s.studentid, u.firstname, u.lastname
        FROM student s
        JOIN users u ON s.userid = u.userid
        WHERE s.halaqahid IN (SELECT halaqahid FROM teacher WHERE teacherid = '$teacherid')
    ";
    $students_result = $mysqli->query($students_query);
    $students = [];
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Execute the statement and process results
$stmt->execute();
$result = $stmt->get_result();
$current_date = date('Y-m-d');

while ($row = $result->fetch_assoc()) {
    if ($row['tasmik_date'] >= $current_date) {
        $upcoming_meetings[] = $row;
    } else {
        $past_meetings[] = $row;
    }
}
$stmt->close();
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div class="ml-md-auto py-2 py-md-0">
                <button class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">
                    <i class="fas fa-video mr-2"></i>Schedule Quran Recitation Session
                </button>
            </div>
        </div>
        
        <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Upcoming Meetings -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Upcoming Quran Recitation Sessions</div>
                    </div>
                    <div class="card-body">
                        <?php if(empty($upcoming_meetings)): ?>
                            <div class="alert alert-info">No upcoming sessions scheduled.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th><?php echo $role === 'student' ? 'Teacher' : 'Student'; ?></th>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($upcoming_meetings as $meeting): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y - h:i A', strtotime($meeting['tasmik_date'])); ?></td>
                                            <td><?php echo $meeting['firstname'] . ' ' . $meeting['lastname']; ?></td>
                                            <td><?php echo $meeting['juzuk']; ?></td>
                                            <td><?php echo $meeting['start_page'] . ' - ' . $meeting['end_page']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $meeting['status'] === 'approved' ? 'success' : 
                                                        ($meeting['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($meeting['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if(!empty($meeting['meeting_link'])): ?>
                                                    <a href="<?php echo $meeting['meeting_link']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-video mr-1"></i> Join
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if($role === 'teacher' && $meeting['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-success update-status" 
                                                            data-tasmikid="<?php echo $meeting['tasmikid']; ?>" data-status="approved">
                                                        <i class="fas fa-check mr-1"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger update-status" 
                                                            data-tasmikid="<?php echo $meeting['tasmikid']; ?>" data-status="rejected">
                                                        <i class="fas fa-times mr-1"></i> Reject
                                                    </button>
                                                <?php endif; ?>
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
        
        <!-- Past Meetings -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Past Quran Recitation Sessions</div>
                    </div>
                    <div class="card-body">
                        <?php if(empty($past_meetings)): ?>
                            <div class="alert alert-info">No past sessions found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th><?php echo $role === 'student' ? 'Teacher' : 'Student'; ?></th>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($past_meetings as $meeting): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y - h:i A', strtotime($meeting['tasmik_date'])); ?></td>
                                            <td><?php echo $meeting['firstname'] . ' ' . $meeting['lastname']; ?></td>
                                            <td><?php echo $meeting['juzuk']; ?></td>
                                            <td><?php echo $meeting['start_page'] . ' - ' . $meeting['end_page']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $meeting['status'] === 'approved' ? 'success' : 
                                                        ($meeting['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($meeting['status']); ?>
                                                </span>
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

<!-- Modal for scheduling a meeting -->
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Schedule Quran Recitation Session</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <?php if($role === 'student'): ?>
                            <label for="teacherid">Select Teacher</label>
                            <select class="form-control" id="teacherid" name="teacherid" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['teacherid']; ?>">
                                        <?php echo $teacher['firstname'] . ' ' . $teacher['lastname']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <label for="studentid">Select Student</label>
                            <select class="form-control" id="studentid" name="studentid" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach($students as $student): ?>
                                    <option value="<?php echo $student['studentid']; ?>">
                                        <?php echo $student['firstname'] . ' ' . $student['lastname']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="meeting_date">Date and Time</label>
                        <input type="datetime-local" class="form-control" id="meeting_date" name="meeting_date" 
                              min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="juzuk">Juzuk</label>
                                <select class="form-control" id="juzuk" name="juzuk" required>
                                    <?php for($i = 1; $i <= 30; $i++): ?>
                                        <option value="<?php echo $i; ?>">Juzuk <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_page">Start Page</label>
                                <input type="number" class="form-control" id="start_page" name="start_page" min="1" max="604" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_page">End Page</label>
                                <input type="number" class="form-control" id="end_page" name="end_page" min="1" max="604" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_ayah">Start Ayah</label>
                                <input type="number" class="form-control" id="start_ayah" name="start_ayah" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_ayah">End Ayah</label>
                                <input type="number" class="form-control" id="end_ayah" name="end_ayah" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="schedule_meeting" class="btn btn-primary">Schedule Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form for updating status -->
<form id="updateStatusForm" method="post" style="display: none;">
    <input type="hidden" name="tasmikid" id="update_tasmikid">
    <input type="hidden" name="status" id="update_status">
    <input type="hidden" name="update_status" value="1">
</form>

<!-- JavaScript for handling status updates -->
<script>
    $(document).ready(function() {
        $('.update-status').click(function() {
            var tasmikid = $(this).data('tasmikid');
            var status = $(this).data('status');
            
            $('#update_tasmikid').val(tasmikid);
            $('#update_status').val(status);
            $('#updateStatusForm').submit();
        });
    });
</script>

<?php include '../include/footer.php'; ?>