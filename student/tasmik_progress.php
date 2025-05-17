<?php
$pageTitle = "Tasmik Progress";
$breadcrumb = "Page / Tasmik Progress"; 

// Include header file
include '../include/header.php';

// Initialize variables with default values
$studentBasicInfo = [
    'full_name' => 'Student Name',
    'form' => 'Form 3',
    'halaqahname' => 'Halaqah Group',
    'teacher_name' => 'Teacher Name'
];

$totalJuzukCompleted = 0;
$completedPages = 0;
$total_pages = 604; // Standard number of pages in a mushaf
$pages_remaining = $total_pages - $completedPages;
$completed_percentage = 0;
$current_surah = 'Al-Fatihah';
$current_juzuk = 1;
$daysRemaining = 2039; // Default if calculation fails
$required_daily_pages = 0.3; // Default
$on_track = true;
$currentStreak = 0;
$monthly_target = 9; // Default
$recentSubmissions = [];
$latestTasmik = ['juzuk' => 1];

// Month names for the chart
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyProgress = array_fill_keys(range(1, 12), 0);
$yearlyProgress = [
    'Form 1' => 0,
    'Form 2' => 0,
    'Form 3' => 0,
    'Form 4' => 0,
    'Form 5' => 0
];

// Juzuk page ranges
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

// Simple surah names by juzuk
$surahByJuzuk = [
    1 => 'Al-Fatihah',
    2 => 'Al-Baqarah',
    3 => 'Al-Baqarah',
    4 => 'Ali Imran',
    5 => 'An-Nisa',
    6 => 'An-Nisa',
    7 => 'Al-Maidah',
    8 => 'Al-Anam',
    9 => 'Al-Araf',
    10 => 'Al-Anfal',
    11 => 'At-Tawbah',
    12 => 'Hud',
    13 => 'Yusuf',
    14 => 'Al-Hijr',
    15 => 'Al-Isra',
    16 => 'Al-Kahf',
    17 => 'Al-Anbiya',
    18 => 'Al-Muminun',
    19 => 'Al-Furqan',
    20 => 'An-Naml',
    21 => 'Al-Ankabut',
    22 => 'Al-Ahzab',
    23 => 'Ya-Sin',
    24 => 'Az-Zumar',
    25 => 'Fussilat',
    26 => 'Al-Ahqaf',
    27 => 'Az-Zariyat',
    28 => 'Al-Mujadilah',
    29 => 'Al-Mulk',
    30 => 'An-Naba'
];

// Function to get KPI target based on form
function getJuzukKPI($form)
{
    // Remove "Form " prefix if present and convert to integer
    $formNumber = (int)str_replace('Form ', '', $form);

    // KPI targets by form
    $kpiTargets = [
        1 => 6,   // Form 1: 6 juzu'
        2 => 12,  // Form 2: 12 juzu'
        3 => 18,  // Form 3: 18 juzu'
        4 => 24,  // Form 4: 24 juzu'
        5 => 30   // Form 5: 30 juzu'
    ];

    // Return the appropriate KPI target
    return isset($kpiTargets[$formNumber]) ? $kpiTargets[$formNumber] : 0;
}

// Try to get data from database - wrapped in try/catch to handle connection issues
try {
    // Include database connection using the correct path
    require_once '../database/db_connection.php';

    // Check if database connection exists and is valid
    if (isset($conn) && $conn) {
        // Get currently logged in user ID from session
        $userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
        $studentId = null;

        // If user ID exists in session, get the student ID from database
        if ($userId) {
            $studentIdQuery = "
                SELECT studentid 
                FROM student 
                WHERE userid = ?
            ";
            $stmt = $conn->prepare($studentIdQuery);
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $studentId = $row['studentid'];
            }
            $stmt->close();
        }

        // If student ID is not found, try to get it from GET parameter (for testing)
        if (!$studentId && isset($_GET['studentid'])) {
            $studentId = $_GET['studentid'];
        }

        // If still no student ID, use a default for testing
        if (!$studentId) {
            $studentId = 'ST001';
        }

        // Get basic student information
        $studentQuery = "
            SELECT 
                s.studentid, 
                CONCAT(u.firstname, ' ', u.lastname) AS full_name,
                s.form,
                h.halaqahname,
                CONCAT(u_teacher.firstname, ' ', u_teacher.lastname) AS teacher_name
            FROM 
                student s
            JOIN 
                users u ON s.userid = u.userid
            LEFT JOIN 
                halaqah h ON s.halaqahid = h.halaqahid
            LEFT JOIN 
                teacher t ON h.halaqahid = t.halaqahid
            LEFT JOIN 
                users u_teacher ON t.userid = u_teacher.userid
            WHERE 
                s.studentid = ?
        ";

        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        if ($studentResult->num_rows > 0) {
            $studentBasicInfo = $studentResult->fetch_assoc();
        }
        $stmt->close();

        // Get latest tasmik record
        $latestTasmikQuery = "
            SELECT 
                tasmikid,
                juzuk,
                start_page,
                end_page,
                start_ayah,
                end_ayah,
                status,
                submitted_at
            FROM 
                tasmik
            WHERE 
                studentid = ?
            ORDER BY 
                tasmik_date DESC, submitted_at DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($latestTasmikQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $latestTasmikResult = $stmt->get_result();
        if ($latestTasmikResult->num_rows > 0) {
            $latestTasmik = $latestTasmikResult->fetch_assoc();
            $current_juzuk = $latestTasmik['juzuk'];
        }
        $stmt->close();

        // Get recent tasmik submissions
        $recentTasmikQuery = "
            SELECT 
                tasmik_date as date,
                juzuk,
                CONCAT(start_page, '-', end_page) as pages,
                status
            FROM 
                tasmik
            WHERE 
                studentid = ?
            ORDER BY 
                tasmik_date DESC, submitted_at DESC
            LIMIT 5
        ";

        $stmt = $conn->prepare($recentTasmikQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $recentTasmikResult = $stmt->get_result();
        $recentSubmissions = [];
        while ($row = $recentTasmikResult->fetch_assoc()) {
            // Convert status to display format (capitalize first letter)
            $row['status'] = ucfirst($row['status']);
            $recentSubmissions[] = $row;
        }
        $stmt->close();

        // Calculate total juzuk completed
        // $completedJuzukQuery = "
        //     SELECT 
        //         COUNT(DISTINCT juzuk) as completed_juzuk
        //     FROM 
        //         tasmik
        //     WHERE 
        //         studentid = ? AND status = 'accepted'
        // ";

        // $stmt = $conn->prepare($completedJuzukQuery);
        // $stmt->bind_param("s", $studentId);
        // $stmt->execute();
        // $completedJuzukResult = $stmt->get_result();
        // if ($completedJuzukResult->num_rows > 0) {
        //     $completedJuzukData = $completedJuzukResult->fetch_assoc();
        //     $totalJuzukCompleted = $completedJuzukData['completed_juzuk'];
        // }
        // $stmt->close();

        // Calculate juzuk completion percentages
        $juzukCompletionQuery = "
            SELECT 
                juzuk,
                SUM(
                    GREATEST(
                        0,
                        LEAST(end_page, ?) - GREATEST(start_page, ?)
                    ) + 1
                ) as completed_pages
            FROM 
                tasmik
            WHERE 
                studentid = ? AND status = 'accepted'
                AND juzuk = ?
            GROUP BY 
                juzuk
        ";

        // Initialize counter for completed juzuks
        $totalJuzukCompleted = 0;
        $juzukCompletionData = [];

        // Check each juzuk's completion status
        foreach ($juzukPageRanges as $juzuk => $range) {
            $juzukTotalPages = $range['end'] - $range['start'] + 1;

            $stmt = $conn->prepare($juzukCompletionQuery);
            $stmt->bind_param("iisi", $range['end'], $range['start'], $studentId, $juzuk);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $completedPages = $row['completed_pages'] ?? 0;
                $completionPercentage = ($completedPages / $juzukTotalPages) * 100;

                // Consider a juzuk completed if at least 90% of its pages are read
                if ($completionPercentage >= 90) {
                    $totalJuzukCompleted++;
                }

                // Store completion data for the chart
                $juzukCompletionData[$juzuk] = [
                    'total' => $juzukTotalPages,
                    'completed' => $completedPages,
                    'percentage' => $completionPercentage
                ];
            } else {
                $juzukCompletionData[$juzuk] = [
                    'total' => $juzukTotalPages,
                    'completed' => 0,
                    'percentage' => 0
                ];
            }
            $stmt->close();
        }

        // Get monthly progress (pages per month this year)
        $currentYear = date('Y');
        $monthlyProgressQuery = "
            SELECT 
                MONTH(tasmik_date) as month,
                SUM(end_page - start_page + 1) as pages
            FROM 
                tasmik
            WHERE 
                studentid = ? AND YEAR(tasmik_date) = ? AND status = 'accepted'
            GROUP BY 
                MONTH(tasmik_date)
        ";

        $stmt = $conn->prepare($monthlyProgressQuery);
        $stmt->bind_param("si", $studentId, $currentYear);
        $stmt->execute();
        $monthlyProgressResult = $stmt->get_result();
        while ($row = $monthlyProgressResult->fetch_assoc()) {
            $monthlyProgress[$row['month']] = (int)$row['pages'];
        }
        $stmt->close();

        // Get yearly progress (pages per year)
        $yearlyProgressQuery = "
            SELECT 
                YEAR(tasmik_date) as year,
                SUM(end_page - start_page + 1) as pages
            FROM 
                tasmik
            WHERE 
                studentid = ? AND status = 'accepted'
            GROUP BY 
                YEAR(tasmik_date)
        ";

        $stmt = $conn->prepare($yearlyProgressQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $yearlyProgressResult = $stmt->get_result();

        $currentYear = date('Y');
        $formStartYear = $currentYear - (int)str_replace('Form ', '', $studentBasicInfo['form']) + 1;

        while ($row = $yearlyProgressResult->fetch_assoc()) {
            $formYear = $row['year'] - $formStartYear + 1;
            if ($formYear >= 1 && $formYear <= 5) {
                $yearlyProgress['Form ' . $formYear] = (int)$row['pages'];
            }
        }
        $stmt->close();

        // Calculate student streak (consecutive days with submissions)
        $streakQuery = "
            SELECT 
                tasmik_date
            FROM 
                tasmik
            WHERE 
                studentid = ? AND status = 'accepted'
            ORDER BY 
                tasmik_date DESC
        ";

        $stmt = $conn->prepare($streakQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $streakResult = $stmt->get_result();
        $submissionDates = [];
        while ($row = $streakResult->fetch_assoc()) {
            $submissionDates[] = $row['tasmik_date'];
        }
        $stmt->close();

        // Calculate current streak
        $currentStreak = 0;
        if (!empty($submissionDates)) {
            $today = new DateTime();
            $yesterday = clone $today;
            $yesterday->modify('-1 day');

            $latestDate = new DateTime($submissionDates[0]);
            // Only count streak if latest submission is from today or yesterday
            if ($latestDate >= $yesterday) {
                $currentStreak = 1;
                for ($i = 1; $i < count($submissionDates); $i++) {
                    $currentDate = new DateTime($submissionDates[$i - 1]);
                    $prevDate = new DateTime($submissionDates[$i]);
                    $diff = $currentDate->diff($prevDate)->days;

                    if ($diff == 1) {
                        $currentStreak++;
                    } else {
                        break;
                    }
                }
            }
        }

        // Calculate total pages completed and remaining
        $totalPagesQuery = "
            SELECT 
                SUM(end_page - start_page + 1) as completed_pages
            FROM 
                tasmik
            WHERE 
                studentid = ? AND status = 'accepted'
        ";

        $stmt = $conn->prepare($totalPagesQuery);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $totalPagesResult = $stmt->get_result();
        if ($totalPagesResult->num_rows > 0) {
            $totalPagesData = $totalPagesResult->fetch_assoc();
            $completedPages = $totalPagesData['completed_pages'] ?? 0;
        }
        $stmt->close();

        $pages_remaining = $total_pages - $completedPages;
        $completed_percentage = round(($completedPages / $total_pages) * 100);

        // Calculate days remaining until Form 5 ends
        $currentForm = (int)str_replace('Form ', '', $studentBasicInfo['form']);
        $yearsRemaining = 5 - $currentForm;
        $endOfSchoolDate = new DateTime(($currentYear + $yearsRemaining) . '-11-30'); // Assuming school ends in November
        $today = new DateTime();
        $daysRemaining = $today->diff($endOfSchoolDate)->days;

        // Calculate required daily pages to meet target
        $required_daily_pages = $pages_remaining > 0 ?
            round($pages_remaining / max(1, $daysRemaining), 1) : 0;

        // Calculate if student is on track
        $on_track = $required_daily_pages <= 0.5; // Assuming 0.5 pages per day is manageable

        // Set a monthly target based on required progress
        $monthly_target = ceil($required_daily_pages * 30);

        // Get current surah based on juzuk
        $juzuk_val = isset($latestTasmik['juzuk']) ? (int)$latestTasmik['juzuk'] : 1;
        $juzuk_index = min($juzuk_val, 30);
        $current_surah = $surahByJuzuk[$juzuk_index];

        // Calculate KPI metrics
        $kpiTarget = getJuzukKPI($studentBasicInfo['form']);
        $kpiAchieved = $totalJuzukCompleted >= $kpiTarget;
        $kpiPercentage = $kpiTarget > 0 ? round(($totalJuzukCompleted / $kpiTarget) * 100) : 0;
        $kpiRemainingJuzuk = $kpiTarget - $totalJuzukCompleted;
    }
} catch (Exception $e) {
    // Just continue with default values if there's an error
    // In a production environment, you might want to log this error
    error_log("Error fetching data: " . $e->getMessage());
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <!-- Student Info Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <!-- Profile Picture -->
                            <div class="avatar-container mr-3">
                                <div class="avatar-frame">
                                    <?php
                                    // Check if user has a custom profile picture
                                    if (isset($studentBasicInfo['profile_image']) && !empty($studentBasicInfo['profile_image'])) {
                                        // Display the user's custom profile image
                                        echo '<img src="' . htmlspecialchars($studentBasicInfo['profile_image']) . '" alt="Student Profile" class="avatar-img">';
                                    } else {
                                        // Display a person icon as default avatar
                                        echo '<i class="fas fa-user avatar-icon"></i>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Student Information -->
                            <div class="flex-grow-1" style="margin-left: 10px;">
                                <div class="d-flex align-items-center mb-1">
                                    <h4 class="card-title mb-0"><?php echo htmlspecialchars(ucwords($studentBasicInfo['full_name'])); ?></h4>
                                    <span class="badge badge-primary ml-3">Teacher: <?php echo htmlspecialchars($studentBasicInfo['teacher_name']); ?></span>
                                </div>
                                <span class="text-muted">
                                    <?php
                                    $formValue = htmlspecialchars($studentBasicInfo['form']);
                                    echo (strpos(strtolower($formValue), 'form') === 0) ? $formValue : 'Form ' . $formValue;
                                    ?> | <?php echo htmlspecialchars($studentBasicInfo['halaqahname']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Status and Progress Cards -->
        <div class="row row-cards-equal-height">
            <!-- New KPI Status Card -->
            <div class="col-md-4 d-flex flex-column">
                <div class="card flex-grow-1 <?php echo $kpiAchieved ? 'bg-success-light' : 'bg-warning-light'; ?>">
                    <div class="card-header">
                        <h4 class="card-title">Form <?php echo $currentForm; ?> KPI Status</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Target: <?php echo $kpiTarget; ?> Juzuk</span>
                            <span class="text-muted">Completed: <?php echo $totalJuzukCompleted; ?> Juzuk</span>
                        </div>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar <?php echo $kpiAchieved ? 'bg-success' : 'bg-warning'; ?>"
                                role="progressbar"
                                style="width: <?php echo min($kpiPercentage, 100); ?>%"
                                aria-valuenow="<?php echo $kpiPercentage; ?>"
                                aria-valuemin="0"
                                aria-valuemax="100">
                                <?php echo $kpiPercentage; ?>%
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <?php if ($kpiAchieved): ?>
                                <span class="badge badge-success">KPI Target Achieved</span>
                            <?php else: ?>
                                <span class="badge badge-warning">KPI Target Not Yet Achieved</span>
                                <p class="mt-2 text-muted">Need <?php echo $kpiRemainingJuzuk; ?> more Juzuk to reach target</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Overall Quran Progress -->
                <div class="card flex-grow-1 mt-3">
                    <div class="card-header">
                        <h4 class="card-title">Overall Quran Completion</h4>
                    </div>
                    <div class="card-body">
                        <div class="progress-card">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><?php echo $completed_percentage; ?>% Completed</span>
                                <span class="text-muted"><?php echo $completedPages; ?> of <?php echo $total_pages; ?> Pages</span>
                            </div>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completed_percentage; ?>%"
                                    aria-valuenow="<?php echo $completed_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $completed_percentage; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="badge badge-info">Pages Remaining: <?php echo $pages_remaining; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Juzuk Progress Chart -->
            <div class="col-md-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h4 class="card-title">Juzuk Progress</h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="chart-container flex-grow-1" style="min-height: 240px; position: relative;">
                            <canvas id="juzukProgress"></canvas>
                        </div>
                        <div class="mt-auto text-center">
                            <span class="badge badge-success"><?php echo $totalJuzukCompleted; ?> Juzuk Completed</span>
                            <span class="badge badge-info"><?php echo 30 - $totalJuzukCompleted; ?> Juzuk Remaining</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="col-md-4 d-flex flex-column">
                <!-- Countdown Card -->
                <div class="card bg-primary-gradient">
                    <div class="card-body text-center text-white py-3">
                        <h4 class="mb-2">Countdown to Completion</h4>
                        <div class="d-flex justify-content-center countdown-container">
                            <div class="countdown-item">
                                <span class="countdown-number"><?php echo $daysRemaining; ?></span>
                                <span class="countdown-label">Days</span>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">Remaining until the end of Form 5</p>
                    </div>
                </div>

                <!-- Current Juzuk Status Cards -->
                <div class="card card-stats card-round bg-light mt-3">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-book-open"></i>
                                </div>
                            </div>
                            <div class="col col-stats ml-3 ml-sm-0">
                                <div class="numbers">
                                    <p class="card-category mb-1">Current Juzuk</p>
                                    <h4 class="card-title mb-0">Juzuk <?php echo $current_juzuk; ?></h4>
                                    <p class="card-category mb-0">Surah: <?php echo $current_surah; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Target Cards -->
                <div class="card card-stats card-round <?php echo $on_track ? 'bg-light' : 'bg-warning-light'; ?> mt-3 flex-grow-1">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center <?php echo $on_track ? 'icon-success' : 'icon-warning'; ?> bubble-shadow-small">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ml-3 ml-sm-0">
                                <div class="numbers">
                                    <p class="card-category mb-1">Daily Target</p>
                                    <h4 class="card-title mb-0"><?php echo number_format($required_daily_pages, 1); ?> pages/day</h4>
                                    <p class="card-category mb-0">
                                        <?php echo $on_track ? 'On Track' : 'Need to increase pace'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Monthly Progress (Pages)</h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyProgress"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="badge badge-primary">Target: <?php echo $monthly_target; ?> pages/month</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Progress By Year</h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="yearlyProgress"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="badge badge-info">Total Pages Completed: <?php echo $completedPages; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Submissions and Streak Card -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Recent Submissions</h4>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recentSubmissions) > 0): ?>
                                        <?php foreach ($recentSubmissions as $submission): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($submission['date']); ?></td>
                                                <td><?php echo htmlspecialchars($submission['juzuk']); ?></td>
                                                <td><?php echo htmlspecialchars($submission['pages']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $submission['status'] == 'Approved' ? 'success' : ($submission['status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars($submission['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No recent submissions</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h4 class="card-title">Current Streak</h4>
                    </div>
                    <div class="card-body text-center d-flex flex-column">
                        <div class="d-flex justify-content-center mb-2">
                            <div class="streak-circle" style="width: 120px; height: 120px;">
                                <span class="streak-number"><?php echo $currentStreak; ?></span>
                                <span class="streak-text">days</span>
                            </div>
                        </div>
                        <p class="small mb-2">Keep up the good work! Your consistency is key to success.</p>
                        <div class="mt-2">
                            <div class="progress mb-1" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min($currentStreak * 5, 100); ?>%"
                                    aria-valuenow="<?php echo $currentStreak; ?>" aria-valuemin="0" aria-valuemax="20">
                                </div>
                            </div>
                            <small class="text-muted">Next milestone: <?php echo max(0, 20 - $currentStreak); ?> more days to reach 20-day streak!</small>
                        </div>

                        <!-- Optional: compact version of the streak benefits -->
                        <div class="mt-auto">
                            <div class="streak-stats p-2 mt-2 bg-light rounded">
                                <small class="mb-0">Daily practice builds long-term memory and deeper understanding.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Juzuk Progress -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Detailed Juzuk Progress</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-sm table-condensed">
                        <thead style="position: sticky; top: 0; background-color: white; z-index: 1;">
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
                                $juzData = $juzukCompletionData[$i] ?? ['percentage' => 0, 'completed' => 0, 'total' => $juzukPageRanges[$i]['end'] - $juzukPageRanges[$i]['start'] + 1];
                                $progressClass = $juzData['percentage'] >= 90 ? 'success' : ($juzData['percentage'] > 0 ? 'primary' : 'secondary');
                                ?>
                                <tr style="line-height: 1;">
                                    <td class="py-1"><?php echo $i; ?></td>
                                    <td class="py-1"><?php echo $juzukPageRanges[$i]['start']; ?>-<?php echo $juzukPageRanges[$i]['end']; ?></td>
                                    <td class="py-1"><?php echo $juzData['completed']; ?>/<?php echo $juzData['total']; ?></td>
                                    <td class="py-1">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 4px;">
                                                <div class="progress-bar bg-<?php echo $progressClass; ?>"
                                                    role="progressbar"
                                                    style="width: <?php echo $juzData['percentage']; ?>%"
                                                    aria-valuenow="<?php echo $juzData['percentage']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <div class="ml-2" style="min-width: 50px; text-align: right;">
                                                <span class="text-<?php echo $progressClass; ?>" style="font-size: 0.8rem;"><?php echo round($juzData['percentage'], 1); ?>%</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .row-cards-equal-height {
        display: flex;
        flex-wrap: wrap;
    }

    .row-cards-equal-height>[class*='col-'] {
        display: flex;
        flex-direction: column;
    }

    .chart-container {
        position: relative;
        min-height: 240px;
        width: 100%;
    }

    .avatar-container {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-frame {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-icon {
        font-size: 40px;
        color: #aaa;
    }

    .streak-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: linear-gradient(45deg, #1572E8, #2BC0E4);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: 0 0 20px rgba(21, 114, 232, 0.3);
    }

    .streak-.streak-number {
        font-size: 50px;
        font-weight: bold;
        line-height: 1;
    }

    .streak-text {
        font-size: 18px;
    }

    .countdown-container {
        margin-top: 10px;
    }

    .countdown-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 15px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        min-width: 100px;
    }

    .countdown-number {
        font-size: 36px;
        font-weight: bold;
        line-height: 1;
    }

    .countdown-label {
        font-size: 14px;
        opacity: 0.9;
    }

    .bg-warning-light {
        background-color: rgba(255, 191, 0, 0.1);
    }

    .bg-success-light {
        background-color: rgba(46, 202, 106, 0.1);
    }

    .bg-primary-gradient {
        background: linear-gradient(45deg, #1572E8, #5AB1EF);
    }
</style>

<!-- Charts JS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Juzuk Progress Chart (Doughnut)
        var juzukCtx = document.getElementById('juzukProgress').getContext('2d');
        var juzukChart = new Chart(juzukCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Remaining'],
                datasets: [{
                    data: [<?php echo $totalJuzukCompleted; ?>, <?php echo 30 - $totalJuzukCompleted; ?>],
                    backgroundColor: ['#2ecc71', '#ecf0f1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                },
                cutoutPercentage: 70,
                animation: {
                    animateScale: true
                }
            }
        });

        // Monthly Progress Chart (Bar)
        var monthlyCtx = document.getElementById('monthlyProgress').getContext('2d');
        var monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthNames); ?>,
                datasets: [{
                    label: 'Pages',
                    data: <?php echo json_encode(array_values($monthlyProgress)); ?>,
                    backgroundColor: '#1572E8',
                    borderWidth: 0,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        // Yearly Progress Chart (Bar)
        var yearlyCtx = document.getElementById('yearlyProgress').getContext('2d');
        var yearlyChart = new Chart(yearlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($yearlyProgress)); ?>,
                datasets: [{
                    label: 'Pages',
                    data: <?php echo json_encode(array_values($yearlyProgress)); ?>,
                    backgroundColor: '#6861CE',
                    borderWidth: 0,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });

        // KPI Target Chart - New chart to visualize KPI
        var kpiCtx = document.getElementById('kpiTargetChart').getContext('2d');
        var kpiChart = new Chart(kpiCtx, {
            type: 'horizontalBar',
            data: {
                labels: ['KPI Progress'],
                datasets: [{
                        label: 'Completed',
                        data: [<?php echo $totalJuzukCompleted; ?>],
                        backgroundColor: '#2ecc71',
                        borderWidth: 0,
                        borderRadius: 5
                    },
                    {
                        label: 'Target',
                        data: [<?php echo $kpiTarget; ?>],
                        backgroundColor: '#3498db',
                        borderWidth: 0,
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            max: 30 // Total number of Juzuk in Quran
                        }
                    }]
                }
            }
        });
    });
</script>

<?php include '../include/footer.php'; ?>