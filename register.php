<?php
require_once 'db.php';
startSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if user already exists
        $existing = db()->fetchOne("SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username]);
        
        if ($existing) {
            $error = 'Email or username already exists.';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $booking_url = generateBookingUrl($username);
            
            $user_id = db()->insert('users', [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'password' => $hashed_password,
                'booking_url' => $booking_url
            ]);
            
            if ($user_id) {
                // Create default meeting type
                db()->insert('meeting_types', [
                    'user_id' => $user_id,
                    'title' => '30 Minute Meeting',
                    'description' => 'Quick 30 minute discussion',
                    'duration' => 30
                ]);
                
                // Set default availability (Monday to Friday, 9 AM to 5 PM)
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                foreach ($days as $day) {
                    db()->insert('availability', [
                        'user_id' => $user_id,
                        'day_of_week' => $day,
                        'start_time' => '09:00:00',
                        'end_time' => '17:00:00'
                    ]);
                }
                
                $_SESSION['user_id'] = $user_id;
                echo "<script>window.location.href = 'dashboard.php';</script>";
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Calendly Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .auth-form {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-visual {
            background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4285f4;
            text-decoration: none;
            margin-bottom: 2rem;
            display: block;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .subtitle {
            color: #666;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #4285f4;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #3367d6;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }

        .success {
            background: #efe;
            color: #363;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #cfc;
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .auth-links a {
            color: #4285f4;
            text-decoration: none;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .visual-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .visual-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-top: 2rem;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .features-list li::before {
            content: '✓';
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .auth-visual {
                display: none;
            }

            .auth-form {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <a href="index.php" class="logo">📅 Calendly</a>
            <h1>Create your account</h1>
            <p class="subtitle">Join thousands of professionals who trust our scheduling platform</p>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Create Account</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
        
        <div class="auth-visual">
            <div class="visual-content">
                <h2>Welcome to the future of scheduling</h2>
                <p>Start booking meetings effortlessly with our powerful scheduling platform.</p>
                
                <ul class="features-list">
                    <li>Easy calendar integration</li>
                    <li>Automated email notifications</li>
                    <li>Custom booking pages</li>
                    <li>Mobile-friendly interface</li>
                    <li>Real-time availability</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });

        // Real-time password matching
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>
</html>
