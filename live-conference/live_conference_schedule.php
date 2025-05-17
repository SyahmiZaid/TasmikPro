<?php
$pageTitle = "Schedule Live Conference";
$breadcrumb = "Pages / Schedule Live Conference";
include '../include/header.php';

// Database connection
require_once '../database/db_connection.php';

// Check if the teacher is logged in
if (!isset($_SESSION['userid'])) {
    // Redirect to login page if not logged in
    header("Location: ../index.php");
    exit();
}

// Fetch the teacherid and halaqahid based on the userid stored in the session
$userid = $_SESSION['userid'];
$teacherID = null;
$halaqahID = null;

$query = "SELECT teacherid, halaqahid FROM teacher WHERE userid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userid);
$stmt->execute();
$stmt->bind_result($teacherID, $halaqahID);
$stmt->fetch();
$stmt->close();

if (!$teacherID) {
    die("Error: Your account is not registered as a teacher. Please contact the administrator.");
}

// Handle form submission for scheduling a new conference
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get student ID from the form
    $studentid = $_POST['studentid'];

    // Get scheduled date and time
    $scheduledDate = $_POST['date'];
    $scheduledTime = $_POST['time'];
    $scheduled_at = $scheduledDate . ' ' . $scheduledTime . ':00';

    // Generate the meeting room name
    $meetingRoom = "tasmik_room_" . $teacherID;

    // Insert into database
    $query = "INSERT INTO zoom_meetings (tasmikid, studentid, teacherid, meeting_id, meeting_link, scheduled_at) 
          VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $tasmikid, $studentid, $teacherID, $meeting_id, $meeting_link, $scheduled_at);

    if ($stmt->execute()) {
        $success = "Live conference scheduled successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all students for the dropdown - ONLY FROM TEACHER'S HALAQAH
$studentQuery = "SELECT s.studentid, CONCAT(u.firstname, ' ', u.lastname) AS fullname 
                 FROM student s 
                 LEFT JOIN users u ON s.userid = u.userid 
                 WHERE s.halaqahid = ?
                 ORDER BY fullname";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $halaqahID);
$stmt->execute();
$studentResult = $stmt->get_result();
$stmt->close();

// Fetch tasmik records where live_conference is 'yes' - ONLY FROM TEACHER'S HALAQAH
$tasmikQuery = "SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) AS student_name 
                FROM tasmik t
                LEFT JOIN student s ON t.studentid = s.studentid
                LEFT JOIN users u ON s.userid = u.userid
                WHERE t.live_conference = 'yes' 
                  AND t.tasmikid NOT IN (SELECT tasmikid FROM zoom_meetings)
                  AND s.halaqahid = ?
                ORDER BY t.tasmik_date DESC";
$stmt = $conn->prepare($tasmikQuery);
$stmt->bind_param("s", $halaqahID);
$stmt->execute();
$tasmikResult = $stmt->get_result();
$stmt->close();
?>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs for Tasmik Records and Scheduled Conferences -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card-header">
                    <ul class="nav nav-pills nav-secondary" id="conferenceTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tasmik-tab" data-bs-toggle="pill" href="#tasmik" role="tab" aria-controls="tasmik" aria-selected="true">Tasmik Records</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="scheduled-tab" data-bs-toggle="pill" href="#scheduled" role="tab" aria-controls="scheduled" aria-selected="false">Scheduled Conferences</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content mt-3" id="conferenceTabsContent">
                        <!-- Tasmik Records Tab -->
                        <div class="tab-pane fade show active" id="tasmik" role="tabpanel" aria-labelledby="tasmik-tab">
                            <div class="card" style="background-color: transparent; border: none;">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Tasmik ID</th>
                                                    <th>Student</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($tasmikResult->num_rows > 0): ?>
                                                    <?php while ($row = $tasmikResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['tasmikid']; ?></td>
                                                            <td><?php echo $row['student_name']; ?> (<?php echo $row['studentid']; ?>)</td>
                                                            <td><?php echo date('M d, Y', strtotime($row['tasmik_date'])); ?></td>
                                                            <td>
                                                                <button class="btn btn-primary btn-sm schedule-meeting"
                                                                    data-tasmikid="<?php echo $row['tasmikid']; ?>"
                                                                    data-studentid="<?php echo $row['studentid']; ?>"
                                                                    data-studentname="<?php echo $row['student_name']; ?>"
                                                                    data-tasmikdate="<?php echo date('M d, Y', strtotime($row['tasmik_date'])); ?>">
                                                                    Schedule Meeting
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No records available for live conference.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scheduled Conferences Tab -->
                        <div class="tab-pane fade" id="scheduled" role="tabpanel" aria-labelledby="scheduled-tab">
                            <div class="card" style="background-color: transparent; border: none;">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Meeting ID</th>
                                                    <th>Student</th>
                                                    <th>Scheduled At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Fetch scheduled conferences - ONLY FROM TEACHER'S HALAQAH
                                                $scheduledQuery = "SELECT z.meeting_id, z.scheduled_at, z.meeting_link, 
                                                                      CONCAT(u.firstname, ' ', u.lastname) AS student_name 
                                                                FROM zoom_meetings z
                                                                LEFT JOIN student s ON z.studentid = s.studentid
                                                                LEFT JOIN users u ON s.userid = u.userid
                                                                WHERE z.teacherid = ? AND s.halaqahid = ?";
                                                $stmt = $conn->prepare($scheduledQuery);
                                                $stmt->bind_param("ss", $teacherID, $halaqahID);
                                                $stmt->execute();
                                                $scheduledResult = $stmt->get_result();

                                                if ($scheduledResult->num_rows > 0): ?>
                                                    <?php while ($row = $scheduledResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['meeting_id']; ?></td>
                                                            <td><?php echo $row['student_name']; ?></td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($row['scheduled_at'])); ?></td>
                                                            <td>
                                                                <a href="live_conference.php?room=<?php echo urlencode($row['meeting_link']); ?>" class="btn btn-success btn-sm">
                                                                    Start Meeting
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No scheduled conferences available.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Styles -->
        <style>
            .nav-pills .nav-link {
                border-radius: 0.25rem;
                color: #007bff;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                margin-right: 5px;
            }

            .nav-pills .nav-link.active {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }

            .card {
                background-color: transparent !important;
                border: none !important;
            }

            .tab-content .table {
                margin-bottom: 0;
            }
        </style>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.schedule-meeting').click(function() {
            var tasmikid = $(this).data('tasmikid');
            var studentid = $(this).data('studentid');
            var studentname = $(this).data('studentname');
            var tasmikdate = $(this).data('tasmikdate');

            Swal.fire({
                title: 'Schedule Meeting',
                html: `
                <div class="form-group">
                    <label><strong>Tasmik ID:</strong> ${tasmikid}</label><br>
                    <label><strong>Student Name:</strong> ${studentname}</label><br>
                    <label><strong>Tasmik Date:</strong> ${tasmikdate}</label><br><br>
                    <label for="swal-date">Date</label>
                    <input type="date" id="swal-date" class="form-control" required min="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label for="swal-time">Time</label>
                    <input type="time" id="swal-time" class="form-control" required>
                </div>
            `,
                showCancelButton: true,
                confirmButtonText: 'Schedule',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const date = document.getElementById('swal-date').value;
                    const time = document.getElementById('swal-time').value;

                    if (!date || !time) {
                        Swal.showValidationMessage('Please fill out both date and time.');
                        return false;
                    }

                    return {
                        date,
                        time
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const {
                        date,
                        time
                    } = result.value;

                    // Show loading indicator
                    Swal.fire({
                        title: 'Scheduling...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Make AJAX request to schedule the meeting
                    $.ajax({
                        url: 'schedule_meeting.php',
                        method: 'POST',
                        data: {
                            tasmikid: tasmikid,
                            studentid: studentid,
                            date: date,
                            time: time
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Scheduled!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'An error occurred while scheduling the meeting.',
                                    'error'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);

                            let errorMessage = 'An error occurred while scheduling the meeting.';
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse && errorResponse.message) {
                                    errorMessage = errorResponse.message;
                                }
                            } catch (e) {
                                console.error('Failed to parse error response:', xhr.responseText);
                            }

                            Swal.fire(
                                'Error!',
                                errorMessage,
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>

<?php include '../include/footer.php'; ?>