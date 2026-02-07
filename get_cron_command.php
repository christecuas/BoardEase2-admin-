<?php
echo "<h3>Cron Job Command</h3>";
echo "<p>Copy this command and paste it into your cPanel Cron Job setup:</p>";
echo "<pre style='background:#f4f4f4; padding:15px; border-radius:5px;'>";
echo "/usr/local/bin/php " . __DIR__ . "/cron_check_expired_approvals.php";
echo "</pre>";
echo "<p>Or simple version:</p>";
echo "<pre style='background:#f4f4f4; padding:15px; border-radius:5px;'>";
echo "php " . __DIR__ . "/cron_check_expired_approvals.php";
echo "</pre>";
?>
