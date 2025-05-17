<!-- filepath: c:\Users\Asus\OneDrive\Desktop\FYP\(4) Development\TasmikPro\vle-portal\course_student.php -->
<?php
$pageTitle = "Course";
$breadcrumb = "Pages / VLE - Student Portal / Course";
include '../include/header.php';

// Get the logged-in user's ID
$userid = $_SESSION['userid'] ?? ''; // Assuming userid is stored in session

// Database connection
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the student ID using the user ID
$studentSql = "SELECT studentid FROM student WHERE userid = ?";
$studentStmt = $conn->prepare($studentSql);
$studentStmt->bind_param("s", $userid);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows > 0) {
    $studentRow = $studentResult->fetch_assoc();
    $studentId = $studentRow['studentid'];
} else {
    die("Student ID not found for the logged-in user.");
}

// Get course ID from URL parameter
$courseId = isset($_GET['courseid']) ? $_GET['courseid'] : '';

// Get course details
$courseSql = "SELECT c.course_name, u.firstname, u.lastname 
              FROM vle_courses c
              JOIN teacher t ON c.created_by = t.teacherid
              JOIN users u ON t.userid = u.userid
              WHERE c.courseid = ?";
$courseStmt = $conn->prepare($courseSql);
$courseStmt->bind_param("s", $courseId);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

if ($courseResult->num_rows > 0) {
    $course = $courseResult->fetch_assoc();
    $teacherName = $course['firstname'] . ' ' . $course['lastname'];
    $courseName = $course['course_name'];
} else {
    $teacherName = "Unknown Teacher";
    $courseName = "Course Not Found";
}

// Get assessments for this course created by the course's teacher
$assessmentSql = "SELECT a.* 
                  FROM vle_assessments a
                  JOIN vle_courses c ON a.courseid = c.courseid
                  WHERE a.courseid = ? AND c.created_by = a.teacherid
                  ORDER BY a.due_date DESC";
$assessmentStmt = $conn->prepare($assessmentSql);
$assessmentStmt->bind_param("s", $courseId);
$assessmentStmt->execute();
$assessmentResult = $assessmentStmt->get_result();

$assessments = [];
while ($row = $assessmentResult->fetch_assoc()) {
    $assessments[] = $row;
}
?>

<div class="container">
    <link rel="stylesheet" href="style.css" />
    <div class="page-inner">
        <!-- Banner section -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="custom-banner-container" style="height: 200px; margin-top: 0; padding-top: 0;">
                    <div class="custom-banner-bg"></div>
                    <div class="custom-banner-content col-md-8">
                        <h1 class="custom-banner-title"><?php echo htmlspecialchars(ucwords($courseName)); ?></h1>
                        <p class="custom-banner-subtitle">Instructor: <?php echo htmlspecialchars(ucwords($teacherName)); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true">Course</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-grades-tab" data-bs-toggle="pill" href="#pills-grades" role="tab" aria-controls="pills-grades" aria-selected="false">Grades</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-announcements-tab" data-bs-toggle="pill" href="#pills-announcements" role="tab" aria-controls="pills-announcements" aria-selected="false">Announcements</a>
                </li>
            </ul>
            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                <!-- Course Assignments and Assessments -->
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                    <?php if (count($assessments) > 0): ?>
                        <?php foreach ($assessments as $assessment): ?>
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <!-- Add Icon Based on Assessment Type -->
                                            <?php
                                            $icon = '';
                                            switch ($assessment['type']) {
                                                case 'exercise':
                                                    $icon = '<i class="fas fa-clipboard-list me-2" style="color: #007bff;"></i>'; // Exercise icon
                                                    break;
                                                case 'note':
                                                    $icon = '<i class="fas fa-sticky-note me-2" style="color: #ffc107;"></i>'; // Note icon
                                                    break;
                                                case 'tasmik':
                                                    $icon = '<i class="fas fa-book-reader me-2" style="color: #28a745;"></i>'; // Tasmik icon
                                                    break;
                                                case 'murajaah':
                                                    $icon = '<i class="fas fa-book me-2" style="color: #17a2b8;"></i>'; // Murajaah icon
                                                    break;
                                                default:
                                                    $icon = '<i class="fas fa-question-circle me-2" style="color: #6c757d;"></i>'; // Default icon
                                                    break;
                                            }
                                            ?>
                                            <!-- Display Icon and Title -->
                                            <h4 class="card-title mb-0 me-2" style="color: black">
                                                <?php echo $icon; ?>
                                                <?php echo ucfirst(htmlspecialchars($assessment['type'])); ?>: <?php echo htmlspecialchars($assessment['title']); ?>
                                            </h4>
                                            <span class="badge bg-<?php echo $assessment['status'] == 'published' ? 'success' : ($assessment['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p><?php echo htmlspecialchars($assessment['description']); ?></p>
                                                <?php if ($assessment['type'] !== 'note'): ?>
                                                    <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($assessment['due_date'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 d-flex justify-content-end align-items-end flex-column">
                                                <?php
                                                // Check if the assessment is marked as done
                                                $isDoneSql = "SELECT is_done FROM vle_assessment_submissions WHERE assessmentid = ? AND studentid = ?";
                                                $isDoneStmt = $conn->prepare($isDoneSql);
                                                $isDoneStmt->bind_param("ss", $assessment['assessmentid'], $studentId);
                                                $isDoneStmt->execute();
                                                $isDoneResult = $isDoneStmt->get_result();
                                                $isDoneRow = $isDoneResult->fetch_assoc();
                                                $isDone = $isDoneRow['is_done'] ?? 0;
                                                ?>
                                                <form id="markAsDoneForm-<?php echo $assessment['assessmentid']; ?>" class="mt-2">
                                                    <input type="hidden" name="assessment_id" value="<?php echo htmlspecialchars($assessment['assessmentid']); ?>">
                                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentId); ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($courseId); ?>">
                                                    <button type="button" class="btn <?php echo $isDone ? 'btn-success' : 'btn-outline-success'; ?>" onclick="toggleMarkAsDone('<?php echo $assessment['assessmentid']; ?>')">
                                                        <?php echo $isDone ? '<i class="fas fa-check"></i> Done' : 'Mark as Done'; ?>
                                                    </button>
                                                </form>
                                                <a href="assessment_submission.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="btn custom-view-details mb-2" style="margin-top: 10px;">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="alert alert-info" role="alert">
                                        No assessments found for this course.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Grades Tab -->
                <div class="tab-pane fade" id="pills-grades" role="tabpanel" aria-labelledby="pills-grades-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="card-title mb-0">My Course Grades</h4>
                        </div>
                        <div class="card-body">
                            <!-- Grade Summary Info Box -->
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <h5 class="mb-0">Grade Information</h5>
                                </div>
                                <p class="mb-1"><span class="badge bg-success">Completed/Graded</span> - Assessment has been completed and graded</p>
                                <p class="mb-1"><span class="badge bg-warning">Submitted/Pending</span> - Assessment has been submitted but awaiting evaluation</p>
                                <p class="mb-1"><span class="badge bg-danger">Not Submitted</span> - Student has not submitted this assessment yet</p>
                                <p class="mb-0"><small>* For Tasmik and Murajaah, completion is marked as 100% for average calculation</small></p>
                            </div>

                            <!-- Filter options -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="filterAssessmentType" class="form-label">Filter by Type:</label>
                                    <select class="form-select" id="filterAssessmentType">
                                        <option value="">All Assessment Types</option>
                                        <option value="exercise">Exercise</option>
                                        <option value="tasmik">Tasmik</option>
                                        <option value="murajaah">Murajaah</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="gradesTable">
                                    <thead>
                                        <tr>
                                            <th>Assessment</th>
                                            <th>Type</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                            <th>Submitted Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get all assessments for this course
                                        $assessmentsSql = "SELECT a.assessmentid, a.title, a.type, a.due_date
                                         FROM vle_assessments a
                                         WHERE a.courseid = ? AND a.type != 'note'
                                         ORDER BY a.due_date DESC";
                                        $assessmentsStmt = $conn->prepare($assessmentsSql);
                                        $assessmentsStmt->bind_param("s", $courseId);
                                        $assessmentsStmt->execute();
                                        $assessmentsResult = $assessmentsStmt->get_result();

                                        $totalScore = 0;
                                        $scoreCount = 0;

                                        if ($assessmentsResult->num_rows > 0) {
                                            while ($assessment = $assessmentsResult->fetch_assoc()) {
                                                // Get this student's submission
                                                $submissionSql = "SELECT score, status, is_done, submitted_at 
                                               FROM vle_assessment_submissions 
                                               WHERE assessmentid = ? AND studentid = ?";
                                                $submissionStmt = $conn->prepare($submissionSql);
                                                $submissionStmt->bind_param("ss", $assessment['assessmentid'], $studentId);
                                                $submissionStmt->execute();
                                                $submissionResult = $submissionStmt->get_result();
                                                $submission = $submissionResult->fetch_assoc();

                                                echo '<tr class="assessment-row" data-type="' . htmlspecialchars($assessment['type']) . '">';
                                                echo '<td>' . htmlspecialchars(ucwords(strtolower($assessment['title']))) . '</td>';
                                                echo '<td>' . ucfirst(htmlspecialchars($assessment['type'])) . '</td>';
                                                echo '<td>' . date('d M Y', strtotime($assessment['due_date'])) . '</td>';

                                                // Status column
                                                echo '<td>';
                                                if ($submission) {
                                                    if ($assessment['type'] === 'exercise' && $submission['status'] === 'graded') {
                                                        echo '<span class="badge bg-success">Graded</span>';
                                                    } elseif (($assessment['type'] === 'tasmik' || $assessment['type'] === 'murajaah') && $submission['is_done']) {
                                                        echo '<span class="badge bg-success">Completed</span>';
                                                    } elseif ($submission['is_done']) {
                                                        echo '<span class="badge bg-warning">Submitted</span>';
                                                    } else {
                                                        echo '<span class="badge bg-warning">Pending</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-danger">Not Submitted</span>';
                                                }
                                                echo '</td>';

                                                // Score column
                                                echo '<td>';
                                                if ($submission) {
                                                    if ($assessment['type'] === 'exercise' && $submission['status'] === 'graded') {
                                                        echo $submission['score'];
                                                        $totalScore += $submission['score'];
                                                        $scoreCount++;
                                                    } elseif (($assessment['type'] === 'tasmik' || $assessment['type'] === 'murajaah') && $submission['is_done']) {
                                                        echo '100';
                                                        $totalScore += 100; // Counting completed as 100%
                                                        $scoreCount++;
                                                    } else {
                                                        echo '-';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                                echo '</td>';

                                                // Submitted date column
                                                echo '<td>';
                                                if ($submission && isset($submission['submitted_at'])) {
                                                    echo date('d M Y', strtotime($submission['submitted_at']));
                                                } else {
                                                    echo '-';
                                                }
                                                echo '</td>';

                                                echo '</tr>';
                                            }

                                            // Add average row
                                            // $average = $scoreCount > 0 ? round($totalScore / $scoreCount) : '-';
                                            // echo '<tr class="table-info font-weight-bold">';
                                            // echo '<td colspan="4" class="text-end fw-bold">Average Score:</td>';
                                            // echo '<td class="fw-bold">' . $average . '</td>';
                                            // echo '<td></td>';
                                            // echo '</tr>';
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center">No assessments found for this course.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Export options -->
                            <div class="mt-4 text-end">
                                <button class="btn btn-sm btn-outline-primary me-2" id="exportCsv">
                                    <i class="fas fa-file-csv me-1"></i> Export as CSV
                                </button>
                                <button class="btn btn-sm btn-outline-danger" id="exportPdf">
                                    <i class="fas fa-file-pdf me-1"></i> Export as PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements Tab -->
                <div class="tab-pane fade" id="pills-announcements" role="tabpanel" aria-labelledby="pills-announcements-tab">
                    <!-- Announcements content here -->
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

<script>
    function toggleMarkAsDone(assessmentId) {
        const form = document.getElementById(`markAsDoneForm-${assessmentId}`);
        const formData = new FormData(form);
        const button = form.querySelector('button');

        // Determine the current state (Done or Mark as Done)
        const isDone = button.classList.contains('btn-success');

        // Add a flag to the form data to indicate the desired state
        formData.append('toggle_state', isDone ? 0 : 1); // 1 = Mark as Done, 0 = Undo

        fetch('mark_as_done.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                } else {
                    throw new Error('Failed to toggle mark as done');
                }
            })
            .then(data => {
                if (data.trim() === 'Success') {
                    // Toggle the button state dynamically
                    if (isDone) {
                        button.classList.remove('btn-success');
                        button.classList.add('btn-outline-success');
                        button.innerHTML = 'Mark as Done';
                    } else {
                        button.classList.remove('btn-outline-success');
                        button.classList.add('btn-success');
                        button.innerHTML = '<i class="fas fa-check"></i> Done';
                    }
                } else {
                    alert('Failed to toggle mark as done: ' + data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Filter table by assessment type
        const filterSelect = document.getElementById('filterAssessmentType');
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const selectedType = this.value;
                const rows = document.querySelectorAll('#gradesTable tbody tr.assessment-row');

                rows.forEach(row => {
                    if (!selectedType || row.getAttribute('data-type') === selectedType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Export as CSV functionality
        document.getElementById('exportCsv').addEventListener('click', function() {
            // Get table data
            const table = document.getElementById('gradesTable');
            let csvContent = "data:text/csv;charset=utf-8,";

            // Add headers
            const headers = [];
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.innerText);
            });
            csvContent += headers.join(',') + '\r\n';

            // Add rows
            table.querySelectorAll('tbody tr').forEach(tr => {
                const rowData = [];
                tr.querySelectorAll('td').forEach(td => {
                    // Clean up the data (remove badges, etc.)
                    let cellText = td.innerText.trim().replace(/(\r\n|\n|\r)/gm, " ");
                    rowData.push('"' + cellText + '"');
                });
                csvContent += rowData.join(',') + '\r\n';
            });

            // Download CSV
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "my_grades.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>

<!-- Custom Styles -->
<style>
    /* Banner styling */
    .custom-banner-container {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        position: relative;
    }

    .custom-banner-bg {
        background: url('/assets/img/Vle-Banner.jpg') no-repeat center center;
        background-size: cover;
        height: 100%;
        width: 100%;
        position: absolute;
    }

    .custom-banner-content {
        padding: 2.5rem;
        position: relative;
        z-index: 10;
    }

    .custom-banner-title {
        color: white;
        font-weight: 700;
        font-size: 2.5rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        margin-bottom: 0.5rem;
    }

    .custom-banner-subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.25rem;
    }

    /* Tab styling */
    .nav-pills .nav-link {
        border-radius: 8px;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link.active {
        background-color: #4e73df;
        box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
        transform: translateY(-2px);
    }

    .nav-pills .nav-link:not(.active):hover {
        background-color: #f8f9fc;
        transform: translateY(-1px);
    }

    /* Card styling */
    .card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border-radius: 10px;
        margin-bottom: 1.5rem;
        transition: all 0.2s ease-in-out;
    }

    .card:hover {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.25);
    }

    .card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e3e6f0;
        background-color: #f8f9fc;
        border-top-left-radius: 10px !important;
        border-top-right-radius: 10px !important;
    }

    .card-header.bg-primary {
        background: linear-gradient(135deg, rgb(140, 165, 225) 0%, rgb(45, 75, 175) 100%) !important;
        border-bottom: none;
    }

    .card-header.bg-primary .card-title {
        color: white;
    }

    .card-body {
        padding: 1.25rem;
    }

    /* Table styling */
    .table {
        margin-bottom: 0;
        color: #5a5c69;
        border-color: #e3e6f0;
    }

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #e3e6f0;
    }

    .table thead th {
        font-weight: 600;
        border-bottom: 2px solid #e3e6f0;
        background-color: rgb(47, 78, 141) !important;
        color: white;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fc;
    }

    /* Button styling */
    .custom-view-details {
        background-color: #eef4ff;
        color: #4e73df;
        border: 1px solid #d1e1ff;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        transition: all 0.2s ease;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
    }

    .custom-view-details:hover {
        background-color: #4e73df;
        color: white;
        border-color: #4e73df;
        box-shadow: 0 0.25rem 0.5rem rgba(78, 115, 223, 0.25);
        transform: translateY(-1px);
    }

    /* Success button styling */
    .btn-outline-success {
        background-color: #f8f9fa;
        color: #28a745;
        border: 1px solid #28a745;
        border-radius: 8px;
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }

    .btn-outline-success:hover {
        background-color: #28a745;
        color: #fff;
        box-shadow: 0 0.25rem 0.5rem rgba(40, 167, 69, 0.25);
        transform: translateY(-1px);
    }

    .btn-success {
        background-color: #28a745;
        color: #fff;
        border: 1px solid #28a745;
        border-radius: 8px;
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
        box-shadow: 0 0.25rem 0.5rem rgba(40, 167, 69, 0.25);
        transform: translateY(-1px);
    }

    /* Badge styling */
    .badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .badge.bg-success {
        background-color: #1cc88a !important;
    }

    .badge.bg-warning {
        background-color: #f6c23e !important;
        color: #444 !important;
    }

    .badge.bg-danger {
        background-color: #e74a3b !important;
    }

    /* Table headers and cells */
    .student-grades-table th,
    .student-grades-table td {
        vertical-align: middle;
        text-align: center;
        padding: 1rem 0.75rem;
    }

    /* DataTables customization */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 5px;
        margin: 0 3px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #4e73df;
        border-color: #4e73df;
        color: white !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #eef4ff;
        border-color: #d1e1ff;
        color: #4e73df !important;
    }

    /* Announcements styling */
    .announcement-card {
        transition: transform 0.2s;
    }

    .announcement-card:hover {
        transform: translateY(-5px);
    }

    .announcement-date {
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>