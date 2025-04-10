<?php
$pageTitle = "Add Halaqah";
$breadcrumb = "Pages / Manage Halaqah / Add Halaqah";
include '../include/header.php';
require_once '../database/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $halaqahname = trim($_POST['halaqahname']);

    // Validate input
    if (empty($halaqahname)) {
        $error_message = "Halaqah name is required.";
    } else {
        // Ensure the Halaqah name starts with a capital letter
        $halaqahname = ucfirst(strtolower($halaqahname));

        // Generate unique halaqah ID
        $halaqah_prefix = "HLQ";
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(halaqahid, 4) AS UNSIGNED)) AS max_id FROM halaqah");
        $stmt->execute();
        $stmt->bind_result($max_id);
        $stmt->fetch();
        $stmt->close();
        $new_id = $max_id + 1;
        $halaqahid = $halaqah_prefix . str_pad($new_id, 3, '0', STR_PAD_LEFT);

        // Prepare SQL to insert data into halaqah table
        $stmt = $conn->prepare("INSERT INTO halaqah (halaqahid, halaqahname) VALUES (?, ?)");
        $stmt->bind_param("ss", $halaqahid, $halaqahname);

        if ($stmt->execute()) {
            $success_message = "Halaqah added successfully.";
            $redirect = true;
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
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
                        <h4 class="card-title">Add New Halaqah</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <?php
                            if (!empty($error_message)) {
                                echo "<p style='color: red;'>$error_message</p>";
                            } elseif (!empty($success_message)) {
                                echo "<p style='color: green;'>$success_message</p>";
                                echo '<script>setTimeout(function(){ window.location.href = "manage_halaqah.php"; }, 1000);</script>';
                            }
                            ?>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Halaqah Name</label>
                                        <input name="halaqahname" type="text" class="form-control" placeholder="Halaqah Name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Add Halaqah</button>
                                <a href="manage_halaqah.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>