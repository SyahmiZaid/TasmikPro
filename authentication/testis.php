<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TasmikPro - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E7D32;
            --secondary-color: #43A047;
            --accent-color: #A5D6A7;
            --light-bg: #F1F8E9;
            --text-color: #212121;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            display: flex;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            width: 900px;
            max-width: 95%;
            background-color: white;
        }
        
        .login-image {
            flex: 1;
            background-image: url('/api/placeholder/500/800');
            background-size: cover;
            background-position: center;
            position: relative;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem;
            color: white;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7));
            z-index: 1;
        }
        
        .login-image-content {
            position: relative;
            z-index: 2;
        }
        
        .login-form {
            flex: 1;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo img {
            height: 80px;
        }
        
        .login-title {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-align: center;
        }
        
        .login-subtitle {
            color: #666;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin-bottom: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group-text {
            background-color: var(--light-bg);
            border: 1px solid #e0e0e0;
            color: #666;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 1.5rem;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .register-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .arabic-decoration {
            font-family: 'Traditional Arabic', serif;
            font-size: 1.5rem;
            color: white;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .user-type-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .user-type-selector .btn {
            margin: 0 0.5rem;
            background-color: var(--light-bg);
            color: var(--text-color);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
        }
        
        .user-type-selector .btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-image {
                min-height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-image">
            <div class="login-image-content">
                <div class="arabic-decoration">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيم</div>
                <h2>TasmikPro</h2>
                <p>Innovation Digital Solution for Efficient Quran Tasmik Monitoring System</p>
                <p>Sekolah Menengah Kebangsaan Agama Maahad Muar</p>
            </div>
        </div>
        <div class="login-form">
            <div class="login-logo">
                <img src="/api/placeholder/240/80" alt="TasmikPro Logo" />
            </div>
            <h3 class="login-title">Welcome Back</h3>
            <p class="login-subtitle">Please login to your account</p>
            
            <div class="user-type-selector">
                <button class="btn active">Student</button>
                <button class="btn">Teacher</button>
                <button class="btn">Parent</button>
                <button class="btn">Admin</button>
            </div>
            
            <form action="login_process.php" method="post">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" placeholder="Email Address" required>
                </div>
                
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" placeholder="Password" required>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register Now</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle active class for user type buttons
        document.querySelectorAll('.user-type-selector .btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.user-type-selector .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>