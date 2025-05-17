<?php
$pageTitle = "Tasmik";
$breadcrumb = "Pages  /  <a href='../student/tasmik_form.php' class='no-link-style'>Tasmik</a>";
include '../include/header.php';
require_once '../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the current user ID from the session
    $userid = $_SESSION['userid'];

    // Check if the student ID exists in the student table
    $stmt = $conn->prepare("SELECT studentid FROM student WHERE userid = ?");
    $stmt->bind_param("s", $userid);
    $stmt->execute();
    $stmt->bind_result($studentid);
    $stmt->fetch();
    $stmt->close();

    if (!$studentid) {
        die("Student ID not found for the current user.");
    }

    // Generate unique tasmik ID
    $prefix = "TSK";
    $date = date("ymd"); // Use 'ymd' to get last two digits of the year
    $stmt = $conn->prepare("SELECT COUNT(*) AS tasmik_count FROM tasmik");
    $stmt->execute();
    $stmt->bind_result($tasmik_count);
    $stmt->fetch();
    $stmt->close();
    $tasmik_count++;
    $tasmikid = $prefix . str_pad($tasmik_count, 2, '0', STR_PAD_LEFT) . $date;

    // Get form data
    $tasmik_date = date("Y-m-d"); // Set today's date
    $juzuk = $_POST['juzuk'];
    $start_page = $_POST['startPage'];
    $end_page = $_POST['endPage'];
    $start_ayah = $_POST['startAyah'];
    $end_ayah = $_POST['endAyah'];
    $live_conference = "no"; // Set live_conference to "no" by default

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO tasmik (tasmikid, studentid, tasmik_date, juzuk, start_page, end_page, start_ayah, end_ayah, live_conference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiiiis", $tasmikid, $studentid, $tasmik_date, $juzuk, $start_page, $end_page, $start_ayah, $end_ayah, $live_conference);

    // Execute the statement
    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Get the current user ID from the session
$userid = $_SESSION['userid'];

// Check if the student ID exists in the student table
$stmt = $conn->prepare("SELECT studentid FROM student WHERE userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$stmt->bind_result($studentid);
$stmt->fetch();
$stmt->close();

if (!$studentid) {
    die("Student ID not found for the current user.");
}

// Fetch tasmik data from the database for the current user
$stmt = $conn->prepare("SELECT tasmikid, studentid, tasmik_date, juzuk, start_page, end_page, start_ayah, end_ayah, live_conference, status FROM tasmik WHERE studentid = ?");
$stmt->bind_param("s", $studentid);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
            <hr>
        </div>
        <!-- Add your code here -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="tasmik-form-tab" data-bs-toggle="tab" href="#tasmik-form" role="tab" aria-controls="tasmik-form" aria-selected="true">Tasmik Form</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="submission-history-tab" data-bs-toggle="tab" href="#submission-history" role="tab" aria-controls="submission-history" aria-selected="false">Submission History</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="tasmik-form" role="tabpanel" aria-labelledby="tasmik-form-tab">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Tasmik Form</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success" role="alert">
                                New record created successfully.
                            </div>
                        <?php endif; ?>
                        <form action="tasmik_form.php" method="POST" onsubmit="return validateForm()">
                            <div class="form-group">
                                <label for="tasmikDate">Date:</label>
                                <input type="date" class="form-control" id="tasmikDate" name="tasmikDate" value="<?php echo date('Y-m-d'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="juzuk">Juzuk:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="juzukInput" aria-label="Text input with dropdown button" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary btn-border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Select Juzuk
                                        </button>
                                        <div class="dropdown-menu" style="max-height: 200px; overflow-y: auto;">
                                            <?php for ($i = 1; $i <= 30; $i++): ?>
                                                <a class="dropdown-item" href="#" onclick="document.getElementById('juzukInput').value='Juzuk <?php echo $i; ?>'; document.getElementById('juzuk').value='<?php echo $i; ?>';">Juzuk <?php echo $i; ?></a>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="juzuk" name="juzuk" required>
                            </div>
                            <div class="form-group">
                                <label for="startPage">Start Page:</label>
                                <input type="number" class="form-control" id="startPage" name="startPage" min="0" max="604" required>
                            </div>
                            <div class="form-group">
                                <label for="endPage">End Page:</label>
                                <input type="number" class="form-control" id="endPage" name="endPage" min="0" max="604" required>
                            </div>
                            <div class="form-group">
                                <label for="startAyah">Start Ayah:</label>
                                <input type="number" class="form-control" id="startAyah" name="startAyah" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="endAyah">End Ayah:</label>
                                <input type="number" class="form-control" id="endAyah" name="endAyah" min="0" required>
                            </div>
                            <!-- Removed Live Conference Option -->
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="submission-history" role="tabpanel" aria-labelledby="submission-history-tab">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Submission History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="basic-datatables" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%; background-color: #343a40; color: white;">Tasmik ID</th>
                                            <th style="background-color: #343a40; color: white;">Date</th>
                                            <th style="background-color: #343a40; color: white;">Juzuk</th>
                                            <th style="background-color: #343a40; color: white;">Start Page</th>
                                            <th style="background-color: #343a40; color: white;">End Page</th>
                                            <th style="background-color: #343a40; color: white;">Start Ayah</th>
                                            <th style="background-color: #343a40; color: white;">End Ayah</th>
                                            <th style="background-color: #343a40; color: white;">Status</th>
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
                                                case 'approved':
                                                    $badgeClass = 'badge-success';
                                                    break;
                                                case 'rejected':
                                                    $badgeClass = 'badge-danger';
                                                    break;
                                                default:
                                                    $badgeClass = 'badge-secondary';
                                                    break;
                                            }
                                            ?>
                                            <tr class="tasmik-row">
                                                <td><?php echo htmlspecialchars($row['tasmikid']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tasmik_date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['juzuk']); ?></td>
                                                <td><?php echo htmlspecialchars($row['start_page']); ?></td>
                                                <td><?php echo htmlspecialchars($row['end_page']); ?></td>
                                                <td><?php echo htmlspecialchars($row['start_ayah']); ?></td>
                                                <td><?php echo htmlspecialchars($row['end_ayah']); ?></td>
                                                <td class="text-center"><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
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
</div>

<?php include '../include/footer.php'; ?>

<script>
    function validateForm() {
        var startPage = document.getElementById('startPage').value;
        var endPage = document.getElementById('endPage').value;
        var startAyah = document.getElementById('startAyah').value;
        var endAyah = document.getElementById('endAyah').value;

        if (parseInt(endPage) < parseInt(startPage)) {
            alert("End Page cannot be less than Start Page.");
            return false;
        }

        if (parseInt(endAyah) < parseInt(startAyah)) {
            alert("End Ayah cannot be less than Start Ayah.");
            return false;
        }

        return true;
    }

    $(document).ready(function() {
        $("#basic-datatables").DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });
    });
</script>