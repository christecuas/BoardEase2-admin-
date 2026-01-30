<?php
echo "<h1>Hostinger Server Time Check</h1>";
echo "<p><strong>Current Server Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Time Zone:</strong> " . date_default_timezone_get() . "</p>";
echo "<hr>";
echo "<p>NOTE: If this time is different from your local time (Philippines), you need to adjust your Cron Job schedules.</p>";
echo "<p>Example: If server says 01:00 (1 AM) but you are at 09:00 (9 AM), there is an 8-hour difference.</p>";
?>