<?php
$pageTitle = "Add Student";
$breadcrumb = "Pages / Manage Student / Add Student";
include '../include/header.php';
require_once '../database/db_connection.php';

// Fetch halaqah data from the database
$halaqah_stmt = $conn->prepare("
    SELECT halaqah.halaqahid, halaqah.halaqahname 
    FROM halaqah
");
$halaqah_stmt->execute();
$halaqah_result = $halaqah_stmt->get_result();

// Fetch available VLE courses for auto-enrollment
$courses_stmt = $conn->prepare("
    SELECT courseid FROM vle_courses
");
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$available_courses = [];
while ($course = $courses_result->fetch_assoc()) {
    $available_courses[] = $course['courseid'];
}

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
    if (empty($firstname) || empty($lastname) || empty($email) || empty($gender)) {
        $error_message = "First name, last name, email, and gender are required.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Start transaction to ensure all operations succeed
            $conn->begin_transaction();
            try {
                // Hash the default password "123"
                $password_hash = password_hash("123", PASSWORD_BCRYPT);

                // Generate unique user ID
                $prefix = "USR";
                $date = date("ymd"); // Use 'ymd' to get last two digits of the year
                $stmt = $conn->prepare("SELECT COUNT(*) AS user_count FROM users");
                $stmt->execute();
                $stmt->bind_result($user_count);
                $stmt->fetch();
                $stmt->close();
                $user_count++;
                $userid = $prefix . str_pad($user_count, 2, '0', STR_PAD_LEFT) . $date;

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
                $role = "student"; // Changed to lowercase to match database enum
                $created_at = date("Y-m-d H:i:s");

                // Prepare SQL to insert data into users table
                $stmt = $conn->prepare("INSERT INTO users (userid, email, password, role, firstname, lastname, createdat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $userid, $email, $password_hash, $role, $firstname, $lastname, $created_at);
                $stmt->execute();
                $stmt->close();

                // Prepare SQL to insert data into student table
                $stmt_student = $conn->prepare("INSERT INTO student (studentid, userid, halaqahid, form, class, ic, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_student->bind_param("sssssss", $student_id, $userid, $halaqahid, $form, $class, $ic, $gender);
                $stmt_student->execute();
                $stmt_student->close();

                // Auto-enroll the student in all available VLE courses
                foreach ($available_courses as $courseid) {
                    $enroll_stmt = $conn->prepare("INSERT INTO vle_enrollment (studentid, courseid) VALUES (?, ?)");
                    $enroll_stmt->bind_param("ss", $student_id, $courseid);
                    $enroll_stmt->execute();
                    $enroll_stmt->close();
                }

                // Commit the transaction if all operations succeeded
                $conn->commit();
                $success_message = "Student added successfully and enrolled in all courses.";

                // Redirect to manage_student.php after 1 second
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'manage_student.php';
                    }, 1000);
                </script>";

            } catch (Exception $e) {
                // Rollback the transaction if any operation failed
                $conn->rollback();
                $error_message = "Error: " . $e->getMessage();
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
                                echo "<div class='alert alert-danger'>{$error_message}</div>";
                            } elseif (!empty($success_message)) {
                                echo "<div class='alert alert-success'>{$success_message}</div>";
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
                                        <label>Halaqah (Optional)</label>
                                        <select name="halaqahid" class="form-control">
                                            <option value="">Select Halaqah</option>
                                            <?php while ($halaqah_row = $halaqah_result->fetch_assoc()): ?>
                                                <option value="<?php echo $halaqah_row['halaqahid']; ?>"><?php echo htmlspecialchars($halaqah_row['halaqahname']); ?></option>
                                            <?php endwhile; ?>
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
                                        <input name="ic" type="text" class="form-control" placeholder="IC">
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