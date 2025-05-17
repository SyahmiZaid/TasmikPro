<?php
$pageTitle = "Course";
$breadcrumb = "Pages / VLE - Teacher Portal / Course";
include '../include/header.php';

// Get course ID from URL parameter
$courseId = isset($_GET['courseid']) ? $_GET['courseid'] : '';

// Database connection
require_once '../database/db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get success or error messages from the URL
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
$errorMessage = isset($_GET['error']) ? $_GET['error'] : '';

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
    $teacherName = ucwords(strtolower($course['firstname'] . ' ' . $course['lastname']));
    $courseName = ucwords(strtolower($course['course_name']));
} else {
    $teacherName = "Unknown Teacher";
    $courseName = "Course Not Found";
}

// Get assessments for this course
$assessmentSql = "SELECT a.*, t.teacherid, u.firstname, u.lastname 
                 FROM vle_assessments a
                 JOIN teacher t ON a.teacherid = t.teacherid
                 JOIN users u ON t.userid = u.userid
                 WHERE a.courseid = ?
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
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        </div>
        <!-- Banner section -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="custom-banner-container" style="Height: 200px">
                    <div class="custom-banner-bg"></div>
                    <div class="custom-banner-content col-md-8">
                        <h1 class="custom-banner-title"><?php echo htmlspecialchars($courseName); ?></h1>
                        <p class="custom-banner-subtitle"><?php echo htmlspecialchars($teacherName); ?></p>
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
                    <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false">Participants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#pills-contact" role="tab" aria-controls="pills-contact" aria-selected="false">Grades</a>
                </li>
            </ul>
            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                    <div class="mb-3">
                        <a href="create_assignment.php?courseid=<?php echo htmlspecialchars($courseId); ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Assignment
                        </a>
                    </div>

                    <!-- Course Assignments and Assessments -->
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
                                                <?php echo ucfirst(htmlspecialchars($assessment['type'])); ?>: <?php echo htmlspecialchars(ucwords(strtolower($assessment['title']))); ?>
                                            </h4>
                                            <span class="badge bg-<?php echo $assessment['status'] == 'published' ? 'success' : ($assessment['status'] == 'draft' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($assessment['status'])); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <a href="edit_assessment.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="text-warning me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-link text-danger p-0" style="border: none; background: none;" onclick="confirmDelete('<?php echo htmlspecialchars($assessment['assessmentid']); ?>', '<?php echo htmlspecialchars($courseId); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                                                <a href="view_assessment.php?id=<?php echo htmlspecialchars($assessment['assessmentid']); ?>" class="btn custom-view-details mb-2">
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

                <!-- Participants Tab -->
                <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="card-title mb-0">Course Participants</h4>
                        </div>
                        <div class="card-body">
                            <!-- Teacher section -->
                            <h5 class="mb-3">Teacher</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get course teacher
                                        $teacherSql = "SELECT t.teacherid, u.firstname, u.lastname, u.email
                                                      FROM vle_courses c
                                                      JOIN teacher t ON c.created_by = t.teacherid
                                                      JOIN users u ON t.userid = u.userid
                                                      WHERE c.courseid = ?";
                                        $teacherStmt = $conn->prepare($teacherSql);
                                        $teacherStmt->bind_param("s", $courseId);
                                        $teacherStmt->execute();
                                        $teacherResult = $teacherStmt->get_result();

                                        while ($teacher = $teacherResult->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(ucwords(strtolower($teacher['firstname'] . ' ' . $teacher['lastname']))); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                                <td>Teacher</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Students section -->
                            <h5 class="mb-3 mt-4">Students</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="studentsTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Form/Class</th>
                                            <th>Enrolled Since</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get enrolled students
                                        $studentsSql = "SELECT s.studentid, u.firstname, u.lastname, u.email, 
                                                       s.form, s.class, e.enrolled_at
                                                     FROM vle_enrollment e
                                                     JOIN student s ON e.studentid = s.studentid
                                                     JOIN users u ON s.userid = u.userid
                                                     WHERE e.courseid = ?
                                                     ORDER BY u.lastname, u.firstname";
                                        $studentsStmt = $conn->prepare($studentsSql);
                                        $studentsStmt->bind_param("s", $courseId);
                                        $studentsStmt->execute();
                                        $studentsResult = $studentsStmt->get_result();

                                        if ($studentsResult->num_rows > 0) {
                                            while ($student = $studentsResult->fetch_assoc()):
                                        ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['studentid']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucwords(strtolower($student['firstname'] . ' ' . $student['lastname']))); ?></td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['form'] . '/' . $student['class']); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($student['enrolled_at'])); ?></td>
                                                </tr>
                                        <?php endwhile;
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center">No students enrolled in this course.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades Tab -->
                <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="card-title mb-0">Course Grades</h4>
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
                                            <th>Student</th>
                                            <?php
                                            // Get all assessments for this course
                                            $assessmentsSql = "SELECT assessmentid, title, type 
                                                             FROM vle_assessments 
                                                             WHERE courseid = ? AND type != 'note'
                                                             ORDER BY due_date DESC";
                                            $assessmentsStmt = $conn->prepare($assessmentsSql);
                                            $assessmentsStmt->bind_param("s", $courseId);
                                            $assessmentsStmt->execute();
                                            $assessmentsResult = $assessmentsStmt->get_result();

                                            $assessments = [];
                                            if ($assessmentsResult->num_rows > 0) {
                                                while ($assessment = $assessmentsResult->fetch_assoc()) {
                                                    $assessments[] = $assessment;
                                                    echo '<th class="assessment-column" data-type="' . htmlspecialchars($assessment['type']) . '">' .
                                                        htmlspecialchars(ucwords(strtolower($assessment['title']))) .
                                                        '<br><small>(' . ucfirst($assessment['type']) . ')</small></th>';
                                                }
                                                echo '<th class="table-info">Average</th>';
                                            } else {
                                                echo '<th>No assessments found</th>';
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get all enrolled students
                                        $studentsSql = "SELECT s.studentid, u.firstname, u.lastname
                                                     FROM vle_enrollment e
                                                     JOIN student s ON e.studentid = s.studentid
                                                     JOIN users u ON s.userid = u.userid
                                                     WHERE e.courseid = ?
                                                     ORDER BY u.lastname, u.firstname";
                                        $studentsStmt = $conn->prepare($studentsSql);
                                        $studentsStmt->bind_param("s", $courseId);
                                        $studentsStmt->execute();
                                        $studentsResult = $studentsStmt->get_result();

                                        if ($studentsResult->num_rows > 0 && count($assessments) > 0) {
                                            while ($student = $studentsResult->fetch_assoc()) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars(ucwords(strtolower($student['firstname'] . ' ' . $student['lastname']))) . '</td>';

                                                $totalScore = 0;
                                                $scoreCount = 0;

                                                // For each assessment, get this student's submission
                                                foreach ($assessments as $assessment) {
                                                    $submissionSql = "SELECT score, status, is_done, submitted_at 
                                                                     FROM vle_assessment_submissions 
                                                                     WHERE assessmentid = ? AND studentid = ?";
                                                    $submissionStmt = $conn->prepare($submissionSql);
                                                    $submissionStmt->bind_param("ss", $assessment['assessmentid'], $student['studentid']);
                                                    $submissionStmt->execute();
                                                    $submissionResult = $submissionStmt->get_result();
                                                    $submission = $submissionResult->fetch_assoc();

                                                    echo '<td class="assessment-cell" data-type="' . htmlspecialchars($assessment['type']) . '">';

                                                    if ($submission) {
                                                        if ($assessment['type'] === 'exercise' && $submission['status'] === 'graded') {
                                                            echo '<span class="badge bg-success">' . $submission['score'] . '</span>';
                                                            $totalScore += $submission['score'];
                                                            $scoreCount++;
                                                        } elseif (($assessment['type'] === 'tasmik' || $assessment['type'] === 'murajaah') && $submission['is_done']) {
                                                            echo '<span class="badge bg-success">Completed</span>';
                                                            $totalScore += 100; // Counting completed as 100%
                                                            $scoreCount++;
                                                        } elseif ($submission['is_done']) {
                                                            echo '<span class="badge bg-warning">Submitted</span>';
                                                        } else {
                                                            echo '<span class="badge bg-warning">Pending</span>';
                                                        }

                                                        if (isset($submission['submitted_at'])) {
                                                            echo '<br><small>' . date('d M Y', strtotime($submission['submitted_at'])) . '</small>';
                                                        }
                                                    } else {
                                                        echo '<span class="badge bg-danger">Not Submitted</span>';
                                                    }

                                                    echo '</td>';
                                                }

                                                // Calculate average
                                                $average = $scoreCount > 0 ? round($totalScore / $scoreCount) : '-';
                                                echo '<td class="fw-bold">' . $average . '</td>';

                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="' . (count($assessments) + 2) . '" class="text-center">';
                                            if ($studentsResult->num_rows === 0) {
                                                echo 'No students enrolled in this course.';
                                            } else {
                                                echo 'No assessments found for this course.';
                                            }
                                            echo '</td></tr>';
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
            </div>
        </div>
    </div>
</div>

<?php
// Close database connection
$conn->close();
include '../include/footer.php';
?>

<!-- Include SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript for SweetAlert2 Confirmation -->
<script>
    function confirmDelete(assessmentId, courseId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete this assessment.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to delete_assessment.php with the assessment ID and course ID
                window.location.href = `delete_assessment.php?assessment_id=${encodeURIComponent(assessmentId)}&courseid=${encodeURIComponent(courseId)}`;
            }
        });
    }

    // Filter grades table by assessment type
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelect = document.getElementById('filterAssessmentType');
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const type = this.value;
                const cells = document.querySelectorAll('.assessment-column, .assessment-cell');

                if (!type) {
                    // Show all columns
                    cells.forEach(cell => cell.style.display = '');
                } else {
                    // Filter columns by type
                    cells.forEach(cell => {
                        if (cell.dataset.type === type || !cell.dataset.type) {
                            cell.style.display = '';
                        } else {
                            cell.style.display = 'none';
                        }
                    });
                }
            });
        }

        // Capital first letter of names in tables
        setTimeout(capitalizeNames, 100);
    });

    // Function to capitalize names in tables
    function capitalizeNames() {
        // Process participant tables
        const nameElements = document.querySelectorAll('#studentsTable tbody td:nth-child(2), .table tbody td:nth-child(1)');
        nameElements.forEach(element => {
            if (element.innerText) {
                element.innerText = element.innerText.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            }
        });
    }
    
    // Export functions
    function exportTableToCSV(filename) {
        let csv = [];
        const rows = document.querySelectorAll('#gradesTable tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Replace HTML content with text only and clean it
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""'); // escape double quotes
                row.push('"' + data + '"');
            }
            
            csv.push(row.join(','));        
        }
        
        // Download CSV file
        downloadCSV(csv.join('\n'), filename);
    }

    function downloadCSV(csv, filename) {
        const csvFile = new Blob([csv], {type: "text/csv"});
        const downloadLink = document.createElement("a");
        
        // File name
        downloadLink.download = filename;
        
        // Create a link to the file
        downloadLink.href = window.URL.createObjectURL(csvFile);
        
        // Hide download link
        downloadLink.style.display = "none";
        
        // Add the link to DOM
        document.body.appendChild(downloadLink);
        
        // Click download link
        downloadLink.click();
        
        // Remove link from DOM
        document.body.removeChild(downloadLink);
    }
</script>

<!-- Optional: Add DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#studentsTable').DataTable({
            "pageLength": 10,
            "initComplete": function() {
                setTimeout(capitalizeNames, 100); // Apply capitalization after DataTables loads
            }
        });

        $('#gradesTable').DataTable({
            "scrollX": true,
            "pageLength": 10,
            "autoWidth": false,
            "columnDefs": [{
                "width": "180px",
                "targets": 0
            }],
            "initComplete": function(settings, json) {
                // Fix table width after initialization
                $(window).resize(function() {
                    $('#gradesTable').DataTable().columns.adjust().draw();
                });

                // Force resize once for initial sizing
                setTimeout(function() {
                    $(window).trigger('resize');
                    capitalizeNames();
                }, 200);
            }
        });
        
        // Export buttons functionality
        $('#exportCsv').click(function() {
            exportTableToCSV('course_grades.csv');
        });
        
        $('#exportPdf').click(function() {
            // You'll need to add a PDF export library like jsPDF
            alert('PDF export functionality requires additional libraries. Please implement as needed.');
        });

        // Ensure table takes full width on tab change
        $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
            if ($(e.target).attr('href') === "#pills-contact") {
                $('#gradesTable').DataTable().columns.adjust().draw();
            }
        });
    });
</script>

<!-- Enhanced Custom Styles -->
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
        background-color:rgb(47, 78, 141) !important;
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

    .btn-success {
        background-color: #1cc88a;
        border-color: #1cc88a;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border-radius: 8px;
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }

    .btn-success:hover {
        background-color: #1ab67c;
        border-color: #1ab67c;
        box-shadow: 0 0.25rem 0.5rem rgba(28, 200, 138, 0.25);
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
    #gradesTable th,
    #gradesTable td {
        vertical-align: middle;
        text-align: center;
        padding: 1rem 0.75rem;
    }

    /* Enhanced Grades Table Styling */
    #gradesTable {
        border-collapse: separate;
        border-spacing: 0;
        width: 100% !important;
    }
    
    #gradesTable th {
        background-color: #4267b2 !important;
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    #gradesTable th:first-child {
        position: sticky;
        left: 0;
        z-index: 20;
        background-color: #4267b2 !important;
    }
    
    #gradesTable td:first-child {
        position: sticky;
        left: 0;
        background-color: #f8f9fc;
        font-weight: 600;
        z-index: 5;
        border-right: 2px solid #e3e6f0;
    }
    
    #gradesTable th:last-child, 
    #gradesTable td:last-child {
        border-left: 2px solid #e3e6f0;
        background-color: rgba(209, 236, 241, 0.2);
        font-weight: bold;
    }
    
    /* Better badges for grades */
    #gradesTable .badge {
        display: inline-block;
        width: 100%;
        max-width: 120px;
        padding: 0.5rem;
    }
    
    /* Grade info box */
    .alert-info {
        background-color: #f1f8ff;
        border-color: #bee5eb;
        color: #0c5460;
    }

    .table-info {
        background-color: rgba(209, 236, 241, 0.2) !important;
    }

    /* Icon styling */
    .btn-link i.fas.fa-trash {
        font-size: 1.2rem;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    .btn-link i.fas.fa-trash:hover {
        color: #e74a3b;
        transform: scale(1.1);
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

    /* Fix for grades table width */
    .dataTables_wrapper {
        width: 100% !important;
    }

    /* Ensure DataTables doesn't shrink the table */
    .dataTables_scroll,
    .dataTables_scrollBody,
    .dataTables_scrollHead {
        width: 100% !important;
    }

    /* Make student name column wider */
    #gradesTable th:first-child,
    #gradesTable td:first-child {
        min-width: 180px;
    }

    /* Fix tables on mobile */
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>