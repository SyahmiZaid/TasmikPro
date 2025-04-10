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

// Fetch recent announcements for the teacher
$recentAnnouncements = $conn->query("SELECT title, message, target_audience, created_at FROM announcement WHERE target_audience = 'teacher' OR target_audience = 'all' ORDER BY created_at DESC LIMIT 5");

// Fetch recent students assigned to the teacher
$recentStudents = $conn->query("SELECT firstname, lastname, class FROM student JOIN users ON student.userid = users.userid WHERE halaqahid = '$halaqahId' ORDER BY users.createdat DESC LIMIT 5");
?>

<div class="container">
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Dashboard</h3>
                <h6 class="op-7 mb-2">Teacher Dashboard</h6>
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
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Classes</p>
                                    <h4 class="card-title"><?php echo $totalClasses; ?></h4>
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
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Announcements</p>
                                    <h4 class="card-title"><?php echo $totalAnnouncements; ?></h4>
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
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Form</p>
                                    <h4 class="card-title"><?php echo $form; ?></h4>
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
                            <div class="card-title">Statistics</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="userStatsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- Recent Students -->
            <div class="col-md-4">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Students</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-list py-0">
                            <?php while ($student = $recentStudents->fetch_assoc()): ?>
                                <div class="item-list">
                                    <div class="avatar">
                                        <span class="avatar-title rounded-circle border border-white bg-primary">
                                            <?php echo strtoupper($student['firstname'][0]); ?>
                                        </span>
                                    </div>
                                    <div class="info-user ms-3">
                                        <div class="username"><?php echo ucwords(htmlspecialchars($student['firstname'] . ' ' . $student['lastname'])); ?></div>
                                        <div class="status">Class: <?php echo htmlspecialchars($student['class']); ?></div>
                                    </div>
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

<?php
$conn->close();
?>