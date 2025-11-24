<?php
/**
 * Test script for payment reminder system
 * This can be run manually to test the payment reminder functionality
 * 
 * Usage: php test_payment_reminders.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Payment Reminder System Test ===\n\n";

// Test the reminder script
echo "Running auto_notify_payment_reminders.php...\n";
echo "----------------------------------------\n\n";

// Include the reminder script
require_once 'auto_notify_payment_reminders.php';

echo "\n=== Test Complete ===\n";
echo "Check the logs/payment_reminders.log file for detailed output.\n";
?>


