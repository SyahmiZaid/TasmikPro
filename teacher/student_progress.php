<?php
$pageTitle = "Student Tasmik Progress";
$breadcrumb = "Pages / Student Tasmik Progress";
include '../include/header.php';

// Database connection
require_once '../database/db_connection.php';

// Juzuk page ranges (reused from student page)
$juzukPageRanges = [
    1 => ['start' => 1, 'end' => 21],
    2 => ['start' => 22, 'end' => 41],
    3 => ['start' => 42, 'end' => 61],
    4 => ['start' => 62, 'end' => 81],
    5 => ['start' => 82, 'end' => 101],
    6 => ['start' => 102, 'end' => 121],
    7 => ['start' => 122, 'end' => 141],
    8 => ['start' => 142, 'end' => 161],
    9 => ['start' => 162, 'end' => 181],
    10 => ['start' => 182, 'end' => 201],
    11 => ['start' => 202, 'end' => 221],
    12 => ['start' => 222, 'end' => 241],
    13 => ['start' => 242, 'end' => 261],
    14 => ['start' => 262, 'end' => 281],
    15 => ['start' => 282, 'end' => 301],
    16 => ['start' => 302, 'end' => 321],
    17 => ['start' => 322, 'end' => 341],
    18 => ['start' => 342, 'end' => 361],
    19 => ['start' => 362, 'end' => 381],
    20 => ['start' => 382, 'end' => 401],
    21 => ['start' => 402, 'end' => 421],
    22 => ['start' => 422, 'end' => 441],
    23 => ['start' => 442, 'end' => 461],
    24 => ['start' => 462, 'end' => 481],
    25 => ['start' => 482, 'end' => 501],
    26 => ['start' => 502, 'end' => 521],
    27 => ['start' => 522, 'end' => 541],
    28 => ['start' => 542, 'end' => 561],
    29 => ['start' => 562, 'end' => 581],
    30 => ['start' => 582, 'end' => 604]
];

// Function to get KPI target based on form
function getJuzukKPI($form)
{
    $formNumber = (int)str_replace('Form ', '', $form);
    $kpiTargets = [
        1 => 6,   // Form 1: 6 juzu'
        2 => 12,  // Form 2: 12 juzu'
        3 => 18,  // Form 3: 18 juzu'
        4 => 24,  // Form 4: 24 juzu'
        5 => 30   // Form 5: 30 juzu'
    ];
    return isset($kpiTargets[$formNumber]) ? $kpiTargets[$formNumber] : 0;
}

// Initialize variables
$teacherId = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
$halaqahId = null;
$halaqahName = null;
$students = [];
$classKpiAchievement = 0;
$classAveragePages = 0;
$formDistribution = [];
$pendingTasmikCount = 0;
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$classMonthlyProgress = array_fill_keys(range(1, 12), 0);
// Initialize yearly progress data - NEW ADDITION
$currentYear = date('Y');
$yearlyProgress = array_fill_keys(range($currentYear - 4, $currentYear), 0);
// Initialize live session stats
$liveSessionStats = [
    'live' => 0,
    'total' => 0,
    'percentage' => 0
];
// Initialize active students variables
$activeStudentsCount = 0;
$activeStudentsPercentage = 0;

// Get teacher's halaqah
try {
    if ($teacherId) {
        $stmt = $conn->prepare("
            SELECT t.teacherid, h.halaqahid, h.halaqahname 
            FROM teacher t
            JOIN halaqah h ON t.halaqahid = h.halaqahid
            JOIN users u ON t.userid = u.userid
            WHERE t.userid = ?
        ");
        $stmt->bind_param("s", $teacherId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $teacherData = $result->fetch_assoc();
            $halaqahId = $teacherData['halaqahid'];
            $halaqahName = $teacherData['halaqahname'];
            $teacherId = $teacherData['teacherid']; // Get the actual teacherid value
        }
    }

    // Get all students in teacher's halaqah
    if ($halaqahId) {
        $stmt = $conn->prepare("
            SELECT s.studentid, CONCAT(u.firstname, ' ', u.lastname) AS full_name, 
                   s.form, s.class, s.gender, u.profile_image
            FROM student s
            JOIN users u ON s.userid = u.userid
            WHERE s.halaqahid = ?
            ORDER BY s.form, s.class, u.firstname
        ");
        $stmt->bind_param("s", $halaqahId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $students[] = $row;

            // Count students by form
            $formKey = $row['form'];
            if (!isset($formDistribution[$formKey])) {
                $formDistribution[$formKey] = 1;
            } else {
                $formDistribution[$formKey]++;
            }
        }
    }

    // Get class-level statistics
    if ($halaqahId) {
        // Get pending tasmik submissions count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_count
            FROM tasmik t
            JOIN student s ON t.studentid = s.studentid
            WHERE s.halaqahid = ? AND t.status = 'pending'
        ");
        $stmt->bind_param("s", $halaqahId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $pendingTasmikCount = $row['pending_count'];
        }

        // Get active students count (students who submitted tasmik in the last 30 days)
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT s.studentid) as active_count
            FROM student s
            JOIN tasmik t ON s.studentid = t.studentid
            WHERE s.halaqahid = ? 
            AND t.submitted_at >= ?
        ");
        $stmt->bind_param("ss", $halaqahId, $thirtyDaysAgo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $activeStudentsCount = $row['active_count'];
        }

        // Calculate active students percentage
        $totalStudents = count($students);
        $activeStudentsPercentage = $totalStudents > 0 ? round(($activeStudentsCount / $totalStudents) * 100) : 0;

        // Get class monthly progress
        $currentYear = date('Y');
        $stmt = $conn->prepare("
            SELECT MONTH(t.tasmik_date) as month, 
                   SUM(t.end_page - t.start_page + 1) as pages
            FROM tasmik t
            JOIN student s ON t.studentid = s.studentid
            WHERE s.halaqahid = ? AND YEAR(t.tasmik_date) = ? AND t.status = 'accepted'
            GROUP BY MONTH(t.tasmik_date)
        ");
        $stmt->bind_param("si", $halaqahId, $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $classMonthlyProgress[$row['month']] = (int)$row['pages'];
        }

        // Get yearly progress data - NEW ADDITION
        $startYear = $currentYear - 4;
        $stmt = $conn->prepare("
            SELECT YEAR(t.tasmik_date) as year, 
                   SUM(t.end_page - t.start_page + 1) as pages
            FROM tasmik t
            JOIN student s ON t.studentid = s.studentid
            WHERE s.halaqahid = ? 
            AND YEAR(t.tasmik_date) BETWEEN ? AND ?
            AND t.status = 'accepted'
            GROUP BY YEAR(t.tasmik_date)
        ");
        $stmt->bind_param("sii", $halaqahId, $startYear, $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $yearlyProgress[$row['year']] = (int)$row['pages'];
        }

        // Get live conference statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN t.live_conference = 'yes' THEN 1 END) as live_count,
                COUNT(*) as total_count
            FROM tasmik t
            JOIN student s ON t.studentid = s.studentid
            WHERE s.halaqahid = ?
        ");
        $stmt->bind_param("s", $halaqahId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $liveSessionStats['live'] = (int)$row['live_count'];
            $liveSessionStats['total'] = (int)$row['total_count'];
            $liveSessionStats['percentage'] = $liveSessionStats['total'] > 0
                ? round(($liveSessionStats['live'] / $liveSessionStats['total']) * 100)
                : 0;
        }
    }

    // Calculate progress statistics for each student
    if (!empty($students)) {
        $totalKpiAchievement = 0;
        $totalPagesRead = 0;

        foreach ($students as $index => $student) {
            $studentId = $student['studentid'];

            // Get student's total pages and juzuk progress
            $stmt = $conn->prepare("
                SELECT 
                    SUM(end_page - start_page + 1) as total_pages,
                    COUNT(DISTINCT juzuk) as juzuk_count
                FROM tasmik
                WHERE studentid = ? AND status = 'accepted'
            ");
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $progressData = $result->fetch_assoc();

            $totalPages = $progressData['total_pages'] ?: 0;
            $juzukCount = $progressData['juzuk_count'] ?: 0;

            // Calculate KPI achievement
            $kpiTarget = getJuzukKPI($student['form']);
            $kpiPercentage = $kpiTarget > 0 ? min(100, round(($juzukCount / $kpiTarget) * 100)) : 0;

            // Add to class totals
            $totalKpiAchievement += $kpiPercentage;
            $totalPagesRead += $totalPages;

            // Store progress data
            $students[$index]['progress'] = [
                'total_pages' => $totalPages,
                'juzuk_count' => $juzukCount,
                'kpi_target' => $kpiTarget,
                'kpi_percentage' => $kpiPercentage
            ];
        }

        // Calculate class average stats
        $studentCount = count($students);
        if ($studentCount > 0) {
            $classKpiAchievement = round($totalKpiAchievement / $studentCount);
            $classAveragePages = round($totalPagesRead / $studentCount);
        }
    }
} catch (Exception $e) {
    // Handle error
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}

// Sort students by KPI achievement (descending)
usort($students, function ($a, $b) {
    return $b['progress']['kpi_percentage'] - $a['progress']['kpi_percentage'];
});
?>

<div class="container">
    <div class="page-inner">
        <!-- Page Header with Stats -->
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row mb-3">
            <div>
                <h2 class="fw-bold pb-2 mb-1"><?= $pageTitle ?></h2>
            </div>
        </div>

        <?php if (empty($halaqahId)): ?>
            <div class="alert alert-warning">
                <div class="d-flex">
                    <div>
                        <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                    </div>
                    <div>
                        <h4 class="alert-heading">Not Assigned to Halaqah</h4>
                        <p class="mb-0">You are not assigned to any halaqah. Please contact the administrator to get assigned to a halaqah.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <!-- Halaqah Overview Stats - Updated Card Design -->
            <div class="row mb-4">
                <!-- KPI Achievement Card -->
                <div class="col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">KPI Achievement</p>
                                        <h4 class="card-title"><?= $classKpiAchievement ?>%</h4>
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $classKpiAchievement ?>%" aria-valuenow="<?= $classKpiAchievement ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="text-muted mt-1 mb-0"><small>Class Average</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pages Read Card -->
                <div class="col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-success bubble-shadow-small">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Pages Read</p>
                                        <h4 class="card-title"><?= $classAveragePages ?></h4>
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $classAveragePages / 10) ?>%" aria-valuenow="<?= $classAveragePages ?>" aria-valuemin="0" aria-valuemax="604"></div>
                                        </div>
                                        <p class="text-muted mt-1 mb-0"><small>Average per Student</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Students Card (replacing Pending Reviews) -->
                <div class="col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-warning bubble-shadow-small">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Active Students</p>
                                        <h4 class="card-title"><?= $activeStudentsCount ?></h4>
                                        <div class="progress" style="height: 4px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $activeStudentsPercentage ?>%" aria-valuenow="<?= $activeStudentsPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="text-muted mt-1 mb-0"><small><?= $activeStudentsPercentage ?>% active in last 30 days</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Sessions Card -->
                <div class="col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-info bubble-shadow-small">
                                        <i class="fas fa-video"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Live Sessions</p>
                                        <h4 class="card-title"><?= $liveSessionStats['percentage'] ?>%</h4>
                                        <div class="small" style="max-height: 40px; overflow-y: auto;">
                                            <div class="d-flex justify-content-between">
                                                <span>Live:</span>
                                                <span class="font-weight-bold"><?= $liveSessionStats['live'] ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Total:</span>
                                                <span class="font-weight-bold"><?= $liveSessionStats['total'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasmik Status Dashboard -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex justify-content-between align-items-center">
                                <h4>Tasmik Status Overview</h4>
                                <?php if ($pendingTasmikCount > 0): ?>
                                    <div class="ml-md-auto py-2 py-md-0" style="margin-bottom: 10px;">
                                        <a href="manage_tasmik.php" class="btn btn-warning btn-round">
                                            <i class="fas fa-clock mr-2"></i> <?= $pendingTasmikCount ?> Pending Reviews
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="row">
                                <?php
                                // Get tasmik status counts
                                $tasmikStatusCounts = [];
                                $statusColors = [
                                    'pending' => 'warning',
                                    'accepted' => 'success',
                                    'repeated' => 'danger'
                                ];

                                if ($halaqahId) {
                                    $stmt = $conn->prepare("
                                        SELECT t.status, COUNT(*) as count
                                        FROM tasmik t
                                        JOIN student s ON t.studentid = s.studentid
                                        WHERE s.halaqahid = ?
                                        GROUP BY t.status
                                    ");
                                    $stmt->bind_param("s", $halaqahId);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($row = $result->fetch_assoc()) {
                                        $tasmikStatusCounts[$row['status']] = $row['count'];
                                    }
                                }

                                foreach ($statusColors as $status => $color) {
                                    $count = $tasmikStatusCounts[$status] ?? 0;
                                ?>
                                    <div class="col-md-4">
                                        <div class="status-card bg-light-<?= $color ?> p-3 rounded">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h4 class="mb-0 text-<?= $color ?> fw-bold"><?= ucfirst($status) ?></h4>
                                                    <p class="mb-0 text-muted">Tasmik Sessions</p>
                                                </div>
                                                <div class="status-count bg-<?= $color ?> text-white rounded-circle">
                                                    <?= $count ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="card-title">Progress Overview</div>
                                    <div class="card-category">Pages read in <?= date('Y') ?></div>
                                </div>
                                <div class="form-group mb-0">
                                    <select id="progressViewSelect" class="form-control form-control-sm">
                                        <option value="monthly" selected>Monthly View</option>
                                        <option value="yearly">Yearly View</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="classMonthlyProgress"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Individual Student Progress</div>
                            <div class="card-category">KPI achievement by student</div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="studentComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tasmik Sessions -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Recent Tasmik Sessions</div>
                            <div class="card-category">Last 7 tasmik sessions across all students</div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Date</th>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Live Session</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($halaqahId) {
                                            $stmt = $conn->prepare("
                                                SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) AS student_name, 
                                                       u.profile_image, s.gender
                                                FROM tasmik t
                                                JOIN student s ON t.studentid = s.studentid
                                                JOIN users u ON s.userid = u.userid
                                                WHERE s.halaqahid = ?
                                                ORDER BY t.tasmik_date DESC, t.submitted_at DESC
                                                LIMIT 7
                                            ");
                                            $stmt->bind_param("s", $halaqahId);
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            while ($row = $result->fetch_assoc()) {
                                                $statusClass = '';
                                                switch ($row['status']) {
                                                    case 'accepted':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'repeated':
                                                        $statusClass = 'danger';
                                                        break;
                                                }

                                                // Calculate pages read
                                                $pagesRead = $row['end_page'] - $row['start_page'] + 1;
                                        ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm" style="margin-right: 10px;">
                                                                <?php if (!empty($row['profile_image'])): ?>
                                                                    <img src="<?= htmlspecialchars($row['profile_image']) ?>"
                                                                        alt="<?= htmlspecialchars(ucwords($row['student_name'])) ?>"
                                                                        class="avatar-img rounded-circle border">
                                                                <?php else: ?>
                                                                    <span class="avatar-text rounded-circle bg-<?= getGenderColor($row['gender']) ?>">
                                                                        <?= strtoupper(substr($row['student_name'], 0, 1)) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="ml-2">
                                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars(ucwords($row['student_name'])) ?></h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= date('d M Y', strtotime($row['tasmik_date'])) ?></td>
                                                    <td><span class="badge bg-primary"><?= $row['juzuk'] ?></span></td>
                                                    <td><?= $pagesRead ?> <small class="text-muted">(<?= $row['start_page'] ?>-<?= $row['end_page'] ?>)</small></td>
                                                    <td>
                                                        <?php if ($row['live_conference'] == 'yes'): ?>
                                                            <span class="badge bg-info"><i class="fas fa-video mr-1"></i> Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge bg-<?= $statusClass ?>"><?= ucfirst($row['status']) ?></span></td>
                                                </tr>
                                        <?php
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Scheduled Zoom Meetings -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Upcoming Zoom Sessions</div>
                            <div class="card-category">Next scheduled Tasmik sessions</div>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get upcoming Zoom meetings with more details
                            $upcomingMeetings = [];

                            if ($teacherId) {
                                $stmt = $conn->prepare("
                                    SELECT zm.*, 
                                        CONCAT(u.firstname, ' ', u.lastname) AS student_name,
                                        u.profile_image, s.gender, s.form, s.class,
                                        t.juzuk, t.start_page, t.end_page
                                    FROM zoom_meetings zm
                                    JOIN student s ON zm.studentid = s.studentid
                                    JOIN users u ON s.userid = u.userid
                                    LEFT JOIN tasmik t ON zm.tasmikid = t.tasmikid
                                    WHERE zm.teacherid = ? AND zm.scheduled_at > NOW()
                                    ORDER BY zm.scheduled_at ASC
                                    LIMIT 5
                                ");
                                $stmt->bind_param("s", $teacherId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                while ($row = $result->fetch_assoc()) {
                                    $upcomingMeetings[] = $row;
                                }
                            }

                            if (empty($upcomingMeetings)):
                            ?>
                                <div class="text-center py-3">
                                    <div class="mb-3">
                                        <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                    </div>
                                    <h5>No upcoming Zoom sessions</h5>
                                    <p class="text-muted">Schedule new sessions with your students</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%">Student</th>
                                                <th style="width: 15%">Date & Time</th>
                                                <th style="width: 15%">Form/Class</th>
                                                <th style="width: 15%">Tasmik Progress</th>
                                                <th style="width: 15%">Meeting ID</th>
                                                <th style="width: 15%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingMeetings as $meeting): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm" style="margin-right: 10px;">
                                                                <?php if (!empty($meeting['profile_image'])): ?>
                                                                    <img src="<?= htmlspecialchars($meeting['profile_image']) ?>"
                                                                        alt="<?= htmlspecialchars(ucwords($meeting['student_name'])) ?>"
                                                                        class="avatar-img rounded-circle border">
                                                                <?php else: ?>
                                                                    <span class="avatar-text rounded-circle bg-<?= getGenderColor($meeting['gender']) ?>">
                                                                        <?= strtoupper(substr($meeting['student_name'], 0, 1)) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="ml-2">
                                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars(ucwords($meeting['student_name'])) ?></h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="meeting-time">
                                                            <div class="date"><?= date('d M Y', strtotime($meeting['scheduled_at'])) ?></div>
                                                            <div class="time fw-bold"><?= date('h:i A', strtotime($meeting['scheduled_at'])) ?></div>
                                                            <div class="time-left text-muted">
                                                                <?php
                                                                $timeLeft = strtotime($meeting['scheduled_at']) - time();
                                                                $days = floor($timeLeft / (60 * 60 * 24));
                                                                $hours = floor(($timeLeft % (60 * 60 * 24)) / (60 * 60));

                                                                if ($days > 0) {
                                                                    echo "in {$days}d {$hours}h";
                                                                } else {
                                                                    echo "in {$hours}h";
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="student-class">
                                                            <span class="form-badge form-badge-sm me-2">
                                                                <span class="form-badge-label"><?= htmlspecialchars($meeting['form'] ?? 'N/A') ?></span>
                                                            </span>
                                                            <div class="text-muted small">Class <?= htmlspecialchars($meeting['class'] ?? 'N/A') ?></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($meeting['juzuk'])): ?>
                                                            <div class="tasmik-progress">
                                                                <div class="juzuk-badge badge bg-primary">Juzuk <?= $meeting['juzuk'] ?></div>
                                                                <div class="text-muted small">
                                                                    Pages <?= $meeting['start_page'] ?? '—' ?> - <?= $meeting['end_page'] ?? '—' ?>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not specified</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="meeting-id">
                                                            <span class="badge bg-secondary"><?= $meeting['meeting_id'] ?></span>
                                                            <!-- <button class="btn btn-sm btn-light border-0 copy-btn"
                                                                data-clipboard-text="<?= $meeting['meeting_id'] ?>"
                                                                title="Copy meeting ID">
                                                                <i class="fas fa-copy text-muted"></i>
                                                            </button> -->
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex">
                                                            <a href="../live-conference/live_conference.php?room=<?php echo urlencode($meeting['meeting_link']); ?>" class="btn btn-primary btn-sm">
                                                                Join
                                                            </a>
                                                            <!-- <a href="reschedule_meeting.php?id=<?= $meeting['id'] ?>"
                                                                class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-edit"></i>
                                                            </a> -->
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 text-center">
                                    <a href="../live-conference/live_conference_schedule.php" class="btn btn-primary btn-round">
                                        <i class="fas fa-plus mr-2"></i> Schedule New Session
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Progress Board -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center flex-nowrap">
                                    <h4 class="card-title mb-0 mr-3 text-nowrap">Student Progress</h4>
                                    <div class="input-group" style="width: auto; min-width: 200px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-transparent border-right-0">
                                                <i class="fa fa-search"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="studentSearch" class="form-control border-left-0" placeholder="Search student...">
                                    </div>
                                </div>
                                <a href="print_progress_report.php?halaqah=<?= $halaqahId ?>"
                                    class="btn btn-primary btn-round print-button ml-3" target="_blank">
                                    <i class="fas fa-print mr-2" style="margin-right: 5px;"></i>Print Report
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped" id="studentTable">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>KPI Target</th>
                                            <th class="w-15">Progress</th>
                                            <th>Status</th>
                                            <th>Pages</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr class="<?= getStudentRowClass($student['progress']['kpi_percentage']) ?>">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-md" style="margin-right: 10px;">
                                                            <?php if (!empty($student['profile_image'])): ?>
                                                                <img src="<?= htmlspecialchars($student['profile_image']) ?>"
                                                                    alt="<?= htmlspecialchars(ucwords($student['full_name'])) ?>"
                                                                    class="avatar-img rounded-circle border">
                                                            <?php else: ?>
                                                                <span class="avatar-text rounded-circle bg-<?= getGenderColor($student['gender']) ?>">
                                                                    <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ml-3">
                                                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars(ucwords($student['full_name'])) ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="target-display">
                                                        <span class="target-number"><?= $student['progress']['kpi_target'] ?></span>
                                                        <span class="target-label">Juzuk</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="fw-bold"><?= $student['progress']['kpi_percentage'] ?>%</span>
                                                            <span class="text-muted"><?= $student['progress']['juzuk_count'] ?>/<?= $student['progress']['kpi_target'] ?> Juzuk</span>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-<?= progressColorClass($student['progress']['kpi_percentage']) ?>"
                                                                role="progressbar"
                                                                style="width: <?= $student['progress']['kpi_percentage'] ?>%"
                                                                aria-valuenow="<?= $student['progress']['kpi_percentage'] ?>"
                                                                aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    // More granular progress status categories
                                                    if ($student['progress']['kpi_percentage'] < 15): ?>
                                                        <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Critical attention needed</small>
                                                    <?php elseif ($student['progress']['kpi_percentage'] < 30): ?>
                                                        <small class="text-danger"><i class="fas fa-exclamation-circle"></i> Below target</small>
                                                    <?php elseif ($student['progress']['kpi_percentage'] < 50): ?>
                                                        <small class="text-warning"><i class="fas fa-exclamation"></i> Needs improvement</small>
                                                    <?php elseif ($student['progress']['kpi_percentage'] < 70): ?>
                                                        <small class="text-warning"><i class="fas fa-info-circle"></i> On track</small>
                                                    <?php elseif ($student['progress']['kpi_percentage'] < 90): ?>
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> Good progress</small>
                                                    <?php else: ?>
                                                        <small class="text-primary"><i class="fas fa-medal"></i> Outstanding achievement</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="pages-badge">
                                                        <i class="fas fa-book-open text-muted mr-1" style="margin-right: 5px;"></i> <?= $student['progress']['total_pages'] ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="detail_progress.php?id=<?= htmlspecialchars($student['studentid']) ?>"
                                                        class="btn btn-primary btn-round btn-sm" data-toggle="tooltip" title="View Details">
                                                        <i class="fa fa-external-link-alt mr-1" style="margin-right: 5px;"></i> View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function progressColorClass($percentage)
{
    if ($percentage >= 90) return 'success';
    if ($percentage >= 70) return 'info';
    if ($percentage >= 50) return 'primary';
    if ($percentage >= 25) return 'warning';
    return 'danger';
}

function getStudentRowClass($kpiPercentage)
{
    if ($kpiPercentage < 30) return 'table-danger-light';
    if ($kpiPercentage < 50) return 'table-warning-light';
    if ($kpiPercentage >= 90) return 'table-success-light';
    return '';
}

function getGenderColor($gender)
{
    switch (strtolower($gender)) {
        case 'male':
            return 'primary';
        case 'female':
            return 'info';
        default:
            return 'secondary';
    }
}
?>

<style>
    /* Core style improvements */
    .fw-bold {
        font-weight: 600;
    }

    .w-20 {
        width: 20%;
    }

    .me-2 {
        margin-right: 0.5rem;
    }

    .mb-1 {
        margin-bottom: 0.25rem;
    }

    .mb-2 {
        margin-bottom: 0.5rem;
    }

    .text-capitalize {
        text-transform: capitalize;
    }

    /* Card Stats enhancements */
    .card-stats {
        box-shadow: 0 0.75rem 1.5rem rgba(18, 38, 63, 0.03);
        border: none;
    }

    .card-stats .card-title {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }

    .card-stats-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        height: 100%;
    }

    .card-stats-item .icon-big {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        background-color: rgba(0, 0, 0, 0.05);
        margin-right: 1rem;
    }

    .card-stats-info {
        flex: 1;
    }

    .card-stats-info h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        line-height: 1;
    }

    /* Icon colors and bubble shadow */
    .icon-primary {
        color: #1572E8;
    }

    .icon-success {
        color: #31CE36;
    }

    .icon-info {
        color: #48ABF7;
    }

    .icon-warning {
        color: #FFAD46;
    }

    .bubble-shadow-small {
        position: relative;
    }

    .bubble-shadow-small:before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        border-radius: 50%;
        background-color: currentColor;
        opacity: 0.15;
        width: 45px;
        height: 45px;
        margin: auto;
        z-index: -1;
    }

    /* Progress view select */
    #progressViewSelect {
        width: auto;
        min-width: 130px;
        background-color: #f8f9fa;
        border-color: #e9ecef;
        box-shadow: none;
    }

    /* Form badges for student distribution */
    .form-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem;
        border-radius: 0.5rem;
        background-color: #f1f1f1;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .form-badge-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
    }

    .form-badge-label {
        font-weight: 600;
        color: #495057;
    }

    .form-badge-count {
        margin-left: 0.5rem;
        background-color: #1572E8;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }

    /* Avatar enhancements */
    .avatar {
        position: relative;
        width: 40px;
        height: 40px;
        overflow: hidden;
    }

    .avatar-md {
        width: 50px;
        height: 50px;
    }

    .avatar-sm {
        width: 35px;
        height: 35px;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-text {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
    }

    /* Table enhancements */
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .table-success-light {
        background-color: rgba(54, 220, 118, 0.1);
    }

    .table-warning-light {
        background-color: rgba(255, 173, 70, 0.1);
    }

    .table-danger-light {
        background-color: rgba(242, 89, 97, 0.1);
    }

    table.table td,
    table.table th {
        padding: 0.75rem 1rem;
        vertical-align: middle;
    }

    /* Target display */
    .target-display {
        display: flex;
        align-items: center;
    }

    .target-number {
        font-size: 1.2rem;
        font-weight: 600;
        margin-right: 0.5rem;
    }

    .target-label {
        color: #6c757d;
        font-size: 0.9rem;
    }

    /* Pages badge */
    .pages-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background-color: #f8f9fa;
        border-radius: 2rem;
        font-weight: 600;
    }

    /* Progress indicator */
    .progress-indicator {
        margin-top: 0.3rem;
        font-size: 0.8rem;
    }

    /* Chart container */
    .chart-container {
        position: relative;
        min-height: 250px;
        width: 100%;
    }

    .form-name-display {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        background-color: #e3f2fd;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        color: #1572E8;
        margin-bottom: 1rem;
    }

    .class-distribution {
        margin-top: 0.5rem;
    }

    .print-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        height: 36px;
        padding: 0 1rem;
    }

    .btn-round {
        line-height: 1.25;
    }

    /* New styles for Tasmik Status Cards */
    .bg-light-success {
        background-color: rgba(54, 220, 118, 0.15);
    }

    .bg-light-warning {
        background-color: rgba(255, 173, 70, 0.15);
    }

    .bg-light-danger {
        background-color: rgba(242, 89, 97, 0.15);
    }

    .status-card {
        transition: all 0.3s ease;
    }

    .status-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .status-count {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    /* Badge styles */
    .badge {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 600;
        border-radius: 0.25rem;
        display: inline-block;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
    }

    .bg-primary {
        background-color: #1572E8 !important;
        color: #fff;
    }

    .bg-success {
        background-color: #31CE36 !important;
        color: #fff;
    }

    .bg-warning {
        background-color: #FFAD46 !important;
        color: #fff;
    }

    .bg-danger {
        background-color: #F25961 !important;
        color: #fff;
    }

    .bg-info {
        background-color: #48ABF7 !important;
        color: #fff;
    }

    .bg-secondary {
        background-color: #6c757d !important;
        color: #fff;
    }
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        if (typeof $().tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Student search functionality
        document.getElementById('studentSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('studentTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const studentCell = rows[i].getElementsByTagName('td')[0];
                const studentName = studentCell.textContent || studentCell.innerText;

                if (studentName.toLowerCase().indexOf(searchValue) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });

        // Progress Chart
        var classMonthlyCtx = document.getElementById('classMonthlyProgress').getContext('2d');
        var progressLabels = {
            monthly: <?= json_encode($monthNames) ?>,
            yearly: <?= json_encode(array_keys($yearlyProgress)) ?>
        };

        var progressData = {
            monthly: <?= json_encode(array_values($classMonthlyProgress)) ?>,
            yearly: <?= json_encode(array_values($yearlyProgress)) ?>
        };

        var currentView = 'monthly';
        var classProgressChart = new Chart(classMonthlyCtx, {
            type: 'bar',
            data: {
                labels: progressLabels.monthly,
                datasets: [{
                    label: 'Pages',
                    data: progressData.monthly,
                    backgroundColor: '#1572E8',
                    borderRadius: 5,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false,
                            drawOnChartArea: false,
                            drawTicks: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Handle view toggle with dropdown
        document.getElementById('progressViewSelect').addEventListener('change', function() {
            currentView = this.value;
            updateProgressChart();
        });

        function updateProgressChart() {
            classProgressChart.data.labels = progressLabels[currentView];
            classProgressChart.data.datasets[0].data = progressData[currentView];
            classProgressChart.update();
        }

        // Initialize clipboard for meeting ID copy buttons
        if (typeof ClipboardJS !== 'undefined') {
            new ClipboardJS('.copy-btn');

            // Add tooltip feedback when copied
            $('.copy-btn').on('click', function() {
                const $this = $(this);
                $this.attr('data-original-title', 'Copied!').tooltip('show');

                setTimeout(function() {
                    $this.attr('data-original-title', 'Copy meeting ID').tooltip('hide');
                }, 1000);
            });
        }

        // Student Comparison Chart 
        var studentNames = [];
        var studentKpiData = [];
        var studentColors = [];

        <?php
        // Get top 10 students by KPI percentage (we already sorted them earlier)
        $topStudents = array_slice($students, 0, 10);
        foreach ($topStudents as $index => $student):
            // Generate a color gradient from green (high) to red (low)
            $hue = 120 * ($student['progress']['kpi_percentage'] / 100); // 120 is green, 0 is red
        ?>
            studentNames.push('<?= addslashes(explode(' ', $student['full_name'])[0]) ?>'); // First name only
            studentKpiData.push(<?= $student['progress']['kpi_percentage'] ?>);
            studentColors.push('hsl(<?= $hue ?>, 70%, 50%)');
        <?php endforeach; ?>

        var studentComparisonCtx = document.getElementById('studentComparisonChart').getContext('2d');
        var studentComparisonChart = new Chart(studentComparisonCtx, {
            type: 'bar',
            data: {
                labels: studentNames,
                datasets: [{
                    label: 'KPI Achievement (%)',
                    data: studentKpiData,
                    backgroundColor: studentColors,
                    borderRadius: 5,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false
                        }
                    },
                    y: {
                        grid: {
                            drawBorder: false,
                            display: false,
                            drawOnChartArea: false,
                            drawTicks: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php include '../include/footer.php'; ?>