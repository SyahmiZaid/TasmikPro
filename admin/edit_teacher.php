<?php
$pageTitle = "Edit Teacher";
$breadcrumb = "Pages / Manage Teacher / Edit Teacher";
include '../include/header.php';
require_once '../database/db_connection.php';

// Get the teacher ID from the query string
$teacher_id = $_GET['id'];

// Fetch the teacher's current details from the database
$stmt = $conn->prepare("
    SELECT 
        teacher.teacherid, 
        teacher.halaqahid, 
        teacher.gender, 
        users.firstname, 
        users.lastname, 
        users.email 
    FROM 
        teacher 
    JOIN 
        users 
    ON 
        teacher.userid = users.userid 
    WHERE 
        teacher.teacherid = ?
");
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$stmt->bind_result($teacherid, $halaqahid, $gender, $firstname, $lastname, $email);
$stmt->fetch();
$stmt->close();

// Fetch halaqah data from the database that do not have a teacher assigned
$halaqah_stmt = $conn->prepare("
    SELECT halaqah.halaqahid, halaqah.halaqahname 
    FROM halaqah 
    LEFT JOIN teacher ON halaqah.halaqahid = teacher.halaqahid 
    WHERE teacher.halaqahid IS NULL OR halaqah.halaqahid = ?
");
$halaqah_stmt->bind_param("s", $halaqahid);
$halaqah_stmt->execute();
$halaqah_result = $halaqah_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $gender = trim($_POST['gender']);
    $halaqahid = trim($_POST['halaqahid']);

    // Validate input
    if (empty($firstname) || empty($lastname) || empty($email) || empty($gender) || empty($halaqahid)) {
        $error_message = "All fields are required.";
    } else {
        // Update the user's details in the users table
        $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE userid = (SELECT userid FROM teacher WHERE teacherid = ?)");
        $stmt->bind_param("ssss", $firstname, $lastname, $email, $teacher_id);
        $stmt->execute();
        $stmt->close();

        // Update the teacher's details in the teacher table
        $stmt = $conn->prepare("UPDATE teacher SET halaqahid = ?, gender = ? WHERE teacherid = ?");
        $stmt->bind_param("sss", $halaqahid, $gender, $teacher_id);
        $stmt->execute();
        $stmt->close();

        $success_message = "Teacher details updated successfully.";

        // Redirect to manage_teacher.php after 1 second
        echo "<script>
            setTimeout(function() {
                window.location.href = 'manage_teacher.php';
            }, 1000);
        </script>";
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
                        <h4 class="card-title">Edit Teacher</h4>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <?php
                            if (!empty($error_message)) {
                                echo "<p style='color: red;'>$error_message</p>";
                            } elseif (!empty($success_message)) {
                                echo "<p style='color: green;'>$success_message</p>";
                            }
                            ?>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>First Name</label>
                                        <input name="firstname" type="text" class="form-control" placeholder="First Name" value="<?php echo htmlspecialchars($firstname); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Last Name</label>
                                        <input name="lastname" type="text" class="form-control" placeholder="Last Name" value="<?php echo htmlspecialchars($lastname); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Email</label>
                                        <input name="email" type="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php if ($gender == 'male') echo 'selected'; ?>>Male</option>
                                            <option value="female" <?php if ($gender == 'female') echo 'selected'; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Halaqah</label>
                                        <select name="halaqahid" class="form-control" required>
                                            <option value="">Select Halaqah</option>
                                            <?php while ($halaqah_row = $halaqah_result->fetch_assoc()): ?>
                                                <option value="<?php echo $halaqah_row['halaqahid']; ?>" <?php if ($halaqah_row['halaqahid'] == $halaqahid) echo 'selected'; ?>><?php echo htmlspecialchars($halaqah_row['halaqahname']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Teacher</button>
                                <a href="manage_teacher.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>