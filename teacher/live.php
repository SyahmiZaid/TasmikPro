<?php
$pageTitle = "Live Test";
$breadcrumb = "Pages / Live Test";
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

// Function to log errors to a file
function logError($message)
{
    $logFile = '../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);
}

// Function to initiate Zoom OAuth flow
function initiateZoomOAuth() {
    $client_id = '5WpSNwBkSiug0tMDdiQ7w';
    $redirect_uri = "http://localhost:3000/teacher/live.php";
    
    // Build the authorization URL
    $auth_url = "https://zoom.us/oauth/authorize?" . http_build_query([
        'response_type' => 'code',
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri
    ]);
    
    // Redirect the user to Zoom's authorization page
    header("Location: " . $auth_url);
    exit;
}

// Handle OAuth callback from Zoom
if (isset($_GET['code'])) {
    // We've received the authorization code from Zoom
    $code = $_GET['code'];
    $client_id = '5WpSNwBkSiug0tMDdiQ7w';
    $client_secret = 'bBDAcxR1GtOVEPC2Kv7yPq8KQBKHLTG';
    $redirect_uri = "http://localhost:3000/teacher/live.php";
    
    // Exchange code for access token
    $token_url = "https://zoom.us/oauth/token";
    $auth = base64_encode($client_id . ':' . $client_secret);
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ]));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Remove in production
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        logError('Zoom OAuth Token Error: ' . $err);
        $error_message = "Failed to connect to Zoom. Please try again.";
    } else {
        $token_data = json_decode($response, true);
        if (isset($token_data['access_token'])) {
            // Store token in session for later use
            $_SESSION['zoom_access_token'] = $token_data['access_token'];
            $_SESSION['zoom_refresh_token'] = $token_data['refresh_token'] ?? null;
            $_SESSION['zoom_token_expiry'] = time() + $token_data['expires_in'];
            $success_message = "Successfully connected to Zoom!";
        } else {
            logError('Failed to get OAuth token. Response: ' . print_r($token_data, true));
            $error_message = "Failed to authenticate with Zoom. Please try again.";
        }
    }
}

// Check if we need to refresh the token
function refreshZoomToken() {
    if (isset($_SESSION['zoom_refresh_token']) && isset($_SESSION['zoom_token_expiry']) && $_SESSION['zoom_token_expiry'] < time() + 60) {
        $client_id = '5WpSNwBkSiug0tMDdiQ7w';
        $client_secret = 'bBDAcxR1GtOVEPC2Kv7yPq8KQBKHLTG';
        $token_url = "https://zoom.us/oauth/token";
        $auth = base64_encode($client_id . ':' . $client_secret);
        
        $ch = curl_init($token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $_SESSION['zoom_refresh_token']
        ]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Remove in production
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if (!$err) {
            $token_data = json_decode($response, true);
            if (isset($token_data['access_token'])) {
                $_SESSION['zoom_access_token'] = $token_data['access_token'];
                $_SESSION['zoom_refresh_token'] = $token_data['refresh_token'] ?? $_SESSION['zoom_refresh_token'];
                $_SESSION['zoom_token_expiry'] = time() + $token_data['expires_in'];
                return true;
            }
        }
        
        // If we get here, refresh failed
        return false;
    }
    
    // No need to refresh
    return true;
}

function createZoomMeeting($mysqli, $tasmikid, $studentid, $meetingDate, $teacherId = null)
{
    // Check if we have a valid access token
    if (!isset($_SESSION['zoom_access_token'])) {
        logError('No Zoom access token available');
        return false;
    }
    
    // Check if token needs refreshing
    if (!refreshZoomToken()) {
        logError('Failed to refresh Zoom token');
        return false;
    }
    
    $token = $_SESSION['zoom_access_token'];
    
    // Parse the meeting date and format it for Zoom API
    $meetingDateTime = new DateTime($meetingDate);
    $formattedDate = $meetingDateTime->format('Y-m-d\TH:i:s');

    // Create meeting data
    $data = array(
        'topic' => 'Quran Recitation Session',
        'type' => 2, // Scheduled meeting
        'start_time' => $formattedDate,
        'timezone' => 'Asia/Kuala_Lumpur', // Adjust to your timezone
        'duration' => 60, // 60 minutes
        'password' => substr(md5(uniqid()), 0, 8),
        'settings' => array(
            'host_video' => true,
            'participant_video' => true,
            'join_before_host' => true,
            'mute_upon_entry' => false,
            'waiting_room' => false,
            'approval_type' => 0, // Automatically approve
            'audio' => 'both', // Both telephony and VoIP
            'auto_recording' => 'none' // No automatic recording
        )
    );

    // API call to create a meeting
    $ch = curl_init('https://api.zoom.us/v2/users/me/meetings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Remove this in production

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        // Handle error case
        logError('Zoom API cURL Error: ' . $err);
        return false;
    }

    // Log the response for debugging
    logError('Zoom API Response: ' . $response . ' (HTTP: ' . $httpCode . ')');

    $result = json_decode($response, true);
    
    // Check if the meeting was created successfully
    if (isset($result['id'])) {
        // Extract meeting details
        $meetingId = $result['id'];
        $password = $result['password'];
        $joinUrl = $result['join_url'];

        // Determine which teacher ID to use
        $teacherid = $teacherId ?? ($_SESSION['role'] === 'teacher' ? $_SESSION['teacherid'] : '');

        // Store meeting details in the database
        $stmt = $mysqli->prepare("INSERT INTO zoom_meetings (tasmikid, studentid, teacherid, meeting_id, password, meeting_link, scheduled_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $tasmikid, $studentid, $teacherid, $meetingId, $password, $joinUrl, $meetingDate);

        if (!$stmt->execute()) {
            logError('Database Error: ' . $stmt->error . ' when storing Zoom meeting details');
            $stmt->close();
            return false;
        }

        $stmt->close();
        return $joinUrl;
    } else {
        // Handle API response error
        logError('Zoom API Error: HTTP Code ' . $httpCode . ', Response: ' . print_r($result, true));
        return false;
    }
}

// Handle form submissions for scheduling or joining meetings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_meeting'])) {
        try {
            // Check if connected to Zoom
            if (!isset($_SESSION['zoom_access_token'])) {
                throw new Exception("You need to connect to Zoom first before scheduling a meeting.");
            }
            
            // Generate a unique tasmikid
            $tasmikid = 'TSM' . time();
            $studentid = $role === 'teacher' ? $_POST['studentid'] : $_SESSION['studentid'];
            $juzuk = $_POST['juzuk'];
            $start_page = $_POST['start_page'];
            $end_page = $_POST['end_page'];
            $start_ayah = $_POST['start_ayah'];
            $end_ayah = $_POST['end_ayah'];
            $meeting_date = $_POST['meeting_date'];

            // Begin transaction
            $mysqli->begin_transaction();

            // FIRST: Insert tasmik record
            $stmt = $mysqli->prepare("INSERT INTO tasmik (tasmikid, studentid, tasmik_date, juzuk, start_page, end_page, start_ayah, end_ayah, live_conference, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'yes', 'pending')");
            $stmt->bind_param("sssiiiis", $tasmikid, $studentid, $meeting_date, $juzuk, $start_page, $end_page, $start_ayah, $end_ayah);

            if (!$stmt->execute()) {
                throw new Exception("Failed to create tasmik record: " . $stmt->error);
            }
            $stmt->close();

            // SECOND: Create Zoom meeting
            if ($role === 'student' && isset($_POST['teacherid'])) {
                // Student is scheduling with a specific teacher
                $teacherId = $_POST['teacherid'];
                $zoomLink = createZoomMeeting($mysqli, $tasmikid, $studentid, $meeting_date, $teacherId);
            } else {
                // Teacher is scheduling
                $zoomLink = createZoomMeeting($mysqli, $tasmikid, $studentid, $meeting_date);
            }

            if ($zoomLink) {
                // Commit the transaction
                $mysqli->commit();
                $success_message = "Live conference scheduled successfully!";
            } else {
                // Rollback the transaction
                $mysqli->rollback();
                $error_message = "Failed to create Zoom meeting. Please try again later.";
            }
        } catch (Exception $e) {
            // Rollback the transaction on any error
            $mysqli->rollback();
            logError('Error in scheduling meeting: ' . $e->getMessage());
            $error_message = "An error occurred: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_status'])) {
        $tasmikid = $_POST['tasmikid'];
        $status = $_POST['status'];

        $stmt = $mysqli->prepare("UPDATE tasmik SET status = ? WHERE tasmikid = ?");
        $stmt->bind_param("ss", $status, $tasmikid);

        if ($stmt->execute()) {
            $success_message = "Tasmik status updated successfully!";
        } else {
            $error_message = "Failed to update status: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Zoom connection request
if (isset($_GET['connect_zoom'])) {
    initiateZoomOAuth();
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
        
        <?php if (!isset($_SESSION['zoom_access_token'])): ?>
        <div class="alert alert-warning">
            <strong>Zoom Connection Required:</strong> You need to connect your Zoom account before scheduling meetings.
            <a href="live.php?connect_zoom=1" class="btn btn-sm btn-primary ml-2">Connect with Zoom</a>
        </div>
        <?php endif; ?>
        
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div class="ml-md-auto py-2 py-md-0">
                <button class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal" <?php echo !isset($_SESSION['zoom_access_token']) ? 'disabled' : ''; ?>>
                    <i class="fas fa-video mr-2"></i>Schedule Quran Recitation Session
                </button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
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
                        <?php if (empty($upcoming_meetings)): ?>
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
                                        <?php foreach ($upcoming_meetings as $meeting): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y - h:i A', strtotime($meeting['tasmik_date'])); ?></td>
                                                <td><?php echo $meeting['firstname'] . ' ' . $meeting['lastname']; ?></td>
                                                <td><?php echo $meeting['juzuk']; ?></td>
                                                <td><?php echo $meeting['start_page'] . ' - ' . $meeting['end_page']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                                               echo $meeting['status'] === 'approved' ? 'success' : ($meeting['status'] === 'rejected' ? 'danger' : 'warning');
                                                                               ?>">
                                                        <?php echo ucfirst($meeting['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($meeting['meeting_link'])): ?>
                                                        <a href="<?php echo $meeting['meeting_link']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-video mr-1"></i> Join
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($role === 'teacher' && $meeting['status'] === 'pending'): ?>
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
                        <?php if (empty($past_meetings)): ?>
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
                                        <?php foreach ($past_meetings as $meeting): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y - h:i A', strtotime($meeting['tasmik_date'])); ?></td>
                                                <td><?php echo $meeting['firstname'] . ' ' . $meeting['lastname']; ?></td>
                                                <td><?php echo $meeting['juzuk']; ?></td>
                                                <td><?php echo $meeting['start_page'] . ' - ' . $meeting['end_page']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                                               echo $meeting['status'] === 'approved' ? 'success' : ($meeting['status'] === 'rejected' ? 'danger' : 'warning');
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
                        <?php if ($role === 'student'): ?>
                            <label for="teacherid">Select Teacher</label>
                            <select class="form-control" id="teacherid" name="teacherid" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['teacherid']; ?>">
                                        <?php echo $teacher['firstname'] . ' ' . $teacher['lastname']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <label for="studentid">Select Student</label>
                            <select class="form-control" id="studentid" name="studentid" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $student): ?>
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
                                    <?php for ($i = 1; $i <= 30; $i++): ?>
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

        // Add validation for date and pages
        $('#scheduleModal form').on('submit', function(e) {
            var startPage = parseInt($('#start_page').val());
            var endPage = parseInt($('#end_page').val());

            if (endPage < startPage) {
                alert('End page cannot be less than start page');
                e.preventDefault();
                return false;
            }

            var meetingDate = new Date($('#meeting_date').val());
            var now = new Date();

            if (meetingDate <= now) {
                alert('Meeting date must be in the future');
                alert('Meeting date must be in the future');
                e.preventDefault();
                return false;
            }

            return true;
        });
        
        // Validate ayah numbers
        $('#start_ayah, #end_ayah').on('change', function() {
            var startAyah = parseInt($('#start_ayah').val());
            var endAyah = parseInt($('#end_ayah').val());
            
            if (startAyah > endAyah && $('#start_page').val() === $('#end_page').val()) {
                alert('End ayah cannot be less than start ayah on the same page');
                $(this).val('');
            }
        });
        
        // Auto-adjust min value for end page based on start page
        $('#start_page').on('change', function() {
            var startPage = parseInt($(this).val());
            $('#end_page').attr('min', startPage);
            
            // If current end page is less than start page, update it
            if (parseInt($('#end_page').val()) < startPage) {
                $('#end_page').val(startPage);
            }
        });
    });
</script>

<?php
include '../include/footer.php';
?>