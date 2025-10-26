@echo off
echo Starting database backup...
cd /d C:\xampp\htdocs\BoardEase2
php auto_backup.php
echo Backup completed!
pause



