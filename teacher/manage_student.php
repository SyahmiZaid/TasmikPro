<?php
$pageTitle = "Manage Student";
$breadcrumb = "Pages / Manage Student";
include '../include/header.php';

// Assuming the teacher's halaqah ID is stored in the session
$teacher_halaqah_id = $_SESSION['halaqahid'];

// Get halaqah name
$stmt = $conn->prepare("SELECT halaqahname FROM halaqah WHERE halaqahid = ?");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$stmt->bind_result($halaqah_name);
$stmt->fetch();
$stmt->close();

// Get total number of students
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM student WHERE halaqahid = ?");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$stmt->bind_result($total_students);
$stmt->fetch();
$stmt->close();

// Get form and gender information for current halaqah
$stmt = $conn->prepare("SELECT form, gender FROM student WHERE halaqahid = ? LIMIT 1");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$result_details = $stmt->get_result();
$halaqah_details = $result_details->fetch_assoc();
$halaqah_form = isset($halaqah_details['form']) ? $halaqah_details['form'] : 'N/A';
$halaqah_gender = isset($halaqah_details['gender']) ? $halaqah_details['gender'] : 'N/A';
$stmt->close();

// Get class distribution
$stmt = $conn->prepare("SELECT class, COUNT(*) as count FROM student WHERE halaqahid = ? GROUP BY class ORDER BY class");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$class_result = $stmt->get_result();
$class_data = [];
while ($row = $class_result->fetch_assoc()) {
    $class_data[$row['class']] = $row['count'];
}
$stmt->close();

// Fetch student data from the database for the teacher's halaqah
// Note: Added users.userid to the SELECT statement
$stmt = $conn->prepare("
    SELECT 
        student.studentid, 
        users.userid,
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
    WHERE 
        student.halaqahid = ?
");
$stmt->bind_param("s", $teacher_halaqah_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Include Chart.js for the charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>
        
        <!-- Dashboard Cards - 4 Card Layout -->
        <div class="row mb-4">
            <!-- Halaqah Name Card -->
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-book-open"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Halaqah Name</p>
                                    <h4 class="card-title"><?php echo $halaqah_name ?? 'N/A'; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Card -->
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Form</p>
                                    <h4 class="card-title">Form <?php echo $halaqah_form; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Students Card -->
            <div class="col-md-3">
                <div class="card card-stats card-round">
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
                                    <h4 class="card-title"><?php echo $total_students; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Distribution Card -->
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Class Distribution</p>
                                    <div class="small" style="max-height: 50px; overflow-y: auto;">
                                        <?php 
                                        $top_classes = array_slice($class_data, 0, 3, true);
                                        foreach ($top_classes as $class => $count): 
                                        ?>
                                            <div class="d-flex justify-content-between">
                                                <span><?php echo ucwords($class); ?>:</span>
                                                <span class="font-weight-bold"><?php echo $count; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dual Charts Row -->
        <div class="row mb-4">
            <!-- Class Distribution Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Class Distribution</div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 240px;">
                            <canvas id="classDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Chart Card (Empty) -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Chart Area 2</div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 240px; display: flex; align-items: center; justify-content: center;">
                            <div class="text-center text-muted">
                                <i class="fas fa-chart-pie fa-3x mb-3"></i>
                                <p>Chart area ready for future content</p>
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
                                        <th style="background-color: #343a40; color: white;">Class</th>
                                        <th style="width: 15%; background-color: #343a40; color: white;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['studentid']); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['student_name'])); ?></td>
                                            <td><?php echo ucwords(htmlspecialchars($row['class'])); ?></td>
                                            <td>
                                                <div class="form-button-action">
                                                    <a href="../student/view_user.php?id=<?php echo $row['studentid']; ?>&role=student" class="btn btn-link btn-info btn-lg" data-bs-toggle="tooltip" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="edit_student.php?id=<?php echo $row['studentid']; ?>" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <button type="button" data-studentid="<?php echo $row['studentid']; ?>" data-bs-toggle="tooltip" title="Remove" class="btn btn-link btn-danger remove-student">
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

        // Handle remove student button click
        $('.remove-student').click(function() {
            var studentId = $(this).data('studentid');

            if (confirm('Are you sure you want to remove this student?')) {
                $.post('remove_student.php', {
                    studentid: studentId
                }, function(response) {
                    alert(response);
                    location.reload();
                });
            }
        });
        
        // Class distribution pie chart
        var classCtx = document.getElementById('classDistributionChart').getContext('2d');
        var classLabels = <?php echo json_encode(array_keys($class_data)); ?>;
        var classCounts = <?php echo json_encode(array_values($class_data)); ?>;
        
        // Custom color palette
        var colorPalette = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
            '#6f42c1', '#5a5c69', '#76a5af', '#2c9faf', '#cc5b22',
            '#a3c2e3', '#6ed8c2', '#9ce0f5', '#ffe8a3', '#f8c0bc'
        ];
        
        var backgroundColors = classLabels.map((_, i) => colorPalette[i % colorPalette.length]);
        
        var classChart = new Chart(classCtx, {
            type: 'pie',
            data: {
                labels: classLabels.map(cls => cls.charAt(0).toUpperCase() + cls.slice(1)),
                datasets: [{
                    data: classCounts,
                    backgroundColor: backgroundColors,
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleColor: '#6e707e',
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' students (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
</script>