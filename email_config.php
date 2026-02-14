<?php
// email_config.php - Email configuration and setup

// Email Configuration Options
class EmailConfig {
    
    // Option 1: Basic PHP mail() function (Simple setup)
    public static function getBasicMailConfig() {
        return [
            'method' => 'basic',
            'from_email' => 'boardease2025@gmail.com',
            'from_name' => 'BoardEase',
            'reply_to' => 'boardease2025@gmail.com'
        ];
    }
    
    // Option 2: Gmail SMTP (Recommended for testing)
    public static function getGmailSMTPConfig() {
        return [
            'method' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'boardease2025@gmail.com',
            'password' => 'vorr xueg nslr nesx',
            'encryption' => 'tls',
            'from_email' => 'boardease2025@gmail.com',
            'from_name' => 'BoardEase',
            'reply_to' => 'boardease2025@gmail.com'
        ];
    }
    
    // Option 3: Custom SMTP (For your own server)
    public static function getCustomSMTPConfig() {
        return [
            'method' => 'smtp',
            'host' => 'mail.yourdomain.com', // Change this
            'port' => 587,
            'username' => 'noreply@yourdomain.com', // Change this
            'password' => 'your-email-password', // Change this
            'encryption' => 'tls',
            'from_email' => 'noreply@yourdomain.com',
            'from_name' => 'BoardEase',
            'reply_to' => 'support@yourdomain.com'
        ];
    }
    
    // Get current configuration
    public static function getCurrentConfig() {
        // Change this to switch between methods
        return self::getGmailSMTPConfig(); // Use Gmail SMTP
    }
}

// Email sending function with multiple methods
function sendEmail($to, $subject, $message, $config = null) {
    if ($config === null) {
        $config = EmailConfig::getCurrentConfig();
    }
    
    switch ($config['method']) {
        case 'basic':
            return sendBasicMail($to, $subject, $message, $config);
        case 'smtp':
            return sendSMTPMail($to, $subject, $message, $config);
        default:
            return false;
    }
}

// Basic PHP mail() function
function sendBasicMail($to, $subject, $message, $config) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $config['from_name'] . " <" . $config['from_email'] . ">" . "\r\n";
    $headers .= "Reply-To: " . $config['reply_to'] . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// SMTP email sending (requires PHPMailer or similar)
function sendSMTPMail($to, $subject, $message, $config) {
    // Include PHPMailer
    require_once 'vendor/autoload.php';
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        $mail->addReplyTo($config['reply_to']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

// Generate a professional email template
function getProfessionalEmailTemplate($title, $content) {
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 0; background-color: #f9f9f9; }
            .wrapper { width: 100%; table-layout: fixed; background-color: #f9f9f9; padding-bottom: 40px; }
            .main { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #333333; border-radius: 8px; overflow: hidden; margin-top: 40px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
            .header { background-color: #A18167; padding: 30px; text-align: center; color: #ffffff; }
            .header h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: 1px; }
            .content { padding: 40px 30px; background-color: #ffffff; }
            .content h2 { color: #A18167; margin-top: 0; font-size: 22px; }
            .footer { padding: 25px; text-align: center; color: #888888; font-size: 13px; background-color: #f4f4f4; }
            .btn { display: inline-block; padding: 14px 30px; background-color: #A18167; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; transition: background-color 0.3s; }
            .otp-box { background-color: #fdfaf7; border: 2px dashed #A18167; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #A18167; margin: 25px 0; border-radius: 8px; }
            hr { border: 0; border-top: 1px solid #eeeeee; margin: 30px 0; }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <table class='main'>
                <tr>
                    <td class='header'>
                        <h1>BoardEase</h1>
                    </td>
                </tr>
                <tr>
                    <td class='content'>
                        $content
                    </td>
                </tr>
                <tr>
                    <td class='footer'>
                        &copy; " . date('Y') . " BoardEase. All rights reserved.<br>
                        Helping you find your perfect home away from home.
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>
    ";
}

// Test email function
function testEmailSetup($testEmail) {
    $subject = "BoardEase Email Test";
    $message = "
    <html>
    <body>
        <h2>Email Test Successful!</h2>
        <p>This is a test email from BoardEase to verify that email sending is working correctly.</p>
        <p>If you received this email, your email configuration is working properly.</p>
        <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    </body>
    </html>
    ";
    
    $result = sendEmail($testEmail, $subject, $message);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Test email sent successfully! Check your inbox.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send test email. Check your email configuration.'
        ];
    }
}
?>
