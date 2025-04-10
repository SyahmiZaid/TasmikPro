<?php
$pageTitle = "Add Student";
$breadcrumb = "Pages / Manage Student / Add Student";
include '../include/header.php';
require_once '../database/db_connection.php';

// Assuming the teacher's halaqah ID is stored in the session
$teacher_halaqah_id = $_SESSION['halaqahid'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $gender = trim($_POST['gender']);
    $form = trim($_POST['form']);
    $class = trim($_POST['class']);
    $ic = trim($_POST['ic']);

    // Validate input
    if (empty($firstname) || empty($lastname) || empty($email) || empty($gender) || empty($form) || empty($class) || empty($ic)) {
        $error_message = "All fields are required.";
    } else {
        // Check if the halaqah already has 13 students
        $stmt = $conn->prepare("SELECT COUNT(*) FROM student WHERE halaqahid = ?");
        $stmt->bind_param("s", $teacher_halaqah_id);
        $stmt->execute();
        $stmt->bind_result($student_count);
        $stmt->fetch();
        $stmt->close();

        if ($student_count >= 13) {
            $error_message = "This halaqah already has the maximum number of 13 students.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT userid FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($existing_userid);
            $stmt->fetch();
            $stmt->close();

            if (!empty($existing_userid)) {
                // Check if the student is already assigned to a halaqah
                $stmt = $conn->prepare("SELECT halaqahid FROM student WHERE userid = ?");
                $stmt->bind_param("s", $existing_userid);
                $stmt->execute();
                $stmt->bind_result($existing_halaqahid);
                $stmt->fetch();
                $stmt->close();

                if (empty($existing_halaqahid)) {
                    // Update the existing student's halaqahid
                    $stmt = $conn->prepare("UPDATE student SET halaqahid = ?, form = ?, class = ?, ic = ?, gender = ? WHERE userid = ?");
                    $stmt->bind_param("ssssss", $teacher_halaqah_id, $form, $class, $ic, $gender, $existing_userid);
                    if ($stmt->execute()) {
                        $success_message = "Student added to halaqah successfully.";

                        // Redirect to manage_student.php after 1 second
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'manage_student.php';
                            }, 1000);
                        </script>";
                    } else {
                        $error_message = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "Email already exists and is assigned to another halaqah.";
                }
            } else {
                // Hash the default password "123"
                $password_hash = password_hash("123", PASSWORD_BCRYPT);

                // Generate unique user ID
                $prefix = "USR";
                $like_prefix = $prefix . '%';
                $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(userid, 4) AS UNSIGNED)) AS max_id FROM users WHERE userid LIKE ?");
                $stmt->bind_param("s", $like_prefix);
                $stmt->execute();
                $stmt->bind_result($max_id);
                $stmt->fetch();
                $stmt->close();
                $new_id = $max_id + 1;
                $userid = $prefix . str_pad($new_id, 4, '0', STR_PAD_LEFT);

                // Generate unique student ID
                $student_prefix = "STD";
                $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(studentid, 4) AS UNSIGNED)) AS max_id FROM student");
                $stmt->execute();
                $stmt->bind_result($max_id);
                $stmt->fetch();
                $stmt->close();
                $new_id = $max_id + 1;
                $student_id = $student_prefix . str_pad($new_id, 3, '0', STR_PAD_LEFT);

                // Set role and created_at
                $role = "Student";
                $created_at = date("Y-m-d H:i:s");

                // Prepare SQL to insert data into users table
                $stmt = $conn->prepare("INSERT INTO users (userid, email, password, role, firstname, lastname, createdat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $userid, $email, $password_hash, $role, $firstname, $lastname, $created_at);

                if ($stmt->execute()) {
                    // Prepare SQL to insert data into student table
                    $stmt_student = $conn->prepare("INSERT INTO student (studentid, userid, halaqahid, form, class, ic, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_student->bind_param("sssssss", $student_id, $userid, $teacher_halaqah_id, $form, $class, $ic, $gender);

                    if ($stmt_student->execute()) {
                        $success_message = "Student added successfully.";

                        // Redirect to manage_student.php after 1 second
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'manage_student.php';
                            }, 1000);
                        </script>";
                    } else {
                        $error_message = "Error: " . $stmt_student->error;
                    }

                    $stmt_student->close();
                } else {
                    $error_message = "Error: " . $stmt->error;
                }

                $stmt->close();
            }
        }
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
                        <h4 class="card-title">Add New Student</h4>
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
                                        <input name="firstname" type="text" class="form-control" placeholder="First Name" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Last Name</label>
                                        <input name="lastname" type="text" class="form-control" placeholder="Last Name" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Email</label>
                                        <input name="email" type="email" class="form-control" placeholder="Email" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control" required>
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Form</label>
                                        <select name="form" class="form-control" required>
                                            <option value="">Select Form</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>Class</label>
                                        <select name="class" class="form-control" required>
                                            <option value="">Select Class</option>
                                            <option value="adib">Adib</option>
                                            <option value="alim">Alim</option>
                                            <option value="arif">Arif</option>
                                            <option value="mujahid">Mujahid</option>
                                            <option value="daie">Daie</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group form-group-default">
                                        <label>IC</label>
                                        <input name="ic" type="text" class="form-control" placeholder="IC" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Add Student</button>
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