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
        // CRITICAL: Use correct column name 'phone' instead of 'phone_number'
        // Also use correct booking statuses: 'Confirmed' (not 'Approved'), 'Pending', 'Cancelled', 'Completed'
        $query = "
            SELECT 
                bh.bh_id,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                COALESCE(bh.bh_address, '') as bh_address,
                COALESCE(bh.bh_description, '') as bh_description,
                COALESCE(bh.bh_rules, '') as bh_rules,
                bh.bh_created_at,
                COALESCE(r.first_name, '') as owner_fname,
                COALESCE(r.middle_name, '') as owner_mname,
                COALESCE(r.last_name, '') as owner_lname,
                COALESCE(r.suffix, '') as owner_suffix,
                COALESCE(r.email, '') as owner_email,
                COALESCE(r.phone, '') as owner_phone,
                COUNT(DISTINCT b.booking_id) as total_bookings,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Confirmed' THEN b.booking_id END) as confirmed_bookings,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Pending' THEN b.booking_id END) as pending_bookings,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Cancelled' THEN b.booking_id END) as cancelled_bookings,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Completed' THEN b.booking_id END) as completed_bookings,
                COUNT(DISTINCT bhr.bhr_id) as total_rooms,
                COUNT(DISTINCT ru.room_id) as total_room_units,
                COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) as occupied_units,
                COUNT(DISTINCT CASE WHEN ru.status = 'Available' THEN ru.room_id END) as available_units,
                COALESCE(AVG(bhr.price), 0) as avg_room_price,
                COALESCE(MIN(bhr.price), 0) as min_room_price,
                COALESCE(MAX(bhr.price), 0) as max_room_price,
                -- Calculate occupancy rate
                CASE 
                    WHEN COUNT(DISTINCT ru.room_id) > 0 
                    THEN ROUND((COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) / COUNT(DISTINCT ru.room_id)) * 100, 2)
                    ELSE 0
                END as occupancy_rate
            FROM boarding_houses bh
            LEFT JOIN users u ON bh.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
            LEFT JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
            LEFT JOIN bookings b ON ru.room_id = b.room_id
            GROUP BY bh.bh_id, bh.bh_name, bh.bh_address, bh.bh_description, bh.bh_rules, bh.bh_created_at,
                     r.first_name, r.middle_name, r.last_name, r.suffix, r.email, r.phone
            ORDER BY bh.bh_created_at DESC
        ";
        
        $result = $connection->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $connection->error);
        }
        
        // Start CSV content with updated columns
        $csv = "Boarding House ID,Name,Address,Description,Rules,Created Date,Owner Name,Owner Email,Owner Phone,Total Bookings,Confirmed Bookings,Pending Bookings,Cancelled Bookings,Completed Bookings,Total Rooms,Total Units,Occupied Units,Available Units,Occupancy Rate (%),Avg Price,Min Price,Max Price\n";
        
        while ($row = $result->fetch_assoc()) {
            // Build owner full name
            $ownerName = trim($row['owner_fname'] . ' ' . $row['owner_mname'] . ' ' . $row['owner_lname']);
            if (!empty($row['owner_suffix'])) {
                $ownerName .= ' ' . $row['owner_suffix'];
            }
            $ownerName = trim($ownerName);
            
            // Get occupancy rate (already calculated in query, but use as fallback)
            $occupancyRate = $row['occupancy_rate'] ?? 0;
            if ($occupancyRate == 0 && $row['total_room_units'] > 0) {
                $occupancyRate = round(($row['occupied_units'] / $row['total_room_units']) * 100, 2);
            }
            
            // Format prices
            $avgPrice = number_format($row['avg_room_price'] ?? 0, 2);
            $minPrice = number_format($row['min_room_price'] ?? 0, 2);
            $maxPrice = number_format($row['max_room_price'] ?? 0, 2);
            
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $row['bh_id'] ?? '',
                '"' . str_replace('"', '""', $row['bh_name'] ?? 'N/A') . '"',
                '"' . str_replace('"', '""', $row['bh_address'] ?? '') . '"',
                '"' . str_replace('"', '""', $row['bh_description'] ?? '') . '"',
                '"' . str_replace('"', '""', $row['bh_rules'] ?? '') . '"',
                $row['bh_created_at'] ?? '',
                '"' . str_replace('"', '""', $ownerName) . '"',
                $row['owner_email'] ?? '',
                $row['owner_phone'] ?? '',
                $row['total_bookings'] ?? 0,
                $row['confirmed_bookings'] ?? 0,
                $row['pending_bookings'] ?? 0,
                $row['cancelled_bookings'] ?? 0,
                $row['completed_bookings'] ?? 0,
                $row['total_rooms'] ?? 0,
                $row['total_room_units'] ?? 0,
                $row['occupied_units'] ?? 0,
                $row['available_units'] ?? 0,
                number_format($occupancyRate, 2),
                $avgPrice,
                $minPrice,
                $maxPrice
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
