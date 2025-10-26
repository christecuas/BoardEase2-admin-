#!/bin/bash

# Setup script for email verification cleanup cron job
# This script sets up automatic cleanup of unverified accounts

# Get the current directory (where the PHP files are located)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CLEANUP_SCRIPT="$SCRIPT_DIR/cleanup_unverified_accounts.php"

# Create the cron job entry
CRON_ENTRY="*/5 * * * * /usr/bin/php $CLEANUP_SCRIPT >> $SCRIPT_DIR/logs/cleanup.log 2>&1"

# Add to crontab
(crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -

echo "Cron job setup complete!"
echo "Cleanup script will run every 5 minutes"
echo "Logs will be saved to: $SCRIPT_DIR/logs/cleanup.log"

# Create logs directory if it doesn't exist
mkdir -p "$SCRIPT_DIR/logs"

# Make the cleanup script executable
chmod +x "$CLEANUP_SCRIPT"

echo "Setup complete! The cleanup process will start automatically."
echo "To view current cron jobs: crontab -l"
echo "To remove the cron job: crontab -e (then delete the line)"
