<?php
// Include the database connection file
require_once '../database/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $gender = trim($_POST['gender']);

    // Validate input
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password) || empty($gender)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

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

            // Set role and created_at
            $role = "student";
            $created_at = date("Y-m-d H:i:s");

            // Prepare SQL to insert data into users table
            $stmt = $conn->prepare("INSERT INTO users (userid, email, password, role, firstname, lastname, createdat) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $userid, $email, $password_hash, $role, $firstname, $lastname, $created_at);

            if ($stmt->execute()) {
                // Generate unique student ID
                $student_prefix = "STD";
                $date = date("ymd"); // Use 'ymd' to get last two digits of the year
                $stmt = $conn->prepare("SELECT COUNT(*) AS student_count FROM student");
                $stmt->execute();
                $stmt->bind_result($student_count);
                $stmt->fetch();
                $stmt->close();
                $student_count++;
                $student_id = $student_prefix . str_pad($student_count, 2, '0', STR_PAD_LEFT) . $date;

                // Prepare SQL to insert data into student table
                $stmt_student = $conn->prepare("INSERT INTO student (studentid, userid, gender) VALUES (?, ?, ?)");
                $stmt_student->bind_param("sss", $student_id, $userid, $gender);

                if ($stmt_student->execute()) {
                    $success_message = "Registration successful. <a href='signin.php'>Login here</a>.";
                } else {
                    $error_message = "Error: " . $stmt_student->error;
                }

                $stmt_student->close();
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Register Form</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="wrapper-register">
        <form action="" method="POST">
            <h2>Student Register</h2>
            <?php
            if (!empty($error_message)) {
                echo "<p style='color: red;'>$error_message</p>";
            } elseif (!empty($success_message)) {
                echo "<p style='color: green;'>$success_message</p>";
            }
            ?>
            <div class="input-group">
                <div class="input-field">
                    <input type="text" name="firstname" required>
                    <label>Enter your first name</label>
                </div>
                <div class="input-field">
                    <input type="text" name="lastname" required>
                    <label>Enter your last name</label>
                </div>
            </div>
            <div class="input-group">
                <div class="input-field">
                    <input type="text" name="email" required>
                    <label>Enter your email</label>
                </div>
            </div>
            <div class="input-field">
                <input type="password" name="password" required>
                <label>Enter your password</label>
            </div>
            <div class="input-field">
                <input type="password" name="confirm_password" required>
                <label>Confirm your password</label>
            </div>
            <div class="input-field">
                <select name="gender" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <button type="submit">Register</button>
            <div class="register">
                <p>Already have an account? <a href="signin.php">Login</a></p>
            </div>
        </form>
    </div>
</body>
</html>