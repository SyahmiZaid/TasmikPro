<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
require_once '../database/db_connection.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signin'])) {
        // Sign-in form submission
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Prepare and execute MySQLi query
        $stmt = $conn->prepare("SELECT u.userid, u.email, u.password, u.role, s.halaqahid AS student_halaqahid, t.halaqahid AS teacher_halaqahid 
                                FROM users u 
                                LEFT JOIN student s ON u.userid = s.userid 
                                LEFT JOIN teacher t ON u.userid = t.userid 
                                WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify password and set session if successful
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['userid'] = $user['userid'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Store halaqahid in session if the user is a student or teacher
            if ($user['role'] == 'student') {
                $_SESSION['halaqahid'] = $user['student_halaqahid'];
            } elseif ($user['role'] == 'teacher') {
                $_SESSION['halaqahid'] = $user['teacher_halaqahid'];
            }

            // Redirect to respective index.php based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: ../admin/index.php");
                    break;
                case 'teacher':
                    header("Location: ../teacher/index.php");
                    break;
                case 'student':
                    header("Location: ../student/index.php");
                    break;
                case 'parent':
                    header("Location: ../parent/index.php");
                    break;
                default:
                    header("Location: ../index.php");
                    break;
            }
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }

        $stmt->close();
    } elseif (isset($_POST['signup'])) {
        // Sign-up form submission
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $contact = trim($_POST['contact']);

        // Validate input
        if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password) || empty($contact)) {
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
                $role = "Parent";
                $created_at = date("Y-m-d H:i:s");

                // Prepare SQL to insert data into users table
                $stmt = $conn->prepare("INSERT INTO users (userid, email, password, role, firstname, lastname, createdat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $userid, $email, $password_hash, $role, $firstname, $lastname, $created_at);

                if ($stmt->execute()) {
                    // Generate unique parent ID
                    $parent_prefix = "PAR";
                    $stmt = $conn->prepare("SELECT COUNT(*) AS parent_count FROM parent");
                    $stmt->execute();
                    $stmt->bind_result($parent_count);
                    $stmt->fetch();
                    $stmt->close();
                    $parent_count++;
                    $parent_id = $parent_prefix . str_pad($parent_count, 2, '0', STR_PAD_LEFT) . $date;

                    // Prepare SQL to insert data into parent table
                    $stmt_parent = $conn->prepare("INSERT INTO parent (parentid, userid, contact) VALUES (?, ?, ?)");
                    $stmt_parent->bind_param("sss", $parent_id, $userid, $contact);

                    if ($stmt_parent->execute()) {
                        $success_message = "Registration successful. <a href='signin.php'>Login here</a>.";
                    } else {
                        $error_message = "Error: " . $stmt_parent->error;
                    }

                    $stmt_parent->close();
                } else {
                    $error_message = "Error: " . $stmt->error;
                }

            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in & Sign up Form</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <main>
        <div class="box">
            <div class="inner-box">
                <div class="forms-wrap">
                    <form action="index.php" method="POST" autocomplete="off" class="sign-in-form">
                        <div class="logo">
                            <img src="#" alt="TasmikPro" />
                            <h4>TasmikPro</h4>
                        </div>

                        <div class="heading">
                            <h2>Welcome Back</h2>
                            <h6>Not registered yet?</h6>
                            <a href="#" class="toggle">Sign up</a>
                        </div>

                        <div class="actual-form">
                            <?php if (!empty($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
                            <div class="input-wrap">
                                <input type="email" name="email" class="input-field" autocomplete="off" required />
                                <label>Email</label>
                            </div>

                            <div class="input-wrap">
                                <input type="password" name="password" class="input-field" autocomplete="off" required />
                                <label>Password</label>
                            </div>

                            <input type="submit" name="signin" value="Sign In" class="sign-btn" />

                            <p class="text">
                                Forgotten your password or your login details?
                                <a href="#">Get help</a> signing in
                            </p>
                        </div>
                    </form>

                    <form action="index.php" method="POST" autocomplete="off" class="sign-up-form">
                        <div class="logo">
                            <img src="#" alt="TasmikPro" />
                            <h4>TasmikPro</h4>
                        </div>

                        <div class="heading">
                            <h2>Get Started</h2>
                            <h6>Already have an account?</h6>
                            <a href="#" class="toggle">Sign in</a>
                        </div>

                        <div class="actual-form">
                            <?php if (!empty($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
                            <?php if (!empty($success_message)) { echo "<p style='color: green;'>$success_message</p>"; } ?>
                            <div class="input-wrap-container">
                                <div class="input-wrap">
                                    <input type="text" name="firstname" class="input-field" autocomplete="off" required />
                                    <label>First Name</label>
                                </div>

                                <div class="input-wrap">
                                    <input type="text" name="lastname" class="input-field" autocomplete="off" required />
                                    <label>Last Name</label>
                                </div>
                            </div>

                            <div class="input-wrap">
                                <input type="email" name="email" class="input-field" autocomplete="off" required />
                                <label>Email</label>
                            </div>

                            <div class="input-wrap">
                                <input type="text" name="contact" class="input-field" autocomplete="off" required />
                                <label>Contact</label>
                            </div>

                            <div class="input-wrap">
                                <input type="password" name="password" class="input-field" autocomplete="off" required />
                                <label>Password</label>
                            </div>

                            <div class="input-wrap">
                                <input type="password" name="confirm_password" class="input-field" autocomplete="off" required />
                                <label>Confirm Password</label>
                            </div>

                            <input type="submit" name="signup" value="Sign Up" class="sign-btn" />

                            <p class="text">
                                By signing up, I agree to the
                                <a href="#">Terms of Services</a> and
                                <a href="#">Privacy Policy</a>
                            </p>
                        </div>
                    </form>
                </div>

                <div class="carousel">
                    <div class="images-wrapper">
                        <img src="../assets/img/image1.png" class="image img-1 show" alt="" />
                        <img src="../assets/img/image2.png" class="image img-2" alt="" />
                        <img src="../assets/img/image3.png" class="image img-3" alt="" />
                    </div>

                    <div class="text-slider">
                        <div class="text-wrap">
                            <div class="text-group">
                                <h2>Create your own courses</h2>
                                <h2>Customize as you like</h2>
                                <h2>Invite students to your class</h2>
                            </div>
                        </div>

                        <div class="bullets">
                            <span class="active" data-value="1"></span>
                            <span data-value="2"></span>
                            <span data-value="3"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Javascript file -->
    <script src="app.js"></script>
</body>
</html>