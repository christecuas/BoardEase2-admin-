<?php
// email_templates.php - Email templates for various notifications

function getAccountApprovalEmailTemplate($userName, $userEmail, $userRole) {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    
    $roleText = ($userRole === 'BH Owner') ? 'Boarding House Owner' : 'Boarder';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Account Approved - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #8D6E63;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #8D6E63;
                margin-bottom: 10px;
            }
            .success-icon {
                font-size: 48px;
                color: #28a745;
                margin-bottom: 20px;
            }
            .main-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .greeting {
                font-size: 24px;
                color: #8D6E63;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                margin-bottom: 25px;
                line-height: 1.8;
            }
            .user-info {
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #8D6E63;
            }
            .user-info h3 {
                color: #8D6E63;
                margin-top: 0;
            }
            .next-steps {
                background-color: #e8f5e8;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #28a745;
            }
            .next-steps h3 {
                color: #28a745;
                margin-top: 0;
            }
            .next-steps ul {
                text-align: left;
                margin: 15px 0;
            }
            .next-steps li {
                margin-bottom: 8px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 14px;
            }
            .button {
                display: inline-block;
                background-color: #8D6E63;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                margin: 20px 0;
            }
            .button:hover {
                background-color: #6d4c41;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo'>🏠 BoardEase</div>
                <div style='color: #6c757d; font-size: 14px;'>Your Boarding House Management Solution</div>
            </div>
            
            <div class='main-content'>
                <div class='success-icon'>✅</div>
                <div class='greeting'>Congratulations, " . htmlspecialchars($userName) . "!</div>
                
                <div class='message'>
                    <strong>Your account has been approved!</strong><br>
                    We're excited to welcome you to the BoardEase community.
                </div>
                
                <div class='user-info'>
                    <h3>Account Details</h3>
                    <p><strong>Name:</strong> " . htmlspecialchars($userName) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($userEmail) . "</p>
                    <p><strong>Account Type:</strong> " . htmlspecialchars($roleText) . "</p>
                    <p><strong>Approval Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                </div>
                
                <div class='next-steps'>
                    <h3>What's Next?</h3>
                    <ul>
                      
                        <li>🔐 Log in using your registered email and password</li>
                        <li>📋 Complete your profile setup</li>";
    
    if ($userRole === 'BH Owner') {
        $template .= "
                        <li>🏠 Add your boarding house listings</li>
                        <li>📸 Upload photos of your rooms and facilities</li>
                        <li>💰 Set your rental rates and policies</li>";
    } else {
        $template .= "
                        <li>🔍 Browse available boarding houses</li>
                        <li>📅 Book rooms that match your preferences</li>
                        <li>💬 Connect with boarding house owners</li>";
    }
    
    $template .= "
                    </ul>
                </div>
                
                <a href='#' class='button'>Get Started with BoardEase</a>
            </div>
            
            <div class='footer'>
                <p><strong>Need Help?</strong></p>
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                <p>📧 Email: support@boardease.com<br>
                📞 Phone: (123) 456-7890</p>
                <p style='margin-top: 20px; font-size: 12px; color: #999;'>
                    This email was sent to " . htmlspecialchars($userEmail) . " because your account was approved on BoardEase.<br>
                    If you did not request this account, please contact our support team immediately.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getAccountRejectionEmailTemplate($userName, $userEmail, $reason = '') {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Account Status Update - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #8D6E63;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #8D6E63;
                margin-bottom: 10px;
            }
            .info-icon {
                font-size: 48px;
                color: #ffc107;
                margin-bottom: 20px;
            }
            .main-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .greeting {
                font-size: 24px;
                color: #8D6E63;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                margin-bottom: 25px;
                line-height: 1.8;
            }
            .reason-box {
                background-color: #fff3cd;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #ffc107;
            }
            .reason-box h3 {
                color: #856404;
                margin-top: 0;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo'>🏠 BoardEase</div>
                <div style='color: #6c757d; font-size: 14px;'>Your Boarding House Management Solution</div>
            </div>
            
            <div class='main-content'>
                <div class='info-icon'>ℹ️</div>
                <div class='greeting'>Hello, " . htmlspecialchars($userName) . "</div>
                
                <div class='message'>
                    We have reviewed your account registration, and unfortunately, we cannot approve your account at this time.
                </div>
                
                " . (!empty($reason) ? "
                <div class='reason-box'>
                    <h3>Reason for Rejection</h3>
                    <p>" . htmlspecialchars($reason) . "</p>
                </div>
                " : "") . "
                
                <div class='message'>
                    <strong>What can you do next?</strong><br>
                    • Review your registration information and ensure all details are accurate<br>
                    • Make sure all required documents are clear and valid<br>
                    • You may reapply with corrected information<br>
                    • Contact our support team if you have questions
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Need Help?</strong></p>
                <p>If you have any questions or need assistance, please contact our support team.</p>
                <p>📧 Email: support@boardease.com<br>
                📞 Phone: (123) 456-7890</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getAccountSuspensionEmailTemplate($userName, $userEmail, $reason = '') {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Account Suspended - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #8D6E63;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #8D6E63;
                margin-bottom: 10px;
            }
            .warning-icon {
                font-size: 48px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .main-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .greeting {
                font-size: 24px;
                color: #8D6E63;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                margin-bottom: 25px;
                line-height: 1.8;
            }
            .reason-box {
                background-color: #f8d7da;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #dc3545;
            }
            .reason-box h3 {
                color: #721c24;
                margin-top: 0;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo'>🏠 BoardEase</div>
                <div style='color: #6c757d; font-size: 14px;'>Your Boarding House Management Solution</div>
            </div>
            
            <div class='main-content'>
                <div class='warning-icon'>⚠️</div>
                <div class='greeting'>Hello, " . htmlspecialchars($userName) . "</div>
                
                <div class='message'>
                    We are writing to inform you that your BoardEase account has been suspended. You will not be able to access your account until it is reactivated by an administrator.
                </div>
                
                " . (!empty($reason) ? "
                <div class='reason-box'>
                    <h3>Reason for Suspension</h3>
                    <p>" . htmlspecialchars($reason) . "</p>
                </div>
                " : "") . "
                
                <div class='message'>
                    <strong>What does this mean?</strong><br>
                    • You cannot log in to your account<br>
                    • Your account is temporarily disabled<br>
                    • You will need to contact support to resolve this issue<br>
                    • Once resolved, your account will be reactivated
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Need Help?</strong></p>
                <p>If you have any questions or believe this is an error, please contact our support team.</p>
                <p>📧 Email: support@boardease.com<br>
                📞 Phone: (123) 456-7890</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getAccountUnsuspensionEmailTemplate($userName, $userEmail) {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Account Reactivated - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #8D6E63;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #8D6E63;
                margin-bottom: 10px;
            }
            .success-icon {
                font-size: 48px;
                color: #28a745;
                margin-bottom: 20px;
            }
            .main-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .greeting {
                font-size: 24px;
                color: #8D6E63;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                margin-bottom: 25px;
                line-height: 1.8;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo'>🏠 BoardEase</div>
                <div style='color: #6c757d; font-size: 14px;'>Your Boarding House Management Solution</div>
            </div>
            
            <div class='main-content'>
                <div class='success-icon'>✅</div>
                <div class='greeting'>Hello, " . htmlspecialchars($userName) . "</div>
                
                <div class='message'>
                    Good news! Your BoardEase account has been reactivated. You can now log in and access all features of your account.
                </div>
                
                <div class='message'>
                    <strong>What you can do now:</strong><br>
                    • Log in to your account<br>
                    • Access all your features and services<br>
                    • Continue using BoardEase as normal
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Welcome Back!</strong></p>
                <p>If you have any questions, please contact our support team.</p>
                <p>📧 Email: support@boardease.com<br>
                📞 Phone: (123) 456-7890</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getBoardingHouseDeactivationEmailTemplate($ownerName, $ownerEmail, $bhName, $reason = '') {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Boarding House Deactivated - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #8D6E63;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #8D6E63;
                margin-bottom: 10px;
            }
            .warning-icon {
                font-size: 48px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .main-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .greeting {
                font-size: 24px;
                color: #8D6E63;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                margin-bottom: 25px;
                line-height: 1.8;
            }
            .reason-box {
                background-color: #f8d7da;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #dc3545;
            }
            .reason-box h3 {
                color: #721c24;
                margin-top: 0;
            }
            .bh-name {
                font-weight: bold;
                color: #8D6E63;
                font-size: 18px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo'>🏠 BoardEase</div>
                <div style='color: #6c757d; font-size: 14px;'>Your Boarding House Management Solution</div>
            </div>
            
            <div class='main-content'>
                <div class='warning-icon'>⚠️</div>
                <div class='greeting'>Hello, " . htmlspecialchars($ownerName) . "</div>
                
                <div class='message'>
                    We are writing to inform you that your boarding house <span class='bh-name'>" . htmlspecialchars($bhName) . "</span> has been deactivated.
                </div>
                
                " . (!empty($reason) ? "
                <div class='reason-box'>
                    <h3>Reason for Deactivation</h3>
                    <p>" . htmlspecialchars($reason) . "</p>
                </div>
                " : "") . "
                
                <div class='message'>
                    <strong>What does this mean?</strong><br>
                    • Your boarding house will not appear in search results<br>
                    • No new bookings will be accepted<br>
                    • Existing bookings will remain active<br>
                    • You will need to contact support to reactivate your listing
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Need Help?</strong></p>
                <p>If you have any questions or believe this is an error, please contact our support team.</p>
                <p>📧 Email: support@boardease.com<br>
                📞 Phone: (123) 456-7890</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function getBoardingHouseActivationEmailTemplate($ownerName, $ownerEmail, $bhName) {
    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Boarding House Reactivated - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #8D6E63;
            }
            .logo {
                font-size: 28px;
                font-weight: bold;
                color: #8D6E63;
                margin-bottom: 10px;
            }
            .success-icon {
                font-size: 48px;
                color: #28a745;
                margin-bottom: 20px;
            }
            .main-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .greeting {
                font-size: 24px;
                color: #8D6E63;
                margin-bottom: 20px;
            }
            .message {
                font-size: 16px;
                margin-bottom: 25px;
                line-height: 1.8;
            }
            .bh-name {
                font-weight: bold;
                color: #8D6E63;
                font-size: 18px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo'>🏠 BoardEase</div>
                <div style='color: #6c757d; font-size: 14px;'>Your Boarding House Management Solution</div>
            </div>
            
            <div class='main-content'>
                <div class='success-icon'>✅</div>
                <div class='greeting'>Hello, " . htmlspecialchars($ownerName) . "</div>
                
                <div class='message'>
                    Good news! Your boarding house <span class='bh-name'>" . htmlspecialchars($bhName) . "</span> has been reactivated.
                </div>
                
                <div class='message'>
                    <strong>What you can do now:</strong><br>
                    • Your boarding house is now visible in search results<br>
                    • You can accept new bookings<br>
                    • All features are available for your listing
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Welcome Back!</strong></p>
                <p>If you have any questions, please contact our support team.</p>
                <p>📧 Email: support@boardease.com<br>
                📞 Phone: (123) 456-7890</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
