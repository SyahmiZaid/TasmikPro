<?php
$pageTitle = "Manage Halaqah";
$breadcrumb = "Pages / Manage Halaqah";
include '../include/header.php';
require_once '../database/db_connection.php';

// Fetch halaqah data from the database
$stmt = $conn->prepare("
    SELECT 
    halaqah.halaqahid, 
    halaqah.halaqahname AS halaqah_name
FROM 
    halaqah
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
                            <h4 class="card-title">Halaqah Records</h4>
                            <a href="add_halaqah.php" class="btn btn-primary btn-round ms-auto">
                                <i class="fa fa-plus"></i>
                                Add Halaqah
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="basic-datatables" class="display table table-striped table-hover">
                                <thead style="background-color: #343a40; color: white;">
                                    <tr>
                                        <th style="width: 15%; background-color: #343a40; color: white;">Halaqah ID</th>
                                        <th style="background-color: #343a40; color: white;">Halaqah Name</th>
                                        <th style="width: 10%; background-color: #343a40; color: white;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="halaqah-row" data-id="<?php echo $row['halaqahid']; ?>" data-name="<?php echo $row['halaqah_name']; ?>">
                                            <td><?php echo htmlspecialchars($row['halaqahid']); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['halaqah_name'])); ?></td>
                                            <td>
                                                <div class="form-button-action">
                                                    <a href="edit_halaqah.php?id=<?php echo $row['halaqahid']; ?>" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <button type="button" data-bs-toggle="tooltip" title="Remove" class="btn btn-link btn-danger remove-halaqah">
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

<?php include '../include/footer.php'; ?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });

        // Handle remove halaqah button click
        $('.remove-halaqah').click(function() {
            var halaqahId = $(this).closest('tr').data('id');

            if (confirm('Are you sure you want to remove this halaqah?')) {
                // Perform the remove action (e.g., send an AJAX request to remove the halaqah)
                $.post('remove_halaqah.php', {
                    halaqahid: halaqahId
                }, function(response) {
                    alert(response);
                    location.reload();
                });
            }
        });
    });
</script>