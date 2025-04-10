<?php
$pageTitle = "Edit Student";
$breadcrumb = "Pages / Manage Student / Edit Student";
include '../include/header.php';
require_once '../database/db_connection.php';

// Get the student ID from the query string
$student_id = $_GET['id'];

// Fetch the student's current details from the database
$stmt = $conn->prepare("
    SELECT 
        student.studentid, 
        student.halaqahid, 
        student.gender, 
        student.form, 
        student.class, 
        student.ic, 
        users.firstname, 
        users.lastname, 
        users.email 
    FROM 
        student 
    JOIN 
        users 
    ON 
        student.userid = users.userid 
    WHERE 
        student.studentid = ?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$stmt->bind_result($studentid, $halaqahid, $gender, $form, $class, $ic, $firstname, $lastname, $email);
$stmt->fetch();
$stmt->close();

// Fetch halaqah data from the database
$halaqah_stmt = $conn->prepare("
    SELECT halaqah.halaqahid, halaqah.halaqahname 
    FROM halaqah
");
$halaqah_stmt->execute();
$halaqah_result = $halaqah_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $gender = trim($_POST['gender']);
    $halaqahid = trim($_POST['halaqahid']);
    $form = trim($_POST['form']);
    $class = trim($_POST['class']);
    $ic = trim($_POST['ic']);

    // Validate input
    if (empty($firstname) || empty($lastname) || empty($email) || empty($gender) || empty($form) || empty($class) || empty($ic)) {
        $error_message = "All fields except Halaqah are required.";
    } else {
        // Update the user's details in the users table
        $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE userid = (SELECT userid FROM student WHERE studentid = ?)");
        $stmt->bind_param("ssss", $firstname, $lastname, $email, $student_id);
        $stmt->execute();
        $stmt->close();

        // Update the student's details in the student table
        $stmt = $conn->prepare("UPDATE student SET halaqahid = ?, gender = ?, form = ?, class = ?, ic = ? WHERE studentid = ?");
        $stmt->bind_param("ssssss", $halaqahid, $gender, $form, $class, $ic, $student_id);
        $stmt->execute();
        $stmt->close();

        $success_message = "Student details updated successfully.";

        // Redirect to manage_student.php after 1 second
        echo "<script>
            setTimeout(function() {
                window.location.href = 'manage_student.php';
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
                        <h4 class="card-title">Edit Student</h4>
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
                                        <label>Halaqah (Optional)</label>
                                        <select name="halaqahid" class="form-control">
                                            <option value="">Select Halaqah</option>
                                            <?php while ($halaqah_row = $halaqah_result->fetch_assoc()): ?>
                                                <option value="<?php echo $halaqah_row['halaqahid']; ?>" <?php if ($halaqah_row['halaqahid'] == $halaqahid) echo 'selected'; ?>><?php echo htmlspecialchars($halaqah_row['halaqahname']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Form</label>
                                        <select name="form" class="form-control" required>
                                            <option value="">Select Form</option>
                                            <option value="1" <?php if ($form == '1') echo 'selected'; ?>>1</option>
                                            <option value="2" <?php if ($form == '2') echo 'selected'; ?>>2</option>
                                            <option value="3" <?php if ($form == '3') echo 'selected'; ?>>3</option>
                                            <option value="4" <?php if ($form == '4') echo 'selected'; ?>>4</option>
                                            <option value="5" <?php if ($form == '5') echo 'selected'; ?>>5</option>
                                            <option value="6" <?php if ($form == '6') echo 'selected'; ?>>6</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Class</label>
                                        <select name="class" class="form-control" required>
                                            <option value="">Select Class</option>
                                            <option value="adib" <?php if ($class == 'adib') echo 'selected'; ?>>Adib</option>
                                            <option value="alim" <?php if ($class == 'alim') echo 'selected'; ?>>Alim</option>
                                            <option value="arif" <?php if ($class == 'arif') echo 'selected'; ?>>Arif</option>
                                            <option value="mujahid" <?php if ($class == 'mujahid') echo 'selected'; ?>>Mujahid</option>
                                            <option value="daie" <?php if ($class == 'daie') echo 'selected'; ?>>Daie</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>IC</label>
                                        <input name="ic" type="text" class="form-control" placeholder="IC" value="<?php echo htmlspecialchars($ic); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Student</button>
                                <a href="manage_student.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>