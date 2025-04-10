<?php
$pageTitle = "VLE - Teacher Portal";
$breadcrumb = "Pages / VLE - Teacher Portal";
include '../include/header.php';

// Database connection
require_once '../database/db_connection.php';

// Fetch all courses from the vle_courses table
$query = "SELECT * FROM vle_courses";
$result = $conn->query($query);
$courses = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <!-- Teacher Dashboard -->
        <div class="row">
            <!-- Welcome Message & Stats -->
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary">
                        <h4 class="card-title mb-0" style="color: white">Teacher Dashboard</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-3">Welcome back, <?php echo isset($teacherName) ? $teacherName : 'Teacher, Aiman Haziq'; ?>!</h5>
                                <p>You have <span class="badge badge-danger">6</span> assignments pending review, <span class="badge badge-warning">3</span> tasmik sessions scheduled today, and <span class="badge badge-info">8</span> new messages from students.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Cards -->
            <div class="col-md-3">
                <div class="card card-stats mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="col-7 d-flex align-items-center">
                                <div class="numbers">
                                    <p class="card-category">Total Students</p>
                                    <h4 class="card-title">45</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-success">
                                    <i class="fas fa-book-open"></i>
                                </div>
                            </div>
                            <div class="col-7 d-flex align-items-center">
                                <div class="numbers">
                                    <p class="card-category">Active Courses</p>
                                    <h4 class="card-title">2</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-warning">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                            <div class="col-7 d-flex align-items-center">
                                <div class="numbers">
                                    <p class="card-category">Pending Tasks</p>
                                    <h4 class="card-title">12</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-danger">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                            </div>
                            <div class="col-7 d-flex align-items-center">
                                <div class="numbers">
                                    <p class="card-category">At-Risk Students</p>
                                    <h4 class="card-title">3</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Sessions & Tasks -->
        <div class="row">
            <!-- Tasks & Assignments -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Pending Tasks</h4>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Filter
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="#">All Tasks</a>
                                <a class="dropdown-item" href="#">Assignments to Grade</a>
                                <a class="dropdown-item" href="#">Tasmik Reports</a>
                                <a class="dropdown-item" href="#">Administrative</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Course</th>
                                        <th>Priority</th>
                                        <th>Deadline</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Grade Tahriri Assignments (15)</td>
                                        <td>Hifz Al Quran</td>
                                        <td><span class="badge badge-danger">High</span></td>
                                        <td>Today</td>
                                        <td><button class="btn btn-sm btn-primary">Start</button></td>
                                    </tr>
                                    <tr>
                                        <td>Review Quiz Submissions (22)</td>
                                        <td>Maharat Al Quran</td>
                                        <td><span class="badge badge-warning">Medium</span></td>
                                        <td>Tomorrow</td>
                                        <td><button class="btn btn-sm btn-primary">Start</button></td>
                                    </tr>
                                    <tr>
                                        <td>Prepare Progress Reports</td>
                                        <td>All Courses</td>
                                        <td><span class="badge badge-warning">Medium</span></td>
                                        <td>Mar 23, 2025</td>
                                        <td><button class="btn btn-sm btn-primary">Start</button></td>
                                    </tr>
                                    <tr>
                                        <td>Create New Quiz: Tajweed Rules</td>
                                        <td>Maharat Al Quran</td>
                                        <td><span class="badge badge-info">Low</span></td>
                                        <td>Mar 25, 2025</td>
                                        <td><button class="btn btn-sm btn-primary">Start</button></td>
                                    </tr>
                                    <tr>
                                        <td>Update Lesson Plan</td>
                                        <td>Hifz Al Quran</td>
                                        <td><span class="badge badge-info">Low</span></td>
                                        <td>Mar 28, 2025</td>
                                        <td><button class="btn btn-sm btn-primary">Start</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Today's Schedule</h4>
                        <div>
                            <button class="btn btn-sm btn-primary mr-2"><i class="fas fa-calendar-alt mr-1"></i> View Calendar</button>
                            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus mr-1"></i> Add</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="timeline">
                            <li class="timeline-item">
                                <div class="timeline-time">8:30 AM</div>
                                <h6 class="timeline-title">Tasmik Session: Group A</h6>
                                <p class="timeline-text">Surah Al-Baqarah (Verse 1-50) • 5 students</p>
                                <div>
                                    <span class="badge badge-primary">Hifz Al Quran</span>
                                    <span class="badge badge-secondary">Room 103</span>
                                </div>
                            </li>
                            <li class="timeline-item">
                                <div class="timeline-time">10:00 AM</div>
                                <h6 class="timeline-title">Qiraat Lesson</h6>
                                <p class="timeline-text">Introduction to Tajweed Rules • 12 students</p>
                                <div>
                                    <span class="badge badge-primary">Maharat Al Quran</span>
                                    <span class="badge badge-secondary">Room 205</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses & Student Performance -->
        <!-- My Courses -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">My Courses</h4>
                        <a href="add_course.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus mr-1"></i> Create New Course
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($courses)) { ?>
                                <?php foreach ($courses as $course) { ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <img src="../assets/img/courses/default.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($course['course_name']); ?>" onerror="this.src='../assets/img/blogpost.jpg'">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent border-0 d-flex justify-content-between">
                                                <a href="course.php?courseid=<?php echo $course['courseid']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye mr-1"></i> View Details</a>
                                                <a href="#" class="btn btn-success btn-sm"><i class="fas fa-users mr-1"></i> Manage Students</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <div class="col-md-12">
                                    <p class="text-muted text-center">No courses found. Create a new course to get started.</p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Performance & Analytics -->
        <div class="row">
            <!-- Class Performance -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Student Performance Analytics</h4>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6>Hifz Al Quran - Performance Metrics</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Average Completion Rate</td>
                                                <td>68%</td>
                                            </tr>
                                            <tr>
                                                <td>Average Tasmik Score</td>
                                                <td>7.8/10</td>
                                            </tr>
                                            <tr>
                                                <td>Attendance Rate</td>
                                                <td>92%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Maharat Al Quran - Performance Metrics</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Average Quiz Score</td>
                                                <td>76%</td>
                                            </tr>
                                            <tr>
                                                <td>Average Practical Assessment</td>
                                                <td>8.2/10</td>
                                            </tr>
                                            <tr>
                                                <td>Attendance Rate</td>
                                                <td>88%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Requiring Attention -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Students Requiring Attention</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Ahmed Abdullah</h6>
                                    <small class="text-muted">Hifz Al Quran • Missed 3 tasmik sessions</small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <a class="dropdown-item" href="#">View Profile</a>
                                        <a class="dropdown-item" href="#">Send Message</a>
                                        <a class="dropdown-item" href="#">Schedule Meeting</a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Fatima Hassan</h6>
                                    <small class="text-muted">Maharat Al Quran • Low quiz scores (below 60%)</small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                        <a class="dropdown-item" href="#">View Profile</a>
                                        <a class="dropdown-item" href="#">Send Message</a>
                                        <a class="dropdown-item" href="#">Schedule Meeting</a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Yusef Mohammad</h6>
                                    <small class="text-muted">Hifz Al Quran • Struggling with memorization</small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                        <a class="dropdown-item" href="#">View Profile</a>
                                        <a class="dropdown-item" href="#">Send Message</a>
                                        <a class="dropdown-item" href="#">Schedule Meeting</a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item text-center">
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-user-shield mr-1"></i> View All At-Risk Students</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js script for the performance chart -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('performanceChart')) {
            var ctx = document.getElementById('performanceChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['January', 'February', 'March', 'April', 'May'],
                    datasets: [{
                        label: 'Hifz Al Quran',
                        data: [72, 68, 74, 77, 82],
                        backgroundColor: 'rgba(40, 167, 69, 0.2)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        tension: 0.4
                    }, {
                        label: 'Maharat Al Quran',
                        data: [65, 70, 68, 72, 76],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(0, 123, 255, 1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 50,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Average Score (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Course Performance Trends (2025)',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<!-- Student Management Section -->
<div class="container" style="margin-top: -50px;">
    <div class="page-inner">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Student Management</h4>
                        <div>
                            <button class="btn btn-sm btn-primary mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                            <button class="btn btn-sm btn-success mr-2"><i class="fas fa-user-plus mr-1"></i> Add Student</button>
                            <button class="btn btn-sm btn-info"><i class="fas fa-file-export mr-1"></i> Export</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student Name</th>
                                        <th>Courses</th>
                                        <th>Progress</th>
                                        <th>Last Activity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>ST-001</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm mr-2">
                                                    <img src="../assets/img/profile.jpg" alt="Student Avatar" class="avatar-img rounded-circle" onerror="this.src='../assets/img/profile-placeholder.jpg'">
                                                </div>
                                                <div>
                                                    Ahmed Abdullah
                                                    <small class="d-block text-muted">ahmed.abd@example.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Hifz Al Quran</td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 120px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: 35%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small>35%</small>
                                        </td>
                                        <td>Mar 20, 2025</td>
                                        <td><span class="badge badge-danger">At Risk</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton7" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton7">
                                                    <a class="dropdown-item" href="#"><i class="fas fa-user mr-1"></i> View Profile</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-chart-line mr-1"></i> Progress Report</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-envelope mr-1"></i> Send Message</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar-plus mr-1"></i> Schedule Meeting</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"><i class="fas fa-user-times mr-1"></i> Remove from Course</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ST-002</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm mr-2">
                                                    <img src="../assets/img/profile.jpg" alt="Student Avatar" class="avatar-img rounded-circle" onerror="this.src='../assets/img/profile-placeholder.jpg'">
                                                </div>
                                                <div>
                                                    Fatima Hassan
                                                    <small class="d-block text-muted">fatima.h@example.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Maharat Al Quran</td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 120px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: 58%" aria-valuenow="58" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small>58%</small>
                                        </td>
                                        <td>Mar 21, 2025</td>
                                        <td><span class="badge badge-warning">Needs Help</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton8" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton8">
                                                    <a class="dropdown-item" href="#"><i class="fas fa-user mr-1"></i> View Profile</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-chart-line mr-1"></i> Progress Report</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-envelope mr-1"></i> Send Message</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar-plus mr-1"></i> Schedule Meeting</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"><i class="fas fa-user-times mr-1"></i> Remove from Course</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ST-003</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm mr-2">
                                                    <img src="../assets/img/profile.jpg" alt="Student Avatar" class="avatar-img rounded-circle" onerror="this.src='../assets/img/profile-placeholder.jpg'">
                                                </div>
                                                <div>
                                                    Yusef Mohammad
                                                    <small class="d-block text-muted">yusef.m@example.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Hifz Al Quran</td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 120px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: 42%" aria-valuenow="42" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small>42%</small>
                                        </td>
                                        <td>Mar 18, 2025</td>
                                        <td><span class="badge badge-warning">Needs Help</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton9" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton9">
                                                    <a class="dropdown-item" href="#"><i class="fas fa-user mr-1"></i> View Profile</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-chart-line mr-1"></i> Progress Report</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-envelope mr-1"></i> Send Message</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar-plus mr-1"></i> Schedule Meeting</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"><i class="fas fa-user-times mr-1"></i> Remove from Course</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ST-004</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm mr-2">
                                                    <img src="../assets/img/profile.jpg" alt="Student Avatar" class="avatar-img rounded-circle" onerror="this.src='../assets/img/profile-placeholder.jpg'">
                                                </div>
                                                <div>
                                                    Ibrahim Khan
                                                    <small class="d-block text-muted">ibrahim.k@example.com</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Hifz Al Quran, Maharat Al Quran</td>
                                        <td>
                                            <div class="progress" style="height: 6px; width: 120px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small>85%</small>
                                        </td>
                                        <td>Mar 21, 2025</td>
                                        <td><span class="badge badge-success">Excellent</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton10" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton10">
                                                    <a class="dropdown-item" href="#"><i class="fas fa-user mr-1"></i> View Profile</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-chart-line mr-1"></i> Progress Report</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-envelope mr-1"></i> Send Message</a>
                                                    <a class="dropdown-item" href="#"><i class="fas fa-calendar-plus mr-1"></i> Schedule Meeting</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"><i class="fas fa-user-times mr-1"></i> Remove from Course</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span class="text-muted">Showing 1 to 4 of 45 entries</span>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>