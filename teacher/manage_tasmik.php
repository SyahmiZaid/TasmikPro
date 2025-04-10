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
    tasmik.status
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

<body>
    <div class="container">
        <div class="page-inner">
            <div class="page-header">
                <h4 class="page-title"><?php echo $pageTitle; ?></h4>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <!-- Card for Pai Chart -->
                    <div class="card mb-4" id="chart-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-left align-items-center">
                                <div class="icon-big text-center icon-primary">
                                    <i class="fas fa-chart-pie fa-3x"></i>
                                </div>
                                <div class="ml-3" style="margin-left: 20px;">
                                    <p class="card-category">Chart</p>
                                    <h4 class="card-title">Content</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- Card for Halaqah Name -->
                    <div class="card mb-4" id="halaqah-name-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="icon-big text-center icon-primary">
                                    <i class="fas fa-school fa-3x"></i>
                                </div>
                                <div class="ml-3" style="margin-left: 20px;">
                                    <p class="card-category">Halaqah Name</p>
                                    <h4 class="card-title"><?php echo ucwords($halaqah_name); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Card for Number of Students -->
                    <div class="card mb-4" id="student-count-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="icon-big text-center icon-primary">
                                    <i class="fas fa-users fa-3x"></i>
                                </div>
                                <div class="ml-3" style="margin-left: 20px;">
                                    <p class="card-category">Number of Students</p>
                                    <h4 class="card-title"><?php echo $student_count; ?></h4>
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
                                            <tr class="tasmik-row" data-id="<?php echo $row['tasmikid']; ?>" data-studentid="<?php echo $row['studentid']; ?>" data-studentname="<?php echo $row['student_name']; ?>" data-date="<?php echo $row['tasmik_date']; ?>" data-juzuk="<?php echo $row['juzuk']; ?>" data-startpage="<?php echo $row['start_page']; ?>" data-endpage="<?php echo $row['end_page']; ?>" data-startayah="<?php echo $row['start_ayah']; ?>" data-endayah="<?php echo $row['end_ayah']; ?>" data-status="<?php echo $row['status']; ?>">
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
</body>

<!-- Modal for Accept/Reject -->
<div class="modal fade" id="tasmikModal" tabindex="-1" role="dialog" aria-labelledby="tasmikModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="tasmikModalLabel">Tasmik Action</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tasmikId" value="">
                <div id="tasmikDetails">
                    <p><strong>Student ID:</strong> <span id="modalStudentId"></span></p>
                    <p><strong>Student Name:</strong> <span id="modalStudentName"></span></p>
                    <p><strong>Date:</strong> <span id="modalDate"></span></p>
                    <p><strong>Juzuk:</strong> <span id="modalJuzuk"></span></p>
                    <p><strong>Start Page:</strong> <span id="modalStartPage"></span></p>
                    <p><strong>End Page:</strong> <span id="modalEndPage"></span></p>
                    <p><strong>Start Ayah:</strong> <span id="modalStartAyah"></span></p>
                    <p><strong>End Ayah:</strong> <span id="modalEndAyah"></span></p>
                    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                </div>
            </div>
            <div class="modal-footer bg-dark">
                <button type="button" class="btn btn-success" id="acceptBtn">Accept</button>
                <button type="button" class="btn btn-danger" id="rejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>

<?php
// Debug statement to check if halaqahid is set correctly
echo "Halaqah ID: " . $teacher_halaqah_id;

include '../include/footer.php'; ?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });

        // Adjust the height of the "chart" card
        var halaqahNameCardHeight = $('#halaqah-name-card').outerHeight();
        var studentCountCardHeight = $('#student-count-card').outerHeight();
        var combinedHeight = halaqahNameCardHeight + studentCountCardHeight + 20; // Add 20 for padding
        $('#chart-card').css('height', combinedHeight);

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

            $('#tasmikId').val(tasmikId);
            $('#modalStudentId').text(studentId);
            $('#modalStudentName').text(studentName);
            $('#modalDate').text(date);
            $('#modalJuzuk').text(juzuk);
            $('#modalStartPage').text(startPage);
            $('#modalEndPage').text(endPage);
            $('#modalStartAyah').text(startAyah);
            $('#modalEndAyah').text(endAyah);
            $('#modalStatus').text(status.charAt(0).toUpperCase() + status.slice(1));

            $('#tasmikModal').modal('show');
        });

        // Handle accept button click in modal
        $('#acceptBtn').click(function() {
            var tasmikId = $('#tasmikId').val();
            if (confirm('Are you sure you want to accept this Tasmik?')) {
                // Perform the accept action (e.g., send an AJAX request to update the status)
                $.post('../teacher/accept_tasmik.php', { tasmikid: tasmikId }, function(response) {
                    alert(response);
                    location.reload();
                });
            }
        });

        // Handle reject button click in modal
        $('#rejectBtn').click(function() {
            var tasmikId = $('#tasmikId').val();
            if (confirm('Are you sure you want to reject this Tasmik?')) {
                // Perform the reject action (e.g., send an AJAX request to update the status)
                $.post('../teacher/reject_tasmik.php', { tasmikid: tasmikId }, function(response) {
                    alert(response);
                    location.reload();
                });
            }
        });
    });
</script>