<?php
$pageTitle = "Dashboard";
$breadcrumb = "Pages / Dashboard";
include '../include/header.php';

// Fetch key metrics
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalAnnouncements = $conn->query("SELECT COUNT(*) AS count FROM announcement")->fetch_assoc()['count'];
$totalTeachers = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'teacher'")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'student'")->fetch_assoc()['count'];
$totalParents = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'parent'")->fetch_assoc()['count'];

// Fetch recent announcements
$recentAnnouncements = $conn->query("SELECT title, message, target_audience, created_at FROM announcement ORDER BY created_at DESC LIMIT 5");

// Fetch new users
$newUsers = $conn->query("SELECT firstname, lastname, role, createdat FROM users ORDER BY createdat DESC LIMIT 5");

// Fetch user statistics for the chart (example: count of users registered each month)
$userStats = $conn->query("
    SELECT DATE_FORMAT(createdat, '%Y-%m') AS month, COUNT(*) AS count 
    FROM users 
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Dashboard</h3>
                <h6 class="op-7 mb-2">Admin Dashboard</h6>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="../announcement/add_announcement.php" class="btn btn-label-info btn-round me-2">Add Announcement</a>
                <a href="#" class="btn btn-primary btn-round">Add User</a>
            </div>
        </div>
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Users</p>
                                    <h4 class="card-title"><?php echo $totalUsers; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Teachers</p>
                                    <h4 class="card-title"><?php echo $totalTeachers; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
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
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-danger bubble-shadow-small">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Parents</p>
                                    <h4 class="card-title"><?php echo $totalParents; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- User Statistics Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">User Statistics</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="userStatsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- New Users -->
            <div class="col-md-4">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">New Users</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-list py-0">
                            <?php while ($user = $newUsers->fetch_assoc()): ?>
                                <div class="item-list">
                                    <div class="avatar">
                                        <span class="avatar-title rounded-circle border border-white bg-primary">
                                            <?php echo strtoupper($user['firstname'][0]); ?>
                                        </span>
                                    </div>
                                    <div class="info-user ms-3">
                                        <div class="username"><?php echo ucwords(htmlspecialchars($user['firstname'] . ' ' . $user['lastname'])); ?></div>
                                        <div class="status"><?php echo ucfirst($user['role']); ?></div>
                                    </div>
                                    <!-- <button class="btn btn-icon btn-link op-8 me-1">
                                        <i class="far fa-envelope"></i>
                                    </button>
                                    <button class="btn btn-icon btn-link btn-danger op-8">
                                        <i class="fas fa-ban"></i>
                                    </button> -->
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Recent Announcements -->
            <div class="col-md-8">
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
                                    <?php while ($row = $recentAnnouncements->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo htmlspecialchars($row['message']); ?></td>
                                            <td><?php echo htmlspecialchars($row['target_audience']); ?></td>
                                            <td class="text-end"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('userStatsChart').getContext('2d');
    const userStatsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($userStats, 'month')); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode(array_column($userStats, 'count')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php
$conn->close();
?>