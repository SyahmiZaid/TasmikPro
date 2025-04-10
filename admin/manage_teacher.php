<?php
$pageTitle = "Manage Teacher";
$breadcrumb = "Pages / Manage Teacher";
include '../include/header.php';
require_once '../database/db_connection.php';

// Fetch teacher data from the database
$stmt = $conn->prepare("
    SELECT 
    teacher.teacherid, 
    CONCAT(users.firstname, ' ', users.lastname) AS teacher_name, 
    users.email, 
    teacher.halaqahid,
    teacher.gender
FROM 
    teacher
JOIN 
    users 
ON 
    teacher.userid = users.userid
");
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Teacher Records</h4>
                            <a href="add_teacher.php" class="btn btn-primary btn-round ms-auto">
                                <i class="fa fa-plus"></i>
                                Add Teacher
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="basic-datatables" class="display table table-striped table-hover">
                                <thead style="background-color: #343a40; color: white;">
                                    <tr>
                                        <th style="width: 15%; background-color: #343a40; color: white;">Teacher ID</th>
                                        <th style="background-color: #343a40; color: white;">Teacher Name</th>
                                        <th style="background-color: #343a40; color: white;">Halaqah ID</th>
                                        <th style="width: 10%; background-color: #343a40; color: white;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['teacherid']); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['teacher_name'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['halaqahid']); ?></td>
                                            <td>
                                                <div class="form-button-action">
                                                    <!-- View Button -->
                                                    <a href="../student/view_user.php?id=<?php echo $row['teacherid']; ?>&role=teacher" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    </button>
                                                    <!-- Edit Button -->
                                                    <a href="edit_teacher.php?id=<?php echo $row['teacherid']; ?>" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <!-- Remove Button -->
                                                    <button type="button" data-bs-toggle="tooltip" title="Remove" class="btn btn-link btn-danger remove-teacher">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </div>
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
    </div>
</div>

<!-- Modal for displaying teacher details -->
<div class="modal fade" id="teacherDetailModal" tabindex="-1" role="dialog" aria-labelledby="teacherDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="teacherDetailModalLabel">Teacher Details</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Teacher ID:</strong> <span id="modalTeacherId"></span></p>
                <p><strong>Name:</strong> <span id="modalTeacherName"></span></p>
                <p><strong>Email:</strong> <span id="modalTeacherEmail"></span></p>
                <p><strong>Halaqah ID:</strong> <span id="modalHalaqahId"></span></p>
                <p><strong>Gender:</strong> <span id="modalGender"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

        // Handle remove teacher button click
        $('.remove-teacher').click(function(event) {
            event.stopPropagation(); // Prevent the row click event from triggering
            var teacherId = $(this).closest('tr').data('id');

            if (confirm('Are you sure you want to remove this teacher?')) {
                // Perform the remove action (e.g., send an AJAX request to remove the teacher)
                $.post('remove_teacher.php', {
                    teacherid: teacherId
                }, function(response) {
                    alert(response);
                    location.reload();
                });
            }
        });
    });
</script>