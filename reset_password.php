<?php
// reset_password.php
// Validates token and shows password reset form

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get token from URL
$token = $_GET['token'] ?? null;

if (!$token) {
    showError("Invalid or missing reset token.");
    exit;
}

// Validate token
$stmt = $conn->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
if (!$stmt) {
    showError("Database error occurred.");
    $conn->close();
    exit;
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    showError("Invalid or expired reset token.");
    $stmt->close();
    $conn->close();
    exit;
}

$resetData = $result->fetch_assoc();
$stmt->close();

// Check if token is used
if ($resetData['used'] == 1) {
    showError("This reset link has already been used. Please request a new one.");
    $conn->close();
    exit;
}

// Check if token is expired
$expiresAt = strtotime($resetData['expires_at']);
$now = time();

if ($now > $expiresAt) {
    showError("This reset link has expired. Please request a new one.");
    $conn->close();
    exit;
}

$email = $resetData['email'];

// Show password reset form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BoardEase</title>
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
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #A18167;
        }
        .error {
            color: #d32f2f;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        .success {
            color: #2e7d32;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        button {
            width: 100%;
            padding: 14px;
            background-color: #A18167;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #8a6f56;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Your Password</h1>
        <p class="subtitle">Enter your new password below</p>
        
        <form id="resetForm" method="POST" action="update_password.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error" id="passwordError"></div>
                <div class="password-requirements">
                    Password must be at least 8 characters long
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" required>
                <div class="error" id="confirmError"></div>
            </div>
            
            <button type="submit" id="submitBtn">Reset Password</button>
        </form>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const passwordError = document.getElementById('passwordError');
            const confirmError = document.getElementById('confirmError');
            const submitBtn = document.getElementById('submitBtn');
            
            // Clear previous errors
            passwordError.style.display = 'none';
            confirmError.style.display = 'none';
            
            // Validate password length
            if (password.length < 8) {
                passwordError.textContent = 'Password must be at least 8 characters long';
                passwordError.style.display = 'block';
                return;
            }
            
            // Check if passwords match
            if (password !== confirmPassword) {
                confirmError.textContent = 'Passwords do not match';
                confirmError.style.display = 'block';
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting Password...';
            
            // Submit form
            const formData = new FormData(this);
            fetch('update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('.container').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <h1 style="color: #2e7d32; margin-bottom: 20px;">✓</h1>
                            <h2 style="color: #333; margin-bottom: 10px;">Password Reset Successful!</h2>
                            <p style="color: #666; margin-bottom: 30px;">Your password has been successfully reset. You can now log in with your new password.</p>
                            <a href="https://192.168.101.7/BoardEase2/login.html" style="display: inline-block; padding: 12px 30px; background-color: #A18167; color: white; text-decoration: none; border-radius: 5px;">Go to Login</a>
                        </div>
                    `;
                } else {
                    passwordError.textContent = data.message || 'An error occurred. Please try again.';
                    passwordError.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset Password';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                passwordError.textContent = 'Network error. Please try again.';
                passwordError.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();

function showError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 40px;
                max-width: 450px;
                text-align: center;
            }
            h1 { color: #d32f2f; margin-bottom: 20px; }
            p { color: #666; margin-bottom: 30px; }
            a {
                display: inline-block;
                padding: 12px 30px;
                background-color: #A18167;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>⚠️ Error</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="forgot_password.html">Request New Reset Link</a>
        </div>
    </body>
    </html>
    <?php
}
?>

