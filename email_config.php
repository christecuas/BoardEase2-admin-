<?php
// email_config.php - Email configuration and setup

// Email Configuration Options
class EmailConfig {
    
    // Option 1: Basic PHP mail() function (Simple setup)
    public static function getBasicMailConfig() {
        return [
            'method' => 'basic',
            'from_email' => 'noreply@boardease.com',
            'from_name' => 'BoardEase',
            'reply_to' => 'support@boardease.com'
        ];
    }
    
    // Option 2: Gmail SMTP (Recommended for testing)
    public static function getGmailSMTPConfig() {
        return [
            'method' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'christecuas947@gmail.com',
            'password' => 'alaw glsx czbf qdhs',
            'encryption' => 'tls',
            'from_email' => 'christecuas947@gmail.com',
            'from_name' => 'BoardEase',
            'reply_to' => 'christecuas947@gmail.com'
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
