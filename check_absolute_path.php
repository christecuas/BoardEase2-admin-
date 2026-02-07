<?php
echo "<h3>Cron Job Path Check</h3>";
echo "Your current script directory is: <br><b>" . __DIR__ . "</b><br><br>";
echo "To run your scripts via Cron, use these paths:<br>";
$files = [
    'auto_notify_payment_reminders.php',
    'auto_mark_overdue.php',
    'auto_backup.php',
    'cleanup_old_notifications.php',
    'cleanup_unverified_accounts.php',
    'auto_complete_stays.php'
];

echo "<ul>";
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<li>/usr/bin/php " . __DIR__ . "/$file</li>";
    } else {
        echo "<li><span style='color:red'>Not found:</span> $file</li>";
    }
}
echo "</ul>";
?>
