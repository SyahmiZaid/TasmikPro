<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection file
require_once '../database/db_connection.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
  if ($user && password_verify($password, $user['password'])) { // Using password_verify for hashed passwords
    $_SESSION['userid'] = $user['userid']; // Store userid in session
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
    $error = "Invalid email or password.";
  }

  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Form</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .admin-link {
      font-size: 0.8em;
      color: #888;
      text-align: right;
      display: block;
      margin-top: 10px;
    }

    .admin-link i {
      margin-left: 5px;
    }
  </style>
</head>

<body>
  <div class="wrapper">
    <form action="" method="POST">
      <h2>Login</h2>
      <?php if (!empty($error)) {
        echo "<p style='color: white;'>$error</p>";
      } ?>
      <div class="input-field">
        <input type="text" name="email" required>
        <label>Enter your email</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Enter your password</label>
      </div>
      <button type="submit">Log In</button>
      <div class="register">
        <p>Parent. Don't have an account? <a href="signup.php">Register</a></p>
      </div>
      <a href="admin_signup.php" class="admin-link"><i class="fas fa-user-shield"></i></a>
    </form>
  </div>
</body>

</html>