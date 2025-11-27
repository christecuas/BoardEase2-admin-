<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoardEase Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            height: 100%;
            height: 100dvh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            overflow-y: hidden;
            overflow-x: hidden;
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }

        html::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F5F5DC 0%, #D2B48C 50%, #CD853F 100%);
            min-height: 100%;
            min-height: 100dvh;
            height: 100%;
            height: 100dvh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            overflow-y: hidden;
            overflow-x: hidden;
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }

        body::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        /* Glimmer Effects */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.1) 50%,
                transparent 70%
            );
            animation: glimmer 3s ease-in-out infinite;
            z-index: 1;
        }

        body::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                -45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.08) 50%,
                transparent 70%
            );
            animation: glimmer 4s ease-in-out infinite reverse;
            z-index: 1;
        }

        @keyframes glimmer {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            50% {
                transform: translateX(0%) translateY(0%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }

        /* Floating particles effect */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 4px;
            height: 4px;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 6px;
            height: 6px;
            left: 20%;
            animation-delay: 1s;
        }

        .particle:nth-child(3) {
            width: 3px;
            height: 3px;
            left: 30%;
            animation-delay: 2s;
        }

        .particle:nth-child(4) {
            width: 5px;
            height: 5px;
            left: 40%;
            animation-delay: 3s;
        }

        .particle:nth-child(5) {
            width: 4px;
            height: 4px;
            left: 50%;
            animation-delay: 4s;
        }

        .particle:nth-child(6) {
            width: 6px;
            height: 6px;
            left: 60%;
            animation-delay: 5s;
        }

        .particle:nth-child(7) {
            width: 3px;
            height: 3px;
            left: 70%;
            animation-delay: 0.5s;
        }

        .particle:nth-child(8) {
            width: 5px;
            height: 5px;
            left: 80%;
            animation-delay: 1.5s;
        }

        .particle:nth-child(9) {
            width: 4px;
            height: 4px;
            left: 90%;
            animation-delay: 2.5s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            50% {
                transform: translateY(-10vh) rotate(180deg);
            }
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
            overflow-y: hidden;
            overflow-x: hidden;
            z-index: 10;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }

        .login-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #D2B48C, #CD853F);
        }

        /* Additional glimmer effects for the container */
        .login-container::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, 
                transparent, 
                rgba(210, 180, 140, 0.1), 
                transparent, 
                rgba(205, 133, 63, 0.1), 
                transparent
            );
            border-radius: 22px;
            z-index: -1;
            animation: containerGlimmer 2s ease-in-out infinite;
        }

        @keyframes containerGlimmer {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.02);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #D2B48C;
            background: white;
            box-shadow: 0 0 0 3px rgba(210, 180, 140, 0.2), 0 0 20px rgba(210, 180, 140, 0.1);
            transform: translateY(-1px);
        }

        .input-icon:focus-within i {
            color: #D2B48C;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 10;
            pointer-events: none;
        }

        .input-icon input {
            padding-left: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 15;
            cursor: pointer;
            background: transparent;
            border: none;
            padding: 4px;
            font-size: 16px;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: auto;
            line-height: 1;
        }

        .password-toggle:hover {
            color: #D2B48C;
        }

        .password-toggle:focus {
            outline: none;
        }

        .input-icon.password-field {
            position: relative;
            width: 100%;
        }

        .input-icon.password-field input {
            padding-right: 40px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Remove default browser password toggle icons */
        .input-icon.password-field input::-webkit-textfield-decoration-container {
            display: none;
        }

        .input-icon.password-field input::-webkit-credentials-auto-fill-button {
            display: none !important;
        }

        .input-icon.password-field input::-ms-reveal {
            display: none;
        }

        .input-icon.password-field input::-ms-clear {
            display: none;
        }

        .input-icon.password-field .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            margin: 0;
            padding: 5px;
            line-height: 1;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 20;
            width: 30px;
            height: 30px;
            pointer-events: auto;
        }

        .input-icon.password-field .password-toggle i {
            font-size: 16px;
            color: #999;
            pointer-events: none;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #D2B48C, #CD853F);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #CD853F, #B8860B);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(210, 180, 140, 0.3);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            display: none;
        }

        .success-message {
            background: #efe;
            color: #363;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #363;
            display: none;
        }

        .loading {
            display: none;
        }

        .loading.show {
            display: inline-block;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }

        .footer a {
            color: #D2B48C;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 767.98px) {
            html, body {
                height: 100dvh !important;
                min-height: 100dvh !important;
                max-height: 100dvh !important;
                overflow: hidden !important;
            }

            .login-container {
                margin: 1.5rem 2rem;
                padding: 1.25rem 1rem;
                max-width: calc(100% - 4rem);
                width: calc(100% - 4rem);
                max-height: calc(100dvh - 3rem);
                overflow: hidden !important;
                overflow-y: hidden !important;
                overflow-x: hidden !important;
            }

            /* Smaller font sizes for mobile */
            .login-header h1 {
                font-size: 1.5rem !important;
            }

            .login-header p {
                font-size: 0.85rem !important;
            }

            .form-group label {
                font-size: 0.85rem !important;
            }

            .form-group input {
                font-size: 0.9rem !important;
                padding: 0.75rem 1rem !important;
                padding-left: 2.5rem !important;
            }

            .password-field input {
                padding-right: 2.5rem !important;
            }

            .password-field .password-toggle {
                right: 0.75rem !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }

            .input-icon i {
                font-size: 0.9rem !important;
                left: 0.9rem !important;
            }

            .password-toggle {
                font-size: 0.9rem !important;
                right: 0.65rem !important;
            }

            .login-btn {
                font-size: 0.9rem !important;
                padding: 0.75rem 1rem !important;
            }

            .error-message,
            .success-message {
                font-size: 0.85rem !important;
                padding: 0.75rem !important;
            }

            .footer {
                font-size: 0.75rem !important;
                margin-top: 1.5rem !important;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1.25rem 1.5rem;
                padding: 1rem 0.875rem;
                max-width: calc(100% - 3rem);
                width: calc(100% - 3rem);
                overflow: hidden !important;
                overflow-y: hidden !important;
                overflow-x: hidden !important;
            }

            /* Even smaller fonts for very small screens */
            .login-header h1 {
                font-size: 1.3rem !important;
            }

            .login-header p {
                font-size: 0.8rem !important;
            }

            .form-group label {
                font-size: 0.8rem !important;
            }

            .form-group input {
                font-size: 0.85rem !important;
                padding: 0.65rem 0.9rem !important;
                padding-left: 2.25rem !important;
            }

            .password-field input {
                padding-right: 2.25rem !important;
            }

            .password-field .password-toggle {
                right: 0.65rem !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }

            .input-icon i {
                font-size: 0.85rem !important;
                left: 0.8rem !important;
            }

            .password-toggle {
                font-size: 0.85rem !important;
                right: 0.55rem !important;
            }

            .login-btn {
                font-size: 0.85rem !important;
                padding: 0.65rem 1rem !important;
            }

            .error-message,
            .success-message {
                font-size: 0.8rem !important;
                padding: 0.65rem !important;
            }

            .footer {
                font-size: 0.7rem !important;
            }
        }

        @media (max-width: 375px) {
            .login-container {
                margin: 1rem 1.25rem;
                padding: 0.875rem 0.625rem;
                max-width: calc(100% - 2.5rem);
                width: calc(100% - 2.5rem);
                overflow: hidden !important;
                overflow-y: hidden !important;
                overflow-x: hidden !important;
            }

            .login-header h1 {
                font-size: 1.2rem !important;
            }

            .login-header p {
                font-size: 0.75rem !important;
            }

            .form-group label {
                font-size: 0.75rem !important;
            }

            .form-group input {
                font-size: 0.8rem !important;
                padding: 0.6rem 0.8rem !important;
                padding-left: 2rem !important;
            }

            .password-field input {
                padding-right: 2rem !important;
            }

            .password-field .password-toggle {
                right: 0.55rem !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }

            .input-icon i {
                font-size: 0.8rem !important;
                left: 0.75rem !important;
            }

            .password-toggle {
                font-size: 0.8rem !important;
                right: 0.5rem !important;
            }

            .login-btn {
                font-size: 0.8rem !important;
                padding: 0.6rem 1rem !important;
            }

            .error-message,
            .success-message {
                font-size: 0.75rem !important;
                padding: 0.6rem !important;
            }

            .footer {
                font-size: 0.65rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Floating particles for glimmer effect -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div style="text-align: center; margin-bottom: 1rem;">
                <img src="../uploads/boardease_logo.jpg" alt="BoardEase Logo" class="login-logo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; animation: bounce 2s ease-in-out infinite; box-shadow: 0 4px 15px rgba(210, 180, 140, 0.3);">
            </div>
            <h1 style="text-align: center;">BoardEase Admin</h1>
            <p>Sign in to access the admin dashboard</p>
        </div>
        
        <style>
            @keyframes bounce {
                0%, 100% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-10px);
                }
            }
            
            @media (max-width: 767.98px) {
                .login-logo {
                    width: 60px !important;
                    height: 60px !important;
                }
                
                .login-header {
                    padding-top: 1.5rem !important;
                }
            }
            
            @media (max-width: 480px) {
                .login-logo {
                    width: 60px !important;
                    height: 60px !important;
                }
                
                .login-header {
                    padding-top: 1rem !important;
                }
            }
        </style>

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="Enter your email" autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon password-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="off">
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye-slash" id="passwordToggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In</span>
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </button>
        </form>

        <div class="footer">
            <p>&copy; 2025 BoardEase. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            // Hide previous messages
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.querySelector('span').style.display = 'none';
            loginBtn.querySelector('.loading').classList.add('show');
            
            // Send login request
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            fetch('../admin_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = data.message || 'Login successful! Redirecting...';
                    successMessage.style.display = 'block';
                    
                    // Redirect to admin dashboard
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard.php';
                    }, 1500);
                } else {
                    errorMessage.textContent = data.message || 'Login failed. Please try again.';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                errorMessage.textContent = 'Connection error. Please try again.';
                errorMessage.style.display = 'block';
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset button state
                loginBtn.disabled = false;
                loginBtn.querySelector('span').style.display = 'inline';
                loginBtn.querySelector('.loading').classList.remove('show');
            });
        });


        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const passwordToggleIcon = document.getElementById('passwordToggleIcon');

        if (passwordToggle && passwordInput && passwordToggleIcon) {
            passwordToggle.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    // Show password - change to text and show open eye icon
                    passwordInput.type = 'text';
                    passwordToggleIcon.classList.remove('fa-eye-slash');
                    passwordToggleIcon.classList.add('fa-eye');
                } else {
                    // Hide password - change to password and show closed eye icon
                    passwordInput.type = 'password';
                    passwordToggleIcon.classList.remove('fa-eye');
                    passwordToggleIcon.classList.add('fa-eye-slash');
                }
            });
        }

        // No auto-focus for security
    </script>
</body>
</html>
