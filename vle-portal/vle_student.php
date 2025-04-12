<?php
$pageTitle = "VLE - Student Portal";
$breadcrumb = "Pages / VLE - Student Portal";
include '../include/header.php';

// Start session and get the logged-in user's ID
session_start();
$userid = $_SESSION['userid'] ?? ''; // Assuming userid is stored in session

// Database connection
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the student ID using the user ID
$studentSql = "SELECT studentid FROM student WHERE userid = ?";
$studentStmt = $conn->prepare($studentSql);
if (!$studentStmt) {
    die("Query preparation failed: " . $conn->error);
}
$studentStmt->bind_param("s", $userid);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows > 0) {
    $studentRow = $studentResult->fetch_assoc();
    $studentId = $studentRow['studentid'];
} else {
    die("Student ID not found for the logged-in user.");
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <!-- Welcome Message -->
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Welcome to Your Learning Portal</h4>
                    </div>
                    <div class="card-body">
                        <p>Welcome back, <?php echo isset($userName) ? $userName : 'Student'; ?>! You have <span class="badge badge-info">3</span> upcoming deadlines and <span class="badge badge-warning">5</span> new messages.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Progress -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Your Progress</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Hifz Al Quran</span>
                            <span>75%</span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Maharat Al Quran</span>
                            <span>45%</span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h4 class="card-title">Upcoming Deadlines</h4>
                        <button class="btn btn-sm btn-primary"><i class="fas fa-calendar-alt mr-1"></i> View Calendar</button>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Tasmik: Surah Al-Baqarah</h6>
                                    <small class="text-muted">Hifz Al Quran</small>
                                </div>
                                <span class="badge badge-danger">2 days left</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Quiz: Qiraat</h6>
                                    <small class="text-muted">Maharat Al Quran</small>
                                </div>
                                <span class="badge badge-warning">5 days left</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Assignment: Tahriri</h6>
                                    <small class="text-muted">Hifz Al Quran</small>
                                </div>
                                <span class="badge badge-info">1 week left</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Courses Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">My Courses</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            // Fetch courses the student is enrolled in
                            $courseSql = "SELECT c.courseid, c.course_name, c.description, u.firstname AS teacher_firstname, u.lastname AS teacher_lastname
                                          FROM vle_courses c
                                          JOIN vle_enrollment e ON c.courseid = e.courseid
                                          JOIN teacher t ON c.created_by = t.teacherid
                                          JOIN users u ON t.userid = u.userid
                                          WHERE e.studentid = ?";
                            $courseStmt = $conn->prepare($courseSql);
                            $courseStmt->bind_param("s", $studentId);
                            $courseStmt->execute();
                            $courseResult = $courseStmt->get_result();

                            if ($courseResult->num_rows > 0):
                                while ($course = $courseResult->fetch_assoc()): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <img src="../assets/img/courses/default.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($course['course_name']); ?>" onerror="this.src='../assets/img/blogpost.jpg'">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                                <p class="card-text text-muted">Instructor: <?php echo htmlspecialchars($course['teacher_firstname'] . ' ' . $course['teacher_lastname']); ?></p>
                                                <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent border-0">
                                                <a href="course_student.php?courseid=<?php echo htmlspecialchars($course['courseid']); ?>" class="btn btn-primary btn-sm btn-block"><i class="fas fa-eye mr-1"></i> View Course</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <div class="col-md-12">
                                    <p class="text-muted text-center">You are not enrolled in any courses yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Announcements Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Announcements</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-bullhorn mr-2"></i> System Maintenance</h5>
                            <p>The VLE will be unavailable on Sunday, March 16th from 2:00 AM to 4:00 AM for scheduled maintenance.</p>
                            <small class="text-muted">Posted: March 14, 2025</small>
                        </div>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-calendar-alt mr-2"></i> New Course Available</h5>
                            <p>A new course on "Advanced Data Visualization" is now open for enrollment. Limited spots available!</p>
                            <small class="text-muted">Posted: March 10, 2025</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Close database connection
$conn->close();
include '../include/footer.php';
?>