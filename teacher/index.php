<?php
$pageTitle = "Dashboard";
$breadcrumb = "Pages / <a href='../teacher/index.php' class='no-link-style'>Dashboard</a>";
include '../include/header.php';

// Fetch key metrics for the teacher
$teacherId = $_SESSION['userid']; // Assuming the teacher's user ID is stored in the session
$halaqahId = $conn->query("SELECT halaqahid FROM teacher WHERE userid = '$teacherId'")->fetch_assoc()['halaqahid'];
$totalStudents = $conn->query("SELECT COUNT(*) AS count FROM student WHERE halaqahid = '$halaqahId'")->fetch_assoc()['count'];
$totalClasses = $conn->query("SELECT COUNT(DISTINCT class) AS count FROM student WHERE halaqahid = '$halaqahId'")->fetch_assoc()['count'];
$totalAnnouncements = $conn->query("SELECT COUNT(*) AS count FROM announcement WHERE target_audience = 'teacher' OR target_audience = 'all'")->fetch_assoc()['count'];
$form = $conn->query("SELECT DISTINCT form FROM student WHERE halaqahid = '$halaqahId'")->fetch_assoc()['form'];

// Get pending tasmik sessions count
$pendingTasmik = $conn->query("SELECT COUNT(*) AS count FROM tasmik t 
                               JOIN student s ON t.studentid = s.studentid 
                               WHERE s.halaqahid = '$halaqahId' AND t.status = 'pending'")->fetch_assoc()['count'];

// Get total completed juzuks for all students
$completedJuzuks = $conn->query("SELECT SUM(juzuk) AS total FROM (
                                 SELECT studentid, MAX(juzuk) as juzuk FROM tasmik 
                                 WHERE status = 'accepted' 
                                 GROUP BY studentid) AS max_juzuks 
                                 JOIN student s ON max_juzuks.studentid = s.studentid 
                                 WHERE s.halaqahid = '$halaqahId'")->fetch_assoc()['total'] ?? 0;

// Get this month's progress (count of accepted tasmik sessions)
$currentMonth = date('m');
$currentYear = date('Y');
$monthlyProgress = $conn->query("SELECT COUNT(*) AS count FROM tasmik t 
                                JOIN student s ON t.studentid = s.studentid 
                                WHERE s.halaqahid = '$halaqahId' 
                                AND t.status = 'accepted' 
                                AND MONTH(t.tasmik_date) = $currentMonth 
                                AND YEAR(t.tasmik_date) = $currentYear")->fetch_assoc()['count'];

// Get upcoming zoom meetings
$upcomingMeetings = $conn->query("SELECT zm.*, u.firstname, u.lastname 
                                 FROM zoom_meetings zm 
                                 JOIN student s ON zm.studentid = s.studentid
                                 JOIN users u ON s.userid = u.userid
                                 WHERE s.halaqahid = '$halaqahId' 
                                 AND zm.scheduled_at > NOW()
                                 ORDER BY zm.scheduled_at ASC LIMIT 5");

// Fetch recent announcements for the teacher
$recentAnnouncements = $conn->query("SELECT title, message, target_audience, created_at FROM announcement WHERE target_audience = 'teacher' OR target_audience = 'all' ORDER BY created_at DESC LIMIT 5");
?>

<!-- Required CSS for dashboard components -->
<style>
.card-body.chart-container {
    height: 300px;
    position: relative;
}
.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
}
.card-stats .card-body {
    min-height: 130px; /* Ensure all cards have the same minimum height */
}
</style>

<div class="container">
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Dashboard</h3>
                <h6 class="op-7 mb-2">Teacher Dashboard</h6>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="../announcement/index.php" class="btn btn-label-info btn-round me-2">
                    <i class="fas fa-bullhorn me-2"></i>Announcements
                </a>
                <a href="student_progress.php" class="btn btn-primary btn-round">
                    <i class="fas fa-chart-line me-2"></i>View Progress
                </a>
            </div>
        </div>
        
        <!-- Enhanced Summary Cards -->
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Students</p>
                                    <h4 class="card-title"><?php echo $totalStudents; ?></h4>
                                </div>
                            </div>
                        </div>
                        <!-- Added extra elements to make card height consistent -->
                        <!-- <div class="mt-2">
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" 
                                    aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted mt-1 d-block text-right">Active students</small>
                        </div> -->
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Pending Reviews</p>
                                    <h4 class="card-title"><?php echo $pendingTasmik; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="student_progress.php" class="btn btn-sm btn-warning w-100">Review Now</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
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
                                    <p class="card-category">Completed Juzuks</p>
                                    <h4 class="card-title"><?php echo $completedJuzuks; ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <?php 
                            // Calculate target based on form (Form 2 = 12 juzuks per student)
                            $targetJuzuks = $form == "Form 2" ? 12 * $totalStudents : 18 * $totalStudents;
                            $progressPercent = min(100, ($completedJuzuks / max(1, $targetJuzuks)) * 100);
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progressPercent; ?>%" 
                                aria-valuenow="<?php echo $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted mt-1 d-block text-right"><?php echo round($progressPercent, 1); ?>% of halaqah target</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Monthly Progress</p>
                                    <h4 class="card-title"><?php echo $monthlyProgress; ?> sessions</h4>
                                </div>
                            </div>
                        </div>
                        <?php 
                        // Calculate monthly trend (simplified)
                        $lastMonth = date('m', strtotime('-1 month'));
                        $lastMonthYear = date('Y', strtotime('-1 month'));
                        $lastMonthProgress = $conn->query("SELECT COUNT(*) AS count FROM tasmik t 
                                                        JOIN student s ON t.studentid = s.studentid 
                                                        WHERE s.halaqahid = '$halaqahId' AND t.status = 'accepted' 
                                                        AND MONTH(t.tasmik_date) = $lastMonth 
                                                        AND YEAR(t.tasmik_date) = $lastMonthYear")->fetch_assoc()['count'];
                        $trend = $lastMonthProgress > 0 ? (($monthlyProgress - $lastMonthProgress) / $lastMonthProgress) * 100 : 100;
                        $trendIcon = $trend >= 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger';
                        ?>
                        <div class="mt-2 text-right">
                            <small class="text-<?php echo $trend >= 0 ? 'success' : 'danger'; ?>">
                                <i class="fas <?php echo $trendIcon; ?>"></i> 
                                <?php echo abs(round($trend)); ?>% vs last month
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Progress Chart -->
        <div class="row">
            <div class="col-md-8">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Monthly Progress Overview</div>
                            <div class="card-tools">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary active" id="view-monthly">
                                        Monthly
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="view-yearly">
                                        Yearly
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Student Performance -->
            <div class="col-md-4">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Top Performing Students</div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th class="text-center">Juzuk</th>
                                        <th class="text-center">KPI %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get top 5 students by completed juzuks
                                    $topStudents = $conn->query("
                                        SELECT s.studentid, u.firstname, u.lastname, MAX(t.juzuk) as max_juzuk,
                                               (MAX(t.juzuk) / " . ($form == "Form 2" ? 12 : 18) . " * 100) as kpi_percent
                                        FROM student s
                                        JOIN users u ON s.userid = u.userid
                                        JOIN tasmik t ON s.studentid = t.studentid
                                        WHERE s.halaqahid = '$halaqahId' AND t.status = 'accepted'
                                        GROUP BY s.studentid, u.firstname, u.lastname
                                        ORDER BY max_juzuk DESC
                                        LIMIT 5
                                    ");
                                    
                                    while ($student = $topStudents->fetch_assoc()): 
                                        $kpiClass = '';
                                        if ($student['kpi_percent'] >= 75) $kpiClass = 'text-success';
                                        elseif ($student['kpi_percent'] >= 50) $kpiClass = 'text-info';
                                        elseif ($student['kpi_percent'] >= 25) $kpiClass = 'text-warning';
                                        else $kpiClass = 'text-danger';
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2">
                                                        <span class="avatar-title rounded-circle bg-primary">
                                                            <?php echo strtoupper($student['firstname'][0]); ?>
                                                        </span>
                                                    </div>
                                                    <?php echo $student['firstname'] . ' ' . $student['lastname']; ?>
                                                </div>
                                            </td>
                                            <td class="text-center"><?php echo $student['max_juzuk']; ?></td>
                                            <td class="text-center">
                                                <span class="<?php echo $kpiClass; ?>">
                                                    <?php echo round($student['kpi_percent']); ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Upcoming Meetings -->
            <div class="col-md-6">
                <div class="card card-round">
                    <div class="card-header bg-primary text-white">
                        <div class="card-title">Upcoming Online Meetings</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Date & Time</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($upcomingMeetings && $upcomingMeetings->num_rows > 0):
                                        while ($meeting = $upcomingMeetings->fetch_assoc()): 
                                            $meetingDate = new DateTime($meeting['scheduled_at']);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2">
                                                        <span class="avatar-title rounded-circle bg-info">
                                                            <?php echo strtoupper($meeting['firstname'][0]); ?>
                                                        </span>
                                                    </div>
                                                    <?php echo $meeting['firstname'] . ' ' . $meeting['lastname']; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo $meetingDate->format('d M Y'); ?><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo $meetingDate->format('h:i A'); ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo $meeting['meeting_link']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-video me-1"></i> Join
                                                </a>
                                                <button class="btn btn-sm btn-info copy-btn" data-clipboard-text="<?php echo $meeting['meeting_id']; ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile; 
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3">
                                                <i class="fas fa-calendar-check text-muted mb-2" style="font-size: 24px;"></i>
                                                <p class="mb-0">No upcoming meetings scheduled</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="../live_conference/schedule.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle"></i> Schedule New Meeting
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links & Tools -->
            <div class="col-md-6">
                <div class="card card-round">
                    <div class="card-header bg-info text-white">
                        <div class="card-title">Quick Tools</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="student_progress.php" class="btn btn-light btn-block p-3 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-chart-bar text-primary mb-2" style="font-size: 24px;"></i>
                                    <span>Student Progress</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="manage_tasmik.php" class="btn btn-light btn-block p-3 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-tasks text-success mb-2" style="font-size: 24px;"></i>
                                    <span>Manage Tasmik</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="../live_conference/index.php" class="btn btn-light btn-block p-3 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-video text-info mb-2" style="font-size: 24px;"></i>
                                    <span>Live Conference</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="../document/index.php" class="btn btn-light btn-block p-3 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-file-alt text-warning mb-2" style="font-size: 24px;"></i>
                                    <span>Documents</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Announcements</div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Title</th>
                                        <th scope="col">Message</th>
                                        <th scope="col">Target Audience</th>
                                        <th scope="col" class="text-end">Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($recentAnnouncements && $recentAnnouncements->num_rows > 0):
                                        while ($row = $recentAnnouncements->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo htmlspecialchars($row['message']); ?></td>
                                            <td><?php echo htmlspecialchars($row['target_audience']); ?></td>
                                            <td class="text-end"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-3">
                                                <p class="mb-0">No recent announcements</p>
                                            </td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Progress Chart Data
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const currentMonthIndex = new Date().getMonth();
    
    // Fetch monthly data via PHP
    const monthlyData = [
        <?php
        // Get monthly tasmik counts for the current year
        $currentYear = date('Y');
        $monthlyData = array_fill(0, 12, 0); // Initialize with zeros
        
        $monthlyQuery = $conn->query("SELECT MONTH(tasmik_date) as month, COUNT(*) as count 
                                      FROM tasmik t JOIN student s ON t.studentid = s.studentid 
                                      WHERE s.halaqahid = '$halaqahId' AND YEAR(tasmik_date) = $currentYear 
                                      AND t.status = 'accepted' GROUP BY MONTH(tasmik_date)");
        
        if ($monthlyQuery) {
            while ($row = $monthlyQuery->fetch_assoc()) {
                $monthlyData[$row['month'] - 1] = $row['count'];
            }
        }
        
        echo implode(',', $monthlyData);
        ?>
    ];
    
    // Fetch yearly data via PHP
    const yearlyData = [
        <?php
        // Get yearly tasmik counts for the last 5 years
        $currentYear = date('Y');
        $yearlyData = array();
        
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $count = $conn->query("SELECT COUNT(*) as count FROM tasmik t 
                                  JOIN student s ON t.studentid = s.studentid 
                                  WHERE s.halaqahid = '$halaqahId' 
                                  AND YEAR(tasmik_date) = $year 
                                  AND t.status = 'accepted'")->fetch_assoc()['count'];
            $yearlyData[$i] = $count;
        }
        
        echo implode(',', array_reverse($yearlyData));
        ?>
    ];
    
    // Setup chart
    const ctx = document.getElementById('progressChart').getContext('2d');
    let currentView = 'monthly';
    let progressChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'Tasmik Sessions',
                data: monthlyData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Handle view toggle
    document.getElementById('view-monthly').addEventListener('click', function() {
        if (currentView !== 'monthly') {
            currentView = 'monthly';
            updateChart();
            document.getElementById('view-yearly').classList.remove('active');
            this.classList.add('active');
        }
    });
    
    document.getElementById('view-yearly').addEventListener('click', function() {
        if (currentView !== 'yearly') {
            currentView = 'yearly';
            updateChart();
            document.getElementById('view-monthly').classList.remove('active');
            this.classList.add('active');
        }
    });
    
    function updateChart() {
        if (currentView === 'monthly') {
            progressChart.data.labels = monthNames;
            progressChart.data.datasets[0].data = monthlyData;
            progressChart.options.scales.x = { 
                title: { display: true, text: 'Month' }
            };
        } else {
            const yearLabels = [];
            for (let i = 4; i >= 0; i--) {
                yearLabels.push((new Date().getFullYear() - i).toString());
            }
            progressChart.data.labels = yearLabels;
            progressChart.data.datasets[0].data = yearlyData;
            progressChart.options.scales.x = { 
                title: { display: true, text: 'Year' }
            };
        }
        progressChart.update();
    }
    
    // Initialize clipboard for meeting ID copy buttons
    if (typeof ClipboardJS !== 'undefined') {
        new ClipboardJS('.copy-btn');
        
        // Add tooltip feedback when copied
        $('.copy-btn').on('click', function() {
            const $this = $(this);
            $this.attr('title', 'Copied!').tooltip('show');
            
            setTimeout(function() {
                $this.attr('title', 'Copy meeting ID').tooltip('hide');
            }, 1000);
        });
    }
});
</script>

<?php include '../include/footer.php'; ?>

<?php
$conn->close();
?>