<?php
$pageTitle = "Live Conference Schedule";
$breadcrumb = "Pages / Live Conference";
include '../include/header.php';

// Redirect if not logged in
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Get student ID from session user
$userid = $_SESSION['userid'];
$stmt = $conn->prepare("SELECT studentid FROM student WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Student information not found.";
} else {
    $student = $result->fetch_assoc();
    $studentId = $student['studentid'];

    // Fetch upcoming meetings for this student
    $stmt = $conn->prepare("
        SELECT zm.*, t.juzuk, t.start_page, t.end_page, 
               CONCAT(u.firstname, ' ', u.lastname) AS teacher_name
        FROM zoom_meetings zm
        JOIN tasmik t ON zm.tasmikid = t.tasmikid
        JOIN teacher te ON zm.teacherid = te.teacherid
        JOIN users u ON te.userid = u.userid
        WHERE zm.studentid = ? AND zm.scheduled_at >= NOW()
        ORDER BY zm.scheduled_at ASC
    ");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $meetingsResult = $stmt->get_result();

    // Fetch past meetings
    $stmt = $conn->prepare("
        SELECT zm.*, t.juzuk, t.start_page, t.end_page, 
               CONCAT(u.firstname, ' ', u.lastname) AS teacher_name
        FROM zoom_meetings zm
        JOIN tasmik t ON zm.tasmikid = t.tasmikid
        JOIN teacher te ON zm.teacherid = te.teacherid
        JOIN users u ON te.userid = u.userid
        WHERE zm.studentid = ? AND zm.scheduled_at < NOW()
        ORDER BY zm.scheduled_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $pastMeetingsResult = $stmt->get_result();
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php else: ?>
            <!-- Upcoming Meetings Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">
                                    <i class="fas fa-video mr-2"></i> Upcoming Tasmik Conferences
                                </h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($meetingsResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Juzuk/Pages</th>
                                                <th>Teacher</th>
                                                <th>Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($meeting = $meetingsResult->fetch_assoc()): ?>
                                                <?php
                                                $scheduledTime = new DateTime($meeting['scheduled_at']);
                                                $now = new DateTime();
                                                $interval = $now->diff($scheduledTime);
                                                $minutesToMeeting = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                                                $isActive = $minutesToMeeting <= 15 && $scheduledTime > $now;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="meeting-indicator <?= $isActive ? 'active' : '' ?>"></div>
                                                            <div>
                                                                <div class="meeting-date">
                                                                    <?= $scheduledTime->format('d M Y') ?>
                                                                </div>
                                                                <div class="meeting-time">
                                                                    <?= $scheduledTime->format('h:i A') ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-primary">Juzuk <?= $meeting['juzuk'] ?></span>
                                                        <div class="mt-1 text-muted small">
                                                            Pages <?= $meeting['start_page'] ?>-<?= $meeting['end_page'] ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($meeting['teacher_name']) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($isActive): ?>
                                                            <span class="badge badge-success">Ready to Join</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info">
                                                                <?= getTimeRemaining($scheduledTime, $now) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($isActive): ?>
                                                            <a href="../live-conference/live_conference.php?room=<?= urlencode($meeting['meeting_link']) ?>"
                                                                class="btn btn-success btn-round btn-sm">
                                                                <i class="fas fa-video mr-1"></i> Join Now
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="../live-conference/live_conference.php?room=<?= urlencode($meeting['meeting_link']) ?>"
                                                                class="btn btn-primary btn-round btn-sm"
                                                                <?= $minutesToMeeting > 60 ? 'disabled' : '' ?>>
                                                                <i class="fas fa-calendar-check mr-1"></i> Join Meeting
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-video-slash fa-5x text-muted mb-3"></i>
                                        <h4 class="mt-4">No Upcoming Meetings</h4>
                                        <p class="text-muted">You don't have any scheduled Tasmik conferences right now.</p>
                                        <p class="text-muted">When you submit Tasmik requests in the VLE, your teacher will schedule a live conference.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Past Meetings Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title">
                                    <i class="fas fa-history mr-2"></i> Past Conferences
                                </h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($pastMeetingsResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Juzuk/Pages</th>
                                                <th>Teacher</th>
                                                <th>Meeting ID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($meeting = $pastMeetingsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <div class="meeting-date">
                                                                <?= date('d M Y', strtotime($meeting['scheduled_at'])) ?>
                                                            </div>
                                                            <div class="meeting-time text-muted">
                                                                <?= date('h:i A', strtotime($meeting['scheduled_at'])) ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-secondary">Juzuk <?= $meeting['juzuk'] ?></span>
                                                        <div class="mt-1 text-muted small">
                                                            Pages <?= $meeting['start_page'] ?>-<?= $meeting['end_page'] ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($meeting['teacher_name']) ?>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted"><?= $meeting['meeting_id'] ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-history fa-3x text-muted"></i>
                                        <p class="mt-3 text-muted">No past conference records found.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"><i class="fas fa-info-circle mr-2"></i> How Live Conferences Work</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center mb-4">
                                    <div class="icon-box">
                                        <i class="fas fa-calendar-check text-primary fa-3x mb-3"></i>
                                        <h5>1. Schedule</h5>
                                        <p class="text-muted">Your teacher schedules a live Tasmik session</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center mb-4">
                                    <div class="icon-box">
                                        <i class="fas fa-bell text-primary fa-3x mb-3"></i>
                                        <h5>2. Notification</h5>
                                        <p class="text-muted">You'll receive a notification with meeting details</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center mb-4">
                                    <div class="icon-box">
                                        <i class="fas fa-video text-primary fa-3x mb-3"></i>
                                        <h5>3. Join</h5>
                                        <p class="text-muted">Click "Join Meeting" button when it's time</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center mb-4">
                                    <div class="icon-box">
                                        <i class="fas fa-book-open text-primary fa-3x mb-3"></i>
                                        <h5>4. Recite</h5>
                                        <p class="text-muted">Complete your Tasmik with your teacher's guidance</p>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-lightbulb mr-2"></i> <strong>Tips for Jitsi Meetings:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Jitsi works directly in your browser - no downloads needed</li>
                                    <li>Allow browser permissions for camera and microphone access</li>
                                    <li>Enter your full name when joining so your teacher can identify you</li>
                                    <li>Use headphones for better audio quality</li>
                                    <li>Jitsi works best in Chrome or Chromium-based browsers</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .meeting-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #ccc;
        margin-right: 10px;
    }

    .meeting-indicator.active {
        background-color: #2ecc71;
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.3);
        animation: pulse 2s infinite;
    }

    .meeting-date {
        font-weight: 600;
    }

    .meeting-time {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .icon-box {
        padding: 20px;
        border-radius: 10px;
        background-color: #f8f9fa;
        height: 100%;
        transition: all 0.3s ease;
    }

    .icon-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.5);
        }

        70% {
            box-shadow: 0 0 0 6px rgba(46, 204, 113, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(46, 204, 113, 0);
        }
    }
</style>

<?php
// Helper function to display time remaining in a human-readable format
function getTimeRemaining($scheduledTime, $currentTime)
{
    $interval = $currentTime->diff($scheduledTime);

    if ($interval->days > 0) {
        return 'In ' . $interval->days . ' days';
    } elseif ($interval->h > 0) {
        return 'In ' . $interval->h . ' hours';
    } else {
        return 'In ' . $interval->i . ' minutes';
    }
}
?>

<?php include '../include/footer.php'; ?>