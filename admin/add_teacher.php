<?php
$pageTitle = "Add Teacher";
$breadcrumb = "Pages / Manage Teacher / Add Teacher";
include '../include/header.php';
require_once '../database/db_connection.php';

// Fetch halaqah data from the database that do not have a teacher assigned
$halaqah_stmt = $conn->prepare("
    SELECT halaqah.halaqahid, halaqah.halaqahname 
    FROM halaqah 
    LEFT JOIN teacher ON halaqah.halaqahid = teacher.halaqahid 
    WHERE teacher.halaqahid IS NULL
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

            // Generate unique teacher ID
            $teacher_prefix = "TCH";
            $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(teacherid, 4) AS UNSIGNED)) AS max_id FROM teacher");
            $stmt->execute();
            $stmt->bind_result($max_id);
            $stmt->fetch();
            $stmt->close();
            $new_id = $max_id + 1;
            $teacher_id = $teacher_prefix . str_pad($new_id, 3, '0', STR_PAD_LEFT);

            // Set role and created_at
            $role = "Teacher";
            $created_at = date("Y-m-d H:i:s");

            // Prepare SQL to insert data into users table
            $stmt = $conn->prepare("INSERT INTO users (userid, email, password, role, firstname, lastname, createdat) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $userid, $email, $password_hash, $role, $firstname, $lastname, $created_at);

            if ($stmt->execute()) {
                // Prepare SQL to insert data into teacher table
                $stmt_teacher = $conn->prepare("INSERT INTO teacher (teacherid, userid, halaqahid, gender) VALUES (?, ?, ?, ?)");
                $stmt_teacher->bind_param("ssss", $teacher_id, $userid, $halaqahid, $gender);

                if ($stmt_teacher->execute()) {
                    $success_message = "Teacher added successfully.";

                    // Redirect to manage_teacher.php after 1 second
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'manage_teacher.php';
                        }, 1000);
                    </script>";
                } else {
                    $error_message = "Error: " . $stmt_teacher->error;
                }

                $stmt_teacher->close();
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            $stmt->close();
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
                        <h4 class="card-title">Add New Teacher</h4>
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
                                        <label>Halaqah (Optional)</label>
                                        <select name="halaqahid" class="form-control">
                                            <option value="">Select Halaqah</option>
                                            <?php while ($halaqah_row = $halaqah_result->fetch_assoc()): ?>
                                                <option value="<?php echo $halaqah_row['halaqahid']; ?>"><?php echo htmlspecialchars($halaqah_row['halaqahname']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Add Teacher</button>
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