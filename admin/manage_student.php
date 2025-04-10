<?php
$pageTitle = "Manage Student";
$breadcrumb = "Pages / Manage Student";
include '../include/header.php';
require_once '../database/db_connection.php';

// Fetch student data from the database
$stmt = $conn->prepare("
    SELECT 
    student.studentid, 
    CONCAT(users.firstname, ' ', users.lastname) AS student_name, 
    users.email, 
    student.halaqahid,
    student.parentid,
    student.form,
    student.class,
    student.ic,
    student.gender
FROM 
    student
JOIN 
    users 
ON 
    student.userid = users.userid
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
                            <h4 class="card-title">Student Records</h4>
                            <a href="add_student.php" class="btn btn-primary btn-round ms-auto">
                                <i class="fa fa-plus"></i>
                                Add Student
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="basic-datatables" class="display table table-striped table-hover">
                                <thead style="background-color: #343a40; color: white;">
                                    <tr>
                                        <th style="width: 15%; background-color: #343a40; color: white;">Student ID</th>
                                        <th style="background-color: #343a40; color: white;">Student Name</th>
                                        <th style="background-color: #343a40; color: white;">Form</th>
                                        <th style="background-color: #343a40; color: white;">Class</th>
                                        <th style="background-color: #343a40; color: white;">Gender</th>
                                        <th style="width: 10%; background-color: #343a40; color: white;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['studentid']); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['student_name'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['form']); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['class'])); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['gender'])); ?></td>
                                            <td>
                                                <div class="form-button-action">
                                                    <!-- View Button -->
                                                    <a href="../student/view_user.php?id=<?php echo $row['studentid']; ?>&role=student" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <!-- Edit Button -->
                                                    <a href="edit_student.php?id=<?php echo $row['studentid']; ?>" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <!-- Remove Button -->
                                                    <button type="button" data-bs-toggle="tooltip" title="Remove" class="btn btn-link btn-danger remove-student">
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

<!-- Modal for displaying student details -->
<div class="modal fade" id="studentDetailModal" tabindex="-1" role="dialog" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="studentDetailModalLabel">Student Details</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Student ID:</strong> <span id="modalStudentId"></span></p>
                <p><strong>Name:</strong> <span id="modalStudentName"></span></p>
                <p><strong>Email:</strong> <span id="modalStudentEmail"></span></p>
                <p><strong>Halaqah ID:</strong> <span id="modalHalaqahId"></span></p>
                <p><strong>Parent ID:</strong> <span id="modalParentId"></span></p>
                <p><strong>Form:</strong> <span id="modalForm"></span></p>
                <p><strong>Class:</strong> <span id="modalClass"></span></p>
                <p><strong>IC:</strong> <span id="modalIc"></span></p>
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

        // Handle remove student button click
        $('.remove-student').click(function(event) {
            event.stopPropagation(); // Prevent the row click event from triggering
            var studentId = $(this).closest('tr').data('id');

            if (confirm('Are you sure you want to remove this student?')) {
                // Perform the remove action (e.g., send an AJAX request to remove the student)
                $.post('remove_student.php', {
                    studentid: studentId
                }, function(response) {
                    alert(response);
                    location.reload();
                });
            }
        });
    });
</script>