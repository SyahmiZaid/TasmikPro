<?php
$pageTitle = "Manage Tasmik";
$breadcrumb = "Pages / Manage Tasmik";
include '../include/header.php';

// Get the current teacher's Halaqah ID from the session or database
$teacher_halaqah_id = $_SESSION['halaqahid']; // Assuming the Halaqah ID is stored in the session

// Fetch the number of students with the same Halaqah ID
$stmt = $conn->prepare("SELECT COUNT(*) AS student_count FROM student WHERE halaqahid = ?");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$stmt->bind_result($student_count);
$stmt->fetch();
$stmt->close();

// Fetch the Halaqah name from the database
$stmt = $conn->prepare("SELECT halaqahname FROM halaqah WHERE halaqahid = ?");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$stmt->bind_result($halaqah_name);
$stmt->fetch();
$stmt->close();

// Add this section - Get today's tasmik data
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as today_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
        SUM(CASE WHEN status = 'repeated' THEN 1 ELSE 0 END) as repeated_count
    FROM 
        tasmik
    JOIN 
        student ON tasmik.studentid = student.studentid
    WHERE 
        student.halaqahid = ? AND DATE(tasmik.tasmik_date) = ?
");
$stmt->bind_param("ss", $teacher_halaqah_id, $today);
$stmt->execute();
$result_today = $stmt->get_result();
$today_data = $result_today->fetch_assoc();
$stmt->close();

// Calculate submission stats
$submitted_students = $today_data['today_count'];
$submission_rate = $student_count > 0 ? round(($submitted_students / $student_count) * 100) : 0;
$not_submitted = $student_count - $submitted_students;

// Get list of students who submitted today for the card
$stmt = $conn->prepare("
    SELECT 
        CONCAT(users.firstname, ' ', users.lastname) AS student_name,
        tasmik.status
    FROM 
        tasmik
    JOIN 
        student ON tasmik.studentid = student.studentid
    JOIN 
        users ON student.userid = users.userid
    WHERE 
        student.halaqahid = ? AND DATE(tasmik.tasmik_date) = ?
    ORDER BY tasmik.tasmik_date DESC
    LIMIT 5
");
$stmt->bind_param("ss", $teacher_halaqah_id, $today);
$stmt->execute();
$result_students_today = $stmt->get_result();
$stmt->close();

// Fetch tasmik data from the database where the student's Halaqah ID matches the teacher's Halaqah ID
$stmt = $conn->prepare("
    SELECT 
        tasmik.tasmikid, 
        tasmik.studentid, 
        CONCAT(users.firstname, ' ', users.lastname) AS student_name, 
        tasmik.tasmik_date, 
        tasmik.juzuk, 
        tasmik.start_page, 
        tasmik.end_page, 
        tasmik.start_ayah, 
        tasmik.end_ayah, 
        tasmik.live_conference, 
        tasmik.status,
        tasmik.feedback
    FROM 
        tasmik
    JOIN 
        student 
    ON 
        tasmik.studentid = student.studentid
    JOIN 
        users 
    ON 
        student.userid = users.userid
    WHERE 
        student.halaqahid = ?
");
$stmt->bind_param("s", $teacher_halaqah_id);

// Check if the query executes successfully
if (!$stmt->execute()) {
    die("Query failed: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!-- Include Chart.js for the pie chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Include SweetAlert2 library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add custom styles for the cards -->
<style>
    .card {
        transition: all 0.3s ease;
        border-radius: 0.5rem;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    .bg-light {
        background-color: #f8f9fc !important;
    }

    .list-group-item {
        transition: all 0.2s;
    }

    .list-group-item:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .badge-warning {
        background-color: #f6c23e;
        color: #fff;
    }

    .badge-success {
        background-color: #1cc88a;
    }

    .badge-danger {
        background-color: #e74a3b;
    }

    .badge-light {
        background-color: #f8f9fa;
        color: #212529;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    }

    /* Add these styles for dashboard look */
    .icon-big {
        font-size: 2.2rem;
    }

    .icon-big.icon-primary {
        color: #1572E8;
    }

    .icon-big.icon-info {
        color: #48ABF7;
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

    .card-stats .card-category {
        margin-top: 0;
        font-size: 0.875rem;
        color: #8d9498;
    }

    .card-stats .card-title {
        margin-bottom: 0;
        font-weight: 600;
    }

    .card-stats .col-icon {
        width: 30%;
        display: flex;
        justify-content: center;
    }

    .card-stats .col-stats {
        width: 70%;
    }

    .card-round {
        border-radius: 8px;
    }

    /* Additional modal styles */
    .modal-card-header {
        padding: 1.25rem;
        background: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    .modal-section-title {
        color: #4e73df;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .data-label {
        font-size: 0.8rem;
        color: #858796;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .data-value {
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .status-badge {
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        text-transform: uppercase;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .modal-section {
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .05);
        background: white;
        margin-bottom: 1.5rem;
    }

    .feedback-box {
        background-color: #f8f9fc;
        border-left: 4px solid #4e73df;
        padding: 1rem;
        border-radius: 0.25rem;
    }
</style>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <div class="row">
            <!-- Daily Tasmik Chart Card -->
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm border-0" id="chart-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <h5 class="card-title font-weight-bold">
                                <i class="fas fa-chart-pie text-primary mr-2"></i> Today's Tasmik Participation
                            </h5>
                            <span class="badge badge-primary badge-pill" style="margin-top: 5px;"><?php echo $submission_rate; ?>% Submitted</span>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php if ($student_count > 0): ?>
                                    <div class="chart-container position-relative" style="height:200px;">
                                        <canvas id="participationChart"></canvas>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No students registered in this halaqah.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3">
                                        <h6 class="card-subtitle mb-2 text-muted">Today's Submissions</h6>
                                        <?php if ($result_students_today->num_rows > 0): ?>
                                            <ul class="list-group list-group-flush">
                                                <?php while ($student = $result_students_today->fetch_assoc()): ?>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($student['status']) {
                                                        case 'pending':
                                                            $status_class = 'text-warning';
                                                            break;
                                                        case 'accepted':
                                                            $status_class = 'text-success';
                                                            break;
                                                        case 'repeated':
                                                            $status_class = 'text-danger';
                                                            break;
                                                        default:
                                                            $status_class = 'text-secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <li class="list-group-item bg-transparent border-bottom px-0 py-2">
                                                        <i class="fas fa-user-circle mr-2 <?php echo $status_class; ?>" style="margin-right: 5px; padding-top: 3px"></i>
                                                        <?php echo htmlspecialchars(ucwords($student['student_name'])); ?>
                                                        <span class="float-right badge <?php echo str_replace('text-', 'badge-', $status_class); ?>">
                                                            <?php echo ucfirst($student['status']); ?>
                                                        </span>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted mt-3">No students have submitted tasmik today.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Halaqah Name Card - Dashboard Style -->
                <div class="card card-stats card-round mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-school"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Halaqah</p>
                                    <h4 class="card-title"><?php echo ucwords($halaqah_name); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card for Number of Students - Dashboard Style -->
                <div class="card card-stats card-round mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Students</p>
                                    <h4 class="card-title"><?php echo $student_count; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Tasmik Summary Card - Dashboard Style -->
                <div class="card card-stats card-round">
                    <div class="card-body py-3">
                        <div class="text-center mb-3">
                            <p class="card-category mb-0">Today's Submissions</p>
                        </div>
                        <div class="row">
                            <div class="col-4 text-center" style="margin-bottom: 5px;">
                                <div class="mb-1">
                                    <i class="fas fa-clock text-warning" style="font-size: 1.5rem;"></i>
                                </div>
                                <p class="card-category text-warning mb-0 small">Pending</p>
                                <h5 class="card-title mb-0" style="font-size: 1.1rem;"><?php echo isset($today_data['pending_count']) ? $today_data['pending_count'] : 0; ?></h5>
                            </div>
                            <div class="col-4 text-center">
                                <div class="mb-1">
                                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                </div>
                                <p class="card-category text-success mb-0 small">Accepted</p>
                                <h5 class="card-title mb-0" style="font-size: 1.1rem;"><?php echo isset($today_data['accepted_count']) ? $today_data['accepted_count'] : 0; ?></h5>
                            </div>
                            <div class="col-4 text-center">
                                <div class="mb-1">
                                    <i class="fas fa-redo text-danger" style="font-size: 1.5rem;"></i>
                                </div>
                                <p class="card-category text-danger mb-0 small">Repeated</p>
                                <h5 class="card-title mb-0" style="font-size: 1.1rem;"><?php echo isset($today_data['repeated_count']) ? $today_data['repeated_count'] : 0; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Tasmik Records</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="basic-datatables" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="background-color: #343a40; color: white;">Tasmik ID</th>
                                        <th style="background-color: #343a40; color: white;">Student ID</th>
                                        <th style="background-color: #343a40; color: white;">Student Name</th>
                                        <th style="background-color: #343a40; color: white;">Date</th>
                                        <th style="background-color: #343a40; color: white;">Juzuk</th>
                                        <th style="background-color: #343a40; color: white; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <?php
                                        // Determine the badge class based on the status
                                        $badgeClass = '';
                                        switch ($row['status']) {
                                            case 'pending':
                                                $badgeClass = 'badge-warning';
                                                break;
                                            case 'accepted':
                                                $badgeClass = 'badge-success';
                                                break;
                                            case 'repeated':
                                                $badgeClass = 'badge-danger';
                                                break;
                                            default:
                                                $badgeClass = 'badge-secondary';
                                                break;
                                        }
                                        ?>
                                        <tr class="tasmik-row"
                                            data-id="<?php echo $row['tasmikid']; ?>"
                                            data-studentid="<?php echo $row['studentid']; ?>"
                                            data-studentname="<?php echo $row['student_name']; ?>"
                                            data-date="<?php echo $row['tasmik_date']; ?>"
                                            data-juzuk="<?php echo $row['juzuk']; ?>"
                                            data-startpage="<?php echo $row['start_page']; ?>"
                                            data-endpage="<?php echo $row['end_page']; ?>"
                                            data-startayah="<?php echo $row['start_ayah']; ?>"
                                            data-endayah="<?php echo $row['end_ayah']; ?>"
                                            data-status="<?php echo $row['status']; ?>"
                                            data-feedback="<?php echo htmlspecialchars($row['feedback'] ?? ''); ?>">
                                            <td><?php echo htmlspecialchars($row['tasmikid']); ?></td>
                                            <td><?php echo htmlspecialchars($row['studentid']); ?></td>
                                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tasmik_date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['juzuk']); ?></td>
                                            <td style="text-align: center;"><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
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

<!-- Enhanced Modal for Tasmik Details -->
<div class="modal fade" id="tasmikModal" tabindex="-1" role="dialog" aria-labelledby="tasmikModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <!-- Modal Header with Gradient -->
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; border-radius: 0.5rem 0.5rem 0 0;">
                <h5 class="modal-title" id="tasmikModalLabel">
                    <i class="fas fa-book-open me-2"></i> Tasmik Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body with Sections -->
            <div class="modal-body py-4">
                <input type="hidden" id="tasmikId" value="">

                <!-- Status Badge -->
                <div class="text-center mb-4">
                    <span id="modalStatusBadge" class="badge rounded-pill px-4 py-2" style="font-size: 1rem;"></span>
                </div>

                <div class="row">
                    <!-- Student Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-light py-3">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-user-graduate me-2"></i> Student Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">STUDENT ID</label>
                                    <div class="fw-bold" id="modalStudentId"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">STUDENT NAME</label>
                                    <div class="fw-bold" id="modalStudentName"></div>
                                </div>
                                <div>
                                    <label class="form-label text-muted small">SUBMISSION DATE</label>
                                    <div class="fw-bold" id="modalDate"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasmik Details -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-light py-3">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-quran me-2"></i> Recitation Details
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label text-muted small">JUZUK</label>
                                    <div class="fw-bold" id="modalJuzuk"></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label text-muted small">START PAGE</label>
                                        <div class="fw-bold" id="modalStartPage"></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted small">END PAGE</label>
                                        <div class="fw-bold" id="modalEndPage"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label text-muted small">START AYAH</label>
                                        <div class="fw-bold" id="modalStartAyah"></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted small">END AYAH</label>
                                        <div class="fw-bold" id="modalEndAyah"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback Section -->
                <div id="feedbackSection" style="display: none;">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light py-3">
                            <h6 class="mb-0 text-primary">
                                <i class="fas fa-comment-dots me-2"></i> Teacher's Feedback
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-light rounded" id="modalFeedback" style="border-left: 4px solid #4e73df;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer with Action Buttons -->
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                <button type="button" class="btn btn-success px-4 py-2 rounded-pill shadow-sm" id="acceptBtn">
                    <i class="fas fa-check me-2"></i> Accept Tasmik
                </button>
                <button type="button" class="btn btn-danger px-4 py-2 rounded-pill shadow-sm" id="rejectBtn">
                    <i class="fas fa-times me-2"></i> Reject Tasmik
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });

        // Handle row click to show modal
        $('.tasmik-row').click(function() {
            var tasmikId = $(this).data('id');
            var studentId = $(this).data('studentid');
            var studentName = $(this).data('studentname');
            var date = $(this).data('date');
            var juzuk = $(this).data('juzuk');
            var startPage = $(this).data('startpage');
            var endPage = $(this).data('endpage');
            var startAyah = $(this).data('startayah');
            var endAyah = $(this).data('endayah');
            var status = $(this).data('status');
            var feedback = $(this).data('feedback');

            // Set the status badge appearance based on status
            var badgeClass = '';
            var badgeIcon = '';
            switch (status) {
                case 'pending':
                    badgeClass = 'bg-warning text-dark';
                    badgeIcon = '<i class="fas fa-clock me-2"></i>';
                    break;
                case 'accepted':
                    badgeClass = 'bg-success';
                    badgeIcon = '<i class="fas fa-check-circle me-2"></i>';
                    break;
                case 'repeated':
                    badgeClass = 'bg-danger';
                    badgeIcon = '<i class="fas fa-redo me-2"></i>';
                    break;
                default:
                    badgeClass = 'bg-secondary';
                    badgeIcon = '<i class="fas fa-info-circle me-2"></i>';
            }

            $('#tasmikId').val(tasmikId);
            $('#modalStudentId').text(studentId);
            $('#modalStudentName').text(studentName);
            $('#modalDate').text(date);
            $('#modalJuzuk').text(juzuk);
            $('#modalStartPage').text(startPage);
            $('#modalEndPage').text(endPage);
            $('#modalStartAyah').text(startAyah);
            $('#modalEndAyah').text(endAyah);

            // Set the status badge
            $('#modalStatusBadge').attr('class', 'badge rounded-pill px-4 py-2 ' + badgeClass)
                .html(badgeIcon + status.charAt(0).toUpperCase() + status.slice(1));

            // Handle feedback display
            if (feedback && feedback.trim() !== '') {
                $('#feedbackSection').show();
                $('#modalFeedback').text(feedback);
            } else {
                $('#feedbackSection').hide();
            }

            $('#tasmikModal').modal('show');
        });

        // Handle accept button click in modal
        $('#acceptBtn').click(function() {
            var tasmikId = $('#tasmikId').val();
            var studentName = $('#modalStudentName').text();

            // Close the Bootstrap modal first to prevent conflicts
            $('#tasmikModal').modal('hide');

            // Short delay to ensure modal is fully closed
            setTimeout(function() {
                Swal.fire({
                    title: 'Accept Tasmik',
                    input: 'textarea', // Using SweetAlert's built-in textarea instead of HTML
                    inputLabel: 'Feedback for ' + studentName,
                    inputPlaceholder: 'Provide feedback to the student (optional)',
                    inputAttributes: {
                        'aria-label': 'Type your feedback here'
                    },
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Accept',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Get the feedback text
                        const feedback = result.value || '';

                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Updating tasmik status',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send AJAX request with feedback
                        $.post('../teacher/accept_tasmik.php', {
                                tasmikid: tasmikId,
                                feedback: feedback
                            })
                            .done(function(response) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Tasmik accepted successfully',
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            })
                            .fail(function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Something went wrong',
                                    icon: 'error'
                                });
                            });
                    }
                });
            }, 300); // 300ms delay to ensure modal transition is complete
        });

        // Handle reject button click in modal
        $('#rejectBtn').click(function() {
            var tasmikId = $('#tasmikId').val();
            var studentName = $('#modalStudentName').text();

            // Close the Bootstrap modal first to prevent conflicts
            $('#tasmikModal').modal('hide');

            // Short delay to ensure modal is fully closed
            setTimeout(function() {
                Swal.fire({
                    title: 'Reject Tasmik',
                    input: 'textarea', // Using SweetAlert's built-in textarea
                    inputLabel: 'Feedback for ' + studentName + ' (required)',
                    inputPlaceholder: 'Please provide feedback on why this tasmik is being rejected',
                    inputAttributes: {
                        'aria-label': 'Type your feedback here'
                    },
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Feedback is required when rejecting a tasmik';
                        }
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Reject',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Get the feedback text
                        const feedback = result.value;

                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Updating tasmik status',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send AJAX request with feedback
                        $.post('../teacher/reject_tasmik.php', {
                                tasmikid: tasmikId,
                                feedback: feedback
                            })
                            .done(function(response) {
                                Swal.fire({
                                    title: 'Rejected',
                                    text: 'Tasmik has been rejected with feedback',
                                    icon: 'info'
                                }).then(() => {
                                    location.reload();
                                });
                            })
                            .fail(function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Something went wrong',
                                    icon: 'error'
                                });
                            });
                    }
                });
            }, 300); // 300ms delay to ensure modal transition is complete
        });

        // Initialize the participation chart if students exist
        <?php if ($student_count > 0): ?>
            var ctx = document.getElementById('participationChart').getContext('2d');
            var chartData = {
                labels: ['Submitted', 'Not Submitted'],
                datasets: [{
                    data: [
                        <?php echo $submitted_students; ?>,
                        <?php echo $not_submitted; ?>
                    ],
                    backgroundColor: ['#a3e0a3', '#f8f9fc'],
                    borderColor: ['#a3e0a3', '#e9ecef'],
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            };

            var participationChart = new Chart(ctx, {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.7)',
                            bodyFont: {
                                size: 14
                            },
                            callbacks: {
                                label: function(tooltipItem) {
                                    var dataset = tooltipItem.dataset;
                                    var currentValue = dataset.data[tooltipItem.dataIndex];
                                    var percentage = Math.round((currentValue / <?php echo max(1, $student_count); ?>) * 100);
                                    return tooltipItem.label + ': ' + currentValue + ' students (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                },
            });
        <?php endif; ?>
    });
</script>