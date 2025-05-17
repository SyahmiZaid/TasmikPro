<?php
$pageTitle = "Student Tasmik Detail";
$breadcrumb = "Pages / <a href='dashboard.php'>Dashboard</a> / <a href='test.php'>Student Progress</a> / Student Detail";
include '../include/header.php';

// Database connection
require_once '../database/db_connection.php';

// Error handling - hide PHP errors in production
$debugMode = false; // Set to true to see PHP errors during development
if (!$debugMode) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Juzuk page ranges (reused from student page)
$juzukPageRanges = [
    1 => ['start' => 1, 'end' => 21],
    2 => ['start' => 22, 'end' => 41],
    3 => ['start' => 42, 'end' => 62],
    4 => ['start' => 63, 'end' => 82],
    5 => ['start' => 83, 'end' => 106],
    6 => ['start' => 107, 'end' => 128],
    7 => ['start' => 129, 'end' => 150],
    8 => ['start' => 151, 'end' => 172],
    9 => ['start' => 173, 'end' => 190],
    10 => ['start' => 191, 'end' => 208],
    11 => ['start' => 209, 'end' => 220],
    12 => ['start' => 221, 'end' => 240],
    13 => ['start' => 241, 'end' => 260],
    14 => ['start' => 261, 'end' => 280],
    15 => ['start' => 281, 'end' => 300],
    16 => ['start' => 301, 'end' => 320],
    17 => ['start' => 321, 'end' => 340],
    18 => ['start' => 341, 'end' => 360],
    19 => ['start' => 361, 'end' => 380],
    20 => ['start' => 381, 'end' => 400],
    21 => ['start' => 401, 'end' => 420],
    22 => ['start' => 421, 'end' => 440],
    23 => ['start' => 441, 'end' => 460],
    24 => ['start' => 461, 'end' => 480],
    25 => ['start' => 481, 'end' => 500],
    26 => ['start' => 501, 'end' => 520],
    27 => ['start' => 521, 'end' => 540],
    28 => ['start' => 541, 'end' => 560],
    29 => ['start' => 561, 'end' => 581],
    30 => ['start' => 582, 'end' => 604]
];

// Surah names by juzuk (simplified)
$surahByJuzuk = [
    1 => 'Al-Fatihah',
    2 => 'Al-Baqarah',
    3 => 'Al-Baqarah',
    4 => 'Ali Imran',
    5 => 'An-Nisa',
    6 => 'Al-Ma\'idah',
    7 => 'Al-An\'am',
    8 => 'Al-A\'raf',
    9 => 'Al-Anfal',
    10 => 'At-Tawbah',
    11 => 'Yunus',
    12 => 'Hud',
    13 => 'Yusuf',
    14 => 'Ibrahim',
    15 => 'Al-Hijr',
    16 => 'An-Nahl',
    17 => 'Al-Isra',
    18 => 'Al-Kahf',
    19 => 'Maryam',
    20 => 'Ta-Ha',
    21 => 'Al-Anbiya',
    22 => 'Al-Hajj',
    23 => 'Al-Mu\'minun',
    24 => 'An-Nur',
    25 => 'Al-Furqan',
    26 => 'Ash-Shu\'ara',
    27 => 'An-Naml',
    28 => 'Al-Qasas',
    29 => 'Al-Ankabut',
    30 => 'An-Naba'
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
$studentId = isset($_GET['id']) ? $_GET['id'] : null;
$teacherId = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
$studentData = null;
$studentTasmikData = [];
$juzukCompletionData = [];
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyProgress = array_fill_keys(range(1, 12), 0);
$totalJuzukCompleted = 0;
$completedPages = 0;
$totalPages = 604;
$completedPercentage = 0;
$currentJuzuk = 1;
$currentStreak = 0;
$daysRemaining = 0;
$tasmikDates = [];
$kpiTarget = 0;
$kpiPercentage = 0;
$kpiRemainingJuzuk = 0;

try {
    // Get student data
    if ($studentId) {
        $stmt = $conn->prepare("
            SELECT s.studentid, s.userid, s.form, s.class, s.gender, s.halaqahid, s.ic,
                   CONCAT(u.firstname, ' ', u.lastname) AS full_name, u.profile_image,
                   h.halaqahname,
                   CONCAT(tu.firstname, ' ', tu.lastname) AS teacher_name
            FROM student s
            JOIN users u ON s.userid = u.userid
            JOIN halaqah h ON s.halaqahid = h.halaqahid
            JOIN teacher t ON h.halaqahid = t.halaqahid
            JOIN users tu ON t.userid = tu.userid
            WHERE s.studentid = ?
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $studentData = $result->fetch_assoc();

            // Initialize juzukCompletionData array with default values for all 30 juzuk
            for ($i = 1; $i <= 30; $i++) {
                $totalPages = $juzukPageRanges[$i]['end'] - $juzukPageRanges[$i]['start'] + 1;
                $juzukCompletionData[$i] = [
                    'total' => $totalPages,
                    'completed' => 0,
                    'percentage' => 0
                ];
            }

            // Get all tasmik records for this student
            $stmt = $conn->prepare("
                SELECT tasmikid, juzuk, start_page, end_page, start_ayah, end_ayah, 
                       tasmik_date, live_conference, status
                FROM tasmik
                WHERE studentid = ?
                ORDER BY tasmik_date DESC
            ");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $tasmikResult = $stmt->get_result();

            while ($tasmik = $tasmikResult->fetch_assoc()) {
                $studentTasmikData[] = $tasmik;
            }

            // Calculate juzuk completion data
            foreach ($studentTasmikData as $tasmik) {
                if ($tasmik['status'] == 'approved') {
                    $juzuk = $tasmik['juzuk'];
                    $startPage = $tasmik['start_page'];
                    $endPage = $tasmik['end_page'];
                    $pagesRead = $endPage - $startPage + 1;

                    // Update completed pages for this juzuk
                    $juzukCompletionData[$juzuk]['completed'] += $pagesRead;

                    // Calculate total pages read
                    $completedPages += $pagesRead;

                    // Track the date for streak calculation
                    $tasmikDates[] = $tasmik['tasmik_date'];

                    // Track monthly progress
                    $month = (int)date('n', strtotime($tasmik['tasmik_date']));
                    $year = (int)date('Y', strtotime($tasmik['tasmik_date']));

                    if ($year == date('Y')) {
                        $monthlyProgress[$month] += $pagesRead;
                    }
                }
            }

            // Calculate completion percentages for each juzuk
            foreach ($juzukCompletionData as $juzuk => $data) {
                $total = $data['total'];
                $completed = $data['completed'];
                $juzukCompletionData[$juzuk]['percentage'] = ($total > 0) ? min(100, ($completed / $total) * 100) : 0;

                // Count completed juzuk (if percentage is 100%)
                if ($juzukCompletionData[$juzuk]['percentage'] >= 100) {
                    $totalJuzukCompleted++;
                }

                // Find current juzuk (the lowest incomplete one)
                if ($juzukCompletionData[$juzuk]['percentage'] < 100 && $currentJuzuk == 1) {
                    $currentJuzuk = $juzuk;
                }
            }

            // Calculate overall Quran completion percentage
            $completedPercentage = round(($completedPages / $totalPages) * 100);

            // Calculate KPI related metrics
            $kpiTarget = getJuzukKPI($studentData['form']);
            $kpiPercentage = $kpiTarget > 0 ? min(100, round(($totalJuzukCompleted / $kpiTarget) * 100)) : 0;
            $kpiRemainingJuzuk = max(0, $kpiTarget - $totalJuzukCompleted);

            // Calculate streak data (simplified implementation)
            if (!empty($tasmikDates)) {
                $lastActivity = new DateTime(max($tasmikDates));
                $today = new DateTime();
                $diff = $today->diff($lastActivity);
                $daysSinceLastActivity = $diff->days;

                // If the student has read in the last 3 days, consider it an active streak
                if ($daysSinceLastActivity <= 3) {
                    // Count consecutive days of reading (simplified)
                    $currentStreak = min(count($tasmikDates), 30); // Cap at 30 for now
                }
            }

            // Calculate days remaining in school year (simplified)
            $currentYear = date('Y');
            $schoolEndDate = new DateTime($currentYear . '-11-30'); // Assuming school year ends on Nov 30
            $today = new DateTime();

            if ($today <= $schoolEndDate) {
                $interval = $today->diff($schoolEndDate);
                $daysRemaining = $interval->days;
            } else {
                // School year already ended, set days to next year
                $nextYear = $currentYear + 1;
                $nextSchoolEndDate = new DateTime($nextYear . '-11-30');
                $interval = $today->diff($nextSchoolEndDate);
                $daysRemaining = $interval->days;
            }
        } else {
            // Student not found - set error message
            $error = "Student not found. Please check the student ID and try again.";
        }
    } else {
        // No student ID provided
        $error = "No student ID provided. Please select a student from the list.";
    }
} catch (Exception $e) {
    // Handle error
    $error = $e->getMessage();
}

// Helper functions for colors and labels
function progressColorClass($percentage)
{
    if ($percentage >= 90) return 'success';
    if ($percentage >= 70) return 'info';
    if ($percentage >= 50) return 'primary';
    if ($percentage >= 25) return 'warning';
    return 'danger';
}

function statusBadgeColor($status)
{
    switch ($status) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'repeated':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getCompletionClass($percentage)
{
    if ($percentage >= 90) return 'completed';
    if ($percentage > 0) return 'in-progress';
    return 'not-started';
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Page Title -->
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row mb-3">
            <div>
                <h2 class="text-primary fw-bold pb-2 mb-1">Student Progress Details</h2>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <div class="d-flex">
                    <div>
                        <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                    </div>
                    <div>
                        <h4 class="alert-heading">Error</h4>
                        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                        <a href="test.php" class="btn btn-sm btn-danger mt-3">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Student List
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif (!$studentData): ?>
            <div class="alert alert-warning">
                <div class="d-flex">
                    <div>
                        <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                    </div>
                    <div>
                        <h4 class="alert-heading">Student Data Not Available</h4>
                        <p class="mb-0">Unable to load student data. Please go back to the student list and try again.</p>
                        <a href="test.php" class="btn btn-warning mt-3">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Student List
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>

            <!-- Student Profile Header - Enhanced with better styling -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-profile">
                        <div class="card-header" style="background-image: url('../assets/img/TasmiPro/Course.jpg')">
                            <div class="profile-picture">
                                <div class="avatar avatar-xl">
                                    <?php if (!empty($studentData['profile_image'])): ?>
                                        <img src="<?= htmlspecialchars($studentData['profile_image']) ?>"
                                            alt="Profile"
                                            class="avatar-img rounded-circle border border-white shadow">
                                    <?php else: ?>
                                        <span class="avatar-text rounded-circle bg-white shadow border border-grey">
                                            <?= !empty($studentData['full_name']) ? strtoupper(substr($studentData['full_name'], 0, 1)) : '?' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="user-profile text-center">
                                <div class="name">
                                    <?= !empty($studentData['full_name']) ?
                                        htmlspecialchars(ucwords(strtolower($studentData['full_name']))) :
                                        'Student Name'
                                    ?>
                                </div>
                                <div class="student-details d-flex justify-content-center my-3">
                                    <div class="student-id-badge">
                                        <i class="fas fa-id-card"></i> <?= htmlspecialchars($studentData['ic'] ?? 'N/A') ?>
                                    </div>
                                    <div class="student-form-badge">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?= htmlspecialchars($studentData['form'] ?? 'N/A') ?>
                                        <?= htmlspecialchars($studentData['class'] ?? '') ?>
                                    </div>
                                    <div class="student-gender-badge">
                                        <i class="fas fa-<?= isset($studentData['gender']) && strtolower($studentData['gender']) === 'male' ? 'mars' : 'venus' ?>"></i>
                                        <?= htmlspecialchars($studentData['gender'] ?? 'N/A') ?>
                                    </div>
                                </div>

                                <div class="desc mb-4">
                                    <i class="fas fa-users mr-1"></i> <?= htmlspecialchars($studentData['halaqahname'] ?? 'N/A') ?>
                                    <div class="teacher-name mt-1">
                                        <small class="text-muted">Teacher: <?= htmlspecialchars($studentData['teacher_name'] ?? 'N/A') ?></small>
                                    </div>
                                </div>

                                <div class="progress-stats d-flex justify-content-center mb-3">
                                    <div class="stat-item px-3 border-right">
                                        <h5 class="fw-bold mb-0"><?= $totalJuzukCompleted ?>/30</h5>
                                        <small class="text-muted">Juzuk Completed</small>
                                        <div class="progress progress-sm mt-2">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: <?= ($totalJuzukCompleted / 30) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="stat-item px-3 border-right">
                                        <h5 class="fw-bold mb-0"><?= $completedPages ?>/604</h5>
                                        <small class="text-muted">Pages Read</small>
                                        <div class="progress progress-sm mt-2">
                                            <div class="progress-bar bg-primary" role="progressbar"
                                                style="width: <?= ($completedPages / 604) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="stat-item px-3">
                                        <h5 class="fw-bold mb-0"><?= $currentStreak ?></h5>
                                        <small class="text-muted">Day Streak</small>
                                        <div class="streak-flames mt-2">
                                            <?php for ($i = 0; $i < min(5, $currentStreak); $i++): ?>
                                                <i class="fas fa-fire text-warning"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center mt-3">
                                    <a href="test.php" class="btn btn-secondary btn-round mr-2">
                                        <i class="fas fa-arrow-left mr-1"></i> Back to List
                                    </a>
                                    <a href="print_student_report.php?student=<?= htmlspecialchars($studentId) ?>"
                                        class="btn btn-primary btn-round" target="_blank">
                                        <i class="fas fa-print mr-1"></i> Print Report
                                    </a>
                                    <?php if (!empty($studentTasmikData) && count(array_filter($studentTasmikData, function ($t) {
                                        return $t['status'] === 'pending';
                                    }))): ?>
                                        <a href="pending_tasmik.php?student=<?= htmlspecialchars($studentId) ?>"
                                            class="btn btn-warning btn-round ml-2">
                                            <i class="fas fa-clock mr-1"></i> Review Pending
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Summary Cards - Modernized design -->
            <div class="row mb-4">
                <!-- KPI Achievement Card -->
                <div class="col-md-4">
                    <div class="card card-stats bg-<?= progressColorClass($kpiPercentage) ?>-gradient text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary">
                                        <i class="fas fa-award fa-2x pulse-icon"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="numbers">
                                        <p class="card-category">KPI Achievement</p>
                                        <h3 class="card-title"><?= $kpiPercentage ?>%</h3>
                                        <p class="card-text">
                                            <?= $totalJuzukCompleted ?> of <?= $kpiTarget ?> Juzuk
                                            <?php if ($kpiRemainingJuzuk > 0): ?>
                                                (<?= $kpiRemainingJuzuk ?> remaining)
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="progress progress-white mt-3" style="height: 7px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $kpiPercentage ?>%"
                                    aria-valuenow="<?= $kpiPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overall Quran Progress -->
                <div class="col-md-4">
                    <div class="card card-stats bg-primary-gradient text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-info">
                                        <i class="fas fa-book-open fa-2x"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="numbers">
                                        <p class="card-category">Quran Progress</p>
                                        <h3 class="card-title"><?= $completedPercentage ?>%</h3>
                                        <p class="card-text">
                                            <?= $completedPages ?> of <?= $totalPages ?> pages
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="progress progress-white mt-3" style="height: 7px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $completedPercentage ?>%"
                                    aria-valuenow="<?= $completedPercentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline & Current Position -->
                <div class="col-md-4">
                    <div class="card card-stats bg-info-gradient text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-success">
                                        <i class="fas fa-hourglass-half fa-2x"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="numbers">
                                        <p class="card-category">Current Status</p>
                                        <h3 class="card-title">Juzuk <?= $currentJuzuk ?></h3>
                                        <p class="card-text">
                                            <?= $surahByJuzuk[$currentJuzuk] ?? 'Unknown Surah' ?>
                                            <span class="badge badge-light ml-1"><?= $daysRemaining ?> days remaining</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="progress progress-white mt-3" style="height: 7px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= ($currentJuzuk / 30) * 100 ?>%"
                                    aria-valuenow="<?= $currentJuzuk ?>" aria-valuemin="0" aria-valuemax="30"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row - Enhanced with better visualization -->
            <div class="row">
                <!-- Monthly Progress Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Monthly Progress (<?= date('Y') ?>)</h4>
                            <p class="card-category">Pages read each month</p>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyProgressChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Juzuk Completion Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"><i class="fas fa-chart-line mr-2"></i>Juzuk Completion</h4>
                            <p class="card-category">Completion percentage by Juzuk</p>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="juzukCompletionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Juzuk Grid View (Added from test.php) -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"><i class="fas fa-th-large mr-2"></i>Juzuk Overview</h4>
                            <p class="card-category">Visual representation of Juzuk completion status</p>
                        </div>
                        <div class="card-body">
                            <div class="juzuk-progress-grid">
                                <?php
                                for ($i = 1; $i <= 30; $i++):
                                    $juzData = isset($juzukCompletionData[$i]) ? $juzukCompletionData[$i] : ['percentage' => 0, 'completed' => 0, 'total' => 0];
                                    $completionClass = getCompletionClass($juzData['percentage']);
                                ?>
                                    <div class="juzuk-tile <?= $completionClass ?>">
                                        <div class="juzuk-number"><?= $i ?></div>
                                        <div class="juzuk-progress">
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: <?= $juzData['percentage'] ?>%"
                                                    aria-valuenow="<?= $juzData['percentage'] ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="juzuk-percentage"><?= round($juzData['percentage']) ?>%</span>
                                        </div>
                                        <div class="juzuk-pages"><?= $juzData['completed'] ?>/<?= $juzData['total'] ?> pages</div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout for Detailed Data -->
            <div class="row">
                <!-- Juzuk Detailed Progress -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title"><i class="fas fa-list-alt mr-2"></i>Top Juzuk Progress</h4>
                                <button class="btn btn-sm btn-primary ml-auto" data-toggle="modal" data-target="#juzukDetailsModal">
                                    <i class="fas fa-expand-arrows-alt mr-1"></i> View Full Table
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Display top 10 Juzuk by completion percentage (descending)
                                        $sortedJuzuk = [];
                                        if (!empty($juzukCompletionData)) {
                                            foreach ($juzukCompletionData as $juzuk => $data) {
                                                $sortedJuzuk[$juzuk] = $data['percentage'];
                                            }
                                            arsort($sortedJuzuk);
                                            $count = 0;
                                            foreach ($sortedJuzuk as $juzuk => $percentage):
                                                if ($count++ >= 10) break;
                                                $data = $juzukCompletionData[$juzuk];
                                                $progressClass = progressColorClass($data['percentage']);
                                        ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-light">Juzuk <?= $juzuk ?></span>
                                                    </td>
                                                    <td><?= $data['completed'] ?>/<?= $data['total'] ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress flex-grow-1" style="height: 5px;">
                                                                <div class="progress-bar bg-<?= $progressClass ?>"
                                                                    role="progressbar"
                                                                    style="width: <?= $data['percentage'] ?>%"
                                                                    aria-valuenow="<?= $data['percentage'] ?>"
                                                                    aria-valuemin="0"
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <div class="ml-2 progress-value"><?= round($data['percentage']) ?>%</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php
                                            endforeach;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Submissions -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h4 class="card-title"><i class="fas fa-history mr-2"></i>Recent Submissions</h4>
                                <button class="btn btn-sm btn-primary ml-auto" data-toggle="modal" data-target="#allSubmissionsModal">
                                    <i class="fas fa-list mr-1"></i> View All Submissions
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($studentTasmikData)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-folder-open text-muted fa-3x mb-3"></i>
                                                        <p class="mb-0">No tasmik submissions found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            // Display only the 5 most recent submissions
                                            $recentTasmik = array_slice($studentTasmikData, 0, 5);
                                            foreach ($recentTasmik as $tasmik):
                                            ?>
                                                <tr>
                                                    <td><?= date('d M Y', strtotime($tasmik['tasmik_date'])) ?></td>
                                                    <td>
                                                        <span class="badge badge-primary">Juzuk <?= $tasmik['juzuk'] ?></span>
                                                    </td>
                                                    <td><?= $tasmik['start_page'] ?>-<?= $tasmik['end_page'] ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= statusBadgeColor($tasmik['status']) ?>">
                                                            <?= ucfirst($tasmik['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($tasmik['status'] == 'pending'): ?>
                                                            <a href="review_tasmik.php?id=<?= $tasmik['tasmikid'] ?>"
                                                                class="btn btn-sm btn-primary btn-round">
                                                                <i class="fas fa-check mr-1"></i> Review
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="view_tasmik.php?id=<?= $tasmik['tasmikid'] ?>"
                                                                class="btn btn-sm btn-info btn-round">
                                                                <i class="fas fa-eye mr-1"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline and Additional Stats -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title"><i class="fas fa-stream mr-2"></i>Tasmik Timeline</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($studentTasmikData)): ?>
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-history text-muted fa-3x mb-3"></i>
                                    <p class="mb-0">No tasmik history found for this student</p>
                                </div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php
                                    $timelineItems = array_slice($studentTasmikData, 0, 6);
                                    foreach ($timelineItems as $index => $tasmik):
                                        $colorClass = statusBadgeColor($tasmik['status']);
                                        $icon = $tasmik['status'] == 'approved' ? 'check' : ($tasmik['status'] == 'pending' ? 'clock' : 'times');
                                    ?>
                                        <div class="timeline-item">
                                            <div class="timeline-badge bg-<?= $colorClass ?>">
                                                <i class="fas fa-<?= $icon ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h6 class="text-<?= $colorClass ?> mb-1">
                                                    Juzuk <?= $tasmik['juzuk'] ?> (Pages <?= $tasmik['start_page'] ?>-<?= $tasmik['end_page'] ?>)
                                                </h6>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted">
                                                        <i class="far fa-calendar-alt mr-1"></i>
                                                        <?= date('d M Y', strtotime($tasmik['tasmik_date'])) ?>
                                                    </span>
                                                    <span class="badge badge-<?= $colorClass ?>">
                                                        <?= ucfirst($tasmik['status']) ?>
                                                    </span>
                                                </div>
                                                <p class="mt-2 mb-0">
                                                    Ayat <?= $tasmik['start_ayah'] ?>-<?= $tasmik['end_ayah'] ?> •
                                                    <span class="badge badge-<?= $tasmik['live_conference'] == 'yes' ? 'info' : 'secondary' ?>">
                                                        <i class="fas <?= $tasmik['live_conference'] == 'yes' ? 'fa-video' : 'fa-microphone' ?> mr-1"></i>
                                                        <?= $tasmik['live_conference'] == 'yes' ? 'Live Conference' : 'Recorded' ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <!-- Juzuk Details Modal -->
            <div class="modal fade" id="juzukDetailsModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Complete Juzuk Progress</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Completed</th>
                                            <th>Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 1; $i <= 30; $i++): ?>
                                            <?php
                                            $data = isset($juzukCompletionData[$i]) ? $juzukCompletionData[$i] : ['percentage' => 0, 'completed' => 0, 'total' => 0];
                                            $progressClass = progressColorClass($data['percentage']);
                                            ?>
                                            <tr>
                                                <td><?= $i ?></td>
                                                <td><?= $juzukPageRanges[$i]['start'] ?>-<?= $juzukPageRanges[$i]['end'] ?></td>
                                                <td><?= $data['completed'] ?>/<?= $data['total'] ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1" style="height: 7px;">
                                                            <div class="progress-bar bg-<?= $progressClass ?>"
                                                                role="progressbar"
                                                                style="width: <?= $data['percentage'] ?>%"
                                                                aria-valuenow="<?= $data['percentage'] ?>"
                                                                aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <div class="ml-2"><?= round($data['percentage']) ?>%</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Submissions Modal -->
            <div class="modal fade" id="allSubmissionsModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">All Tasmik Submissions</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Juzuk</th>
                                            <th>Pages</th>
                                            <th>Ayahs</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($studentTasmikData)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No tasmik submissions found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($studentTasmikData as $tasmik): ?>
                                                <tr>
                                                    <td><?= date('d M Y', strtotime($tasmik['tasmik_date'])) ?></td>
                                                    <td><?= $tasmik['juzuk'] ?></td>
                                                    <td><?= $tasmik['start_page'] ?>-<?= $tasmik['end_page'] ?></td>
                                                    <td><?= $tasmik['start_ayah'] ?>-<?= $tasmik['end_ayah'] ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $tasmik['live_conference'] == 'yes' ? 'info' : 'secondary' ?>">
                                                            <?= $tasmik['live_conference'] == 'yes' ? 'Live Conference' : 'Recorded' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= statusBadgeColor($tasmik['status']) ?>">
                                                            <?= ucfirst($tasmik['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($tasmik['status'] == 'pending'): ?>
                                                            <a href="review_tasmik.php?id=<?= $tasmik['tasmikid'] ?>"
                                                                class="btn btn-sm btn-primary">
                                                                Review
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="view_tasmik.php?id=<?= $tasmik['tasmikid'] ?>"
                                                                class="btn btn-sm btn-info">
                                                                View
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<style>
    /* Enhanced Card gradients */
    .bg-primary-gradient {
        background: linear-gradient(45deg, #1572E8, #5AB1EF);
    }

    .bg-success-gradient {
        background: linear-gradient(45deg, #31CE36, #63EC74);
    }

    .bg-info-gradient {
        background: linear-gradient(45deg, #48ABF7, #0F96F7);
    }

    .bg-warning-gradient {
        background: linear-gradient(45deg, #FFAD46, #F3C74D);
    }

    .bg-danger-gradient {
        background: linear-gradient(45deg, #F25961, #FF8A88);
    }

    /* Profile styling */
    .card-profile .card-header {
        height: 80px;
        /* Reduced from 100px */
        background-size: cover;
        background-position: center;
    }

    .card-profile .profile-picture {
        text-align: center;
        position: relative;
        margin-top: -50px;
        /* Adjusted from -60px to reduce space */
    }

    .card-profile .card-body {
        padding-top: 12px;
        /* Reduced top padding */
    }

    .avatar-xl {
        width: 90px;
        /* Increased size slightly */
        height: 90px;
    }

    .avatar-xl .avatar-text {
        font-size: 36px;
    }

    .avatar-text {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }

    .avatar .avatar-text {
        width: 100%;
        height: 100%;
    }

    .chart-container {
        position: relative;
        min-height: 250px;
        width: 100%;
    }

    /* Progress bars in cards */
    .progress-white {
        background: rgba(255, 255, 255, 0.2);
    }

    .progress-white .progress-bar {
        background: #fff;
    }

    /* Timeline styling - enhanced */
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        left: 20px;
        height: 100%;
        width: 2px;
        background: #eee;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 25px;
        margin-left: 40px;
    }

    .timeline-badge {
        position: absolute;
        top: 0;
        left: -40px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        text-align: center;
        font-size: 10px;
        line-height: 20px;
        color: white;
    }

    .timeline-content {
        padding: 15px;
        border-radius: 5px;
        background: #f8f9fa;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .timeline-content:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Student detail badges */
    .student-details {
        gap: 15px;
    }

    .student-id-badge,
    .student-form-badge,
    .student-gender-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        background-color: #f8f9fa;
        border-radius: 20px;
        font-size: 0.9rem;
    }

    .student-id-badge i,
    .student-form-badge i,
    .student-gender-badge i {
        margin-right: 5px;
        color: #1572E8;
    }

    /* Stats improvements */
    .progress-stats .stat-item {
        text-align: center;
    }

    .progress-stats .stat-item h5 {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .progress-sm {
        height: 4px;
        border-radius: 4px;
        overflow: hidden;
    }

    /* Streak flames */
    .streak-flames {
        font-size: 1.2rem;
        letter-spacing: -3px;
    }

    /* Card stats improvements */
    .card-stats .icon-big {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.1);
        margin-right: 15px;
    }

    .card-stats h3.card-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .pulse-icon {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    /* Enhanced empty state */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #6c757d;
    }

    /* Juzuk grid from test.php */
    .juzuk-progress-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
    }

    .juzuk-tile {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        text-align: center;
        transition: all 0.2s ease;
    }

    .juzuk-tile:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .juzuk-tile.completed {
        background-color: rgba(46, 202, 106, 0.1);
        border-left: 3px solid #2ecc71;
    }

    .juzuk-tile.in-progress {
        background-color: rgba(52, 152, 219, 0.1);
        border-left: 3px solid #3498db;
    }

    .juzuk-tile.not-started {
        background-color: rgba(236, 240, 241, 0.5);
    }

    .juzuk-number {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .juzuk-progress {
        margin-bottom: 0.5rem;
    }

    .juzuk-percentage {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .juzuk-pages {
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* Progress value indicator */
    .progress-value {
        font-weight: 600;
        font-size: 0.85rem;
        width: 45px;
        text-align: right;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Progress Chart - Enhanced with better colors and hover effects
        var monthlyCtx = document.getElementById('monthlyProgressChart').getContext('2d');
        var monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthNames) ?>,
                datasets: [{
                    label: 'Pages',
                    data: <?= json_encode(array_values($monthlyProgress)) ?>,
                    backgroundColor: function(context) {
                        const value = context.dataset.data[context.dataIndex];
                        return value > 0 ?
                            'rgba(21, 114, 232, 0.8)' :
                            'rgba(220, 220, 220, 0.5)';
                    },
                    borderWidth: 0,
                    borderRadius: 5,
                    hoverBackgroundColor: '#5AB1EF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label + ' ' + new Date().getFullYear();
                            },
                            label: function(context) {
                                return context.parsed.y + ' pages read';
                            }
                        }
                    }
                },
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
                }
            }
        });

        // Juzuk Completion Chart - Enhanced with better visualization
        var juzukData = [];
        var juzukLabels = [];
        var juzukColors = [];

        <?php for ($i = 1; $i <= 30; $i++): ?>
            juzukLabels.push('<?= $i ?>');
            juzukData.push(<?= isset($juzukCompletionData[$i]) ? $juzukCompletionData[$i]['percentage'] : 0 ?>);

            var percentage = <?= isset($juzukCompletionData[$i]) ? $juzukCompletionData[$i]['percentage'] : 0 ?>;
            if (percentage >= 90) {
                juzukColors.push('#31CE36'); // Success
            } else if (percentage >= 70) {
                juzukColors.push('#48ABF7'); // Info
            } else if (percentage >= 50) {
                juzukColors.push('#1572E8'); // Primary
            } else if (percentage >= 25) {
                juzukColors.push('#FFAD46'); // Warning
            } else if (percentage > 0) {
                juzukColors.push('#F25961'); // Danger
            } else {
                juzukColors.push('#E0E0E0'); // Gray
            }
        <?php endfor; ?>

        var juzukCtx = document.getElementById('juzukCompletionChart').getContext('2d');
        var juzukChart = new Chart(juzukCtx, {
            type: 'bar',
            data: {
                labels: juzukLabels,
                datasets: [{
                    label: 'Completion %',
                    data: juzukData,
                    backgroundColor: juzukColors,
                    borderWidth: 0,
                    borderRadius: 3,
                    maxBarThickness: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return 'Juzuk ' + tooltipItems[0].label;
                            },
                            label: function(context) {
                                return context.parsed.y.toFixed(1) + '% completed';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
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
                }
            }
        });
    });
</script>

<?php include '../include/footer.php'; ?>