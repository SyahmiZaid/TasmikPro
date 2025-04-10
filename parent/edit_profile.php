<?php
$pageTitle = "Edit Profile";
$breadcrumb = "Pages / <a href='../parent/view_profile.php' class='no-link-style'>Profile</a> / <a href='edit_profile.php' class='no-link-style'>Edit Profile</a>";
include '../include/header.php';
require_once '../database/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid = $_SESSION['userid'];
$role = $_SESSION['role'];
$message = '';
$alertClass = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to database
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if this is an image upload form submission
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK && !isset($_POST['firstname'])) {
        // This is just a profile image upload - handle only the image
        $conn->begin_transaction();
        try {
            // Handle profile image upload
            $uploadDir = "../uploads/profile_images/";

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $newFilename = $userid . '_' . time() . '.' . $fileExtension;
            $targetFile = $uploadDir . $newFilename;

            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['profile_image']['tmp_name']);

            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                    // Update profile image path in database
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE userid = ?");
                    $stmt->bind_param("ss", $targetFile, $userid);
                    $stmt->execute();
                    $message = "Profile image updated successfully!";
                    $alertClass = "alert-success";
                } else {
                    throw new Exception("Error uploading image.");
                }
            } else {
                throw new Exception("Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.");
            }

            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $alertClass = "alert-danger";
        }
    }
    // For the main profile update form
    else if (isset($_POST['firstname'])) {
        // Get form data
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $email = trim($_POST['email']);

        // Optional: Handle password change
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Begin transaction
        $conn->begin_transaction();
        try {
            // Update basic user information
            $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE userid = ?");
            $stmt->bind_param("ssss", $firstname, $lastname, $email, $userid);
            $stmt->execute();

            // Handle password change if requested
            if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE userid = ?");
                $stmt->bind_param("s", $userid);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if (password_verify($currentPassword, $user['password'])) {
                    if ($newPassword === $confirmPassword) {
                        // Hash the new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                        // Update password
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE userid = ?");
                        $stmt->bind_param("ss", $hashedPassword, $userid);
                        $stmt->execute();
                    } else {
                        throw new Exception("New passwords do not match");
                    }
                } else {
                    throw new Exception("Current password is incorrect");
                }
            }

            // Handle role-specific updates
            switch ($role) {
                case 'parent':
                    if (isset($_POST['contact'])) {
                        $contact = trim($_POST['contact']);
                        $stmt = $conn->prepare("UPDATE parent SET contact = ? WHERE userid = ?");
                        $stmt->bind_param("ss", $contact, $userid);
                        $stmt->execute();
                    }
                    break;

                case 'student':
                    if (isset($_POST['ic']) && isset($_POST['gender'])) {
                        $ic = trim($_POST['ic']);
                        $gender = $_POST['gender'];
                        $stmt = $conn->prepare("UPDATE student SET ic = ?, gender = ? WHERE userid = ?");
                        $stmt->bind_param("sss", $ic, $gender, $userid);
                        $stmt->execute();
                    }
                    break;

                case 'teacher':
                    if (isset($_POST['gender'])) {
                        $gender = $_POST['gender'];
                        $stmt = $conn->prepare("UPDATE teacher SET gender = ? WHERE userid = ?");
                        $stmt->bind_param("ss", $gender, $userid);
                        $stmt->execute();
                    }
                    break;
            }

            // Commit transaction
            $conn->commit();
            $message = "Profile updated successfully!";
            $alertClass = "alert-success";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $alertClass = "alert-danger";
        }
    }
    // For password update form
    else if (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
            $conn->begin_transaction();
            try {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE userid = ?");
                $stmt->bind_param("s", $userid);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if (password_verify($currentPassword, $user['password'])) {
                    if ($newPassword === $confirmPassword) {
                        // Hash the new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                        // Update password
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE userid = ?");
                        $stmt->bind_param("ss", $hashedPassword, $userid);
                        $stmt->execute();

                        $message = "Password updated successfully!";
                        $alertClass = "alert-success";
                    } else {
                        throw new Exception("New passwords do not match");
                    }
                } else {
                    throw new Exception("Current password is incorrect");
                }

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error: " . $e->getMessage();
                $alertClass = "alert-danger";
            }
        } else {
            $message = "All password fields are required";
            $alertClass = "alert-warning";
        }
    }
}

// Fetch current user data
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user base information
$stmt = $conn->prepare("SELECT u.userid, u.email, u.role, u.firstname, u.lastname, u.profile_image 
                       FROM users u WHERE u.userid = ?");
$stmt->bind_param("s", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get role-specific information
$roleSpecificData = [];
switch ($role) {
    case 'parent':
        $stmt = $conn->prepare("SELECT parentid, contact FROM parent WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();
        break;

    case 'student':
        $stmt = $conn->prepare("SELECT studentid, ic, gender FROM student WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();
        break;

    case 'teacher':
        $stmt = $conn->prepare("SELECT teacherid, gender FROM teacher WHERE userid = ?");
        $stmt->bind_param("s", $userid);
        $stmt->execute();
        $roleResult = $stmt->get_result();
        $roleSpecificData = $roleResult->fetch_assoc();
        break;
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title"><?php echo $pageTitle; ?></h4>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card card-profile">
                    <div class="card-header" style="background-image: url('../assets/img/profilebg.jpg')">
                        <div class="profile-picture">
                            <div class="avatar avatar-xl">
                                <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                    <img src="<?php echo $user['profile_image']; ?>" alt="Profile Picture" class="avatar-img rounded-circle">
                                <?php else: ?>
                                    <div class="avatar-img rounded-circle bg-primary d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user-circle text-white fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <div class="view-profile-photo mt-3">
                            <form id="profile-image-form" enctype="multipart/form-data" method="POST">
                                <input type="file" name="profile_image" id="profile-image-input" class="d-none" accept="image/*">
                                <button type="button" id="change-photo-btn" class="btn btn-primary btn-rounded btn-sm">
                                    <i class="fa fa-camera mr-1"></i> Change Photo
                                </button>
                                <button type="submit" id="save-photo-btn" class="btn btn-success btn-rounded btn-sm mt-2 d-none">
                                    <i class="fa fa-save mr-1"></i> Save Photo
                                </button>
                            </form>
                        </div>
                        <div class="user-profile mt-3">
                            <div class="name"><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></div>
                            <div class="job"><?php echo ucfirst($user['role']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Password Update Section -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Password Update</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="current_password">
                                                    <i class="fa fa-eye-slash"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="new_password">
                                                    <i class="fa fa-eye-slash"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text toggle-password" data-target="confirm_password">
                                                    <i class="fa fa-eye-slash"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" name="update_password" class="btn btn-primary" style="margin-left: 10px; width: auto;">
                                        <i class="fa fa-lock mr-1"></i> Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Profile Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstname">First Name</label>
                                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $user['firstname']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastname">Last Name</label>
                                        <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $user['lastname']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>

                            <?php if ($role === 'parent'): ?>
                                <div class="form-group">
                                    <label for="contact">Contact Number</label>
                                    <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $roleSpecificData['contact'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>

                            <?php if ($role === 'student'): ?>
                                <div class="form-group">
                                    <label for="ic">IC Number</label>
                                    <input type="text" class="form-control" id="ic" name="ic" value="<?php echo $roleSpecificData['ic'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="gender" value="male" class="selectgroup-input" <?php echo (isset($roleSpecificData['gender']) && $roleSpecificData['gender'] === 'male') ? 'checked' : ''; ?>>
                                            <span class="selectgroup-button">Male</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="gender" value="female" class="selectgroup-input" <?php echo (isset($roleSpecificData['gender']) && $roleSpecificData['gender'] === 'female') ? 'checked' : ''; ?>>
                                            <span class="selectgroup-button">Female</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="gender" value="other" class="selectgroup-input" <?php echo (isset($roleSpecificData['gender']) && $roleSpecificData['gender'] === 'other') ? 'checked' : ''; ?>>
                                            <span class="selectgroup-button">Other</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($role === 'teacher'): ?>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <div class="selectgroup selectgroup-pills">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="gender" value="male" class="selectgroup-input" <?php echo (isset($roleSpecificData['gender']) && $roleSpecificData['gender'] === 'male') ? 'checked' : ''; ?>>
                                            <span class="selectgroup-button">Male</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="gender" value="female" class="selectgroup-input" <?php echo (isset($roleSpecificData['gender']) && $roleSpecificData['gender'] === 'female') ? 'checked' : ''; ?>>
                                            <span class="selectgroup-button">Female</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="gender" value="other" class="selectgroup-input" <?php echo (isset($roleSpecificData['gender']) && $roleSpecificData['gender'] === 'other') ? 'checked' : ''; ?>>
                                            <span class="selectgroup-button">Other</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="form-group mb-0">
                                <div class="d-flex justify-content-between">
                                    <a href="view_profile.php" class="btn btn-default">
                                        <i class="fa fa-arrow-left mr-1"></i> Back to Profile
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save mr-1"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle profile image change
        const profileImageInput = document.getElementById('profile-image-input');
        const changePhotoBtn = document.getElementById('change-photo-btn');
        const savePhotoBtn = document.getElementById('save-photo-btn');
        const profileImageForm = document.getElementById('profile-image-form');
        const avatarImg = document.querySelector('.avatar-img');

        changePhotoBtn.addEventListener('click', function() {
            profileImageInput.click();
        });

        profileImageInput.addEventListener('change', function(event) {
            if (event.target.files.length > 0) {
                const file = event.target.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    // If there's an avatar image element, update it
                    if (avatarImg.tagName === 'IMG') {
                        avatarImg.src = e.target.result;
                    } else {
                        // Create new image element if it doesn't exist
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Profile Picture';
                        newImg.className = 'avatar-img rounded-circle';
                        avatarImg.parentNode.replaceChild(newImg, avatarImg);
                    }

                    savePhotoBtn.classList.remove('d-none');
                };

                reader.readAsDataURL(file);
            }
        });

        // Password toggle visibility
        const togglePasswordElements = document.querySelectorAll('.toggle-password');

        togglePasswordElements.forEach(function(element) {
            element.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        });

        // Form validation
        const mainForm = document.querySelector('.card-body form');

        if (mainForm) {
            mainForm.addEventListener('submit', function(event) {
                const newPassword = document.getElementById('new_password')?.value || '';
                const confirmPassword = document.getElementById('confirm_password')?.value || '';

                if (newPassword && newPassword !== confirmPassword) {
                    event.preventDefault();
                    alert('New passwords do not match!');
                }
            });
        }
    });
</script>

<?php
// Close the connection at the very end
$conn->close();
include '../include/footer.php';
?>