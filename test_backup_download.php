<!DOCTYPE html>
<html>
<head>
    <title>Test Backup Download</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-btn { 
            padding: 15px 30px; 
            background: #007bff; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            margin: 10px;
        }
        .test-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>Database Backup Test</h2>
    <p>Click the button below to test the backup download:</p>
    
    <button class="test-btn" onclick="testBackup()">
        <i class="fas fa-database"></i> Test Backup Download
    </button>
    
    <div id="result" style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;"></div>

    <script>
        function testBackup() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>🔄 Testing backup download...</p>';
            
            // Create download link
            const downloadLink = document.createElement('a');
            downloadLink.href = 'backup_database.php?action=backup';
            downloadLink.download = 'boardease_backup_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.sql';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            
            // Trigger download
            downloadLink.click();
            
            // Clean up
            document.body.removeChild(downloadLink);
            
            // Show result
            setTimeout(() => {
                resultDiv.innerHTML = '<p>✅ Backup download triggered! Check your Downloads folder for the SQL file.</p>';
            }, 1000);
        }
    </script>
</body>
</html>


