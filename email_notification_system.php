<?php
// Email Notification System for BoardEase
// Handles sending emails for registration notifications and approvals

class EmailNotificationSystem {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    private $admin_email;
    
    public function __construct() {
        // Load email configuration
        require_once 'email_config.php';
        
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_username = SMTP_USERNAME;
        $this->smtp_password = SMTP_PASSWORD;
        $this->from_email = FROM_EMAIL;
        $this->from_name = FROM_NAME;
        $this->admin_email = ADMIN_EMAIL;
    }
    
    /**
     * Send email notification to admin about new registration
     */
    public function sendRegistrationNotificationToAdmin($registrationData) {
        $subject = "New User Registration - BoardEase";
        
        $message = $this->getRegistrationNotificationTemplate($registrationData);
        
        return $this->sendEmail(
            $this->admin_email,
            $subject,
            $message,
            $registrationData['email'] // Reply-to the user's email
        );
    }
    
    /**
     * Send approval email to user
     */
    public function sendApprovalEmailToUser($userData) {
        $subject = "Account Approved - Welcome to BoardEase!";
        
        $message = $this->getApprovalEmailTemplate($userData);
        
        return $this->sendEmail(
            $userData['email'],
            $subject,
            $message
        );
    }
    
    /**
     * Send rejection email to user
     */
    public function sendRejectionEmailToUser($userData, $reason = '') {
        $subject = "Account Registration Update - BoardEase";
        
        $message = $this->getRejectionEmailTemplate($userData, $reason);
        
        return $this->sendEmail(
            $userData['email'],
            $subject,
            $message
        );
    }
    
    /**
     * Get registration notification template for admin
     */
    private function getRegistrationNotificationTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Registration - BoardEase</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;'>
                    New User Registration
                </h2>
                
                <p>A new user has registered on BoardEase and is waiting for approval.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>Registration Details:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 30%;'>Name:</td>
                            <td style='padding: 8px 0;'>{$data['first_name']} {$data['middle_name']} {$data['last_name']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Email:</td>
                            <td style='padding: 8px 0;'>{$data['email']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Phone:</td>
                            <td style='padding: 8px 0;'>{$data['phone']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Role:</td>
                            <td style='padding: 8px 0;'>{$data['role']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Address:</td>
                            <td style='padding: 8px 0;'>{$data['address']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Birth Date:</td>
                            <td style='padding: 8px 0;'>{$data['birth_date']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>ID Type:</td>
                            <td style='padding: 8px 0;'>{$data['valid_id_type']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>ID Number:</td>
                            <td style='padding: 8px 0;'>{$data['id_number']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Registration Date:</td>
                            <td style='padding: 8px 0;'>{$data['created_at']}</td>
                        </tr>
                    </table>
                </div>
                
                <div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #0066cc; margin-top: 0;'>Action Required:</h4>
                    <p>Please review this registration and approve or reject it through the admin dashboard.</p>
                    <p><strong>Registration ID:</strong> {$data['id']}</p>
                </div>
                
                <p style='color: #666; font-size: 14px;'>
                    This is an automated notification from BoardEase System.
                </p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get approval email template for user
     */
    private function getApprovalEmailTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Account Approved - BoardEase</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #28a745; text-align: center;'>
                    🎉 Welcome to BoardEase!
                </h2>
                
                <div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
                    <h3 style='color: #155724; margin-top: 0;'>Account Approved!</h3>
                    <p style='color: #155724; margin-bottom: 0;'>
                        Dear {$data['first_name']} {$data['last_name']},
                    </p>
                </div>
                
                <p>Great news! Your BoardEase account has been approved and is now active.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>Account Details:</h3>
                    <p><strong>Name:</strong> {$data['first_name']} {$data['middle_name']} {$data['last_name']}</p>
                    <p><strong>Email:</strong> {$data['email']}</p>
                    <p><strong>Role:</strong> {$data['role']}</p>
                </div>
                
                <div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #0066cc; margin-top: 0;'>What's Next?</h4>
                    <ul style='color: #333;'>
                        <li>You can now log in to your BoardEase account</li>
                        <li>Complete your profile setup</li>
                        <li>Start exploring available boarding houses</li>
                        <li>Contact support if you need any assistance</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='#' style='background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block;'>
                        Log In to BoardEase
                    </a>
                </div>
                
                <p style='color: #666; font-size: 14px; text-align: center;'>
                    Thank you for choosing BoardEase!<br>
                    If you have any questions, please contact our support team.
                </p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get rejection email template for user
     */
    private function getRejectionEmailTemplate($data, $reason) {
        $reasonText = $reason ? "<p><strong>Reason:</strong> {$reason}</p>" : "";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Registration Update - BoardEase</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc3545; text-align: center;'>
                    Registration Update
                </h2>
                
                <div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;'>
                    <h3 style='color: #721c24; margin-top: 0;'>Registration Not Approved</h3>
                    <p style='color: #721c24; margin-bottom: 0;'>
                        Dear {$data['first_name']} {$data['last_name']},
                    </p>
                </div>
                
                <p>We regret to inform you that your BoardEase registration could not be approved at this time.</p>
                
                {$reasonText}
                
                <div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='color: #0066cc; margin-top: 0;'>What can you do?</h4>
                    <ul style='color: #333;'>
                        <li>Review your registration information for accuracy</li>
                        <li>Ensure all required documents are clear and valid</li>
                        <li>Contact our support team for assistance</li>
                        <li>You may re-register with corrected information</li>
                    </ul>
                </div>
                
                <p style='color: #666; font-size: 14px; text-align: center;'>
                    If you believe this is an error, please contact our support team.<br>
                    We're here to help you get started with BoardEase.
                </p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send email using PHP's mail function (simple method)
     * For production, consider using PHPMailer or similar library
     */
    private function sendEmail($to, $subject, $message, $replyTo = null) {
        $headers = array();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->from_name . ' <' . $this->from_email . '>';
        
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        $headerString = implode("\r\n", $headers);
        
        try {
            $result = mail($to, $subject, $message, $headerString);
            
            if ($result) {
                error_log("Email sent successfully to: $to");
                return true;
            } else {
                error_log("Failed to send email to: $to");
                return false;
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration() {
        $testData = array(
            'id' => 'TEST',
            'first_name' => 'Test',
            'middle_name' => 'User',
            'last_name' => 'Account',
            'email' => $this->admin_email,
            'phone' => '123-456-7890',
            'role' => 'boarder',
            'address' => 'Test Address',
            'birth_date' => '1990-01-01',
            'valid_id_type' => 'Driver License',
            'id_number' => 'TEST123456',
            'created_at' => date('Y-m-d H:i:s')
        );
        
        return $this->sendRegistrationNotificationToAdmin($testData);
    }
}

// Usage example:
// $emailSystem = new EmailNotificationSystem();
// $emailSystem->sendRegistrationNotificationToAdmin($registrationData);
// $emailSystem->sendApprovalEmailToUser($userData);
?>
