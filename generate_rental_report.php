<?php
// Rental Report Generation
// This file generates rental/occupancy reports in CSV format for download

require_once 'dbConfig.php';

// Function to generate rental report
function generateRentalReport() {
    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // Query to get rental/occupancy data
        $query = "
            SELECT 
                bh.bh_id,
                bh.bh_name,
                bh.bh_address,
                bh.bh_description,
                bh.bh_rules,
                bh.bh_created_at,
                r.f_name as owner_fname,
                r.m_name as owner_mname,
                r.l_name as owner_lname,
                r.email as owner_email,
                r.phone_number as owner_phone,
                COUNT(DISTINCT b.booking_id) as total_bookings,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Approved' THEN b.booking_id END) as approved_bookings,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Pending' THEN b.booking_id END) as pending_bookings,
                COUNT(DISTINCT bhr.bhr_id) as total_rooms,
                COUNT(DISTINCT ru.room_id) as total_room_units,
                COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) as occupied_units,
                COUNT(DISTINCT CASE WHEN ru.status = 'Available' THEN ru.room_id END) as available_units,
                AVG(bhr.price) as avg_room_price,
                MIN(bhr.price) as min_room_price,
                MAX(bhr.price) as max_room_price
            FROM boarding_houses bh
            LEFT JOIN users u ON bh.user_id = u.user_id
            LEFT JOIN registration r ON u.reg_id = r.reg_id
            LEFT JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
            LEFT JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
            LEFT JOIN bookings b ON ru.room_id = b.room_id
            GROUP BY bh.bh_id, bh.bh_name, bh.bh_address, bh.bh_description, bh.bh_rules, bh.bh_created_at,
                     r.f_name, r.m_name, r.l_name, r.email, r.phone_number
            ORDER BY bh.bh_created_at DESC
        ";
        
        $result = $connection->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $connection->error);
        }
        
        // Start CSV content
        $csv = "Boarding House ID,Name,Address,Description,Rules,Created Date,Owner Name,Owner Email,Owner Phone,Total Bookings,Approved Bookings,Pending Bookings,Total Rooms,Total Units,Occupied Units,Available Units,Avg Price,Min Price,Max Price\n";
        
        while ($row = $result->fetch_assoc()) {
            $ownerName = trim($row['owner_fname'] . ' ' . $row['owner_mname'] . ' ' . $row['owner_lname']);
            $occupancyRate = $row['total_room_units'] > 0 ? round(($row['occupied_units'] / $row['total_room_units']) * 100, 2) : 0;
            
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $row['bh_id'],
                '"' . str_replace('"', '""', $row['bh_name']) . '"',
                '"' . str_replace('"', '""', $row['bh_address']) . '"',
                '"' . str_replace('"', '""', $row['bh_description']) . '"',
                '"' . str_replace('"', '""', $row['bh_rules']) . '"',
                $row['bh_created_at'],
                '"' . str_replace('"', '""', $ownerName) . '"',
                $row['owner_email'],
                $row['owner_phone'],
                $row['total_bookings'],
                $row['approved_bookings'],
                $row['pending_bookings'],
                $row['total_rooms'],
                $row['total_room_units'],
                $row['occupied_units'],
                $row['available_units'],
                $row['avg_room_price'],
                $row['min_room_price'],
                $row['max_room_price']
            );
        }
        
        $connection->close();
        return $csv;
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Handle the report request
if (isset($_GET['action']) && $_GET['action'] === 'rental_report') {
    // Generate report
    $report = generateRentalReport();
    
    if (strpos($report, 'Error:') === 0) {
        // If there's an error, return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $report]);
        exit;
    }
    
    // Set headers for file download
    $filename = 'rental_report_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($report));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output the report
    echo $report;
    exit;
}

// If accessed directly without action parameter, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
