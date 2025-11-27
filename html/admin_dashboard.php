<?php
// Fetch all user data
require_once '../dbConfig.php';

$pending_registrations = [];
$active_users = [];
$inactive_users = [];
$pending_count = 0;

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    

    // Get pending registrations
    $sql = "SELECT id, role, first_name, middle_name, last_name, suffix, birth_date, phone, address, email, 
                   gcash_num, valid_id_type, id_number, idFrontFile, idBackFile, gcash_qr, 
                   status, email_verified, created_at
            FROM registrations 
            WHERE status = 'pending' 
            ORDER BY created_at DESC";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pending_registrations[] = array(
                "id" => $row['id'],
                "role" => $row['role'],
                "first_name" => $row['first_name'],
                "middle_name" => $row['middle_name'],
                "last_name" => $row['last_name'],
                "suffix" => $row['suffix'],
                "full_name" => trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . (!empty($row['suffix']) ? ' ' . $row['suffix'] : '')),
                "birth_date" => $row['birth_date'],
                "phone" => $row['phone'],
                "address" => $row['address'],
                "email" => $row['email'],
                "gcash_num" => $row['gcash_num'],
                "valid_id_type" => $row['valid_id_type'],
                "id_number" => $row['id_number'],
                "id_front_file" => $row['idFrontFile'],
                "id_back_file" => $row['idBackFile'],
                "gcash_qr" => $row['gcash_qr'],
                "status" => $row['status'],
                "email_verified" => $row['email_verified'],
                "created_at" => $row['created_at']
            );
        }
        $pending_count = count($pending_registrations);
        
        // Get total active users count
        $users_sql = "SELECT COUNT(*) as count FROM users u 
                      JOIN registrations r ON u.reg_id = r.id 
                      WHERE u.status = 'Active' AND r.status = 'approved'";
        $users_result = $conn->query($users_sql);
        $total_users_count = $users_result->fetch_assoc()['count'];
        
        // Get total active boarding houses count
        $bh_sql = "SELECT COUNT(*) as count FROM boarding_houses WHERE status = 'Active'";
        $bh_result = $conn->query($bh_sql);
        $total_bh_count = $bh_result->fetch_assoc()['count'];
        
    }

    // Get active users (from users table) - use DISTINCT to avoid duplicates
    $sql = "SELECT DISTINCT u.user_id, u.reg_id, u.status, u.profile_picture,
                   r.role, r.first_name, r.middle_name, r.last_name, r.suffix, r.phone, r.email, r.created_at,
                   CASE 
                       WHEN r.role = 'BH Owner' THEN (
                           SELECT COUNT(*) 
                           FROM boarding_houses bh 
                           WHERE bh.user_id = u.user_id 
                           AND bh.status = 'Active'
                       )
                       ELSE 0
                   END as properties_count
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            WHERE u.status = 'Active'
            ORDER BY r.created_at DESC";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $active_users[] = array(
                "user_id" => $row['user_id'],
                "reg_id" => $row['reg_id'],
                "role" => $row['role'],
                "first_name" => $row['first_name'],
                "middle_name" => $row['middle_name'],
                "last_name" => $row['last_name'],
                "suffix" => $row['suffix'],
                "full_name" => trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . (!empty($row['suffix']) ? ' ' . $row['suffix'] : '')),
                "phone" => $row['phone'],
                "email" => $row['email'],
                "status" => $row['status'],
                "profile_picture" => $row['profile_picture'],
                "created_at" => $row['created_at'],
                "properties_count" => $row['properties_count']
            );
        }
    }

    // Get inactive users (from users table) - use DISTINCT to avoid duplicates
    $sql = "SELECT DISTINCT u.user_id, u.reg_id, u.status, u.profile_picture,
                   r.role, r.first_name, r.middle_name, r.last_name, r.suffix, r.phone, r.email, r.created_at,
                   CASE 
                       WHEN r.role = 'BH Owner' THEN (
                           SELECT COUNT(*) 
                           FROM boarding_houses bh 
                           WHERE bh.user_id = u.user_id 
                           AND bh.status = 'Active'
                       )
                       ELSE 0
                   END as properties_count
            FROM users u
            JOIN registrations r ON u.reg_id = r.id
            WHERE u.status = 'Inactive'
            ORDER BY r.created_at DESC";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $inactive_users[] = array(
                "user_id" => $row['user_id'],
                "reg_id" => $row['reg_id'],
                "role" => $row['role'],
                "first_name" => $row['first_name'],
                "middle_name" => $row['middle_name'],
                "last_name" => $row['last_name'],
                "suffix" => $row['suffix'],
                "full_name" => trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . (!empty($row['suffix']) ? ' ' . $row['suffix'] : '')),
                "phone" => $row['phone'],
                "email" => $row['email'],
                "status" => $row['status'],
                "profile_picture" => $row['profile_picture'],
                "created_at" => $row['created_at'],
                "properties_count" => $row['properties_count']
            );
        }
    }

} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>BoardEase Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin_dashboard.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            height: 100%;
            height: 100dvh; /* Dynamic viewport height for mobile */
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #E6DAC8 0%, #F5F5DC 50%, #E6DAC8 100%);
            color: #4A4A4A;
            display: flex;
            min-height: 100%;
            min-height: 100dvh; /* Dynamic viewport height for mobile */
            height: 100%;
            height: 100dvh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            height: 100dvh; /* Dynamic viewport height for mobile */
            background: 
                radial-gradient(circle at 20% 80%, rgba(141, 110, 99, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(141, 110, 99, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(230, 218, 200, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 60% 60%, rgba(141, 110, 99, 0.04) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: #E6DAC8;
            position: fixed;
            height: 100vh;
            height: 100dvh; /* Dynamic viewport height for mobile */
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 4px 0 25px rgba(141, 110, 99, 0.3);
            backdrop-filter: blur(15px);
            border-right: 2px solid rgba(230, 218, 200, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Offcanvas sidebar styling */
        .offcanvas.sidebar {
            width: 280px !important;
            max-width: 85vw !important;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%) !important;
            color: #E6DAC8;
            z-index: 1055 !important;
        }

        .offcanvas.sidebar .offcanvas-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 1.5rem 1.25rem;
        }

        .offcanvas.sidebar .offcanvas-title {
            font-size: 1.35rem;
            font-weight: 600;
            color: white;
        }

        .offcanvas.sidebar .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            min-width: 44px;
            min-height: 44px;
            padding: 0.5rem;
        }

        /* Fix backdrop shadow - make it lighter and not covering sidebar */
        .offcanvas-backdrop {
            background-color: rgba(0, 0, 0, 0.3) !important;
            z-index: 1050 !important;
        }

        .offcanvas-backdrop.show {
            opacity: 0.3 !important;
        }

        /* Ensure sidebar is above backdrop */
        #sidebarOffcanvas {
            z-index: 1055 !important;
        }

        @media (max-width: 575.98px) {
            .offcanvas.sidebar {
                width: 70% !important;
                max-width: 70% !important;
            }

            .offcanvas.sidebar .offcanvas-header {
                padding: 1.25rem 1rem;
            }

            .offcanvas.sidebar .offcanvas-title,
            .offcanvas.sidebar .offcanvas-title h5,
            .offcanvas.sidebar h5.offcanvas-title {
                font-size: 1.1rem !important;
            }
            
            .offcanvas.sidebar .offcanvas-title span {
                font-size: 1.1rem !important;
            }

            .offcanvas.sidebar .nav-item {
                padding: 1rem 1.25rem !important;
                min-height: 48px;
                font-size: 0.85rem !important;
            }
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-left: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .nav-item:hover::before {
            left: 100%;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.15);
            border-left-color: #D2B48C;
            transform: translateX(5px);
            box-shadow: inset 0 0 20px rgba(255,255,255,0.1);
        }

        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #A97A50;
            font-weight: 600;
        }

        .nav-item i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 0;
            padding: 0;
            background: linear-gradient(135deg, #E6DAC8 0%, #F5F5DC 50%, #E6DAC8 100%);
            min-height: 100vh;
            animation: fadeInUp 0.6s ease-out;
            position: relative;
        }

        /* Responsive margin for desktop */
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
            }
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 10% 20%, rgba(141, 110, 99, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(230, 218, 200, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-header {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(141, 110, 99, 0.3);
            margin-bottom: 2rem;
            border: 1px solid rgba(230, 218, 200, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            animation: slideInDown 0.8s ease-out;
        }

        .content-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #E6DAC8 0%, #F5F5DC 100%);
            border-radius: 20px 20px 0 0;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #E6DAC8;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        @keyframes textGlow {
            from {
                text-shadow: 0 0 5px rgba(141, 110, 99, 0.3);
            }
            to {
                text-shadow: 0 0 20px rgba(141, 110, 99, 0.6);
            }
        }

        .content-header p {
            color: #F5F5DC;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.2rem; /* Reduced from 1.5rem (80%) */
            margin-bottom: 1.6rem; /* Reduced from 2rem (80%) */
            animation: fadeInUp 0.8s ease-out 0.2s both;
            font-size: 0.8em; /* Scale down all text to 80% */
        }

        .stat-card {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            border-radius: 16px; /* Reduced from 20px (80%) */
            box-shadow: 
                0 6.4px 25.6px rgba(141, 110, 99, 0.15),
                0 3.2px 12.8px rgba(141, 110, 99, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: cardSlideIn 0.6s ease-out;
            border: 1px solid rgba(141, 110, 99, 0.3);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            border-radius: 20px 20px 0 0;
            opacity: 0;
            transition: opacity 0.3s ease;
            box-shadow: 0 2px 8px rgba(141, 110, 99, 0.3);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        @keyframes cardSlideIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        /* Pause pulse animation on hover for pending card */
        .stat-card.pending-card-pulse:hover {
            animation: none !important;
            transform: translateY(-10px) scale(1.02) !important;
            box-shadow: 0 20px 40px rgba(255, 193, 7, 0.25) !important;
        }

        .stat-card.pending-card-pulse:hover .stat-icon {
            animation: none !important;
        }

        .stat-card.pending-card-pulse:hover .stat-content h3 {
            animation: none !important;
            transform: scale(1) !important;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        /* Slow pulse animation for pending approvals card when there are pending approvals */
        .stat-card.pending-card-pulse {
            animation: slowPulse 1.5s ease-in-out infinite !important;
        }

        @keyframes slowPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 
                    0 6.4px 25.6px rgba(141, 110, 99, 0.15),
                    0 3.2px 12.8px rgba(141, 110, 99, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }
            50% {
                transform: scale(1.02);
                box-shadow: 
                    0 6.4px 25.6px rgba(255, 193, 7, 0.3),
                    0 3.2px 12.8px rgba(255, 193, 7, 0.2),
                    0 0 20px rgba(255, 193, 7, 0.15),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }
        }

        /* Pulse the icon inside the pending card */
        .stat-card.pending-card-pulse .stat-icon {
            animation: iconSlowPulse 1.5s ease-in-out infinite !important;
        }

        @keyframes iconSlowPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(255, 193, 7, 0.5);
            }
        }

        /* Pulse the number when there are pending approvals */
        .stat-card.pending-card-pulse .stat-content h3 {
            animation: numberSlowPulse 1.5s ease-in-out infinite !important;
        }

        @keyframes numberSlowPulse {
            0%, 100% {
                transform: scale(1);
                color: #e74c3c;
            }
            50% {
                transform: scale(1.05);
                color: #c0392b;
            }
        }

        .stat-icon {
            width: 56px; /* Reduced from 70px (80%) */
            height: 56px; /* Reduced from 70px (80%) */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.44rem; /* Reduced from 1.8rem (80%) */
            color: white;
            margin: 0 auto 0.6rem; /* Reduced from 0.8rem */
            position: relative;
            animation: iconFloat 3s ease-in-out infinite;
        }

        @keyframes iconFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .stat-content h3 {
            font-size: 2rem; /* Reduced from 2.5rem (80%) */
            font-weight: bold;
            margin-bottom: 0.3rem; /* Reduced from 0.4rem */
            color: #2c3e50;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            animation: numberCount 1s ease-out;
        }

        @keyframes numberCount {
            from {
                transform: scale(0.5);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .stat-content p {
            color: #34495e;
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 0 1px 1px rgba(0,0,0,0.05);
        }

        /* Special styling for pending count when > 0 */
        .stat-card:nth-child(3) .stat-content h3 {
            color: #e74c3c;
            font-weight: 800;
        }

        .stat-card:nth-child(3) .stat-content p {
            color: #c0392b;
            font-weight: 600;
        }

        /* Animations for pending count emphasis */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .dashboard-grid .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-grid .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-grid .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-grid .dashboard-card:nth-child(4) { animation-delay: 0.4s; }

        .priority-section {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%) !important;
            color: white !important;
            padding: 2rem !important;
            border-radius: 15px !important;
            margin-bottom: 2rem !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }

        .priority-section h2 {
            margin-bottom: 1.5rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            color: white !important;
        }

        .pending-approvals {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(380px, 380px)) !important;
            gap: 2rem !important;
            animation: fadeInUp 0.8s ease-out 0.4s both !important;
            align-items: stretch !important;
            justify-content: start !important;
        }

        /* Staggered animation for approval cards */
        .pending-approvals .approval-card {
            animation: cardSlideIn 0.6s ease-out both;
        }

        .pending-approvals .approval-card:nth-child(1) { animation-delay: 0.1s; }
        .pending-approvals .approval-card:nth-child(2) { animation-delay: 0.2s; }
        .pending-approvals .approval-card:nth-child(3) { animation-delay: 0.3s; }
        .pending-approvals .approval-card:nth-child(4) { animation-delay: 0.4s; }
        .pending-approvals .approval-card:nth-child(5) { animation-delay: 0.5s; }
        .pending-approvals .approval-card:nth-child(6) { animation-delay: 0.6s; }

        .dashboard-card {
            background: white;
            border-radius: 12px; /* Reduced from 15px (80%) */
            box-shadow: 0 3.2px 12px rgba(0,0,0,0.1); /* Reduced from 4px 15px (80%) */
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            animation: cardSlideIn 0.6s ease-out;
            font-size: 0.8em; /* Scale down all text to 80% */
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8D6E63, #A97A50, #D2B48C);
            border-radius: 20px 20px 0 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dashboard-card:hover::before {
            opacity: 1;
        }

        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: white;
            padding: 1.2rem; /* Reduced from 1.5rem (80%) */
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }

        .card-header h3 {
            font-size: 1.04rem; /* Reduced from 1.3rem (80%) */
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .card-header i {
            font-size: 1rem;
            animation: iconPulse 2s ease-in-out infinite;
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Professional Loading States */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Enhanced Dashboard Animations */
        .dashboard-section {
            animation: fadeInUp 0.8s ease-out;
        }

        .dashboard-section .stat-card {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Professional Hover Effects */
        .dashboard-card:hover .card-header {
            background: linear-gradient(135deg, #A97A50 0%, #D2B48C 100%);
        }

        .dashboard-card:hover .card-header i {
            animation: iconBounce 0.6s ease-in-out;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .action-btn {
            background: linear-gradient(135deg, #8D6E63, #A97A50);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(141, 110, 99, 0.3);
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, #A97A50, #D2B48C);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(141, 110, 99, 0.4);
        }

        .action-btn:active {
            transform: translateY(-1px) scale(1.02);
        }

        .action-btn.danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .action-btn.danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }

        .action-btn.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .action-btn.success:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .user-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px !important;
            height: 40px !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #8D6E63, #A97A50) !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: bold !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 8px rgba(141, 110, 99, 0.3) !important;
            flex-shrink: 0;
        }

        .approval-card:hover .user-avatar {
            transform: scale(1.1) rotate(5deg) !important;
            box-shadow: 0 4px 12px rgba(141, 110, 99, 0.4) !important;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .recent-activity {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .activity-content p {
            font-size: 0.8rem;
            color: #666;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #999;
        }

        .approval-card {
            background: white !important;
            border-radius: 8px !important;
            padding: 0.5rem !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            display: flex !important;
            flex-direction: column !important;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            position: relative !important;
            overflow: hidden !important;
            cursor: pointer;
        }

        .approval-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 3px !important;
            background: linear-gradient(90deg, #8D6E63, #A97A50, #D2B48C) !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
            z-index: 1;
        }

        .approval-card:hover::before {
            opacity: 1 !important;
        }

        .approval-card:hover {
            transform: translateY(-5px) scale(1.02) !important;
            box-shadow: 0 8px 25px rgba(141, 110, 99, 0.25) !important;
        }

        .approval-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.05rem;
            flex-shrink: 0;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .approval-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            min-width: 200px;
            color: #000;
        }
        
        .approval-user-info {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }
        
        .approval-user strong {
            color: #000;
            display: inline-block;
        }
        
        .approval-role {
            display: inline-flex;
            gap: 0.3rem;
            font-size: 0.85rem;
            color: #000;
        }
        
        .role-type,
        .role-status {
            color: #000;
        }
        
        .approval-user small {
            color: #000;
        }

        .approval-actions {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
            flex-shrink: 0;
            justify-content: flex-end;
            align-items: flex-start;
        }

        .approval-details {
            font-size: 0.9rem;
            color: #000;
            margin-bottom: 0.2rem;
            margin-top: 0.5rem;
            overflow: hidden;
            line-height: 1.2;
        }
        
        .approval-details strong {
            color: #000;
        }
        
        .approval-details br {
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        .verification-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
            padding: 0.2rem 0.5rem !important;
            border-radius: 15px !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            margin-top: 0.15rem !important;
            margin-bottom: 0.05rem !important;
            transition: all 0.3s ease !important;
        }

        .verification-verified {
            background: #d4edda !important;
            color: #155724 !important;
        }

        .verification-pending {
            background: #fff3cd !important;
            color: #856404 !important;
            animation: pulse 2s ease-in-out infinite !important;
        }

        .verification-failed {
            background: #f8d7da !important;
            color: #721c24 !important;
        }

        .approval-card:hover .verification-badge {
            transform: scale(1.05) !important;
        }

        .email-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .email-sent {
            color: #28a745;
        }

        .email-failed {
            color: #dc3545;
        }

        .registration-date {
            font-size: 0.8rem;
            color: #000;
            margin-top: 0.1rem;
            margin-bottom: 0;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 0;
            background: #f8f9fa;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: #666;
        }

        .tab:hover {
            background: rgba(141, 110, 99, 0.1);
            color: #8D6E63;
        }

        .tab.active {
            border-bottom-color: #5D4037;
            color: #5D4037;
            font-weight: 600;
            background: white;
        }

        .tab-content {
            display: none;
            padding: 0;
            overflow: visible;
        }

        .tab-content.active {
            display: block;
            overflow: visible;
        }

        .tab-content .card-content {
            padding: 1.5rem;
        }

        /* Scrollable data areas for boarding houses section */
        #boarding-houses-section #all-tab .table-container {
            position: relative;
        }

        #boarding-houses-section #all-tab .table-responsive {
            max-height: calc(100vh - 500px);
            overflow-y: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }

        /* Sticky table header - keeps column names fixed */
        #boarding-houses-section #all-tab .table {
            margin-bottom: 0;
        }

        #boarding-houses-section #all-tab .table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f8f9fa !important;
        }

        #boarding-houses-section #all-tab .table thead th {
            background: #f8f9fa !important;
            position: sticky;
            top: 0;
            z-index: 11;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #dee2e6;
        }

        #boarding-houses-section #by-owner-tab .card-content {
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.5rem;
            -webkit-overflow-scrolling: touch;
        }

        /* Default scrollbar for by-owner tab content */

        /* Default scrollbar for table responsive in all-tab */

        /* User Management Section - Sticky headers and brown scrollbars */
        #user-management-section .table-container {
            position: relative;
        }

        #user-management-section .table-responsive {
            max-height: calc(100vh - 500px);
            overflow-y: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }

        #user-management-section .table {
            margin-bottom: 0;
        }

        /* Sticky table headers for User Management */
        #user-management-section .table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f8f9fa !important;
        }

        #user-management-section .table thead th {
            background: #f8f9fa !important;
            position: sticky;
            top: 0;
            z-index: 11;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #dee2e6;
        }

        /* Default scrollbar for User Management tables */

        /* Reduce padding for system notifications container specifically */
        #system-tab .card-content {
            padding-top: 0.5rem;
            padding-bottom: 1rem;
        }
        
        #system-notifications-container {
            margin-top: 0;
        }
        
        /* Remove any gap above first notification */
        #system-notifications-container .notification-item:first-child {
            margin-top: 0;
            padding-top: 0.75rem;
        }

        /* Notifications section scrollable content */
        #notifications-section #system-tab .card-content {
            position: relative;
        }
        
        /* Loading Indicator Styling */
        #system-notifications-loading {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            padding: 3rem 2rem;
            text-align: center;
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            animation: fadeIn 0.3s ease-in;
        }
        
        #system-notifications-loading .fa-spinner {
            font-size: 2.5rem;
            color: #8D6E63;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        #system-notifications-loading div:first-child {
            margin-bottom: 1rem;
        }
        
        #system-notifications-loading div:nth-child(2) {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        #system-notifications-loading div:nth-child(3) {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
        }

        /* Responsive Design - Tablet and below */
        @media (max-width: 991.98px) {
            .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .analytics-overview-grid {
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
                padding: 0.5rem !important;
            }

            .analytics-overview-item {
                min-height: auto !important;
                padding: 1.25rem !important;
            }
        }

        /* Responsive Design - Mobile */
        @media (max-width: 767.98px) {
            html, body {
                height: 100dvh !important;
                min-height: 100dvh !important;
                max-height: 100dvh !important;
                overflow-x: hidden;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                min-height: 100dvh !important;
            }

            .container-fluid {
                padding: 1rem !important;
                min-height: calc(100dvh - 2rem) !important;
            }

            /* Ensure modal covers full viewport on mobile */
            .modal {
                height: 100dvh !important;
                min-height: 100dvh !important;
                max-height: 100dvh !important;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .pending-approvals {
                grid-template-columns: 1fr !important;
                gap: 1.5rem;
            }

            .content-header {
                padding: 1.25rem 1rem !important;
                margin-bottom: 1.5rem !important;
            }

            .content-header h1 {
                font-size: 1.3rem !important;
                line-height: 1.3;
            }

            .content-header p {
                font-size: 0.85rem !important;
                margin-bottom: 0 !important;
            }

            /* Comprehensive font size adjustments for all components on mobile */
            /* Buttons */
            .btn-modern,
            .btn,
            button,
            .action-btn,
            .btn-primary,
            .btn-secondary,
            .btn-success,
            .btn-danger,
            .btn-warning {
                font-size: 0.85rem !important;
                padding: 0.5rem 1rem !important;
            }

            /* Stat Cards */
            .stat-card .stat-content h3,
            .stat-card h3 {
                font-size: 1.5rem !important;
            }

            .stat-card .stat-content p,
            .stat-card p {
                font-size: 0.8rem !important;
            }

            .stat-card .stat-label {
                font-size: 0.75rem !important;
            }

            /* Tables */
            .table th,
            .table td,
            table th,
            table td {
                font-size: 0.85rem !important;
                padding: 0.5rem 0.75rem !important;
            }

            .table thead th {
                font-size: 0.9rem !important;
            }

            /* Cards */
            .card-header h3,
            .card h3,
            .card-header h2,
            .card h2 {
                font-size: 1rem !important;
            }

            .card-body,
            .card p,
            .card span,
            .card div {
                font-size: 0.85rem !important;
            }

            /* Section Headings */
            .section-title,
            .section-title h2,
            .section-title h3 {
                font-size: 1.1rem !important;
            }

            /* Approval Cards */
            .approval-card h4,
            .approval-card .user-name {
                font-size: 0.9rem !important;
            }

            .approval-card p,
            .approval-card .user-email,
            .approval-card .user-role {
                font-size: 0.8rem !important;
            }

            .verification-badge {
                font-size: 0.7rem !important;
                padding: 2px 8px !important;
            }

            /* Status Badges */
            .status-badge,
            .status-badge-table,
            .badge {
                font-size: 0.75rem !important;
                padding: 3px 10px !important;
            }

            /* Tabs */
            .tabs .tab,
            .tab {
                font-size: 0.85rem !important;
                padding: 0.5rem 1rem !important;
            }

            /* Filters */
            .table-filters .filter-btn,
            .filter-btn {
                font-size: 0.85rem !important;
                padding: 0.4rem 0.8rem !important;
            }

            /* Search Boxes */
            .search-box input,
            input[type="text"],
            input[type="search"],
            input[type="email"],
            input[type="date"],
            select {
                font-size: 0.85rem !important;
            }

            /* Lists */
            .admin-list,
            .activity-list,
            .boarding-houses-list,
            .user-list {
                font-size: 0.85rem !important;
            }

            .admin-item h4,
            .activity-item h4,
            .boarding-house-item h4 {
                font-size: 0.9rem !important;
            }

            .admin-item p,
            .activity-item p,
            .boarding-house-item p {
                font-size: 0.8rem !important;
            }

            /* Analytics */
            .analytics-card h3,
            .analytics-card .card-title {
                font-size: 1.2rem !important;
            }

            .analytics-card p,
            .analytics-card .card-text {
                font-size: 0.85rem !important;
            }

            /* Charts */
            .chart-container h4 {
                font-size: 0.9rem !important;
            }

            /* Notifications */
            .notification-item,
            .notification-item p,
            .notification-item span {
                font-size: 0.85rem !important;
            }

            .notification-item h4 {
                font-size: 0.9rem !important;
            }

            /* Sidebar/Navigation */
            .sidebar .nav-link,
            .offcanvas .nav-link,
            .menu-item,
            .nav-item a {
                font-size: 0.85rem !important;
            }

            .sidebar .menu-title,
            .offcanvas .menu-title {
                font-size: 0.9rem !important;
            }

            /* General text elements */
            h1 { font-size: 1.3rem !important; }
            h2 { font-size: 1.1rem !important; }
            h3 { font-size: 1rem !important; }
            h4 { font-size: 0.9rem !important; }
            h5 { font-size: 0.85rem !important; }
            h6 { font-size: 0.8rem !important; }
            p, span, div, li, td, th { font-size: 0.85rem !important; }
            small { font-size: 0.75rem !important; }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -1rem;
                padding: 0 1rem;
            }

            .table-responsive {
                border-radius: 0;
            }

            .table {
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.75rem 0.5rem !important;
                white-space: nowrap;
            }

            .stat-card .card-body {
                padding: 1.25rem !important;
            }

            .stat-icon {
                width: 48px !important;
                height: 48px !important;
                font-size: 1.2rem !important;
                margin-bottom: 0.6rem !important;
            }

            .stat-content h3 {
                font-size: 1.75rem !important;
            }

            .stat-content p {
                font-size: 0.95rem !important;
            }

            .dashboard-card {
                margin-bottom: 1rem;
            }

            .card-header {
                padding: 1rem !important;
            }

            .card-header h3 {
                font-size: 1.1rem !important;
            }

            .card-content {
                padding: 1rem !important;
            }

            .action-btn {
                padding: 0.625rem 1.25rem !important;
                font-size: 0.875rem !important;
                min-height: 44px; /* Touch-friendly */
            }

            .approval-card {
                padding: 0.75rem !important;
            }

            .approval-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.75rem !important;
            }

            .approval-actions {
                width: 100%;
                justify-content: flex-start !important;
            }

            .approval-actions .action-btn {
                flex: 1;
                min-width: auto;
            }

            .tabs {
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
            }

            .tab {
                padding: 0.75rem 1rem !important;
                font-size: 0.875rem !important;
                min-width: fit-content !important;
                white-space: nowrap !important;
                flex-shrink: 0 !important;
            }

            .priority-section {
                padding: 1.25rem 1rem !important;
                margin-top: 2rem !important;
            }

            .priority-section h2 {
                font-size: 1.25rem !important;
                flex-wrap: wrap;
            }

            .filter-btn {
                padding: 0.5rem 0.875rem !important;
                font-size: 0.85rem !important;
                margin: 0 !important;
                min-height: 40px !important;
            }

            .table-filters {
                padding: 0.75rem 1rem !important;
                gap: 0.5rem !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                flex-wrap: nowrap !important;
            }

            /* Owner section - no horizontal scrolling, display all content */
            .owner-section {
                padding: 1rem !important;
                width: 100% !important;
                overflow-x: visible !important;
            }

            .owner-header {
                flex-wrap: wrap !important;
                width: 100% !important;
            }

            .boarding-houses-list {
                width: 100% !important;
            }

            .boarding-house-item {
                flex-wrap: wrap !important;
                width: 100% !important;
            }

            .house-status {
                flex-wrap: wrap !important;
            }

            /* Adjust scrollable areas for mobile */
            #boarding-houses-section #all-tab .table-responsive {
                max-height: calc(100vh - 300px) !important;
            }

            #user-management-section .table-responsive {
                max-height: calc(100vh - 300px) !important;
            }

            /* Notifications section mobile */
            #system-notifications-container {
                max-height: calc(100vh - 300px) !important;
            }

            #boarding-houses-section #by-owner-tab .card-content {
                max-height: calc(100vh - 350px) !important;
            }

            .owner-boarding-houses {
                max-height: calc(100vh - 350px) !important;
            }

            /* Modal improvements for mobile */
            .modal-content {
                margin: 0.5rem !important;
                width: calc(100% - 1rem) !important;
                max-width: 100% !important;
                max-height: calc(100vh - 1rem) !important;
                overflow: hidden !important;
            }

            .modal-body {
                padding: 1rem !important;
            }

            /* Modal tabs - horizontal scroll on mobile */
            .settings-tabs,
            .account-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
                flex-wrap: nowrap;
            }

            .settings-tabs .tab,
            .account-tabs .tab {
                white-space: nowrap;
                flex-shrink: 0;
                min-width: fit-content;
                padding: 10px 16px;
            }

            /* Tab content - responsive on mobile */
            #notificationSettingsModal .tab-content,
            #accountManagementModal .tab-content {
                overflow-x: hidden;
                overflow-y: visible;
                word-wrap: break-word;
                overflow-wrap: break-word;
                min-height: fit-content;
            }

            /* Ensure modal content structure on mobile - small screens */
            #notificationSettingsModal .modal-content,
            #accountManagementModal .modal-content,
            #userDetailsModal .modal-content,
            #boardingHouseDetailsModal .modal-content {
                margin: 0.5rem !important;
                width: calc(100% - 1rem) !important;
                max-width: 100% !important;
                max-height: 90vh !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
            }

            /* Modal header - fixed height on mobile */
            #notificationSettingsModal .modal-header,
            #accountManagementModal .modal-header {
                flex-shrink: 0 !important;
                padding: 1rem !important;
                min-height: auto !important;
            }

            /* Modal footer - fixed height on mobile */
            #notificationSettingsModal .modal-footer,
            #accountManagementModal .modal-footer {
                flex-shrink: 0 !important;
                padding: 1rem !important;
                min-height: auto !important;
            }

            /* Modal body should scroll to show all content on small screens */
            #notificationSettingsModal .modal-body,
            #accountManagementModal .modal-body,
            #userDetailsModal .modal-body,
            #boardingHouseDetailsModal .modal-body {
                overflow-y: scroll !important;
                overflow-x: hidden !important;
                flex: 1 1 0% !important;
                height: 0 !important;
                min-height: 0 !important;
                -webkit-overflow-scrolling: touch;
                padding: 1rem !important;
                padding-bottom: 4rem !important;
                position: relative;
            }

            /* Font size adjustments for mobile - make text smaller */
            #notificationSettingsModal .modal-header h2,
            #accountManagementModal .modal-header h2 {
                font-size: 1.1rem !important;
            }

            #notificationSettingsModal .tab-content h3,
            #accountManagementModal .tab-content h3 {
                font-size: 1rem !important;
            }

            #notificationSettingsModal .tab-content h4,
            #accountManagementModal .tab-content h4 {
                font-size: 0.9rem !important;
            }

            #notificationSettingsModal .tab-content p,
            #accountManagementModal .tab-content p {
                font-size: 0.85rem !important;
            }

            #notificationSettingsModal .tab-content,
            #accountManagementModal .tab-content {
                font-size: 0.85rem !important;
            }

            #notificationSettingsModal .settings-tabs .tab,
            #accountManagementModal .account-tabs .tab {
                font-size: 0.85rem !important;
                padding: 8px 12px !important;
            }

            #notificationSettingsModal .btn-modern,
            #accountManagementModal .btn-modern {
                font-size: 0.85rem !important;
                padding: 0.5rem 1rem !important;
            }

            #notificationSettingsModal .setting-item,
            #notificationSettingsModal .channel-item,
            #notificationSettingsModal .template-item,
            #accountManagementModal .admin-item,
            #accountManagementModal .activity-item {
                font-size: 0.85rem !important;
            }

            #notificationSettingsModal .setting-item h4,
            #notificationSettingsModal .channel-item h4,
            #notificationSettingsModal .template-item h4 {
                font-size: 0.9rem !important;
            }

            #notificationSettingsModal .admin-info h4,
            #accountManagementModal .admin-info h4 {
                font-size: 0.9rem !important;
            }

            #notificationSettingsModal .admin-info p,
            #accountManagementModal .admin-info p {
                font-size: 0.8rem !important;
            }

            /* Font size adjustments for User Details and Boarding House modals on mobile */
            #userDetailsModal .modal-header h2,
            #boardingHouseDetailsModal .modal-header h2 {
                font-size: 1.1rem !important;
            }

            #userDetailsModal .modal-body h2,
            #userDetailsModal .modal-body h3,
            #boardingHouseDetailsModal .modal-body h2,
            #boardingHouseDetailsModal .modal-body h3 {
                font-size: 1rem !important;
            }

            #userDetailsModal .modal-body h4,
            #userDetailsModal .modal-body h5,
            #boardingHouseDetailsModal .modal-body h4,
            #boardingHouseDetailsModal .modal-body h5 {
                font-size: 0.9rem !important;
            }

            #userDetailsModal .modal-body p,
            #userDetailsModal .modal-body span,
            #userDetailsModal .modal-body div,
            #boardingHouseDetailsModal .modal-body p,
            #boardingHouseDetailsModal .modal-body span,
            #boardingHouseDetailsModal .modal-body div {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .modal-body,
            #boardingHouseDetailsModal .modal-body {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .user-details-section h3,
            #userDetailsModal .profile-info h2,
            #boardingHouseDetailsModal .user-details-section h3,
            #boardingHouseDetailsModal .property-info h3 {
                font-size: 1rem !important;
            }

            #userDetailsModal .user-details-section h4,
            #boardingHouseDetailsModal .user-details-section h4 {
                font-size: 0.9rem !important;
            }

            #userDetailsModal .info-label,
            #userDetailsModal .info-value,
            #boardingHouseDetailsModal .info-label,
            #boardingHouseDetailsModal .info-value {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .status-badge,
            #boardingHouseDetailsModal .status-badge {
                font-size: 0.75rem !important;
                padding: 3px 10px !important;
            }

            #userDetailsModal .boarding-house-item,
            #userDetailsModal .booking-item,
            #boardingHouseDetailsModal .room-item,
            #boardingHouseDetailsModal .booking-item {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .boarding-house-item strong,
            #userDetailsModal .booking-item .item-title,
            #boardingHouseDetailsModal .room-item h4,
            #boardingHouseDetailsModal .booking-item .item-title {
                font-size: 0.9rem !important;
            }

            #userDetailsModal .boarding-house-item p,
            #userDetailsModal .booking-item .item-details,
            #boardingHouseDetailsModal .room-item p,
            #boardingHouseDetailsModal .booking-item .item-details {
                font-size: 0.8rem !important;
            }

            #userDetailsModal .property-address,
            #boardingHouseDetailsModal .property-address {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .user-info-grid,
            #boardingHouseDetailsModal .user-info-grid {
                font-size: 0.85rem !important;
            }

            /* Ensure tab content is fully visible */
            #notificationSettingsModal .tab-content,
            #accountManagementModal .tab-content {
                min-height: fit-content;
                padding-bottom: 2rem;
                overflow: visible !important;
            }

            /* Ensure all containers inside can expand */
            #notificationSettingsModal .tab-content > *,
            #accountManagementModal .tab-content > * {
                overflow: visible !important;
            }

            #notificationSettingsModal #current-settings-container,
            #notificationSettingsModal #channels-container,
            #notificationSettingsModal #types-container,
            #notificationSettingsModal #templates-container {
                overflow: visible !important;
                min-height: fit-content !important;
            }

            /* Settings grid - single column on mobile */
            .settings-grid,
            .template-section,
            .channel-settings {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            /* Admin controls - stack vertically on mobile */
            .tab-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .admin-controls {
                flex-direction: column;
                width: 100%;
                gap: 10px;
            }

            .search-box {
                width: 100%;
            }

            .search-box input {
                width: 100%;
            }

            /* Admin item - stack vertically on mobile */
            .admin-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .admin-info {
                width: 100%;
            }

            .admin-status {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .admin-actions {
                width: 100%;
                justify-content: flex-end;
            }

            /* Activity list - single column on mobile */
            .activity-list {
                grid-template-columns: 1fr;
            }

            .activity-item {
                flex-direction: column;
                align-items: flex-start;
            }

            /* Activity filters - stack on mobile */
            .activity-filters {
                flex-direction: column;
                gap: 10px;
            }

            .activity-filters select,
            .activity-filters input {
                width: 100%;
            }

            /* Security stats - single column on mobile */
            #security-stats-container {
                grid-template-columns: 1fr !important;
                display: grid !important;
            }

            /* All dynamically generated containers - responsive */
            #current-settings-container,
            #channels-container,
            #types-container,
            #templates-container {
                width: 100%;
                box-sizing: border-box;
            }

            /* Override any inline grid styles for mobile */
            #current-settings-container .settings-grid,
            #channels-container .channels-grid,
            #types-container .types-grid {
                grid-template-columns: 1fr !important;
            }

            /* All text content - wrap properly */
            #notificationSettingsModal .tab-content,
            #accountManagementModal .tab-content {
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            #notificationSettingsModal .tab-content h3,
            #accountManagementModal .tab-content h3,
            #notificationSettingsModal .tab-content p,
            #accountManagementModal .tab-content p,
            #notificationSettingsModal .tab-content h4,
            #accountManagementModal .tab-content h4,
            #notificationSettingsModal .tab-content span,
            #accountManagementModal .tab-content span,
            #notificationSettingsModal .tab-content div,
            #accountManagementModal .tab-content div {
                word-wrap: break-word;
                overflow-wrap: break-word;
                max-width: 100%;
            }

            /* Buttons - full width on mobile if needed */
            #notificationSettingsModal .btn-modern,
            #accountManagementModal .btn-modern {
                width: auto;
                min-width: fit-content;
            }

            /* Cards and containers - full width */
            .setting-card,
            .channel-card,
            .template-item,
            .channel-item {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            /* Tables in modals - responsive */
            #notificationSettingsModal .tab-content table,
            #accountManagementModal .tab-content table {
                width: 100%;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Setting items - stack on mobile */
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            /* Channel items - full width */
            .channel-item {
                width: 100%;
                box-sizing: border-box;
            }

            /* Template items - full width */
            .template-item {
                width: 100%;
                box-sizing: border-box;
            }

            .template-item textarea {
                width: 100%;
                box-sizing: border-box;
            }

            /* Form elements mobile-friendly */
            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 16px !important; /* Prevents zoom on iOS */
                padding: 0.75rem !important;
                min-height: 44px; /* Touch-friendly */
            }

            /* Button groups */
            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-group .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .btn-group .btn:last-child {
                margin-bottom: 0;
            }
        }

        /* Additional responsive utilities - Small mobile */
        @media (max-width: 575.98px) {
            .container-fluid {
                padding: 0.75rem !important;
            }

            .content-header {
                padding: 1rem 0.75rem !important;
            }

            .content-header h1 {
                font-size: 1.25rem !important;
            }

            .content-header p {
                font-size: 0.875rem !important;
            }

            .stat-card .card-body {
                padding: 1rem !important;
            }

            .stat-icon {
                width: 44px !important;
                height: 44px !important;
                font-size: 1.1rem !important;
            }

            .stat-content h3 {
                font-size: 1.5rem !important;
            }

            .stat-content p {
                font-size: 0.875rem !important;
            }

            .card-header {
                padding: 0.875rem !important;
            }

            .card-header h3 {
                font-size: 1rem !important;
            }

            .card-content {
                padding: 0.875rem !important;
            }

            .action-btn {
                padding: 0.5rem 1rem !important;
                font-size: 0.8125rem !important;
            }

            .priority-section {
                padding: 1rem 0.75rem !important;
            }

            .priority-section h2 {
                font-size: 1.1rem !important;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.375rem !important;
                font-size: 0.8125rem !important;
            }

            .approval-card {
                padding: 0.625rem !important;
            }

            .user-avatar {
                width: 36px !important;
                height: 36px !important;
                font-size: 0.875rem !important;
            }

            .approval-user-info strong {
                font-size: 0.95rem !important;
            }

            .approval-details {
                font-size: 0.8125rem !important;
            }

            .verification-badge {
                font-size: 0.75rem !important;
                padding: 0.15rem 0.4rem !important;
            }

            /* Owner section - display all content, no horizontal scroll */
            .owner-section {
                padding: 0.875rem !important;
                width: 100% !important;
            }

            .owner-header {
                width: 100% !important;
                flex-wrap: wrap !important;
            }

            .boarding-houses-list {
                width: 100% !important;
            }

            .boarding-house-item {
                width: 100% !important;
                flex-wrap: wrap !important;
            }
        }

        /* Extra small devices */
        @media (max-width: 375px) {
            .content-header h1 {
                font-size: 1.1rem !important;
            }

            .content-header p {
                font-size: 0.8rem !important;
            }

            .stat-content h3 {
                font-size: 1.2rem !important;
            }

            .stat-content p {
                font-size: 0.75rem !important;
            }

            .card-header h3 {
                font-size: 0.9rem !important;
            }

            .action-btn {
                padding: 0.5rem 0.875rem !important;
                font-size: 0.75rem !important;
            }

            /* Even smaller fonts for very small screens - all components */
            /* Buttons */
            .btn-modern,
            .btn,
            button,
            .action-btn,
            .btn-primary,
            .btn-secondary,
            .btn-success,
            .btn-danger,
            .btn-warning {
                font-size: 0.75rem !important;
                padding: 0.4rem 0.8rem !important;
            }

            /* Stat Cards */
            .stat-card .stat-content h3,
            .stat-card h3 {
                font-size: 1.3rem !important;
            }

            .stat-card .stat-content p,
            .stat-card p {
                font-size: 0.7rem !important;
            }

            .stat-card .stat-label {
                font-size: 0.65rem !important;
            }

            /* Tables */
            .table th,
            .table td,
            table th,
            table td {
                font-size: 0.75rem !important;
                padding: 0.4rem 0.6rem !important;
            }

            .table thead th {
                font-size: 0.8rem !important;
            }

            /* Cards */
            .card-header h3,
            .card h3,
            .card-header h2,
            .card h2 {
                font-size: 0.9rem !important;
            }

            .card-body,
            .card p,
            .card span,
            .card div {
                font-size: 0.75rem !important;
            }

            /* Section Headings */
            .section-title,
            .section-title h2,
            .section-title h3 {
                font-size: 1rem !important;
            }

            /* Approval Cards */
            .approval-card h4,
            .approval-card .user-name {
                font-size: 0.85rem !important;
            }

            .approval-card p,
            .approval-card .user-email,
            .approval-card .user-role {
                font-size: 0.75rem !important;
            }

            .verification-badge {
                font-size: 0.65rem !important;
                padding: 2px 6px !important;
            }

            /* Status Badges */
            .status-badge,
            .status-badge-table,
            .badge {
                font-size: 0.7rem !important;
                padding: 2px 8px !important;
            }

            /* Tabs */
            .tabs .tab,
            .tab {
                font-size: 0.75rem !important;
                padding: 0.4rem 0.8rem !important;
            }

            /* Filters */
            .table-filters .filter-btn,
            .filter-btn {
                font-size: 0.75rem !important;
                padding: 0.3rem 0.6rem !important;
            }

            /* Search Boxes */
            .search-box input,
            input[type="text"],
            input[type="search"],
            input[type="email"],
            input[type="date"],
            select {
                font-size: 0.75rem !important;
            }

            /* Lists */
            .admin-list,
            .activity-list,
            .boarding-houses-list,
            .user-list {
                font-size: 0.75rem !important;
            }

            .admin-item h4,
            .activity-item h4,
            .boarding-house-item h4 {
                font-size: 0.85rem !important;
            }

            .admin-item p,
            .activity-item p,
            .boarding-house-item p {
                font-size: 0.7rem !important;
            }

            /* Analytics */
            .analytics-card h3,
            .analytics-card .card-title {
                font-size: 1rem !important;
            }

            .analytics-card p,
            .analytics-card .card-text {
                font-size: 0.75rem !important;
            }

            /* Charts */
            .chart-container h4 {
                font-size: 0.85rem !important;
            }

            /* Notifications */
            .notification-item,
            .notification-item p,
            .notification-item span {
                font-size: 0.75rem !important;
            }

            .notification-item h4 {
                font-size: 0.85rem !important;
            }

            /* Sidebar/Navigation - even smaller */
            .sidebar .nav-link,
            .offcanvas .nav-link,
            .menu-item,
            .nav-item a {
                font-size: 0.75rem !important;
            }

            .sidebar .menu-title,
            .offcanvas .menu-title {
                font-size: 0.85rem !important;
            }

            /* General text elements - even smaller */
            h1 { font-size: 1.1rem !important; }
            h2 { font-size: 1rem !important; }
            h3 { font-size: 0.9rem !important; }
            h4 { font-size: 0.85rem !important; }
            h5 { font-size: 0.8rem !important; }
            h6 { font-size: 0.75rem !important; }
            p, span, div, li, td, th { font-size: 0.75rem !important; }
            small { font-size: 0.65rem !important; }

            /* Modal for very small screens */
            #notificationSettingsModal .modal-content,
            #accountManagementModal .modal-content,
            #userDetailsModal .modal-content,
            #boardingHouseDetailsModal .modal-content {
                margin: 0.25rem !important;
                width: calc(100% - 0.5rem) !important;
                max-height: calc(100vh - 0.5rem) !important;
                height: calc(100vh - 0.5rem) !important;
            }

            #notificationSettingsModal .modal-header,
            #accountManagementModal .modal-header,
            #userDetailsModal .modal-header,
            #boardingHouseDetailsModal .modal-header {
                padding: 0.75rem !important;
            }

            #notificationSettingsModal .modal-header h2,
            #accountManagementModal .modal-header h2,
            #userDetailsModal .modal-header h2,
            #boardingHouseDetailsModal .modal-header h2 {
                font-size: 0.95rem !important;
            }

            #notificationSettingsModal .modal-footer,
            #accountManagementModal .modal-footer {
                padding: 0.75rem !important;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            #notificationSettingsModal .modal-body,
            #accountManagementModal .modal-body,
            #userDetailsModal .modal-body,
            #boardingHouseDetailsModal .modal-body {
                padding: 0.75rem !important;
                padding-bottom: 3rem !important;
            }

            #notificationSettingsModal .btn-modern,
            #accountManagementModal .btn-modern {
                padding: 0.5rem 1rem !important;
                font-size: 0.8rem !important;
            }

            /* Even smaller fonts for very small screens */
            #notificationSettingsModal .modal-header h2,
            #accountManagementModal .modal-header h2 {
                font-size: 1rem !important;
            }

            #notificationSettingsModal .tab-content h3,
            #accountManagementModal .tab-content h3 {
                font-size: 0.9rem !important;
            }

            #notificationSettingsModal .tab-content,
            #accountManagementModal .tab-content {
                font-size: 0.8rem !important;
            }

            #notificationSettingsModal .settings-tabs .tab,
            #accountManagementModal .account-tabs .tab {
                font-size: 0.8rem !important;
                padding: 6px 10px !important;
            }

            #notificationSettingsModal .tab-content h4,
            #accountManagementModal .tab-content h4 {
                font-size: 0.8rem !important;
            }

            #notificationSettingsModal .tab-content p,
            #accountManagementModal .tab-content p {
                font-size: 0.75rem !important;
            }

            #notificationSettingsModal .setting-item,
            #notificationSettingsModal .channel-item,
            #notificationSettingsModal .template-item,
            #accountManagementModal .admin-item,
            #accountManagementModal .activity-item {
                font-size: 0.75rem !important;
            }

            #notificationSettingsModal .setting-item h4,
            #notificationSettingsModal .channel-item h4,
            #notificationSettingsModal .template-item h4 {
                font-size: 0.85rem !important;
            }

            #notificationSettingsModal .admin-info h4,
            #accountManagementModal .admin-info h4 {
                font-size: 0.8rem !important;
            }

            #notificationSettingsModal .admin-info p,
            #accountManagementModal .admin-info p {
                font-size: 0.7rem !important;
            }

            #notificationSettingsModal .status-badge,
            #accountManagementModal .status-badge {
                font-size: 0.7rem !important;
                padding: 2px 8px !important;
            }

            #notificationSettingsModal .admin-role,
            #accountManagementModal .admin-role {
                font-size: 0.65rem !important;
            }

            /* Even smaller fonts for User Details and Boarding House modals on very small screens */
            #userDetailsModal .modal-body h2,
            #userDetailsModal .modal-body h3,
            #boardingHouseDetailsModal .modal-body h2,
            #boardingHouseDetailsModal .modal-body h3 {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .modal-body h4,
            #userDetailsModal .modal-body h5,
            #boardingHouseDetailsModal .modal-body h4,
            #boardingHouseDetailsModal .modal-body h5 {
                font-size: 0.8rem !important;
            }

            #userDetailsModal .modal-body,
            #userDetailsModal .modal-body p,
            #userDetailsModal .modal-body span,
            #userDetailsModal .modal-body div,
            #boardingHouseDetailsModal .modal-body,
            #boardingHouseDetailsModal .modal-body p,
            #boardingHouseDetailsModal .modal-body span,
            #boardingHouseDetailsModal .modal-body div {
                font-size: 0.75rem !important;
            }

            #userDetailsModal .user-details-section h3,
            #userDetailsModal .profile-info h2,
            #boardingHouseDetailsModal .user-details-section h3,
            #boardingHouseDetailsModal .property-info h3 {
                font-size: 0.85rem !important;
            }

            #userDetailsModal .user-details-section h4,
            #boardingHouseDetailsModal .user-details-section h4 {
                font-size: 0.8rem !important;
            }

            #userDetailsModal .info-label,
            #userDetailsModal .info-value,
            #boardingHouseDetailsModal .info-label,
            #boardingHouseDetailsModal .info-value {
                font-size: 0.75rem !important;
            }

            #userDetailsModal .status-badge,
            #boardingHouseDetailsModal .status-badge {
                font-size: 0.7rem !important;
                padding: 2px 8px !important;
            }

            #userDetailsModal .boarding-house-item,
            #userDetailsModal .booking-item,
            #boardingHouseDetailsModal .room-item,
            #boardingHouseDetailsModal .booking-item {
                font-size: 0.75rem !important;
            }

            #userDetailsModal .boarding-house-item strong,
            #userDetailsModal .booking-item .item-title,
            #boardingHouseDetailsModal .room-item h4,
            #boardingHouseDetailsModal .booking-item .item-title {
                font-size: 0.8rem !important;
            }

            #userDetailsModal .boarding-house-item p,
            #userDetailsModal .booking-item .item-details,
            #boardingHouseDetailsModal .room-item p,
            #boardingHouseDetailsModal .booking-item .item-details {
                font-size: 0.7rem !important;
            }

            #userDetailsModal .property-address,
            #boardingHouseDetailsModal .property-address {
                font-size: 0.75rem !important;
            }
        }

        /* Landscape mobile orientation */
        @media (max-width: 767.98px) and (orientation: landscape) {
            .content-header {
                padding: 1rem !important;
            }

            .content-header h1 {
                font-size: 1.35rem !important;
            }

            .stat-card .card-body {
                padding: 1rem !important;
            }
        }

        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .nav-item,
            .action-btn,
            .tab,
            .filter-btn {
                min-height: 44px;
                min-width: 44px;
            }

            .approval-card {
            cursor: pointer;
        }

            /* Prevent text selection on tap */
            .nav-item,
            .action-btn,
            .tab {
                -webkit-tap-highlight-color: rgba(141, 110, 99, 0.2);
                -webkit-touch-callout: none;
                user-select: none;
            }
        }

        /* Smooth scrolling for mobile */
        @media (max-width: 767.98px) {
            html {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }

            body {
                -webkit-overflow-scrolling: touch;
            }

            .main-content {
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Prevent horizontal scroll on mobile */
        @media (max-width: 767.98px) {
            body {
                overflow-x: hidden;
                width: 100%;
            }

            .main-content {
                overflow-x: hidden;
                width: 100%;
            }

            .container-fluid {
                max-width: 100%;
                overflow-x: hidden;
            }
        }

        /* Section Management */
        .content-section {
            display: none;
            width: 100%;
        }

        .content-section.active {
            display: block;
        }

        .content-section .content-header {
            margin-bottom: 2rem;
        }

        /* Table Styling */
        .data-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-filters {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #8D6E63;
            background: white;
            color: #8D6E63;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            white-space: nowrap;
            flex: 0 0 auto;
            min-height: 38px;
        }

        .filter-btn.active {
            background: #8D6E63;
            color: white;
        }

        .filter-btn:hover {
            background: #8D6E63;
            color: white;
        }

        /* Mobile responsive for filter buttons */
        @media (max-width: 767.98px) {
            .table-filters {
                padding: 0.75rem 1rem;
                gap: 0.5rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
            }

            .filter-btn {
                padding: 0.5rem 0.875rem;
                font-size: 0.85rem;
                min-height: 40px;
                flex-shrink: 0;
            }
        }

        @media (max-width: 575.98px) {
            .table-filters {
                padding: 0.625rem 0.75rem;
                gap: 0.5rem;
            }

            .filter-btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                min-height: 38px;
                border-radius: 18px;
            }
        }

        @media (max-width: 375px) {
            .filter-btn {
                padding: 0.45rem 0.65rem;
                font-size: 0.75rem;
                min-height: 36px;
            }
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .status-badge-table {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .notification-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.3s ease;
            margin: 0;
        }
        
        #system-notifications-container {
            padding: 0 !important;
            margin: 0 !important;
            max-height: calc(100vh - 500px);
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        #system-notifications-container > div:first-child {
            margin-top: 0 !important;
        }
        
        /* Reduce gap above first notification item */
        #system-notifications-container .notification-item:first-of-type {
            margin-top: 0;
            padding-top: 1rem;
        }

        /* Default scrollbar for notifications container */

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content h4 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            color: #333;
        }

        .notification-content p {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #999;
        }

        .compose-notification {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .compose-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .compose-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Boarding Houses Section Styling */
        .owner-boarding-houses {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            max-height: calc(100vh - 400px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.5rem;
            -webkit-overflow-scrolling: touch;
        }

        /* Default scrollbar for owner-boarding-houses */

        .owner-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid #8D6E63;
            width: 100%;
            box-sizing: border-box;
        }

        .owner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .property-count {
            background: #8D6E63;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .boarding-houses-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .boarding-house-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            box-sizing: border-box;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .boarding-house-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .house-info {
            flex: 1;
            min-width: 0;
            margin-right: 1rem;
        }

        .house-info strong {
            display: block;
            margin-bottom: 0.25rem;
            color: #333;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .house-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

         .house-status {
             display: flex;
             align-items: center;
             gap: 1rem;
             flex-wrap: wrap;
         }

         /* Button Container Styling for Single Line */
         .action-buttons-container {
             display: flex;
             gap: 0.5rem;
             flex-wrap: nowrap;
             align-items: center;
         }

         .action-buttons-container .action-btn {
             white-space: nowrap;
             padding: 0.5rem 0.75rem;
             font-size: 0.8rem;
             min-width: auto;
         }

         /* User Management Table Button Styling */
         #user-management-section .action-btn {
             white-space: nowrap;
             padding: 0.5rem 0.75rem;
             font-size: 0.8rem;
             margin-left: 0.25rem;
         }

         /* Notifications Section Button Styling */
         #notifications-section .action-btn {
             white-space: nowrap;
             padding: 0.5rem 0.75rem;
             font-size: 0.8rem;
         }

         /* Report and Settings Card Styling */
         .report-card, .settings-card {
             background: white;
             border-radius: 10px;
             padding: 1.5rem;
             box-shadow: 0 2px 8px rgba(0,0,0,0.1);
             text-align: center;
             transition: transform 0.3s ease, box-shadow 0.3s ease;
         }

         .report-card:hover, .settings-card:hover {
             transform: translateY(-3px);
             box-shadow: 0 4px 15px rgba(0,0,0,0.15);
         }

         .report-icon, .settings-icon {
             width: 60px;
             height: 60px;
             border-radius: 50%;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 1.5rem;
             color: white;
             margin: 0 auto 1rem;
         }

         .report-card h4, .settings-card h4 {
             font-size: 1.2rem;
             font-weight: 600;
             margin-bottom: 0.5rem;
             color: #333;
         }

         .report-card p, .settings-card p {
             color: #666;
             font-size: 0.9rem;
             margin-bottom: 1rem;
             line-height: 1.4;
         }

         /* Analytics Styling */
         .analytics-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
             gap: 1.5rem;
         }

         .analytics-item {
             display: flex;
             align-items: center;
             gap: 1rem;
             padding: 1rem;
             background: #f8f9fa;
             border-radius: 8px;
         }

         .analytics-chart {
             flex: 1;
         }

         .analytics-info h4 {
             font-size: 1rem;
             font-weight: 600;
             margin-bottom: 0.25rem;
             color: #333;
         }

         .analytics-info p {
             font-size: 0.8rem;
             color: #666;
             margin-bottom: 0.5rem;
         }

         .analytics-trend {
             font-size: 0.9rem;
             font-weight: 600;
             padding: 0.25rem 0.5rem;
             border-radius: 15px;
         }

         .analytics-trend.positive {
             background: #d4edda;
             color: #155724;
         }

         .analytics-trend.negative {
             background: #f8d7da;
             color: #721c24;
         }

         /* Analytics Dashboard */
         .analytics-dashboard {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
             gap: 2rem;
         }

        .analytics-card {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(141, 110, 99, 0.15), 
                        0 4px 16px rgba(141, 110, 99, 0.1),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            overflow: hidden;
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(141, 110, 99, 0.2);
        }

        .analytics-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            border-radius: 20px 20px 0 0;
        }

        .analytics-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 48px rgba(141, 110, 99, 0.25), 
                        0 8px 24px rgba(141, 110, 99, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

         .analytics-card.full-width {
             grid-column: 1 / -1;
         }

        .analytics-header {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: #E6DAC8;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .analytics-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(230, 218, 200, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .analytics-card:hover .analytics-header::before {
            left: 100%;
        }

        .analytics-header h3 {
            font-size: 1.4rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .analytics-header i {
            font-size: 1.6rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

         .analytics-header h3 {
             font-size: 1.3rem;
             font-weight: 600;
             display: flex;
             align-items: center;
             gap: 0.5rem;
         }

         .analytics-period select {
             background: rgba(255,255,255,0.2);
             color: white;
             border: 1px solid rgba(255,255,255,0.3);
             border-radius: 5px;
             padding: 0.5rem;
             font-size: 0.9rem;
         }

         .analytics-period select option {
             background: #8D6E63;
             color: white;
         }

        .analytics-content {
            padding: 2rem;
            background: linear-gradient(145deg, #F5F5DC 0%, #E6DAC8 100%);
            border-radius: 0 0 20px 20px;
            position: relative;
        }

        .analytics-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(141, 110, 99, 0.3), transparent);
        }

        .chart-container {
            margin-bottom: 2rem;
            text-align: center;
            background: linear-gradient(145deg, #F5F5DC 0%, #E6DAC8 100%);
            border-radius: 16px;
            padding: 2rem;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 6px 24px rgba(141, 110, 99, 0.12), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(141, 110, 99, 0.15);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            border-radius: 16px 16px 0 0;
        }

        .chart-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(141, 110, 99, 0.2), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .chart-container h4 {
            color: #5D4037;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .chart-container h4 i {
            color: #2196F3;
            font-size: 1.3rem;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }

        .chart-container canvas {
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(141, 110, 99, 0.1);
            transition: all 0.3s ease;
            width: 100% !important;
            height: 300px !important;
            max-height: 300px !important;
            min-height: 300px !important;
        }
        
        .chart-container > div {
            height: 300px !important;
            min-height: 300px !important;
        }

        .chart-container:hover canvas {
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(141, 110, 99, 0.15);
        }

        .analytics-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .metric {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(141, 110, 99, 0.1), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(141, 110, 99, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .metric::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            border-radius: 12px 12px 0 0;
        }

        .metric:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 8px 24px rgba(141, 110, 99, 0.2), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .metric-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #5D4037;
            margin-bottom: 0.5rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            animation: pulse 2s infinite;
        }

        .metric-label {
            display: block;
            font-size: 0.9rem;
            color: #8D6E63;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

         .metric {
             text-align: center;
             padding: 1rem;
             background: #f8f9fa;
             border-radius: 8px;
         }

         .metric-value {
             display: block;
             font-size: 1.5rem;
             font-weight: bold;
             color: #8D6E63;
             margin-bottom: 0.25rem;
         }

         .metric-label {
             font-size: 0.8rem;
             color: #666;
         }

         /* Geographic Distribution */
         .geographic-grid {
             display: grid;
             grid-template-columns: 1fr 1fr;
             gap: 2rem;
             align-items: start;
         }

         .location-stats {
             display: flex;
             flex-direction: column;
             gap: 1rem;
         }

         .location-item {
             display: flex;
             align-items: center;
             gap: 1rem;
             padding: 1rem;
             background: #f8f9fa;
             border-radius: 8px;
         }

         .location-info {
             flex: 1;
         }

         .location-info h4 {
             font-size: 1rem;
             font-weight: 600;
             margin-bottom: 0.25rem;
             color: #333;
         }

         .location-info p {
             font-size: 0.8rem;
             color: #666;
             margin: 0;
         }

         .location-metrics {
             text-align: center;
             min-width: 80px;
         }

         .location-count {
             display: block;
             font-size: 1.2rem;
             font-weight: bold;
             color: #8D6E63;
         }

         .location-label {
             font-size: 0.7rem;
             color: #666;
         }

         .location-bar {
             width: 100px;
             height: 8px;
             background: #e9ecef;
             border-radius: 4px;
             overflow: hidden;
         }

         .location-fill {
             height: 100%;
             background: linear-gradient(90deg, #8D6E63, #A97A50);
             transition: width 0.3s ease;
         }

         /* Full Width Analytics Overview */
         .full-width-analytics {
             grid-column: 1 / -1;
             margin-top: 2rem;
         }

        .analytics-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem; /* Reduced from 2.5rem (80%) */
            padding: 0.8rem; /* Reduced from 1rem (80%) */
            font-size: 0.8em; /* Scale down all text to 80% */
        }

        .analytics-overview-item {
            background: linear-gradient(145deg, #F5F5DC 0%, #E6DAC8 100%);
            border-radius: 16px; /* Reduced from 20px (80%) */
            padding: 1.6rem; /* Reduced from 2rem (80%) */
            box-shadow: 0 6.4px 25.6px rgba(141, 110, 99, 0.15), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(141, 110, 99, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideInUp 0.6s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-height: 320px; /* Reduced from 400px (80%) */
        }

        .analytics-overview-item:nth-child(1) { animation-delay: 0.1s; }
        .analytics-overview-item:nth-child(2) { animation-delay: 0.2s; }
        .analytics-overview-item:nth-child(3) { animation-delay: 0.3s; }

        .analytics-overview-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            border-radius: 20px 20px 0 0;
        }

        .analytics-overview-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 48px rgba(141, 110, 99, 0.25), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


         .analytics-overview-chart {
             width: 100%;
             height: 200px; /* Reduced from 250px (80%) */
             margin-bottom: 1.2rem; /* Reduced from 1.5rem (80%) */
             display: flex;
             align-items: center;
             justify-content: center;
             background: rgba(255, 255, 255, 0.5);
             border-radius: 9.6px; /* Reduced from 12px (80%) */
             box-shadow: 0 3.2px 12.8px rgba(141, 110, 99, 0.1);
             position: relative;
         }

         .analytics-overview-chart canvas {
             max-width: 100%;
             max-height: 100%;
             border-radius: 8px;
         }

         .analytics-overview-info {
             width: 100%;
             text-align: center;
         }

         .analytics-overview-info h4 {
             font-size: 1.12rem; /* Reduced from 1.4rem (80%) */
             font-weight: 700;
             margin-bottom: 0.64rem; /* Reduced from 0.8rem (80%) */
             color: #5D4037;
             text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
         }

         .analytics-overview-info p {
             font-size: 0.8rem; /* Reduced from 1rem (80%) */
             color: #8D6E63;
             margin-bottom: 0.8rem; /* Reduced from 1rem (80%) */
             font-weight: 500;
         }

         .analytics-trend {
             display: inline-block;
             padding: 0.5rem 1rem;
             background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
             color: #E6DAC8;
             border-radius: 20px;
             font-weight: 600;
             font-size: 0.9rem;
             text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
             box-shadow: 0 2px 8px rgba(141, 110, 99, 0.3);
         }



         /* Flagged Accounts Styling */
         .user-avatar-small.flagged {
             background: linear-gradient(135deg, #dc3545, #c82333);
             color: white;
             border: 2px solid #dc3545;
         }

         .flag-reason {
             font-weight: 600;
             color: #dc3545;
             font-size: 0.9rem;
         }

         .status-badge-table.status-danger {
             background: #f8d7da;
             color: #721c24;
             border: 1px solid #f5c6cb;
         }

         .status-badge-table.status-warning {
             background: #fff3cd;
             color: #856404;
             border: 1px solid #ffeaa7;
         }

         /* Document Verification Modal Styling - handled in CSS file */

         .verification-container {
             display: flex;
             flex-direction: column;
             gap: 2rem;
         }

         .user-info-section, .document-section, .verification-checklist, .verification-notes {
             background: #f8f9fa;
             padding: 1.5rem;
             border-radius: 8px;
             border: 1px solid #e9ecef;
         }

         .user-info-section h3, .document-section h3, .verification-checklist h3, .verification-notes h3 {
             color: #8D6E63;
             margin-bottom: 1rem;
             display: flex;
             align-items: center;
             gap: 0.5rem;
         }

         .info-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
             gap: 1rem;
         }

         .info-item {
             display: flex;
             flex-direction: column;
             gap: 0.25rem;
         }

         .info-item label {
             font-weight: 600;
             color: #666;
             font-size: 0.9rem;
         }

         .info-item span {
             color: #333;
             font-size: 1rem;
         }

         .document-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
             gap: 2rem;
         }

         .document-item {
             text-align: center;
         }

         .document-item h4 {
             color: #8D6E63;
             margin-bottom: 1rem;
         }

         .document-preview {
             margin-bottom: 1rem;
             border: 2px solid #e9ecef;
             border-radius: 8px;
             overflow: hidden;
             background: white;
         }

         .document-preview img {
             width: 100%;
             height: 200px;
             object-fit: cover;
             cursor: pointer;
             transition: transform 0.3s ease;
         }

         .document-preview img:hover {
             transform: scale(1.05);
         }

         .document-actions {
             display: flex;
             gap: 0.5rem;
             justify-content: center;
         }

         .checklist-items {
             display: flex;
             flex-direction: column;
             gap: 1rem;
         }

         .checklist-item {
             display: flex;
             align-items: center;
             gap: 0.75rem;
             cursor: pointer;
             padding: 0.5rem;
             border-radius: 5px;
             transition: background-color 0.3s ease;
         }

         .checklist-item:hover {
             background: #e9ecef;
         }

         .checklist-item input[type="checkbox"] {
             width: 18px;
             height: 18px;
             accent-color: #8D6E63;
         }

         .verification-notes textarea {
             width: 100%;
             padding: 1rem;
             border: 1px solid #e9ecef;
             border-radius: 5px;
             font-family: inherit;
             font-size: 0.9rem;
             resize: vertical;
         }

         .verification-notes textarea:focus {
             outline: none;
             border-color: #8D6E63;
             box-shadow: 0 0 0 2px rgba(141, 110, 99, 0.2);
         }

         .image-modal {
             max-width: 95vw;
             max-height: 95vh;
             width: auto;
             height: auto;
         }

         .image-modal img {
             max-width: 100%;
             max-height: 90vh;
             object-fit: contain;
         }

         /* Email Verification Styling */
         .email-verification-section {
             background: #f8f9fa;
             padding: 1.5rem;
             border-radius: 8px;
             border: 1px solid #e9ecef;
         }

         .email-verification-section h3 {
             color: #8D6E63;
             margin-bottom: 1rem;
             display: flex;
             align-items: center;
             gap: 0.5rem;
         }

         .verification-status {
             margin-bottom: 1rem;
         }

         .status-item {
             display: flex;
             align-items: center;
             gap: 0.75rem;
             padding: 1rem;
             background: white;
             border-radius: 8px;
             border: 1px solid #e9ecef;
         }

         .status-item i {
             font-size: 1.2rem;
         }

         .status-pending {
             color: #ffc107;
         }

         .status-verified {
             color: #28a745;
         }

         .status-failed {
             color: #dc3545;
         }

         .verification-actions {
             display: flex;
             gap: 1rem;
             flex-wrap: wrap;
         }

         .action-btn:disabled {
             opacity: 0.6;
             cursor: not-allowed;
             background: #6c757d;
         }

         .action-btn:disabled:hover {
             background: #6c757d;
             transform: none;
         }

         /* Pending Approvals Button Styling */
         .approval-actions {
             display: flex;
             gap: 0.5rem;
             flex-wrap: wrap;
             align-items: center;
         }

         .approval-actions .action-btn {
             padding: 0.3rem 0.5rem;
             font-size: 0.7rem;
             min-width: auto;
             white-space: nowrap;
             line-height: 1.2;
         }

         .approval-actions .action-btn i {
             font-size: 0.65rem;
             margin-right: 0.2rem;
         }

         /* Final Action Buttons Styling */
         .verification-actions-final {
             background: #f8f9fa;
             padding: 1.5rem;
             border-radius: 8px;
             border: 1px solid #e9ecef;
         }

         .verification-actions-final h3 {
             color: #8D6E63;
             margin-bottom: 1rem;
             display: flex;
             align-items: center;
             gap: 0.5rem;
         }

         .verification-actions-final .action-buttons-container {
             display: flex;
             gap: 1rem;
             justify-content: center;
             flex-wrap: wrap;
         }

         .verification-actions-final .action-btn {
             padding: 0.8rem 1.5rem;
             font-size: 1rem;
             min-width: 150px;
         }

         /* Loading and No Data States */
         .loading-spinner {
             display: flex;
             align-items: center;
             justify-content: center;
             gap: 0.5rem;
             color: #666;
             font-size: 0.9rem;
         }

        .loading-spinner i {
            font-size: 1.2rem;
            color: #8D6E63;
        }

        /* Make all spinner icons brown by default */
        .fa-spinner {
            color: #8D6E63 !important;
        }

        /* Analytics Loading Styles */
        .analytics-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 500px;
            background: transparent;
            border-radius: 20px;
            margin: 2rem 0;
        }

        .analytics-loading .loading-spinner {
            flex-direction: column;
            gap: 1.5rem;
            font-size: 1.2rem;
            color: #8D6E63;
            font-weight: 600;
        }

        .analytics-loading .loading-spinner i {
            font-size: 3rem;
            color: #8D6E63;
            animation: spin 1s linear infinite;
            filter: drop-shadow(0 2px 8px rgba(141, 110, 99, 0.3));
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

         .no-data {
             text-align: center;
             padding: 2rem;
             color: #666;
         }

         .no-data i {
             font-size: 3rem;
             margin-bottom: 1rem;
             opacity: 0.5;
         }

         .no-data p {
             font-size: 1.1rem;
             margin: 0;
         }

         /* Tab Count Styling */
         .tab-count {
             background: #8D6E63;
             color: white;
             padding: 0.3rem 0.6rem;
             border-radius: 15px;
             font-size: 0.75rem;
             margin-left: 0.5rem;
             font-weight: 700;
             min-width: 25px;
             text-align: center;
             display: inline-block;
             border: 2px solid rgba(255, 255, 255, 0.3);
         }

        .tab.active .tab-count {
            background: #5D4037;
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Force approval card effects - highest priority */
        .priority-section .pending-approvals .approval-card {
            background: white !important;
            border-radius: 8px !important;
            padding: 0.5rem !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            display: flex !important;
            flex-direction: column !important;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            position: relative !important;
            overflow: hidden !important;
            cursor: pointer !important;
            border: 2px solid transparent !important;
            animation: pendingCardPulse 2s ease-in-out infinite !important;
        }

        /* Pulsing glow animation for pending approval cards */
        @keyframes pendingCardPulse {
            0%, 100% {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 0 0 0 rgba(255, 193, 7, 0.4);
                border-color: transparent;
            }
            50% {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 0 20px 5px rgba(255, 193, 7, 0.6);
                border-color: rgba(255, 193, 7, 0.5);
            }
        }

        /* Shake animation to draw attention */
        @keyframes pendingCardShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-3px); }
            20%, 40%, 60%, 80% { transform: translateX(3px); }
        }

        /* Apply shake animation on page load when there are pending approvals */
        .priority-section.has-pending .pending-approvals .approval-card {
            animation: pendingCardPulse 2s ease-in-out infinite, pendingCardShake 0.5s ease-in-out 3 !important;
        }

        /* Only show pulse animation when there are pending approvals */
        .priority-section:not(.has-pending) .pending-approvals .approval-card {
            animation: none !important;
        }

        .priority-section:not(.has-pending) .pending-approvals .approval-card::before {
            display: none !important;
        }

        .priority-section .pending-approvals .approval-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 3px !important;
            background: linear-gradient(90deg, #ffc107, #fd7e14, #ffc107) !important;
            opacity: 1 !important;
            animation: pendingBorderGlow 2s ease-in-out infinite !important;
            z-index: 1 !important;
        }

        /* Animated border glow */
        @keyframes pendingBorderGlow {
            0%, 100% {
                opacity: 0.6;
                background: linear-gradient(90deg, #ffc107, #fd7e14, #ffc107);
            }
            50% {
                opacity: 1;
                background: linear-gradient(90deg, #fd7e14, #ffc107, #fd7e14);
            }
        }

        .priority-section .pending-approvals .approval-card:hover::before {
            opacity: 1 !important;
            animation: pendingBorderGlow 1s ease-in-out infinite !important;
        }

        .priority-section .pending-approvals .approval-card:hover {
            transform: translateY(-5px) scale(1.02) !important;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4), 0 0 30px 8px rgba(255, 193, 7, 0.3) !important;
            animation: none !important;
        }

        /* Pulsing user avatar when there are pending approvals */
        .priority-section.has-pending .pending-approvals .approval-card .user-avatar {
            animation: pendingAvatarPulse 2s ease-in-out infinite !important;
        }

        .priority-section:not(.has-pending) .pending-approvals .approval-card .user-avatar {
            animation: none !important;
        }

        @keyframes pendingAvatarPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(141, 110, 99, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 4px 15px rgba(255, 193, 7, 0.5);
            }
        }

        .priority-section .pending-approvals .approval-card:hover .user-avatar {
            transform: scale(1.1) rotate(5deg) !important;
            box-shadow: 0 4px 12px rgba(141, 110, 99, 0.4) !important;
            animation: none !important;
        }

        .priority-section .pending-approvals .approval-card:hover .verification-badge {
            transform: scale(1.05) !important;
        }

        /* Priority section header animation when there are pending approvals */
        .priority-section.has-pending h2 {
            animation: headerPulse 2s ease-in-out infinite !important;
        }

        .priority-section.has-pending h2 .fa-exclamation-circle {
            animation: iconBounce 1s ease-in-out infinite !important;
            color: #ffc107 !important;
        }

        @keyframes headerPulse {
            0%, 100% {
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }
            50% {
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.5);
            }
        }

        @keyframes iconBounce {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-5px) scale(1.1);
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="btn btn-primary d-md-none position-fixed top-0 start-0 m-3 z-index-1050" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" style="z-index: 1050; background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%); border: none; min-width: 48px; min-height: 48px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar - Desktop (always visible on md+) -->
    <div class="sidebar d-none d-md-block">
        <div class="sidebar-header">
            <h1 style="display: flex; align-items: center; gap: 0.5rem;">
                <img src="../uploads/boardease_logo.jpg" alt="BoardEase Logo" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                <span style="line-height: 1.2;">BoardEase Admin</span>
            </h1>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" onclick="showSection('dashboard', event); return false;">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="#" class="nav-item" onclick="showSection('user-management', event); return false;">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="#" class="nav-item" onclick="showSection('notifications', event); return false;">
                <i class="fas fa-bell"></i>
                Notifications
            </a>
             <a href="#" class="nav-item" onclick="showSection('boarding-houses', event); return false;">
                 <i class="fas fa-home"></i>
                 Boarding Houses
             </a>
             <a href="#" class="nav-item" onclick="showSection('analytics', event); return false;">
                 <i class="fas fa-chart-line"></i>
                 Analytics
             </a>
             <a href="#" class="nav-item" onclick="showSection('reports', event); return false;">
                 <i class="fas fa-chart-bar"></i>
                 Reports
             </a>
            <a href="#" class="nav-item" onclick="showSection('settings', event); return false;">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <a href="#" class="nav-item" onclick="logout(); return false;">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </nav>
    </div>

    <!-- Sidebar - Mobile (Offcanvas) -->
    <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarOffcanvasLabel" style="display: flex; align-items: center; gap: 0.5rem;">
                <img src="../uploads/boardease_logo.jpg" alt="BoardEase Logo" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                <span style="line-height: 1.2; font-size: 1.1rem;">BoardEase<br>Admin</span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="showSection('dashboard', event); closeOffcanvas(); return false;">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="#" class="nav-item" onclick="showSection('user-management', event); closeOffcanvas(); return false;">
                    <i class="fas fa-users"></i>
                    User Management
                </a>
                <a href="#" class="nav-item" onclick="showSection('notifications', event); closeOffcanvas(); return false;">
                    <i class="fas fa-bell"></i>
                    Notifications
                </a>
                 <a href="#" class="nav-item" onclick="showSection('boarding-houses', event); closeOffcanvas(); return false;">
                     <i class="fas fa-home"></i>
                     Boarding Houses
                 </a>
                 <a href="#" class="nav-item" onclick="showSection('analytics', event); closeOffcanvas(); return false;">
                     <i class="fas fa-chart-line"></i>
                     Analytics
                 </a>
                 <a href="#" class="nav-item" onclick="showSection('reports', event); closeOffcanvas(); return false;">
                     <i class="fas fa-chart-bar"></i>
                     Reports
                 </a>
                <a href="#" class="nav-item" onclick="showSection('settings', event); closeOffcanvas(); return false;">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="#" class="nav-item" onclick="logout(); closeOffcanvas(); return false;">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid px-3 px-md-4 py-3 py-md-4">
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
                <div class="content-header mb-4">
                <h1>Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening with your BoardEase platform.</p>
            </div>
        <!-- Statistics Cards -->
                <div class="row g-3 g-md-4 mb-4">
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="stat-card card h-100 shadow-sm">
                            <div class="card-body text-center">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8D6E63, #A97A50);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                                    <h3 class="mb-2"><?php echo $total_users_count; ?></h3>
                                    <p class="mb-0">Total Users</p>
                </div>
            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="stat-card card h-100 shadow-sm">
                            <div class="card-body text-center">
                <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-content">
                                    <h3 class="mb-2"><?php echo $total_bh_count; ?></h3>
                                    <p class="mb-0">Boarding Houses</p>
                </div>
            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="stat-card card h-100 shadow-sm <?php echo $pending_count > 0 ? 'pending-card-pulse' : ''; ?>" id="pending-approvals-card">
                            <div class="card-body text-center">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-content">
                                    <h3 class="mb-2"><?php echo $pending_count; ?></h3>
                                    <p class="mb-0">Pending Approvals</p>
                                </div>
                            </div>
                </div>
            </div>
        </div>

        <!-- Analytics Overview - Full Width -->
        <div class="dashboard-card full-width-analytics">
            <div class="card-header">
                <h3><i class="fas fa-chart-line"></i> Analytics Overview</h3>
            </div>
            <div class="card-content">
                <div class="analytics-overview-grid">
                    <div class="analytics-overview-item">
                        <div class="analytics-overview-chart">
                            <canvas id="dashboardUserDistributionChart" width="300" height="200"></canvas>
                        </div>
                        <div class="analytics-overview-info">
                            <h4>User Distribution</h4>
                            <p>BH Owners vs Boarders</p>
                            <span class="analytics-trend" id="user-distribution-trend">Loading...</span>
                        </div>
                    </div>
                    <div class="analytics-overview-item">
                        <div class="analytics-overview-chart">
                            <canvas id="dashboardLocationChart" width="300" height="200"></canvas>
                        </div>
                        <div class="analytics-overview-info">
                            <h4>Geographic Distribution</h4>
                            <p>Users by location</p>
                            <span class="analytics-trend" id="location-trend">Loading...</span>
                        </div>
                    </div>
                    <div class="analytics-overview-item">
                        <div class="analytics-overview-chart">
                            <canvas id="dashboardGrowthChart" width="300" height="200"></canvas>
                        </div>
                        <div class="analytics-overview-info">
                            <h4>Growth Trends</h4>
                            <p>Last 6 months growth</p>
                            <span class="analytics-trend" id="growth-trend">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Section: Pending Approvals -->
        <div class="priority-section <?php echo $pending_count > 0 ? 'has-pending' : ''; ?>" style="margin-top: 3rem;">
            <h2><i class="fas fa-exclamation-circle"></i> Pending User Approvals - Action Required (<?php echo $pending_count; ?>)</h2>
            <div class="pending-approvals">
                <?php if (empty($pending_registrations)): ?>
                    <div class="no-data">No pending registrations</div>
                <?php else: ?>
                    <?php foreach ($pending_registrations as $registration): ?>
                        <?php 
                        $initials = strtoupper(substr($registration['first_name'], 0, 1) . substr($registration['last_name'], 0, 1));
                        $roleText = $registration['role'] === 'BH Owner' ? 'Owner Registration' : 'Boarder Registration';
                        // Calculate time ago from created_at
                        $timeAgo = 'Just now'; // Default fallback
                        if (!empty($registration['created_at']) && $registration['created_at'] !== '0000-00-00 00:00:00') {
                            try {
                                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                $created = new DateTime($registration['created_at'], new DateTimeZone('Asia/Manila'));
                                
                                // Calculate difference
                            $diff = $now->diff($created);
                                
                                // Calculate total seconds difference
                                $totalSeconds = ($diff->days * 86400) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
                                
                                if ($totalSeconds < 0) {
                                    // If negative, it means created_at is in the future (shouldn't happen)
                                    $timeAgo = 'Just now';
                                } elseif ($diff->days > 0) {
                                    $timeAgo = $diff->days . ($diff->days == 1 ? ' day ago' : ' days ago');
                            } elseif ($diff->h > 0) {
                                    $timeAgo = $diff->h . ($diff->h == 1 ? ' hour ago' : ' hours ago');
                            } elseif ($diff->i > 0) {
                                    $timeAgo = $diff->i . ($diff->i == 1 ? ' minute ago' : ' minutes ago');
                            } else {
                                $timeAgo = 'Just now';
                                }
                            } catch (Exception $e) {
                                // If date parsing fails, try to format the date instead
                                error_log("Error parsing created_at date: " . $e->getMessage() . " - Date: " . $registration['created_at']);
                                if (!empty($registration['created_at'])) {
                                    $timeAgo = date('M d, Y H:i', strtotime($registration['created_at']));
                                }
                            }
                        } else {
                            // If created_at is empty or invalid, show the registration date if available
                            if (!empty($registration['created_at']) && $registration['created_at'] !== '0000-00-00 00:00:00') {
                                $timeAgo = date('M d, Y', strtotime($registration['created_at']));
                            }
                        }
                        ?>
                        <?php
                        $roleParts = $registration['role'] === 'BH Owner' ? ['type' => 'Owner', 'status' => 'Registration'] : ['type' => 'Boarder', 'status' => 'Registration'];
                        ?>
                        <div class="approval-card" data-registration-id="<?php echo $registration['id']; ?>">
                    <div class="approval-header">
                        <div class="approval-user">
                                    <div class="user-avatar"><?php echo $initials; ?></div>
                            <div class="approval-user-info">
                                        <strong><?php echo htmlspecialchars($registration['full_name']); ?></strong>
                                        <span class="approval-role"><span class="role-type"><?php echo $roleParts['type']; ?></span> <span class="role-status"><?php echo $roleParts['status']; ?></span></span>
                            </div>
                        </div>
                        <div class="approval-actions">
                                    <button class="action-btn" onclick="viewDocuments(<?php echo $registration['id']; ?>)">
                                <i class="fas fa-id-card"></i> View ID
                            </button>
                        </div>
                    </div>
                    <div class="approval-details">
                                <strong>Email:</strong> <?php echo htmlspecialchars($registration['email']); ?><br>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($registration['phone']); ?><br>
                                <strong>ID Type:</strong> <?php echo htmlspecialchars($registration['valid_id_type']); ?><br>
                                <strong>ID Number:</strong> <?php echo htmlspecialchars($registration['id_number']); ?>
                    </div>
                    <div class="verification-badge verification-pending">
                                <i class="fas fa-clock"></i> Pending Approval
                    </div>
                            <div class="registration-date">Registered: <?php echo $timeAgo; ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
         </div>

         <!-- Dashboard Grid -->
         <div class="dashboard-grid">
             <!-- Quick Actions -->
             <div class="dashboard-card">
                 <div class="card-header">
                     <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                 </div>
                 <div class="card-content">
                     <div style="display: flex; flex-direction: column; gap: 1rem;">
                         <button class="action-btn" onclick="showSection('user-management')">
                             <i class="fas fa-users"></i> Manage Users
                         </button>
                         <button class="action-btn" onclick="showSection('boarding-houses')">
                             <i class="fas fa-home"></i> View Boarding Houses
                         </button>
                         <button class="action-btn" onclick="showSection('notifications')">
                             <i class="fas fa-bell"></i> Send Notifications
                         </button>
                         <button class="action-btn" onclick="showSection('reports')">
                             <i class="fas fa-chart-bar"></i> Generate Reports
                         </button>
                     </div>
                 </div>
             </div>

             <!-- Recent Activity -->
             <div class="dashboard-card">
                 <div class="card-header">
                     <h3><i class="fas fa-clock"></i> Recent Activity</h3>
                 </div>
                 <div class="card-content">
                    <div id="recent-activity-loading" style="text-align: center; padding: 1rem; color: rgba(0,0,0,0.6); display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Loading recent activity...
                             </div>
                    <div class="recent-activity" id="recent-activity-container">
                        <!-- Recent activity items will be loaded here dynamically -->
                             </div>
                         </div>
                             </div>
         </div>
        </div>

        <!-- User Management Section -->
        <div id="user-management-section" class="content-section">
            <div class="content-header">
                <h1>User Management</h1>
                <p>Manage all users in the system - boarders and boarding house owners.</p>
            </div>
            
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-users"></i> User Management</h3>
                    <span style="color: rgba(255,255,255,0.8);" id="total-users-count">Loading...</span>
                </div>
                
                <!-- Tab Navigation -->
                <div class="tabs">
                    <div class="tab active" onclick="switchUserTab('boarders')">
                        <i class="fas fa-user-graduate"></i> Boarders
                        <span class="tab-count" id="boarders-count">-</span>
                    </div>
                    <div class="tab" onclick="switchUserTab('owners')">
                        <i class="fas fa-user-tie"></i> Boarding House Owners
                        <span class="tab-count" id="owners-count">-</span>
                    </div>
                </div>
                
                <!-- Boarders Tab Content -->
                <div class="tab-content active" id="boarders-tab">
                    <div class="table-filters">
                        <button class="filter-btn active" onclick="filterBoarders('all')">All</button>
                        <button class="filter-btn" onclick="filterBoarders('active')">Active</button>
                        <button class="filter-btn" onclick="filterBoarders('inactive')">Inactive</button>
                        <button class="filter-btn" onclick="filterBoarders('pending')">Pending Approval</button>
                    </div>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                 <thead class="table-light">
                                 <tr>
                                     <th>User</th>
                                     <th>Email</th>
                                     <th>Status</th>
                                     <th>Registration Date</th>
                                     <th>Actions</th>
                                 </tr>
                             </thead>
                            <tbody id="boarders-table-body">
                                <?php 
                                // Get all boarders (active, inactive, and pending)
                                $active_boarders = array_filter($active_users, function($user) {
                                    return $user['role'] === 'Boarder';
                                });
                                
                                $inactive_boarders = array_filter($inactive_users, function($user) {
                                    return $user['role'] === 'Boarder';
                                });
                                
                                $pending_boarders = array_filter($pending_registrations, function($reg) {
                                    return $reg['role'] === 'Boarder';
                                });
                                
                                
                                // Show all boarders by default - active + inactive users + pending registrations
                                $all_boarders = [];
                                $processed_emails = [];
                                
                                // First add active users
                                foreach ($active_boarders as $user) {
                                    $all_boarders[] = $user;
                                    $processed_emails[] = $user['email'];
                                }
                                
                                // Then add inactive users
                                foreach ($inactive_boarders as $user) {
                                    if (!in_array($user['email'], $processed_emails)) {
                                        $all_boarders[] = $user;
                                        $processed_emails[] = $user['email'];
                                    }
                                }
                                
                                // Then add pending registrations (if not already processed)
                                foreach ($pending_boarders as $reg) {
                                    if (!in_array($reg['email'], $processed_emails)) {
                                        // Add properties_count field for pending registrations (0 since they don't have properties yet)
                                        $reg['properties_count'] = 0;
                                        $all_boarders[] = $reg;
                                        $processed_emails[] = $reg['email'];
                                    }
                                }
                                
                                
                                
                                if (empty($all_boarders)): ?>
                                <tr id="boarders-no-data">
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: #666;">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        <strong>No boarders found</strong><br>
                                        <small>There are no boarders in the system yet.</small>
                                     </td>
                                </tr>
                                <?php else: 
                                    foreach ($all_boarders as $user): 
                                        $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                                        $registrationDate = date('Y-m-d', strtotime($user['created_at']));
                                        
                                        // Determine status and actions
                                        if (isset($user['user_id'])) {
                                            // This is an active/inactive user
                                            $status = $user['status'] === 'Active' ? 'Active' : 'Inactive';
                                            $statusClass = $user['status'] === 'Active' ? 'status-active' : 'status-inactive';
                                            $suspendButtonText = $user['status'] === 'Active' ? 'Suspend' : 'Unsuspend';
                                            $suspendButtonIcon = $user['status'] === 'Active' ? 'ban' : 'check';
                                            $suspendButtonClass = $user['status'] === 'Active' ? 'danger' : 'success';
                                            $suspendFunction = $user['status'] === 'Active' ? 'suspendUser' : 'unsuspendUser';
                                            $actions = '
                                         <div class="action-buttons-container">
                                                    <button class="action-btn" onclick="viewUserDetails(' . $user['user_id'] . ')">
                                                 <i class="fas fa-eye"></i> View
                                             </button>
                                                    <button class="action-btn ' . $suspendButtonClass . '" onclick="' . $suspendFunction . '(' . $user['user_id'] . ')">
                                                 <i class="fas fa-' . $suspendButtonIcon . '"></i> ' . $suspendButtonText . '
                                             </button>
                                                </div>';
                                            $dataId = 'data-user-id="' . $user['user_id'] . '"';
                                        } else {
                                            // This is a pending registration
                                            $status = 'Pending Approval';
                                            $statusClass = 'status-pending';
                                            $actions = '
                                         <div class="action-buttons-container">
                                                    <button class="action-btn" onclick="viewDocuments(' . $user['id'] . ')">
                                                 <i class="fas fa-id-card"></i> View ID
                                             </button>
                                                    <button class="action-btn success" onclick="approveUser(' . $user['id'] . ')">
                                                 <i class="fas fa-check"></i> Approve
                                             </button>
                                                    <button class="action-btn danger" onclick="rejectUser(' . $user['id'] . ')">
                                                 <i class="fas fa-times"></i> Reject
                                             </button>
                                                </div>';
                                            $dataId = 'data-registration-id="' . $user['id'] . '"';
                                        }
                                    ?>
                                    <tr <?php echo $dataId; ?> data-status="<?php echo strtolower($status); ?>">
                                    <td>
                                        <div class="user-info-cell">
                                                <div class="user-avatar-small"><?php echo $initials; ?></div>
                                            <div>
                                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($user['phone']); ?></small>
                                            </div>
                                        </div>
                                     </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="status-badge-table <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                        <td><?php echo $registrationDate; ?></td>
                                        <td><?php echo $actions; ?></td>
                                </tr>
                                    <?php endforeach; 
                                endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                
                <!-- Owners Tab Content -->
                <div class="tab-content" id="owners-tab">
                    <div class="table-filters">
                        <button class="filter-btn active" onclick="filterOwners('all')">All</button>
                        <button class="filter-btn" onclick="filterOwners('active')">Active</button>
                        <button class="filter-btn" onclick="filterOwners('inactive')">Inactive</button>
                        <button class="filter-btn" onclick="filterOwners('pending')">Pending Approval</button>
                    </div>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                 <thead class="table-light">
                                 <tr>
                                     <th>Owner</th>
                                     <th>Email</th>
                                     <th>Properties</th>
                                     <th>Status</th>
                                     <th>Registration Date</th>
                                     <th>Actions</th>
                                 </tr>
                             </thead>
                            <tbody id="owners-table-body">
                                <?php 
                                // Get all owners (active, inactive, and pending)
                                $active_owners = array_filter($active_users, function($user) {
                                    return $user['role'] === 'BH Owner';
                                });
                                
                                $inactive_owners = array_filter($inactive_users, function($user) {
                                    return $user['role'] === 'BH Owner';
                                });
                                
                                $pending_owners = array_filter($pending_registrations, function($reg) {
                                    return $reg['role'] === 'BH Owner';
                                });
                                
                                // Show all owners by default - active + inactive users + pending registrations
                                $all_owners = [];
                                $processed_emails = [];
                                
                                // First add active users
                                foreach ($active_owners as $user) {
                                    $all_owners[] = $user;
                                    $processed_emails[] = $user['email'];
                                }
                                
                                // Then add inactive users
                                foreach ($inactive_owners as $user) {
                                    if (!in_array($user['email'], $processed_emails)) {
                                        $all_owners[] = $user;
                                        $processed_emails[] = $user['email'];
                                    }
                                }
                                
                                // Then add pending registrations (if not already processed)
                                foreach ($pending_owners as $reg) {
                                    if (!in_array($reg['email'], $processed_emails)) {
                                        // Add properties_count field for pending registrations (0 since they don't have properties yet)
                                        $reg['properties_count'] = 0;
                                        $all_owners[] = $reg;
                                        $processed_emails[] = $reg['email'];
                                    }
                                }
                                
                                if (empty($all_owners)): ?>
                                <tr id="owners-no-data">
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                        <i class="fas fa-user-tie" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        <strong>No owners found</strong><br>
                                        <small>There are no boarding house owners in the system yet.</small>
                                     </td>
                                </tr>
                                <?php else: 
                                    foreach ($all_owners as $user): 
                                        $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                                        $registrationDate = date('Y-m-d', strtotime($user['created_at']));
                                        
                                        // Determine status and actions
                                        if (isset($user['user_id'])) {
                                            // This is an active/inactive user
                                            $status = $user['status'] === 'Active' ? 'Active' : 'Inactive';
                                            $statusClass = $user['status'] === 'Active' ? 'status-active' : 'status-inactive';
                                            $suspendButtonText = $user['status'] === 'Active' ? 'Suspend' : 'Unsuspend';
                                            $suspendButtonIcon = $user['status'] === 'Active' ? 'ban' : 'check';
                                            $suspendButtonClass = $user['status'] === 'Active' ? 'danger' : 'success';
                                            $suspendFunction = $user['status'] === 'Active' ? 'suspendUser' : 'unsuspendUser';
                                            $actions = '
                                         <div class="action-buttons-container">
                                                    <button class="action-btn" onclick="viewUserDetails(' . $user['user_id'] . ')">
                                                 <i class="fas fa-eye"></i> View
                                             </button>
                                                    <button class="action-btn ' . $suspendButtonClass . '" onclick="' . $suspendFunction . '(' . $user['user_id'] . ')">
                                                 <i class="fas fa-' . $suspendButtonIcon . '"></i> ' . $suspendButtonText . '
                                             </button>
                                                </div>';
                                            $dataId = 'data-user-id="' . $user['user_id'] . '"';
                                        } else {
                                            // This is a pending registration
                                            $status = 'Pending Approval';
                                            $statusClass = 'status-pending';
                                            $actions = '
                                         <div class="action-buttons-container">
                                                    <button class="action-btn" onclick="viewDocuments(' . $user['id'] . ')">
                                                 <i class="fas fa-id-card"></i> View ID
                                             </button>
                                                    <button class="action-btn success" onclick="approveUser(' . $user['id'] . ')">
                                                 <i class="fas fa-check"></i> Approve
                                             </button>
                                                    <button class="action-btn danger" onclick="rejectUser(' . $user['id'] . ')">
                                                 <i class="fas fa-times"></i> Reject
                                             </button>
                                                </div>';
                                            $dataId = 'data-registration-id="' . $user['id'] . '"';
                                        }
                                    ?>
                                    <tr <?php echo $dataId; ?> data-status="<?php echo strtolower($status); ?>">
                                    <td>
                                        <div class="user-info-cell">
                                                <div class="user-avatar-small"><?php echo $initials; ?></div>
                                            <div>
                                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($user['phone']); ?></small>
                                            </div>
                                        </div>
                                     </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                     <td><?php echo $user['role'] === 'BH Owner' ? (isset($user['properties_count']) ? $user['properties_count'] : 0) . ' properties' : 'N/A'; ?></td>
                                        <td><span class="status-badge-table <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                        <td><?php echo $registrationDate; ?></td>
                                        <td><?php echo $actions; ?></td>
                                </tr>
                                    <?php endforeach; 
                                endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Notifications Section -->
        <div id="notifications-section" class="content-section">
            <div class="content-header">
                <h1>Notifications</h1>
                <p>Send notifications to users and view system notifications.</p>
            </div>
            
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <span style="color: rgba(255,255,255,0.8);">Manage notifications and system events</span>
                </div>
                
                <!-- Tab Navigation -->
                <div class="tabs">
                    <div class="tab active" onclick="switchNotificationTab('system')">
                        <i class="fas fa-bell"></i> System Notifications
                    </div>
                    <div class="tab" onclick="switchNotificationTab('compose')">
                        <i class="fas fa-paper-plane"></i> Compose Notification
                    </div>
                </div>
                
                <!-- Compose Notification Tab Content -->
                <div class="tab-content" id="compose-tab">
                    <div class="card-content">
                        <form id="notificationForm">
                            <div class="form-group">
                                <label>Recipients:</label>
                                <select name="recipients" required>
                                    <option value="">Select recipients</option>
                                    <option value="all">All Users</option>
                                    <option value="boarders">All Boarders</option>
                                    <option value="owners">All Owners</option>
                                    <option value="specific">Specific Users</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Notification Type:</label>
                                <select name="notification_type" required>
                                    <option value="announcement">Announcement</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subject:</label>
                                <input type="text" name="title" placeholder="Enter notification subject" required>
                            </div>
                            <div class="form-group">
                                <label>Message:</label>
                                <textarea name="message" rows="4" placeholder="Enter your notification message" required></textarea>
                            </div>
                             <div class="btn-group">
                                 <div class="action-buttons-container">
                                     <button type="button" class="action-btn" onclick="clearNotificationForm()">Clear</button>
                                     <button type="submit" class="action-btn success">
                                         <i class="fas fa-paper-plane"></i> Send Notification
                                     </button>
                                 </div>
                             </div>
                        </form>
                    </div>
                </div>
                
                <!-- System Notifications Tab Content -->
                <div class="tab-content active" id="system-tab">
                    <div class="card-content">
                        <div id="system-notifications-loading">
                            <div>
                                <i class="fas fa-spinner"></i>
                            </div>
                            <div>
                                Loading system notifications...
                            </div>
                            <div>
                                Please wait while we fetch all system events
                        </div>
                            </div>
                        <div id="system-notifications-container" style="display: none; padding: 0; margin: 0;">
                            <!-- System notifications will be loaded here dynamically -->
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <!-- Boarding Houses Section -->
        <div id="boarding-houses-section" class="content-section">
            <div class="content-header">
                <h1>Boarding Houses Management</h1>
                <p>View and manage all boarding houses in the system with their respective owners.</p>
            </div>
            
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-home"></i> Boarding Houses</h3>
                    <span style="color: rgba(255,255,255,0.8);" id="boarding-houses-count">Loading...</span>
                </div>
                
                <!-- Tab Navigation -->
                <div class="tabs">
                    <div class="tab active" onclick="switchBoardingHouseTab('all')">
                        <i class="fas fa-list"></i> All Boarding Houses
                    </div>
                    <div class="tab" onclick="switchBoardingHouseTab('by-owner')">
                        <i class="fas fa-user-tie"></i> By Owner
                    </div>
                </div>
                
                <!-- All Boarding Houses Tab Content -->
                <div class="tab-content active" id="all-tab">
                     <div class="table-filters">
                         <button class="filter-btn active" onclick="filterBoardingHouses('all')">All</button>
                         <button class="filter-btn" onclick="filterBoardingHouses('active')">Active</button>
                         <button class="filter-btn" onclick="filterBoardingHouses('inactive')">Inactive</button>
                     </div>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                <tr>
                                    <th>Boarding House</th>
                                    <th>Owner</th>
                                    <th>Location</th>
                                    <th>Rooms</th>
                                    <th>Status</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="boarding-houses-table-body">
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">
                                        <div class="loading-spinner">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            <span>Loading boarding houses...</span>
                                         </div>
                                     </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                
                <!-- By Owner Tab Content -->
                <div class="tab-content" id="by-owner-tab">
                    <div class="card-content">
                        <div class="owner-boarding-houses" id="owners-boarding-houses">
                            <div style="text-align: center; padding: 40px;">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading owners and their boarding houses...</span>
                                        </div>
                                    </div>
                                </div>
                                        </div>
                                             </div>
                                         </div>
                                    </div>

        <!-- Analytics Section -->
        <div id="analytics-section" class="content-section">
            <div class="content-header">
                <h1>Analytics Dashboard</h1>
                <p>Comprehensive analytics and insights for your BoardEase platform.</p>
                                        </div>
            
            <!-- Loading Indicator -->
            <div id="analytics-loading" class="analytics-loading">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Loading analytics data...</span>
                </div>
            </div>
            
            <div class="analytics-container" id="analytics-content" style="display: none;">
                <!-- Analytics Overview Cards -->
                <div class="analytics-overview">
                    <div class="analytics-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                                             </div>
                        <div class="card-content">
                            <h3 id="total-users">-</h3>
                            <p>Total Users</p>
                            <span class="card-subtitle" id="new-users-month">-</span>
                                         </div>
                                    </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">
                            <i class="fas fa-home"></i>
                                        </div>
                        <div class="card-content">
                            <h3 id="total-boarding-houses">-</h3>
                            <p>Boarding Houses</p>
                            <span class="card-subtitle" id="new-bh-month">-</span>
                                             </div>
                                         </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">
                            <i class="fas fa-bed"></i>
                                    </div>
                        <div class="card-content">
                            <h3 id="total-room-units">-</h3>
                            <p>Room Units</p>
                            <span class="card-subtitle" id="occupancy-rate">-</span>
                                </div>
                            </div>
                            
                    <div class="analytics-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                                        </div>
                        <div class="card-content">
                            <h3 id="total-bookings">-</h3>
                            <p>Total Bookings</p>
                            <span class="card-subtitle" id="new-bookings-month">-</span>
                                    </div>
                                </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                        <div class="card-content">
                            <h3 id="total-revenue">₱-</h3>
                            <p>Total Revenue</p>
                            <span class="card-subtitle" id="monthly-revenue">₱-</span>
                                             </div>
                                         </div>
                    
                    <div class="analytics-card">
                        <div class="card-icon">
                            <i class="fas fa-comments"></i>
                                    </div>
                        <div class="card-content">
                            <h3 id="total-messages">-</h3>
                            <p>Messages</p>
                            <span class="card-subtitle" id="monthly-messages">-</span>
                                        </div>
                                             </div>
                                         </div>
                
                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-line"></i> Growth Analytics (Last 6 Months)</h4>
                        <canvas id="growthChart" width="400" height="200"></canvas>
                                    </div>
                    
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-pie"></i> User Distribution</h4>
                        <canvas id="userDistributionChart" width="400" height="200"></canvas>
                                </div>
                    
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-bar"></i> Booking Status</h4>
                        <canvas id="bookingStatusChart" width="400" height="200"></canvas>
                            </div>
                            
                    <div class="chart-container">
                        <h4><i class="fas fa-chart-bar"></i> Payment Status</h4>
                        <canvas id="paymentStatusChart" width="400" height="200"></canvas>
                                        </div>
                    
                    <div class="chart-container">
                        <h4><i class="fas fa-map-marker-alt"></i> Users by Location</h4>
                        <canvas id="userLocationChart" width="400" height="200"></canvas>
                                    </div>
                    
                    <div class="chart-container">
                        <h4><i class="fas fa-home"></i> Boarding Houses by Location</h4>
                        <canvas id="bhLocationChart" width="400" height="200"></canvas>
                                </div>
                                        </div>
                
                <!-- Top Performing and Earning Boarding Houses -->
                <div class="top-boarding-houses-container">
                <div class="top-performing-section">
                    <h4><i class="fas fa-trophy"></i> Top Performing Boarding Houses</h4>
                    <div class="top-performing-list" id="top-boarding-houses">
                        <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="top-performing-section">
                        <h4><i class="fas fa-money-bill-wave"></i> Top Earning Boarding Houses</h4>
                        <div class="top-performing-list" id="top-earning-boarding-houses">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports-section" class="content-section">
            <div class="content-header">
                <h1>Reports & Analytics</h1>
                <p>Generate and download comprehensive PDF reports for system analysis and monitoring.</p>
            </div>
            
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-chart-bar"></i> Reports & Analytics</h3>
                    <span style="color: rgba(255,255,255,0.8);">Generate comprehensive system reports with filtering options</span>
                </div>
                
                <div class="card-content">
                    <div class="reports-grid">
                        <!-- Payment Report Card -->
                        <div class="report-card">
                            <div class="report-icon" style="background: linear-gradient(135deg, #28a745, #20c997); margin-bottom: 1rem;">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h4 style="margin-bottom: 0.5rem;">Payment Reports</h4>
                            <p style="margin-bottom: 1rem; color: #666;">Generate detailed payment transaction reports</p>
                            
                            <!-- Payment Report Filters -->
                            <div class="report-filters" style="margin-bottom: 1rem;">
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">Start Date:</label>
                                    <input type="date" id="paymentStartDate" class="filter-input" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">End Date:</label>
                                    <input type="date" id="paymentEndDate" class="filter-input" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">Boarding House:</label>
                                    <select id="paymentBoardingHouse" class="filter-input" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="">All Boarding Houses</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button class="action-btn" id="paymentReportBtn" onclick="downloadPaymentReportPDF()" style="width: 100%;">
                                <i class="fas fa-file-pdf"></i> Generate PDF Report
                            </button>
                        </div>
                        
                        <!-- Rental Report Card -->
                        <div class="report-card">
                            <div class="report-icon" style="background: linear-gradient(135deg, #007bff, #0056b3); margin-bottom: 1rem;">
                                <i class="fas fa-home"></i>
                            </div>
                            <h4 style="margin-bottom: 0.5rem;">Rental Reports</h4>
                            <p style="margin-bottom: 1rem; color: #666;">View rental statistics, occupancy rates, and upcoming expirations</p>
                            
                            <!-- Rental Report Filters -->
                            <div class="report-filters" style="margin-bottom: 1rem;">
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">Start Date:</label>
                                    <input type="date" id="rentalStartDate" class="filter-input" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">End Date:</label>
                                    <input type="date" id="rentalEndDate" class="filter-input" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="display: block; margin-bottom: 0.25rem; font-weight: 500; font-size: 0.875rem;">Boarding House:</label>
                                    <select id="rentalBoardingHouse" class="filter-input" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="">All Boarding Houses</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button class="action-btn" id="rentalReportBtn" onclick="downloadRentalReportPDF()" style="width: 100%;">
                                <i class="fas fa-file-pdf"></i> Generate PDF Report
                            </button>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div id="settings-section" class="content-section">
            <div class="content-header">
                <h1>System Settings</h1>
                <p>Configure system settings, security, and integrations.</p>
            </div>
            
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-cog"></i> System Settings</h3>
                    <span style="color: rgba(255,255,255,0.8);">Manage system configuration and preferences</span>
                </div>
                
                <div class="card-content">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div class="settings-card">
                            <div class="settings-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                <i class="fas fa-database"></i>
                            </div>
                            <h4>Database Management</h4>
                            <p>Backup and maintain system database</p>
                            <button class="action-btn" id="backupBtn" onclick="backupDatabase()">
                                <i class="fas fa-database"></i> Backup Database
                            </button>
                        </div>
                        
                        
                        <div class="settings-card">
                            <div class="settings-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h4>Notification Settings</h4>
                            <p>Configure notification preferences and templates</p>
                            <button class="action-btn" onclick="openNotificationSettings()">
                                <i class="fas fa-bell"></i> Notification Settings
                            </button>
                        </div>
                        
                        <div class="settings-card">
                            <div class="settings-icon" style="background: linear-gradient(135deg, #8D6E63, #A97A50);">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <h4>Account Management</h4>
                            <p>Manage admin accounts and user permissions</p>
                            <button class="action-btn" onclick="openAccountManagement()">
                                <i class="fas fa-user-cog"></i> Account Management
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div id="analytics-section" class="content-section">
            <div class="content-header">
                <h1>Analytics & Insights</h1>
                <p>Comprehensive analytics and insights for your BoardEase platform.</p>
            </div>
            
            <!-- Analytics Grid -->
            <div class="analytics-dashboard">
                <!-- User Analytics -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3><i class="fas fa-users"></i> User Analytics</h3>
                        <div class="analytics-period">
                            <select>
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 3 months</option>
                                <option>Last year</option>
                            </select>
                        </div>
                    </div>
                    <div class="analytics-content">
                        <div class="chart-container">
                            <canvas id="userAnalyticsChart" width="400" height="200"></canvas>
                        </div>
                        <div class="analytics-metrics">
                            <div class="metric">
                                <span class="metric-value">9</span>
                                <span class="metric-label">Total Users</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">9</span>
                                <span class="metric-label">New This Month</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">+12.5%</span>
                                <span class="metric-label">Growth Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Analytics -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3><i class="fas fa-dollar-sign"></i> Revenue Analytics</h3>
                        <div class="analytics-period">
                            <select>
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 3 months</option>
                                <option>Last year</option>
                            </select>
                        </div>
                    </div>
                    <div class="analytics-content">
                        <div class="chart-container">
                            <canvas id="revenueAnalyticsChart" width="400" height="200"></canvas>
                        </div>
                        <div class="analytics-metrics">
                            <div class="metric">
                                <span class="metric-value">₱2.4M</span>
                                <span class="metric-label">Total Revenue</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">₱180K</span>
                                <span class="metric-label">This Month</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">+8.3%</span>
                                <span class="metric-label">Growth Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Analytics -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3><i class="fas fa-home"></i> Property Analytics</h3>
                        <div class="analytics-period">
                            <select>
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 3 months</option>
                                <option>Last year</option>
                            </select>
                        </div>
                    </div>
                    <div class="analytics-content">
                        <div class="chart-container">
                            <canvas id="propertyAnalyticsChart" width="400" height="200"></canvas>
                        </div>
                        <div class="analytics-metrics">
                            <div class="metric">
                                <span class="metric-value">60</span>
                                <span class="metric-label">Total Properties</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">1</span>
                                <span class="metric-label">Occupied</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">1.16%</span>
                                <span class="metric-label">Occupancy Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Analytics -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <h3><i class="fas fa-credit-card"></i> Payment Analytics</h3>
                        <div class="analytics-period">
                            <select>
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 3 months</option>
                                <option>Last year</option>
                            </select>
                        </div>
                    </div>
                    <div class="analytics-content">
                        <div class="chart-container">
                            <canvas id="paymentAnalyticsChart" width="400" height="200"></canvas>
                        </div>
                        <div class="analytics-metrics">
                            <div class="metric">
                                <span class="metric-value">₱1.2M</span>
                                <span class="metric-label">Total Payments</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">95.2%</span>
                                <span class="metric-label">Success Rate</span>
                            </div>
                            <div class="metric">
                                <span class="metric-value">4.8%</span>
                                <span class="metric-label">Failed Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geographic Distribution -->
                <div class="analytics-card full-width">
                    <div class="analytics-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Geographic Distribution</h3>
                        <div class="analytics-period">
                            <select>
                                <option>All Locations</option>
                                <option>Metro Manila</option>
                                <option>Luzon</option>
                                <option>Visayas</option>
                                <option>Mindanao</option>
                            </select>
                        </div>
                    </div>
                    <div class="analytics-content">
                        <div class="geographic-grid">
                            <div class="location-stats">
                                <div class="location-item">
                                    <div class="location-info">
                                        <h4>Quezon City</h4>
                                        <p>Metro Manila</p>
                                    </div>
                                    <div class="location-metrics">
                                        <span class="location-count">23</span>
                                        <span class="location-label">Properties</span>
                                    </div>
                                    <div class="location-bar">
                                        <div class="location-fill" style="width: 85%;"></div>
                                    </div>
                                </div>
                                <div class="location-item">
                                    <div class="location-info">
                                        <h4>Makati City</h4>
                                        <p>Metro Manila</p>
                                    </div>
                                    <div class="location-metrics">
                                        <span class="location-count">18</span>
                                        <span class="location-label">Properties</span>
                                    </div>
                                    <div class="location-bar">
                                        <div class="location-fill" style="width: 67%;"></div>
                                    </div>
                                </div>
                                <div class="location-item">
                                    <div class="location-info">
                                        <h4>Manila City</h4>
                                        <p>Metro Manila</p>
                                    </div>
                                    <div class="location-metrics">
                                        <span class="location-count">15</span>
                                        <span class="location-label">Properties</span>
                                    </div>
                                    <div class="location-bar">
                                        <div class="location-fill" style="width: 56%;"></div>
                                    </div>
                                </div>
                                <div class="location-item">
                                    <div class="location-info">
                                        <h4>Pasig City</h4>
                                        <p>Metro Manila</p>
                                    </div>
                                    <div class="location-metrics">
                                        <span class="location-count">12</span>
                                        <span class="location-label">Properties</span>
                                    </div>
                                    <div class="location-bar">
                                        <div class="location-fill" style="width: 44%;"></div>
                                    </div>
                                </div>
                                <div class="location-item">
                                    <div class="location-info">
                                        <h4>Other Areas</h4>
                                        <p>Various Locations</p>
                                    </div>
                                    <div class="location-metrics">
                                        <span class="location-count">21</span>
                                        <span class="location-label">Properties</span>
                                    </div>
                                    <div class="location-bar">
                                        <div class="location-fill" style="width: 78%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="geographicChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Verification Modal -->
    <div id="documentModal" class="modal">
        <div class="modal-content document-modal">
            <div class="modal-header">
                <h2><i class="fas fa-id-card"></i> Document Verification</h2>
                <button class="modal-close" onclick="closeModal('documentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="verification-container">
                    <!-- User Information -->
                    <div class="user-info-section">
                        <h3><i class="fas fa-user"></i> User Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name:</label>
                                <span id="verifyName">John Doe</span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span id="verifyEmail">john.doe@email.com</span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span id="verifyPhone">+63 912 345 6789</span>
                            </div>
                            <div class="info-item">
                                <label>Account Type:</label>
                                <span id="verifyType">Boarding House Owner</span>
                            </div>
                            <div class="info-item">
                                <label>Address:</label>
                                <span id="verifyBusiness">Sunshine Boarding House</span>
                            </div>
                        </div>
                    </div>

                    <!-- Document Images -->
                    <div class="document-section">
                        <h3><i class="fas fa-images"></i> Uploaded Documents</h3>
                        <div class="document-grid">
                            <div class="document-item">
                                <h4>Front ID</h4>
                                <div class="document-preview">
                                    <img id="frontIdImage" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkZyb250IElEIEltYWdlPC90ZXh0Pjwvc3ZnPg==" alt="Front ID" class="verification-image" onclick="zoomImage(this.src, 'Front ID')">
                                </div>
                            </div>
                            <div class="document-item">
                                <h4>Back ID</h4>
                                <div class="document-preview">
                                    <img id="backIdImage" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkJhY2sgSUQgSW1hZ2U8L3RleHQ+PC9zdmc+" alt="Back ID" class="verification-image" onclick="zoomImage(this.src, 'Back ID')">
                                </div>
                            </div>
                            <div class="document-item">
                                <h4>GCash QR Code</h4>
                                <div class="document-preview">
                                    <img id="gcashQrImage" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkdDYXNoIFFSIEltYWdlPC90ZXh0Pjwvc3ZnPg==" alt="GCash QR" class="verification-image" onclick="zoomImage(this.src, 'GCash QR Code')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Permits Section (only for BH Owners) -->
                    <div class="document-section" id="businessPermitsContainer" style="display: none;">
                        <!-- Business permits will be dynamically inserted here -->
                    </div>

                    <!-- Verification Checklist -->
                    <div class="verification-checklist">
                        <h3><i class="fas fa-clipboard-check"></i> Verification Checklist</h3>
                        <div class="checklist-items">
                            <label class="checklist-item">
                                <input type="checkbox" id="nameMatch">
                                <span class="checkmark"></span>
                                Name on ID matches user information
                            </label>
                            <label class="checklist-item">
                                <input type="checkbox" id="documentValid">
                                <span class="checkmark"></span>
                                Document appears valid and not tampered
                            </label>
                            <label class="checklist-item">
                                <input type="checkbox" id="informationComplete">
                                <span class="checkmark"></span>
                                All required information is visible and clear
                            </label>
                        </div>
                    </div>

                    <!-- Email Verification Section -->
                    <div class="email-verification-section">
                        <h3><i class="fas fa-envelope"></i> Email Verification</h3>
                        <div class="verification-status" id="verificationStatus">
                            <div class="status-item">
                                <i class="fas fa-clock status-pending"></i>
                                <span id="emailVerificationText">Loading verification status...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="verification-actions-final">
                        <h3><i class="fas fa-gavel"></i> Final Decision</h3>
                        <div class="action-buttons-container">
                            <button class="action-btn danger" onclick="rejectFromModal()" id="rejectBtn">
                                <i class="fas fa-times"></i> Reject Application
                            </button>
                            <button class="action-btn success" onclick="approveFromModal()" id="approveBtn">
                                <i class="fas fa-check"></i> Approve Application
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content image-modal">
            <button class="modal-close" onclick="closeModal('imageModal')">&times;</button>
            <img id="zoomedImage" src="" alt="Zoomed Document">
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div id="rejectionReasonModal" class="modal">
        <div class="modal-content document-modal">
            <div class="modal-header">
                <h2><i class="fas fa-times-circle"></i> Reject Application</h2>
                <button class="modal-close" onclick="closeModal('rejectionReasonModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="padding: 1.5rem;">
                    <p style="margin-bottom: 1.5rem; color: #333; font-size: 1rem;">Please provide a reason for rejecting this application:</p>
                    <textarea id="rejectionReason" placeholder="Enter rejection reason..." rows="5" style="width: 100%; padding: 1rem; border: 2px solid #e9ecef; border-radius: 8px; font-family: inherit; font-size: 0.95rem; resize: vertical; margin-bottom: 1.5rem;"></textarea>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button class="action-btn" onclick="closeModal('rejectionReasonModal')" style="background: #6c757d;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="action-btn danger" onclick="confirmRejection()">
                            <i class="fas fa-check"></i> Reject Application
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suspend User Modal -->
    <div id="suspendUserModal" class="modal">
        <div class="modal-content document-modal">
            <div class="modal-header">
                <h2><i class="fas fa-ban"></i> Suspend User Account</h2>
                <button class="modal-close" onclick="closeModal('suspendUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="padding: 1.5rem;">
                    <p style="margin-bottom: 1rem; color: #333; font-size: 1rem; font-weight: 500;">Please provide a reason for suspending this user account:</p>
                    <p style="margin-bottom: 1.5rem; color: #666; font-size: 0.9rem;">The user will not be able to access their account until it is unsuspended.</p>
                    <textarea id="suspendReason" placeholder="Enter suspension reason..." rows="5" style="width: 100%; padding: 1rem; border: 2px solid #e9ecef; border-radius: 8px; font-family: inherit; font-size: 0.95rem; resize: vertical; margin-bottom: 1.5rem;"></textarea>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button class="action-btn" onclick="closeModal('suspendUserModal')" style="background: #6c757d;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="action-btn danger" onclick="confirmSuspendUser()">
                            <i class="fas fa-ban"></i> Suspend User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivate Boarding House Modal -->
    <div id="deactivateBoardingHouseModal" class="modal">
        <div class="modal-content document-modal">
            <div class="modal-header">
                <h2><i class="fas fa-ban"></i> Deactivate Boarding House</h2>
                <button class="modal-close" onclick="closeModal('deactivateBoardingHouseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="padding: 1.5rem;">
                    <p style="margin-bottom: 1rem; color: #333; font-size: 1rem; font-weight: 500;">Please provide a reason for deactivating this boarding house:</p>
                    <p style="margin-bottom: 1.5rem; color: #666; font-size: 0.9rem;">The boarding house will be hidden from search results and no new bookings will be accepted.</p>
                    <textarea id="deactivateReason" placeholder="Enter deactivation reason..." rows="5" style="width: 100%; padding: 1rem; border: 2px solid #e9ecef; border-radius: 8px; font-family: inherit; font-size: 0.95rem; resize: vertical; margin-bottom: 1.5rem;"></textarea>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button class="action-btn" onclick="closeModal('deactivateBoardingHouseModal')" style="background: #6c757d;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="action-btn danger" onclick="confirmDeactivateBoardingHouse()">
                            <i class="fas fa-ban"></i> Deactivate Boarding House
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Preview Modal -->
    <div id="pdfPreviewModal" class="modal" style="display: none; z-index: 10000;">
        <div class="modal-content" style="max-width: 95%; max-height: 95vh; width: 1200px; padding: 0;">
            <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e9ecef;">
                <h2><i class="fas fa-file-pdf"></i> Report Preview</h2>
                <button class="modal-close" onclick="closePDFPreviewModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 0; height: calc(95vh - 120px); overflow: hidden;">
                <iframe id="pdfPreviewFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-top: 1px solid #e9ecef;">
                <button type="button" class="btn-modern btn-cancel" onclick="closePDFPreviewModal()">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn-modern btn-save" id="pdfDownloadBtn" onclick="downloadPDFFromPreview()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'block';
            // Ensure scroll position is at top when opening
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
            // Additional reset for document modal
            if (modalId === 'documentModal') {
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    modalBody.scrollTop = 0;
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
            // Reset scroll position to top
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
            // Additional reset for document modal
            if (modalId === 'documentModal') {
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    modalBody.scrollTop = 0;
                }
            }
        }

        function switchTab(tabName) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }

        function switchUserTab(tabName) {
            // Remove active class from all user management tabs and contents
            document.querySelectorAll('#user-management-section .tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('#user-management-section .tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        function switchNotificationTab(tabName) {
            // Remove active class from all notification tabs and contents
            document.querySelectorAll('#notifications-section .tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('#notifications-section .tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Load system notifications if system tab is selected
            if (tabName === 'system') {
                // Show loading immediately when switching to system tab
                const loadingElement = document.getElementById('system-notifications-loading');
                const containerElement = document.getElementById('system-notifications-container');
                if (loadingElement) {
                    loadingElement.style.display = 'flex';
                }
                if (containerElement) {
                    containerElement.style.display = 'none';
                }
                loadSystemNotifications();
            }
        }
        
        // Also load system notifications when notifications section is first opened
        // Override the original loadNotificationsData to include system notifications

        function switchBoardingHouseTab(tabName) {
            // Remove active class from all boarding house tabs and contents
            document.querySelectorAll('#boarding-houses-section .tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('#boarding-houses-section .tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Boarding Houses Management Functions
        function filterBoardingHouses(filter) {
            // Update active filter button
            document.querySelectorAll('#all-tab .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Reload data with filter
            loadBoardingHousesData(filter);
        }

        function viewBoardingHouseDetails(houseId) {
            alert(`Viewing details for boarding house: ${houseId}`);
        }

        // Dispute Resolution Functions
        function filterDisputes(filter) {
            // Remove active class from all filter buttons
            document.querySelectorAll('#disputes-tab .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Filter logic would go here
            console.log('Filtering disputes by:', filter);
        }

        function viewDisputeDetails(disputeId) {
            alert(`Viewing dispute details: ${disputeId}`);
        }

        function assignDispute(disputeId) {
            alert(`Assigning dispute: ${disputeId}`);
        }

        function resolveDispute(disputeId) {
            alert(`Resolving dispute: ${disputeId}`);
        }

        function viewResolution(disputeId) {
            alert(`Viewing resolution for dispute: ${disputeId}`);
        }

        // Flagged Accounts Functions
        function filterFlagged(filter) {
            // Remove active class from all filter buttons
            document.querySelectorAll('#flagged-tab .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Filter logic would go here
            console.log('Filtering flagged accounts by:', filter);
        }

        function viewFlaggedDetails(userId) {
            alert(`Viewing flagged account details: ${userId}`);
        }

        function unflagUser(userId) {
            if (confirm('Are you sure you want to unflag this user?')) {
                alert(`User ${userId} has been unflagged`);
                // Here you would update the database and refresh the table
            }
        }

        function suspendUser(userId) {
            // Store user ID in modal
            document.getElementById('suspendUserModal').setAttribute('data-user-id', userId);
            
            // Clear previous reason
            document.getElementById('suspendReason').value = '';
            
            // Open the modal
            openModal('suspendUserModal');
        }

        function unsuspendUser(userId) {
            if (confirm('Are you sure you want to unsuspend this user? They will be able to access their account again.')) {
                processSuspendUser(userId, '', 'unsuspend');
            }
        }

        function unbanUser(userId) {
            if (confirm('Are you sure you want to unban this user?')) {
                alert(`User ${userId} has been unbanned`);
                // Here you would update the database and refresh the table
            }
        }

        // Document Verification Functions
        function viewDocuments(registrationId) {
            console.log('Viewing documents for registration ID:', registrationId);
            
            // Fetch registration data
            fetch('../get_registration_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'registration_id=' + registrationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const reg = data.registration;
                    
                    // Update modal with real data
                    document.getElementById('verifyName').textContent = reg.full_name;
                    document.getElementById('verifyEmail').textContent = reg.email;
                    document.getElementById('verifyPhone').textContent = reg.phone;
                    document.getElementById('verifyType').textContent = reg.role;
                    document.getElementById('verifyBusiness').textContent = reg.address || 'N/A';
                    
                    // Update document images
                    if (reg.id_front_file) {
                        document.getElementById('frontIdImage').src = '../' + reg.id_front_file;
                    }
                    if (reg.id_back_file) {
                        document.getElementById('backIdImage').src = '../' + reg.id_back_file;
                    }
                    if (reg.gcash_qr) {
                        document.getElementById('gcashQrImage').src = '../' + reg.gcash_qr;
                    }
                    
                    // Update business permits if user is BH Owner
                    if (reg.role === 'BH Owner' && reg.business_permits && reg.business_permits.length > 0) {
                        const businessPermitsContainer = document.getElementById('businessPermitsContainer');
                        if (businessPermitsContainer) {
                            let permitsHtml = '<h3><i class="fas fa-file-contract"></i> Business Permits</h3><div class="document-grid">';
                            reg.business_permits.forEach((permit, index) => {
                                permitsHtml += `
                                    <div class="document-item">
                                        <h4>Business Permit ${permit.permit_number || (index + 1)}</h4>
                                        <div class="document-preview">
                                            <img src="../${permit.permit_file}" alt="Business Permit ${permit.permit_number || (index + 1)}" class="verification-image" onclick="zoomImage('../${permit.permit_file}', 'Business Permit ${permit.permit_number || (index + 1)}')">
                                        </div>
                                    </div>
                                `;
                            });
                            permitsHtml += '</div>';
                            businessPermitsContainer.innerHTML = permitsHtml;
                            businessPermitsContainer.style.display = 'block';
                        }
                    } else if (reg.role === 'BH Owner') {
                        const businessPermitsContainer = document.getElementById('businessPermitsContainer');
                        if (businessPermitsContainer) {
                            businessPermitsContainer.innerHTML = '<h3><i class="fas fa-file-contract"></i> Business Permits</h3><div class="no-data"><i class="fas fa-file-contract"></i><p>No business permits uploaded</p></div>';
                            businessPermitsContainer.style.display = 'block';
                        }
                    } else {
                        const businessPermitsContainer = document.getElementById('businessPermitsContainer');
                        if (businessPermitsContainer) {
                            businessPermitsContainer.style.display = 'none';
                        }
                    }
                    
                    // Store registration ID for approve/decline actions
                    document.getElementById('documentModal').setAttribute('data-registration-id', registrationId);
                    
                    // Update email verification status
                    updateEmailVerificationStatus(reg.email_verified);
                    
                    // Show the modal
                    openModal('documentModal');
                    // Reset scroll position for document modal
                    setTimeout(() => {
                        const modal = document.getElementById('documentModal');
                        const modalContent = modal.querySelector('.modal-content');
                        if (modalContent) {
                            modalContent.scrollTop = 0;
                        }
                    }, 100);
                } else {
                    alert('Error loading registration details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading registration details');
            });
        }

        // Modal approval functions
        function approveFromModal() {
            const modal = document.getElementById('documentModal');
            const registrationId = modal.getAttribute('data-registration-id');
            
            if (!registrationId) {
                alert('Error: Registration ID not found');
                return;
            }
            
            if (confirm('Are you sure you want to approve this registration? The user will be moved to the users table.')) {
                processApproval(registrationId);
                closeModal('documentModal');
            }
        }

        function rejectFromModal() {
            const modal = document.getElementById('documentModal');
            const registrationId = modal.getAttribute('data-registration-id');
            
            if (!registrationId) {
                alert('Error: Registration ID not found');
                return;
            }
            
            // Store registration ID in rejection modal
            document.getElementById('rejectionReasonModal').setAttribute('data-registration-id', registrationId);
            
            // Clear previous reason
            document.getElementById('rejectionReason').value = '';
            
            // Close document modal and open rejection reason modal
                closeModal('documentModal');
            setTimeout(() => {
                openModal('rejectionReasonModal');
            }, 300);
        }

        function confirmRejection() {
            const rejectionModal = document.getElementById('rejectionReasonModal');
            const registrationId = rejectionModal.getAttribute('data-registration-id');
            const reason = document.getElementById('rejectionReason').value.trim();
            
            if (!registrationId) {
                alert('Error: Registration ID not found');
                return;
            }
            
            if (!reason) {
                alert('Please provide a reason for rejection.');
                return;
            }
            
            if (confirm('Are you sure you want to reject this registration?')) {
                processRejection(registrationId, reason);
                closeModal('rejectionReasonModal');
            }
        }

        function confirmSuspendUser() {
            const suspendModal = document.getElementById('suspendUserModal');
            const userId = suspendModal.getAttribute('data-user-id');
            const reason = document.getElementById('suspendReason').value.trim();
            
            if (!userId) {
                alert('Error: User ID not found');
                return;
            }
            
            if (!reason) {
                alert('Please provide a reason for suspension.');
                return;
            }
            
            if (confirm('Are you sure you want to suspend this user? They will not be able to access their account.')) {
                processSuspendUser(userId, reason);
                closeModal('suspendUserModal');
            }
        }

        function confirmDeactivateBoardingHouse() {
            const deactivateModal = document.getElementById('deactivateBoardingHouseModal');
            const houseId = deactivateModal.getAttribute('data-house-id');
            const reason = document.getElementById('deactivateReason').value.trim();
            
            if (!houseId) {
                alert('Error: Boarding House ID not found');
                return;
            }
            
            if (!reason) {
                alert('Please provide a reason for deactivation.');
                return;
            }
            
            if (confirm('Are you sure you want to deactivate this boarding house? It will be hidden from search results.')) {
                processDeactivateBoardingHouse(houseId, reason);
                closeModal('deactivateBoardingHouseModal');
            }
        }

        // Actual approval processing function
        function processApproval(registrationId) {
            const formData = new FormData();
            formData.append('registration_id', registrationId);
            formData.append('action', 'approve');

            fetch('../approved_registration.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Approval response:', data);
                if (data.success) {
                    // Show success message - this is a blocking call
                    alert('Registration approved successfully! User has been added to the system.\n\nClick OK to refresh the page automatically.');
                    
                    // After alert closes (user clicks OK), immediately reload the page
                    // Using location.reload(true) forces a hard refresh from server
                    // This ensures fresh data is loaded
                    try {
                        window.location.reload(true);
                    } catch (e) {
                        // Fallback if reload(true) doesn't work (some browsers)
                        const currentUrl = window.location.href.split('?')[0];
                        window.location.href = currentUrl + '?approved=' + data.registration_id + '&t=' + new Date().getTime();
                    }
                } else {
                    alert('Error approving registration: ' + (data.message || data.error_details || 'Unknown error'));
                    console.error('Approval error:', data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error approving registration: ' + error.message + '. Please check the console for details.');
            });
        }

        // Actual rejection processing function
        function processRejection(registrationId, reason) {
            const formData = new FormData();
            formData.append('registration_id', registrationId);
            formData.append('action', 'reject');
            formData.append('reason', reason);

            fetch('../approved_registration.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration rejected successfully!\n\nClick OK to refresh the page automatically.');
                    
                    // After alert closes, immediately reload the page
                    try {
                        window.location.reload(true);
                    } catch (e) {
                        // Fallback if reload(true) doesn't work
                        const currentUrl = window.location.href.split('?')[0];
                        window.location.href = currentUrl + '?rejected=' + registrationId + '&t=' + new Date().getTime();
                    }
                } else {
                    alert('Error rejecting registration: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error rejecting registration. Please try again.');
            });
        }

        // Actual suspend user processing function
        function processSuspendUser(userId, reason, action = 'suspend') {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);
            if (reason) {
                formData.append('reason', reason);
            }

            fetch('../suspend_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = action === 'suspend' 
                        ? 'User suspended successfully!\n\nClick OK to refresh the page automatically.'
                        : 'User unsuspended successfully!\n\nClick OK to refresh the page automatically.';
                    alert(message);
                    
                    // After alert closes, immediately reload the page
                    try {
                        window.location.reload(true);
                    } catch (e) {
                        // Fallback if reload(true) doesn't work
                        const currentUrl = window.location.href.split('?')[0];
                        window.location.href = currentUrl + '?' + action + '=' + userId + '&t=' + new Date().getTime();
                    }
                } else {
                    const errorMsg = action === 'suspend' 
                        ? 'Error suspending user: ' 
                        : 'Error unsuspending user: ';
                    alert(errorMsg + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = action === 'suspend' 
                    ? 'Error suspending user. Please try again.'
                    : 'Error unsuspending user. Please try again.';
                alert(errorMsg);
            });
        }

        // Actual deactivate boarding house processing function
        function processDeactivateBoardingHouse(houseId, reason, action = 'deactivate') {
            const formData = new FormData();
            formData.append('bh_id', houseId);
            formData.append('action', action);
            if (reason) {
                formData.append('reason', reason);
            }

            fetch('../deactivate_boarding_house.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = action === 'deactivate' 
                        ? 'Boarding house deactivated successfully!\n\nClick OK to refresh the page automatically.'
                        : 'Boarding house activated successfully!\n\nClick OK to refresh the page automatically.';
                    alert(message);
                    
                    // After alert closes, immediately reload the page
                    try {
                        window.location.reload(true);
                    } catch (e) {
                        // Fallback if reload(true) doesn't work
                        const currentUrl = window.location.href.split('?')[0];
                        window.location.href = currentUrl + '?' + action + '=' + houseId + '&t=' + new Date().getTime();
                    }
                } else {
                    const errorMsg = action === 'deactivate' 
                        ? 'Error deactivating boarding house: ' 
                        : 'Error activating boarding house: ';
                    alert(errorMsg + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = action === 'deactivate' 
                    ? 'Error deactivating boarding house. Please try again.'
                    : 'Error activating boarding house. Please try again.';
                alert(errorMsg);
            });
        }

        function activateBoardingHouse(houseId) {
            if (confirm('Are you sure you want to activate this boarding house? It will be visible in search results again.')) {
                processDeactivateBoardingHouse(houseId, '', 'activate');
            }
        }

        function getUserData(userId) {
            // Sample user data - in real implementation, this would come from server
            const userDataMap = {
                'john_doe': {
                    name: 'John Doe',
                    email: 'john.doe@email.com',
                    phone: '+63 912 345 6789',
                    type: 'Boarding House Owner',
                    business: 'Sunshine Boarding House',
                    frontIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkpvaG4gRG9lIC0gRnJvbnQgSUQ8L3RleHQ+PC9zdmc+',
                    backIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkpvaG4gRG9lIC0gQmFjayBJRDwvdGV4dD48L3N2Zz4='
                },
                'maria_santos': {
                    name: 'Maria Santos',
                    email: 'maria.santos@email.com',
                    phone: '+63 917 123 4567',
                    type: 'Boarder',
                    business: 'N/A',
                    frontIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk1hcmlhIFNhbnRvcyAtIEZyb250IElEPC90ZXh0Pjwvc3ZnPg==',
                    backIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk1hcmlhIFNhbnRvcyAtIEJhY2sgSUQ8L3RleHQ+PC9zdmc+'
                },
                'anna_garcia': {
                    name: 'Anna Garcia',
                    email: 'anna.garcia@email.com',
                    phone: '+63 919 876 5432',
                    type: 'Boarder',
                    business: 'N/A',
                    frontIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkFubmEgR2FyY2lhIC0gRnJvbnQgSUQ8L3RleHQ+PC9zdmc+',
                    backIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkFubmEgR2FyY2lhIC0gQmFjayBJRDwvdGV4dD48L3N2Zz4='
                },
                'david_lee': {
                    name: 'David Lee',
                    email: 'david.lee@email.com',
                    phone: '+63 918 555 1234',
                    type: 'Boarder',
                    business: 'N/A',
                    frontIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkRhdmlkIExlZSAtIEZyb250IElEPC90ZXh0Pjwvc3ZnPg==',
                    backIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkRhdmlkIExlZSAtIEJhY2sgSUQ8L3RleHQ+PC9zdmc+'
                },
                'robert_brown': {
                    name: 'Robert Brown',
                    email: 'robert.brown@email.com',
                    phone: '+63 918 765 4321',
                    type: 'Boarding House Owner',
                    business: 'Metro Boarding House',
                    frontIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlJvYmVydCBCcm93biAtIEZyb250IElEPC90ZXh0Pjwvc3ZnPg==',
                    backIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlJvYmVydCBCcm93biAtIEJhY2sgSUQ8L3RleHQ+PC9zdmc+'
                },
                'sarah_wilson': {
                    name: 'Sarah Wilson',
                    email: 'sarah.wilson@email.com',
                    phone: '+63 916 234 5678',
                    type: 'Boarding House Owner',
                    business: 'Wilson Residence',
                    frontIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlNhcmFoIFdpbHNvbiAtIEZyb250IElEPC90ZXh0Pjwvc3ZnPg==',
                    backIdImage: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlNhcmFoIFdpbHNvbiAtIEJhY2sgSUQ8L3RleHQ+PC9zdmc+'
                }
            };
            
            return userDataMap[userId] || userDataMap['john_doe'];
        }

        function clearVerificationData() {
            // Clear checkboxes
            document.getElementById('nameMatch').checked = false;
            document.getElementById('documentValid').checked = false;
            document.getElementById('informationComplete').checked = false;
            
            // Clear notes
            // Reset email verification status
            resetEmailVerificationStatus();
        }

        function resetEmailVerificationStatus() {
            // Reset status display
            const statusDiv = document.getElementById('verificationStatus');
            statusDiv.innerHTML = `
                <div class="status-item">
                    <i class="fas fa-clock status-pending"></i>
                    <span>Email verification pending</span>
                </div>
            `;
            
            // Show send button, hide check button
            document.getElementById('sendVerificationBtn').style.display = 'inline-block';
            document.getElementById('checkVerificationBtn').style.display = 'none';
            
            // Disable approve/reject buttons
            document.getElementById('approveBtn').disabled = true;
            document.getElementById('rejectBtn').disabled = true;
        }

        function openImageModal(imageId) {
            const img = document.getElementById(imageId);
            document.getElementById('zoomedImage').src = img.src;
            openModal('imageModal');
        }

        function downloadDocument(docType) {
            alert(`Downloading ${docType} document...`);
            // In real implementation, this would trigger a download
        }

        function zoomDocument(imageId) {
            openImageModal(imageId);
        }

        // Email Verification Functions
        function updateEmailVerificationStatus(emailVerified) {
            const statusDiv = document.getElementById('verificationStatus');
            const textElement = document.getElementById('emailVerificationText');
            const iconElement = statusDiv.querySelector('i');
            
            if (emailVerified == 1) {
                // Email is verified
                iconElement.className = 'fas fa-check-circle status-verified';
                textElement.textContent = 'Email verified';
            } else {
                // Email is not verified
                iconElement.className = 'fas fa-times-circle status-failed';
                textElement.textContent = 'Email not verified';
            }
        }

        function approveWithVerification() {
            const nameMatch = document.getElementById('nameMatch').checked;
            const documentValid = document.getElementById('documentValid').checked;
            const informationComplete = document.getElementById('informationComplete').checked;
            
            // Check if email is verified
            const statusText = document.getElementById('verificationStatus').textContent;
            if (!statusText.includes('verified successfully')) {
                alert('Please ensure email verification is completed before approving.');
                return;
            }
            
            if (!nameMatch || !documentValid || !informationComplete) {
                alert('Please complete all verification checklist items before approving.');
                return;
            }
            
            if (confirm('Are you sure you want to approve this application after verification?')) {
                alert('Application approved successfully!');
                closeModal('documentModal');
                // Here you would update the database and refresh the pending approvals
            }
        }

        function rejectWithReason() {
            // Check if email verification is completed (either verified or failed)
            const statusText = document.getElementById('verificationStatus').textContent;
            if (statusText.includes('pending')) {
                alert('Please complete the email verification process before rejecting.');
                return;
            }
            
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to reject this application?')) {
                    alert(`Application rejected. Reason: ${reason}`);
                    closeModal('documentModal');
                    // Here you would update the database and refresh the pending approvals
                }
            }
        }

        function deactivateBoardingHouse(houseId) {
            // Store boarding house ID in modal
            document.getElementById('deactivateBoardingHouseModal').setAttribute('data-house-id', houseId);
            
            // Clear previous reason
            document.getElementById('deactivateReason').value = '';
            
            // Open the modal
            openModal('deactivateBoardingHouseModal');
        }


        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        function closeOffcanvas() {
            const offcanvasElement = document.getElementById('sidebarOffcanvas');
            if (offcanvasElement) {
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                if (offcanvas) {
                    offcanvas.hide();
                }
            }
        }

        function showSection(sectionName, event) {
            // Prevent default link behavior
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
                
                // Scroll to top of main content
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } else {
                console.error('Section not found: ' + sectionName + '-section');
                return;
            }
            
            // Add active class to clicked nav item
            if (event && event.target) {
                const navItem = event.target.closest('.nav-item');
                if (navItem) {
                    navItem.classList.add('active');
                }
            } else {
                // Fallback: find nav item by section name
                document.querySelectorAll('.nav-item').forEach(item => {
                    const onclickAttr = item.getAttribute('onclick');
                    if (onclickAttr && onclickAttr.includes("'" + sectionName + "'")) {
                        item.classList.add('active');
                    }
                });
            }
            
            // Load data for specific sections
            switch(sectionName) {
                case 'dashboard':
                    // Reload recent activity when dashboard is shown
                    if (typeof loadDashboardRecentActivity === 'function') {
                    loadDashboardRecentActivity();
                    }
                    break;
                case 'user-management':
                    if (typeof loadUserStatsData === 'function') {
                    loadUserStatsData();
                    }
                    break;
                case 'boarding-houses':
                    if (typeof loadBoardingHousesData === 'function') {
                    loadBoardingHousesData();
                    }
                    break;
                case 'notifications':
                    if (typeof loadNotificationsData === 'function') {
                    loadNotificationsData();
                    }
                    break;
                case 'analytics':
                    if (typeof loadAnalyticsData === 'function') {
                    loadAnalyticsData();
                    }
                    break;
                case 'reports':
                    // Load boarding houses for filter dropdowns
                    if (typeof loadBoardingHousesForFilters === 'function') {
                        loadBoardingHousesForFilters();
                    }
                    break;
            }
        }
        
        // Load User Statistics Data
        async function loadUserStatsData() {
            try {
                const response = await fetch('../get_admin_user_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    updateUserStats(data.data);
                } else {
                    console.error('Error loading user stats data:', data.error);
                }
            } catch (error) {
                console.error('Error loading user stats data:', error);
            }
        }
        
        // Update User Statistics
        function updateUserStats(stats) {
            // Update total users count
            const totalUsersElement = document.getElementById('total-users-count');
            if (totalUsersElement) {
                totalUsersElement.textContent = `Total: ${stats.total_users} users`;
            }
            
            // Update boarders count
            const boardersCountElement = document.getElementById('boarders-count');
            if (boardersCountElement) {
                boardersCountElement.textContent = stats.total_boarders;
            }
            
            // Update owners count
            const ownersCountElement = document.getElementById('owners-count');
            if (ownersCountElement) {
                ownersCountElement.textContent = stats.total_owners;
            }
        }

        // User Details Modal Functions
        function viewUserDetails(userId) {
            const modal = document.getElementById('userDetailsModal');
            const content = document.getElementById('userDetailsContent');
            
            if (!modal || !content) {
                alert('Modal elements not found!');
                return;
            }
            
            // Show modal with loading state
            modal.style.display = 'block';
            content.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading user details...</p>
                </div>
            `;
            
            // Load user details
            loadUserDetails(userId);
        }
        
        async function loadUserDetails(userId) {
            try {
                const url = `../get_user_details_simple.php?user_id=${userId}`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    displayUserDetails(data.data);
                } else {
                    showUserDetailsError(data.error);
                }
            } catch (error) {
                showUserDetailsError('Failed to load user details: ' + error.message);
            }
        }
        
        function displayUserDetails(data) {
            const content = document.getElementById('userDetailsContent');
            const user = data.user;
            const boardingHouses = data.boarding_houses || [];
            const bookings = data.bookings || [];
            const businessPermits = data.business_permits || [];
            
            const userInitials = (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase();
            let fullName = user.middle_name ? `${user.first_name} ${user.middle_name} ${user.last_name}` : `${user.first_name} ${user.last_name}`;
            if (user.suffix) {
                fullName += ` ${user.suffix}`;
            }
            const profilePicture = user.profile_picture ? `../${user.profile_picture}` : `https://ui-avatars.com/api/?name=${user.first_name}+${user.last_name}&background=8D6E63&color=fff`;
            
            let html = `
                <div class="user-details-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="user-profile-header">
                        <div class="profile-picture-container">
                            <img src="${profilePicture}" alt="${fullName}" class="user-profile-picture" onclick="zoomImage('${profilePicture}', 'Profile Picture')" onerror="this.src='https://ui-avatars.com/api/?name=${user.first_name}+${user.last_name}&background=8D6E63&color=fff'">
                        </div>
                        <div class="profile-info">
                            <h2>${fullName}</h2>
                            <p class="user-role">${user.role}</p>
                            <div class="status-badges">
                                <span class="status-badge ${user.user_status === 'Active' ? 'status-active' : 'status-inactive'}">
                                    ${user.user_status}
                                </span>
                                <span class="status-badge ${user.reg_status === 'approved' ? 'status-approved' : 'status-pending'}">
                                    ${user.reg_status}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="user-info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value">${fullName}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">First Name</div>
                            <div class="info-value">${user.first_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Middle Name</div>
                            <div class="info-value">${user.middle_name || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Name</div>
                            <div class="info-value">${user.last_name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Suffix</div>
                            <div class="info-value">${user.suffix || 'None'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">${user.email}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">${user.phone || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Role</div>
                            <div class="info-value">${user.role}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">User Status</div>
                            <div class="info-value">
                                <span class="status-badge ${user.user_status === 'Active' ? 'status-active' : 'status-inactive'}">
                                    ${user.user_status}
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Registration Status</div>
                            <div class="info-value">
                                <span class="status-badge ${user.reg_status === 'approved' ? 'status-approved' : 'status-pending'}">
                                    ${user.reg_status}
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value">${user.address || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Birth Date</div>
                            <div class="info-value">${user.birth_date || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">GCash Number</div>
                            <div class="info-value">${user.gcash_num || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Terms Agreed</div>
                            <div class="info-value">${user.cb_agreed ? 'Yes' : 'No'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Registration Date</div>
                            <div class="info-value">${new Date(user.reg_created_at).toLocaleDateString()}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Updated</div>
                            <div class="info-value">${user.reg_updated_at ? new Date(user.reg_updated_at).toLocaleDateString() : 'Never'}</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add ID verification section with images
            html += `
                <div class="user-details-section">
                    <h3><i class="fas fa-id-card"></i> ID Verification</h3>
                    <div class="user-info-grid">
                        <div class="info-item">
                            <div class="info-label">Valid ID Type</div>
                            <div class="info-value">${user.valid_id_type || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">ID Number</div>
                            <div class="info-value">${user.id_number || 'Not provided'}</div>
                        </div>
                    </div>
                    
                    <div class="verification-images-section">
                        <h4>Uploaded Documents</h4>
                        <div class="images-grid">
                            <div class="image-item">
                                <h5>Front ID Image</h5>
                                ${user.idFrontFile ? 
                                    `<img src="../${user.idFrontFile}" alt="Front ID" class="verification-image" onclick="zoomImage('../${user.idFrontFile}', 'Front ID')">` : 
                                    '<div class="no-image">Not provided</div>'
                                }
                            </div>
                            <div class="image-item">
                                <h5>Back ID Image</h5>
                                ${user.idBackFile ? 
                                    `<img src="../${user.idBackFile}" alt="Back ID" class="verification-image" onclick="zoomImage('../${user.idBackFile}', 'Back ID')">` : 
                                    '<div class="no-image">Not provided</div>'
                                }
                            </div>
                            <div class="image-item">
                                <h5>GCash QR Code</h5>
                                ${user.gcash_qr ? 
                                    `<img src="../${user.gcash_qr}" alt="GCash QR" class="verification-image" onclick="zoomImage('../${user.gcash_qr}', 'GCash QR Code')">` : 
                                    '<div class="no-image">Not provided</div>'
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add business permits section if user is BH Owner
            if (user.role === 'BH Owner') {
                html += `
                    <div class="user-details-section">
                        <h3><i class="fas fa-file-contract"></i> Business Permits</h3>
                `;
                
                if (businessPermits.length > 0) {
                    html += `
                        <div class="verification-images-section">
                            <h4>Uploaded Business Permits (${businessPermits.length})</h4>
                            <div class="images-grid">
                    `;
                    
                    businessPermits.forEach((permit, index) => {
                        html += `
                            <div class="image-item">
                                <h5>Business Permit ${permit.permit_number || (index + 1)}</h5>
                                ${permit.permit_file ? 
                                    `<img src="../${permit.permit_file}" alt="Business Permit ${permit.permit_number || (index + 1)}" class="verification-image" onclick="zoomImage('../${permit.permit_file}', 'Business Permit ${permit.permit_number || (index + 1)}')">` : 
                                    '<div class="no-image">Not provided</div>'
                                }
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="no-data">
                            <i class="fas fa-file-contract"></i>
                            <p>No business permits uploaded</p>
                        </div>
                    `;
                }
                
                html += `</div>`;
            }
            
            // Add boarding houses if user is an owner
            if (user.role === 'BH Owner' && boardingHouses.length > 0) {
                html += `
                    <div class="user-details-section">
                        <h3><i class="fas fa-home"></i> Boarding Houses (${boardingHouses.length})</h3>
                `;
                
                boardingHouses.forEach(house => {
                    const statusClass = house.status === 'Active' ? 'status-active' : 'status-inactive';
                    html += `
                        <div class="boarding-house-item">
                            <div class="house-info">
                                <strong>${house.bh_name}</strong>
                                <p>${house.bh_address} • ${house.total_rooms} rooms</p>
                                <small>Created: ${new Date(house.bh_created_at).toLocaleDateString()}</small>
                            </div>
                            <div class="house-status">
                                <span class="status-badge ${statusClass}">${house.status}</span>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            } else if (user.role === 'BH Owner') {
                html += `
                    <div class="user-details-section">
                        <h3><i class="fas fa-home"></i> Boarding Houses</h3>
                        <div class="no-data">
                            <i class="fas fa-home"></i>
                            <p>No boarding houses registered</p>
                        </div>
                    </div>
                `;
            }
            
            // Add bookings if user is a boarder
            if (user.role === 'Boarder' && bookings.length > 0) {
                html += `
                    <div class="user-details-section">
                        <h3><i class="fas fa-calendar-check"></i> Recent Bookings (${bookings.length})</h3>
                `;
                
                bookings.forEach(booking => {
                    const statusLower = (booking.status || '').toLowerCase();
                    const statusClass = statusLower === 'confirmed' ? 'status-active' : 
                                     statusLower === 'pending' ? 'status-pending' : 
                                     statusLower === 'completed' ? 'status-completed' : 'status-inactive';
                    html += `
                        <div class="booking-item">
                            <div class="item-header">
                                <div class="item-title">${booking.bh_name || 'N/A'}</div>
                                <span class="status-badge ${statusClass}">${booking.status || 'N/A'}</span>
                            </div>
                            <div class="item-details">
                                <strong>Room:</strong> ${booking.room_name || 'N/A'} (${booking.room_category || 'N/A'})<br>
                                <strong>Room Unit:</strong> ${booking.room_number || 'N/A'}<br>
                                ${booking.capacity ? `<strong>Capacity:</strong> ${booking.capacity} person(s)<br>` : ''}
                                <strong>Check-in:</strong> ${booking.check_in_date ? new Date(booking.check_in_date).toLocaleDateString() : 'N/A'}<br>
                                <strong>Check-out:</strong> ${booking.check_out_date ? new Date(booking.check_out_date).toLocaleDateString() : 'N/A'}<br>
                                <strong>Amount:</strong> ₱${parseFloat(booking.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}<br>
                                <strong>Booked:</strong> ${booking.created_at ? new Date(booking.created_at).toLocaleDateString() : 'N/A'}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            } else if (user.role === 'Boarder') {
                html += `
                    <div class="user-details-section">
                        <h3><i class="fas fa-calendar-check"></i> Bookings</h3>
                        <div class="no-data">
                            <i class="fas fa-calendar"></i>
                            <p>No bookings found</p>
                        </div>
                    </div>
                `;
            }
            
            content.innerHTML = html;
        }
        
        function showUserDetailsError(error) {
            const content = document.getElementById('userDetailsContent');
            content.innerHTML = `
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error: ${error}</p>
                </div>
            `;
        }
        
        function closeUserDetailsModal() {
            const modal = document.getElementById('userDetailsModal');
            modal.style.display = 'none';
            // Reset scroll position to top
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
        }

        // Boarding House Details Modal Functions
        function viewBoardingHouseDetails(bhId) {
            const modal = document.getElementById('boardingHouseDetailsModal');
            const content = document.getElementById('boardingHouseDetailsContent');
            
            if (!modal || !content) {
                alert('Modal elements not found!');
                return;
            }
            
            // Show modal and loading state
            modal.style.display = 'block';
            content.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading boarding house details...</p>
                </div>
            `;
            
            // Reset scroll position
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
            
            // Load boarding house details
            loadBoardingHouseDetails(bhId);
        }
        
        function loadBoardingHouseDetails(bhId) {
            fetch(`../get_boarding_house_details.php?bh_id=${bhId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBoardingHouseDetails(data.data);
                    } else {
                        displayBoardingHouseError(data.error || 'Failed to load boarding house details');
                    }
                })
                .catch(error => {
                    console.error('Error loading boarding house details:', error);
                    displayBoardingHouseError('Error loading boarding house details. Please try again.');
                });
        }
        
        function displayBoardingHouseDetails(data) {
            const content = document.getElementById('boardingHouseDetailsContent');
            const bh = data.boarding_house;
            const rooms = data.rooms;
            const stats = data.statistics;
            
            const ownerName = `${bh.first_name} ${bh.last_name}`;
            const profilePicture = bh.profile_picture ? `../${bh.profile_picture}` : `https://ui-avatars.com/api/?name=${bh.first_name}+${bh.last_name}&background=8D6E63&color=fff`;
            
            content.innerHTML = `
                <div class="boarding-house-details">
                    <!-- Property Header -->
                    <div class="property-header">
                        <div class="property-image">
                            <img src="${profilePicture}" alt="${ownerName}" class="owner-profile-picture" onclick="zoomImage('${profilePicture}', 'Owner Profile Picture')" onerror="this.src='https://ui-avatars.com/api/?name=${bh.first_name}+${bh.last_name}&background=8D6E63&color=fff'">
                        </div>
                        <div class="property-info">
                            <h3>${bh.bh_name}</h3>
                            <p class="property-address"><i class="fas fa-map-marker-alt"></i> ${bh.bh_address}</p>
                            <div class="property-status">
                                <span class="status-badge ${bh.status.toLowerCase()}">${bh.status}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Owner Information -->
                    <div class="owner-section">
                        <h4><i class="fas fa-user-tie"></i> Property Owner</h4>
                        <div class="owner-info">
                            <div class="owner-details">
                                <strong>${ownerName}</strong>
                                <p><i class="fas fa-envelope"></i> ${bh.email}</p>
                                <p><i class="fas fa-phone"></i> ${bh.phone || 'Not provided'}</p>
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- Property Description -->
                    ${bh.bh_description ? `
                    <div class="description-section">
                        <h4><i class="fas fa-info-circle"></i> Description</h4>
                        <p>${bh.bh_description}</p>
                    </div>
                    ` : ''}
                    
                    <!-- Rooms List -->
                    <div class="rooms-section">
                        <h4><i class="fas fa-bed"></i> Room Details (${rooms.length} room types)</h4>
                        <div class="rooms-list">
                            ${rooms.map(room => `
                                <div class="room-item">
                                    <div class="room-header">
                                        <div class="room-title">
                                            <h5>${room.room_name}</h5>
                                            <span class="room-category">${room.room_category}</span>
                                        </div>
                                        <div class="room-price">
                                            <span class="price-amount">₱${parseFloat(room.price).toLocaleString()}</span>
                                            <span class="price-period">/month</span>
                                        </div>
                                    </div>
                                    <div class="room-details">
                                        <div class="detail-row">
                                            <span class="detail-label"><i class="fas fa-users"></i> Capacity:</span>
                                            <span class="detail-value">${room.capacity} person${room.capacity > 1 ? 's' : ''}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label"><i class="fas fa-home"></i> Available Units:</span>
                                            <span class="detail-value">${room.total_rooms} unit${room.total_rooms > 1 ? 's' : ''}</span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label"><i class="fas fa-calendar"></i> Created:</span>
                                            <span class="detail-value">${new Date(room.created_at).toLocaleDateString()}</span>
                                        </div>
                                    </div>
                                    ${room.room_description ? `
                                    <div class="room-description-section">
                                        <span class="detail-label"><i class="fas fa-info-circle"></i> Description:</span>
                                        <p class="room-description">${room.room_description}</p>
                                    </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <!-- Property Dates -->
                    <div class="dates-section">
                        <h4><i class="fas fa-calendar"></i> Property Information</h4>
                        <div class="date-info">
                            <p><strong>Created:</strong> ${new Date(bh.bh_created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function displayBoardingHouseError(error) {
            const content = document.getElementById('boardingHouseDetailsContent');
            content.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error: ${error}</p>
                </div>
            `;
        }
        
        function closeBoardingHouseDetailsModal() {
            const modal = document.getElementById('boardingHouseDetailsModal');
            modal.style.display = 'none';
            // Reset scroll position to top
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userDetailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
                // Reset scroll position to top
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.scrollTop = 0;
                }
            }
        }

        // Image zoom functionality
        function zoomImage(imageSrc, imageTitle) {
            // Create zoom modal
            const zoomModal = document.createElement('div');
            zoomModal.id = 'imageZoomModal';
            zoomModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                padding: 20px;
                box-sizing: border-box;
            `;
            
            zoomModal.innerHTML = `
                <div style="position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="position: absolute; top: 20px; left: 20px; color: white; font-size: 1.2rem; font-weight: 600; background: rgba(0,0,0,0.7); padding: 8px 16px; border-radius: 4px;">${imageTitle}</div>
                    <button onclick="closeImageZoom()" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 1.2rem;">&times;</button>
                    <img src="${imageSrc}" alt="${imageTitle}" style="max-width: 100%; max-height: calc(100% - 80px); object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
                </div>
            `;
            
            document.body.appendChild(zoomModal);
            
            // Close on click
            zoomModal.onclick = function(e) {
                if (e.target === zoomModal) {
                    closeImageZoom();
                }
            };
        }
        
        function closeImageZoom() {
            const zoomModal = document.getElementById('imageZoomModal');
            if (zoomModal) {
                zoomModal.remove();
            }
        }
        

        
        // Load Notifications Data
        async function loadNotificationsData() {
            try {
                // Always load system notifications when notifications section is opened
                // System tab is active by default, so load system notifications immediately
                console.log('Loading notifications data...');
                
                // Show loading indicator immediately when notifications section is opened
                const loadingElement = document.getElementById('system-notifications-loading');
                const containerElement = document.getElementById('system-notifications-container');
                if (loadingElement) {
                    loadingElement.style.display = 'flex';
                }
                if (containerElement) {
                    containerElement.style.display = 'none';
                }
                
                loadSystemNotifications();
                
                // Also load user notifications list for statistics/other uses
            try {
                const response = await fetch('../get_admin_notifications.php?action=list&type=all&status=all');
                const data = await response.json();
                
                if (data.success) {
                    updateNotificationsTable(data.data);
                        console.log('User notifications loaded:', data.data.notifications?.length || 0);
                } else {
                    console.error('Error loading notifications data:', data.error);
                    }
                } catch (error) {
                    console.error('Error loading user notifications:', error);
                    // Don't fail if user notifications can't be loaded
                }
            } catch (error) {
                console.error('Error loading notifications data:', error);
            }
        }
        
        // Load Boarding Houses Data
        async function loadBoardingHousesData(filter = 'all') {
            try {
                const response = await fetch(`../get_admin_boarding_houses_simple.php?status=${filter}`);
                const data = await response.json();
                
                if (data.success) {
                    updateBoardingHousesTables(data.data);
                } else {
                    console.error('Error loading boarding houses data:', data.error);
                }
            } catch (error) {
                console.error('Error loading boarding houses data:', error);
            }
        }
        
        // Global chart instances for cleanup
        let analyticsCharts = {};
        let analyticsLoaded = false;
        
        // Load Analytics Data
        async function loadAnalyticsData() {
            console.log('Loading analytics data...');
            
            // Check if analytics section is visible
            const analyticsSection = document.getElementById('analytics-section');
            if (!analyticsSection) {
                console.error('Analytics section not found');
                return;
            }
            console.log('Analytics section found');
            
            // Check if analytics is already loaded
            if (analyticsLoaded) {
                console.log('Analytics already loaded, showing cached data');
                showAnalyticsContent();
                return;
            }
            
            // Destroy existing charts to prevent conflicts
            destroyAnalyticsCharts();
            
            // Show loading indicator
            const loadingElement = document.getElementById('analytics-loading');
            const contentElement = document.getElementById('analytics-content');
            if (loadingElement) {
                loadingElement.style.display = 'flex';
            }
            if (contentElement) {
                contentElement.style.display = 'none';
            }
            
            try {
                const response = await fetch('../get_analytics_data.php');
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Analytics data received:', data);
                
                if (data.success) {
                    updateAnalyticsUI(data.data);
                    initializeAnalyticsCharts(data.data);
                    
                    // Mark as loaded and show content
                    analyticsLoaded = true;
                    showAnalyticsContent();
                } else {
                    console.error('Error loading analytics data:', data.error);
                    
                    // Reset loading state and show error
                    analyticsLoaded = false;
                    showAnalyticsContent();
                    const contentElement = document.getElementById('analytics-content');
                    if (contentElement) {
                        contentElement.innerHTML = '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Error loading analytics data. Please try again.</p></div>';
                    }
                }
            } catch (error) {
                console.error('Error loading analytics data:', error);
                
                // Reset loading state and show error
                analyticsLoaded = false;
                showAnalyticsContent();
                const contentElement = document.getElementById('analytics-content');
                if (contentElement) {
                    contentElement.innerHTML = '<div class="no-data"><i class="fas fa-exclamation-triangle"></i><p>Error loading analytics data. Please try again.</p></div>';
                }
            }
        }
        
        // Destroy existing analytics charts
        function destroyAnalyticsCharts() {
            console.log('Destroying existing analytics charts...');
            Object.keys(analyticsCharts).forEach(chartId => {
                if (analyticsCharts[chartId]) {
                    analyticsCharts[chartId].destroy();
                    delete analyticsCharts[chartId];
                }
            });
        }
        
        // Show analytics content (hide loading, show content)
        function showAnalyticsContent() {
            const loadingElement = document.getElementById('analytics-loading');
            const contentElement = document.getElementById('analytics-content');
            
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            if (contentElement) {
                contentElement.style.display = 'block';
            }
        }
        
        // Load Dashboard Analytics (for overview charts)
        async function loadDashboardAnalytics() {
            console.log('Loading dashboard analytics...');
            
            try {
                const response = await fetch('../get_analytics_data.php');
                console.log('Dashboard analytics response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Dashboard analytics data received:', data);
                
                if (data.success) {
                    createDashboardCharts(data.data);
                } else {
                    console.error('Error loading dashboard analytics data:', data.error);
                }
            } catch (error) {
                console.error('Error loading dashboard analytics data:', error);
            }
        }
        
        // Create Dashboard Charts
        function createDashboardCharts(analytics) {
            console.log('Creating dashboard charts with data:', analytics);
            
            // User Distribution Chart
            createDashboardUserDistributionChart(analytics.users.by_role);
            
            // Location Chart
            createDashboardLocationChart(analytics.geographic.users_by_location);
            
            // Growth Chart
            createDashboardGrowthChart(analytics.growth);
        }
        
        // Create Dashboard User Distribution Chart
        function createDashboardUserDistributionChart(userData) {
            console.log('Creating dashboard user distribution chart with data:', userData);
            const ctx = document.getElementById('dashboardUserDistributionChart');
            if (!ctx) {
                console.error('Dashboard user distribution chart canvas not found');
                return;
            }
            
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const labels = Object.keys(userData);
            const data = Object.values(userData);
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#2196F3', '#9C27B0'], // Match analytics section colors
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Update trend text
            const totalUsers = data.reduce((sum, count) => sum + count, 0);
            const bhOwners = userData['BH Owner'] || 0;
            const boarders = userData['Boarder'] || 0;
            const trendElement = document.getElementById('user-distribution-trend');
            if (trendElement) {
                trendElement.textContent = `${bhOwners} Owners, ${boarders} Boarders`;
            }
        }
        
        // Create Dashboard Location Chart
        function createDashboardLocationChart(locationData) {
            console.log('Creating dashboard location chart with data:', locationData);
            const ctx = document.getElementById('dashboardLocationChart');
            if (!ctx) {
                console.error('Dashboard location chart canvas not found');
                return;
            }
            
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const labels = locationData.map(item => item.location);
            const data = locationData.map(item => parseInt(item.user_count));
            
            // Create a distinct color palette for locations (one should be purple)
            const locationColorPalette = [
                '#2196F3', // Blue
                '#9C27B0', // Purple
                '#E91E63', // Pink
                '#FF5722', // Deep Orange
                '#FF9800', // Orange
                '#4CAF50', // Green
                '#00BCD4', // Cyan
                '#3F51B5', // Indigo
                '#F44336', // Red
                '#009688'  // Teal
            ];
            
            // Assign colors to each location (cycle through palette if more locations than colors)
            const backgroundColors = labels.map((label, index) => {
                return locationColorPalette[index % locationColorPalette.length];
            });
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: backgroundColors,
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Update trend text
            const totalUsers = data.reduce((sum, count) => sum + count, 0);
            const topLocation = locationData[0]?.location || 'Unknown';
            const trendElement = document.getElementById('location-trend');
            if (trendElement) {
                trendElement.textContent = `Top: ${topLocation}`;
            }
        }
        
        // Create Dashboard Growth Chart
        function createDashboardGrowthChart(growthData) {
            console.log('Creating dashboard growth chart with data:', growthData);
            const ctx = document.getElementById('dashboardGrowthChart');
            if (!ctx) {
                console.error('Dashboard growth chart canvas not found');
                return;
            }
            
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: growthData.map(item => item.month),
                    datasets: [
                        {
                            label: 'Users',
                            data: growthData.map(item => item.users),
                            borderColor: '#2196F3', // Match analytics section colors
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#2196F3',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Boarding Houses',
                            data: growthData.map(item => item.boarding_houses),
                            borderColor: '#FF9800', // Match analytics section colors
                            backgroundColor: 'rgba(255, 152, 0, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#FF9800',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Update trend text
            const latestMonth = growthData[growthData.length - 1];
            const trendElement = document.getElementById('growth-trend');
            if (trendElement) {
                trendElement.textContent = `Latest: ${latestMonth?.users || 0} users, ${latestMonth?.boarding_houses || 0} properties`;
            }
        }
        
        // Update Analytics UI
        function updateAnalyticsUI(analytics) {
            // Update overview cards
            document.getElementById('total-users').textContent = analytics.users.total_users;
            document.getElementById('new-users-month').textContent = `+${analytics.users.new_users_this_month} this month`;
            
            document.getElementById('total-boarding-houses').textContent = analytics.boarding_houses.total_boarding_houses;
            document.getElementById('new-bh-month').textContent = `+${analytics.boarding_houses.new_boarding_houses_this_month} this month`;
            
            document.getElementById('total-room-units').textContent = analytics.rooms.total_room_units;
            document.getElementById('occupancy-rate').textContent = `${analytics.rooms.occupancy_rate}% occupancy`;
            
            document.getElementById('total-bookings').textContent = analytics.bookings.total_bookings;
            document.getElementById('new-bookings-month').textContent = `+${analytics.bookings.new_bookings_this_month} this month`;
            
            document.getElementById('total-revenue').textContent = `₱${parseFloat(analytics.payments.total_revenue).toLocaleString()}`;
            document.getElementById('monthly-revenue').textContent = `₱${parseFloat(analytics.payments.monthly_revenue).toLocaleString()} this month`;
            
            document.getElementById('total-messages').textContent = analytics.messages.total_messages;
            document.getElementById('monthly-messages').textContent = `${analytics.messages.monthly_messages} this month`;
            
            // Update top performing boarding houses
            updateTopPerformingBoardingHouses(analytics.top_boarding_houses);
            
            // Update top earning boarding houses
            updateTopEarningBoardingHouses(analytics.top_earning_boarding_houses || []);
        }
        
        // Update Top Performing Boarding Houses
        function updateTopPerformingBoardingHouses(boardingHouses) {
            const container = document.getElementById('top-boarding-houses');
            if (!container) return;
            
            if (boardingHouses.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem; font-size: 0.72rem;">No boarding houses data available</p>';
                return;
            }
            
            const html = boardingHouses.map((bh, index) => `
                <div class="top-performing-item">
                    <div class="item-info">
                        <h5>${index + 1}. ${bh.bh_name}</h5>
                        <p>${bh.bh_address}</p>
                    </div>
                    <div class="item-stats">
                        <div class="stat-value">${bh.occupancy_rate}%</div>
                        <div class="stat-label">Occupancy Rate</div>
                        <div style="font-size: 0.64rem; color: #666; margin-top: 0.2rem;">
                            ${bh.occupied_units}/${bh.total_units} units
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = html;
        }
        
        // Update Top Earning Boarding Houses
        function updateTopEarningBoardingHouses(boardingHouses) {
            const container = document.getElementById('top-earning-boarding-houses');
            if (!container) return;
            
            if (boardingHouses.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem; font-size: 0.72rem;">No revenue data available</p>';
                return;
            }
            
            const html = boardingHouses.map((bh, index) => {
                const revenue = parseFloat(bh.total_revenue || 0);
                return `
                <div class="top-performing-item">
                    <div class="item-info">
                        <h5>${index + 1}. ${bh.bh_name}</h5>
                        <p>${bh.bh_address}</p>
                    </div>
                    <div class="item-stats">
                        <div class="stat-value" style="color: #4CAF50;">₱${revenue.toLocaleString()}</div>
                        <div class="stat-label">Total Revenue</div>
                        <div style="font-size: 0.64rem; color: #666; margin-top: 0.2rem;">
                            ${bh.payment_count || 0} payments
                        </div>
                    </div>
                </div>
            `;
            }).join('');
            
            container.innerHTML = html;
        }
        
        // Initialize Analytics Charts
        function initializeAnalyticsCharts(analytics) {
            console.log('Initializing analytics charts with data:', analytics);
            if (!analytics) {
                console.error('No analytics data provided');
                return;
            }
            
            // Growth Chart
            createGrowthChart(analytics.growth);
            
            // User Distribution Chart
            createUserDistributionChart(analytics.users.by_role);
            
            // Booking Status Chart
            createBookingStatusChart(analytics.bookings.by_status);
            
            // Payment Status Chart
            createPaymentStatusChart(analytics.payments.by_status);
            
            // Geographic Charts
            createUserLocationChart(analytics.geographic.users_by_location);
            createBoardingHouseLocationChart(analytics.geographic.boarding_houses_by_location);
        }
        
        // Create Growth Chart
        function createGrowthChart(growthData) {
            console.log('Creating growth chart with data:', growthData);
            const ctx = document.getElementById('growthChart');
            if (!ctx) {
                console.error('Growth chart canvas not found');
                return;
            }
            
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            console.log('Chart.js is loaded, creating chart...');
            try {
                analyticsCharts.growthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: growthData.map(item => item.month),
                    datasets: [
                        {
                            label: 'Users',
                            data: growthData.map(item => item.users),
                            borderColor: '#2196F3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#2196F3',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Boarding Houses',
                            data: growthData.map(item => item.boarding_houses),
                            borderColor: '#FF9800',
                            backgroundColor: 'rgba(255, 152, 0, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#FF9800',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Revenue (₱)',
                            data: growthData.map(item => item.revenue),
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#4CAF50',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Count'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Revenue (₱)'
                            }
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error creating growth chart:', error);
            }
        }
        
        // Create User Distribution Chart
        function createUserDistributionChart(userData) {
            const ctx = document.getElementById('userDistributionChart');
            if (!ctx) return;
            
            try {
                analyticsCharts.userDistributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(userData),
                    datasets: [{
                        data: Object.values(userData),
                        backgroundColor: ['#2196F3', '#9C27B0', '#607D8B'],
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error creating user distribution chart:', error);
            }
        }
        
        // Create Booking Status Chart
        function createBookingStatusChart(bookingData) {
            const ctx = document.getElementById('bookingStatusChart');
            if (!ctx) return;
            
            const labels = Object.keys(bookingData);
            const statusColors = {
                'Pending': '#FFC107',
                'Confirmed': '#2196F3',
                'Completed': '#4CAF50',
                'Cancelled': '#F44336',
                'Active': '#2196F3',
                'Inactive': '#9E9E9E'
            };
            
            const backgroundColors = labels.map(label => statusColors[label] || '#8D6E63');
            const borderColors = labels.map(label => statusColors[label] || '#8D6E63');
            
            try {
                analyticsCharts.bookingStatusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bookings',
                        data: Object.values(bookingData),
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error creating booking status chart:', error);
            }
        }
        
        // Create Payment Status Chart
        function createPaymentStatusChart(paymentData) {
            const ctx = document.getElementById('paymentStatusChart');
            if (!ctx) return;
            
            const labels = Object.keys(paymentData);
            const statusColors = {
                'Paid': '#4CAF50',
                'Fully Paid': '#4CAF50',
                'Pending': '#FFC107',
                'Overdue': '#F44336',
                'Completed': '#4CAF50',
                'Unpaid': '#FF9800'
            };
            
            const backgroundColors = labels.map(label => statusColors[label] || '#2196F3');
            const borderColors = labels.map(label => statusColors[label] || '#2196F3');
            
            try {
                analyticsCharts.paymentStatusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Payments',
                        data: Object.values(paymentData),
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error creating payment status chart:', error);
            }
        }
        
        // Create User Location Chart
        function createUserLocationChart(locationData) {
            console.log('Creating user location chart with data:', locationData);
            const ctx = document.getElementById('userLocationChart');
            if (!ctx) {
                console.error('User location chart canvas not found');
                return;
            }
            
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const labels = locationData.map(item => item.location);
            const data = locationData.map(item => parseInt(item.user_count));
            
            // Create a distinct color palette for locations
            const locationColorPalette = [
                '#2196F3', // Blue
                '#9C27B0', // Purple
                '#E91E63', // Pink
                '#FF5722', // Deep Orange
                '#FF9800', // Orange
                '#FFC107', // Amber
                '#4CAF50', // Green
                '#00BCD4', // Cyan
                '#3F51B5', // Indigo
                '#F44336', // Red
                '#009688', // Teal
                '#795548', // Brown
                '#607D8B', // Blue Grey
                '#9E9E9E', // Grey
                '#673AB7', // Deep Purple
                '#FF6F00'  // Orange 800
            ];
            
            // Assign colors to each location (cycle through palette if more locations than colors)
            const backgroundColors = labels.map((label, index) => {
                return locationColorPalette[index % locationColorPalette.length];
            });
            
            try {
                analyticsCharts.userLocationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderColor: '#fff',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Location'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error creating user location chart:', error);
            }
        }
        
        // Create Boarding House Location Chart
        function createBoardingHouseLocationChart(locationData) {
            console.log('Creating boarding house location chart with data:', locationData);
            const ctx = document.getElementById('bhLocationChart');
            if (!ctx) {
                console.error('Boarding house location chart canvas not found');
                return;
            }
            
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const labels = locationData.map(item => item.location);
            const data = locationData.map(item => parseInt(item.boarding_house_count));
            
            // Create a distinct color palette for locations (one should be purple)
            const locationColorPalette = [
                '#2196F3', // Blue
                '#9C27B0', // Purple
                '#E91E63', // Pink
                '#FF5722', // Deep Orange
                '#FF9800', // Orange
                '#4CAF50', // Green
                '#00BCD4', // Cyan
                '#3F51B5', // Indigo
                '#F44336', // Red
                '#009688'  // Teal
            ];
            
            // Assign colors to each location (cycle through palette if more locations than colors)
            const backgroundColors = labels.map((label, index) => {
                return locationColorPalette[index % locationColorPalette.length];
            });
            
            try {
                analyticsCharts.bhLocationChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Boarding Houses',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error creating boarding house location chart:', error);
            }
        }
        
        // Update Pending Approvals UI
        function updatePendingApprovalsUI(registrations) {
            const pendingApprovalsContainer = document.querySelector('.pending-approvals');
            if (!pendingApprovalsContainer) {
                console.error('Pending approvals container not found');
                return;
            }

            console.log('Updating pending approvals UI with', registrations.length, 'registrations');
            console.log('Container found:', pendingApprovalsContainer);

            if (registrations.length === 0) {
                pendingApprovalsContainer.innerHTML = '<div class="no-data">No pending registrations</div>';
                return;
            }

            let html = '';
            registrations.forEach(registration => {
                const initials = (registration.first_name.charAt(0) + registration.last_name.charAt(0)).toUpperCase();
                const roleText = registration.role === 'BH Owner' ? 'Owner Registration' : 'Boarder Registration';
                
                // Safely parse registration date
                let registrationDate = 'N/A';
                if (registration.created_at) {
                    try {
                        const date = new Date(registration.created_at);
                        if (!isNaN(date.getTime())) {
                            registrationDate = date.toISOString().split('T')[0];
                        }
                    } catch (e) {
                        console.warn('Error parsing registration date:', e);
                    }
                }
                
                // Calculate time ago - ensure created_at exists and is valid
                const timeAgo = registration.created_at ? getTimeAgo(registration.created_at) : 'Recently';
                
                // Debug log to help identify issues
                if (!registration.created_at) {
                    console.warn('Registration missing created_at:', registration.id, registration);
                }
                
                const roleParts = registration.role === 'BH Owner' ? { type: 'Owner', status: 'Registration' } : { type: 'Boarder', status: 'Registration' };
                
                html += `
                    <div class="approval-card" data-registration-id="${registration.id}">
                        <div class="approval-header">
                            <div class="approval-user">
                                <div class="user-avatar">${initials}</div>
                                <div class="approval-user-info">
                                    <strong>${registration.full_name}</strong>
                                    <span class="approval-role"><span class="role-type">${roleParts.type}</span> <span class="role-status">${roleParts.status}</span></span>
                                </div>
                            </div>
                            <div class="approval-actions">
                                <button class="action-btn" onclick="viewDocuments(${registration.id})">
                                    <i class="fas fa-id-card"></i> View ID
                                </button>
                            </div>
                        </div>
                        <div class="approval-details">
                            <strong>Email:</strong> ${registration.email}<br>
                            <strong>Phone:</strong> ${registration.phone}<br>
                            <strong>ID Type:</strong> ${registration.valid_id_type}<br>
                            <strong>ID Number:</strong> ${registration.id_number}
                        </div>
                        <div class="verification-badge verification-pending">
                            <i class="fas fa-clock"></i> Pending Approval
                        </div>
                        <div class="registration-date">Registered: ${timeAgo}</div>
                    </div>
                `;
            });

            console.log('Generated HTML:', html);
            pendingApprovalsContainer.innerHTML = html;
            console.log('HTML updated successfully');
        }

        // Update User Management Pending Registrations
        function updateUserManagementPendingRegistrations(registrations) {
            // Update boarders table with pending registrations
            const boardersTable = document.querySelector('#boarders-tab tbody');
            if (boardersTable) {
                const pendingBoarders = registrations.filter(reg => reg.role === 'Boarder');
                let pendingRows = '';
                
                pendingBoarders.forEach(registration => {
                    const initials = (registration.first_name.charAt(0) + registration.last_name.charAt(0)).toUpperCase();
                    const registrationDate = new Date(registration.created_at).toISOString().split('T')[0];
                    
                    pendingRows += `
                        <tr data-registration-id="${registration.id}">
                            <td>
                                <div class="user-info-cell">
                                    <div class="user-avatar-small">${initials}</div>
                                    <div>
                                        <strong>${registration.full_name}</strong><br>
                                        <small>${registration.phone}</small>
                                    </div>
                                </div>
                            </td>
                            <td>${registration.email}</td>
                            <td><span class="status-badge-table status-pending">Pending Approval</span></td>
                            <td>${registrationDate}</td>
                            <td>
                                <div class="action-buttons-container">
                                    <button class="action-btn" onclick="viewDocuments(${registration.id})">
                                        <i class="fas fa-id-card"></i> View ID
                                    </button>
                                    <button class="action-btn success" onclick="approveUser(${registration.id})">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn danger" onclick="rejectUser(${registration.id})">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                // Add pending registrations to the table
                boardersTable.innerHTML += pendingRows;
            }

            // Update owners table with pending registrations
            const ownersTable = document.querySelector('#owners-tab tbody');
            if (ownersTable) {
                const pendingOwners = registrations.filter(reg => reg.role === 'BH Owner');
                let pendingRows = '';
                
                pendingOwners.forEach(registration => {
                    const initials = (registration.first_name.charAt(0) + registration.last_name.charAt(0)).toUpperCase();
                    const registrationDate = new Date(registration.created_at).toISOString().split('T')[0];
                    
                    pendingRows += `
                        <tr data-registration-id="${registration.id}">
                            <td>
                                <div class="user-info-cell">
                                    <div class="user-avatar-small">${initials}</div>
                                    <div>
                                        <strong>${registration.full_name}</strong><br>
                                        <small>${registration.phone}</small>
                                    </div>
                                </div>
                            </td>
                            <td>${registration.email}</td>
                            <td>0 properties</td>
                            <td><span class="status-badge-table status-pending">Pending Approval</span></td>
                            <td>${registrationDate}</td>
                            <td>
                                <div class="action-buttons-container">
                                    <button class="action-btn" onclick="viewDocuments(${registration.id})">
                                        <i class="fas fa-id-card"></i> View ID
                                    </button>
                                    <button class="action-btn success" onclick="approveUser(${registration.id})">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn danger" onclick="rejectUser(${registration.id})">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                // Add pending registrations to the table
                ownersTable.innerHTML += pendingRows;
            }
        }

        // Helper function to get time ago
        function getTimeAgo(dateString) {
            // Validate input
            if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === 'null' || dateString === '') {
                console.warn('Invalid dateString for getTimeAgo:', dateString);
                return 'Recently';
            }
            
            try {
            const now = new Date();
                let date = new Date(dateString);
                
                // Check if date is valid
                if (isNaN(date.getTime())) {
                    console.warn('Invalid date parsed from:', dateString);
                    // Try to parse as MySQL datetime format
                    const mysqlDate = dateString.replace(' ', 'T');
                    const parsedDate = new Date(mysqlDate);
                    if (isNaN(parsedDate.getTime())) {
                        return 'Recently';
                    }
                    date = parsedDate;
                }
                
                const diffInMs = now - date;
                const diffInSeconds = Math.floor(diffInMs / 1000);
                
                // Handle negative difference (future date)
                if (diffInSeconds < 0) {
                    return 'Just now';
                }
                
                if (diffInSeconds < 60) {
                    return 'Just now';
                }
                
                const diffInMinutes = Math.floor(diffInSeconds / 60);
                if (diffInMinutes < 60) {
                    return diffInMinutes === 1 ? '1 minute ago' : `${diffInMinutes} minutes ago`;
                }
                
                const diffInHours = Math.floor(diffInSeconds / 3600);
                if (diffInHours < 24) {
                    return diffInHours === 1 ? '1 hour ago' : `${diffInHours} hours ago`;
                }
                
                const diffInDays = Math.floor(diffInSeconds / 86400);
                if (diffInDays < 30) {
                    return diffInDays === 1 ? '1 day ago' : `${diffInDays} days ago`;
                }
                
                const diffInMonths = Math.floor(diffInDays / 30);
                if (diffInMonths < 12) {
                    return diffInMonths === 1 ? '1 month ago' : `${diffInMonths} months ago`;
                }
                
                const diffInYears = Math.floor(diffInDays / 365);
                return diffInYears === 1 ? '1 year ago' : `${diffInYears} years ago`;
                
            } catch (error) {
                console.error('Error calculating time ago:', error, 'Date string:', dateString);
                // Fallback: try to format the date
                try {
                    const date = new Date(dateString);
                    if (!isNaN(date.getTime())) {
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    }
                } catch (e) {
                    // If all else fails, return a generic message
                    return 'Recently';
                }
                return 'Recently';
            }
        }

        // Update User Management Tables
        function updateUserManagementTables(userData) {
            // Update boarders table
            const boardersTable = document.querySelector('#boarders-tab .table-body');
            if (boardersTable) {
                const boarders = userData.users.filter(user => user.role === 'Boarder');
                boardersTable.innerHTML = boarders.map(user => `
                    <tr>
                        <td>
                            <div class="user-info">
                                <img src="${user.profile_picture || 'https://via.placeholder.com/40'}" alt="${user.full_name}">
                                <div>
                                    <div class="user-name">${user.full_name}</div>
                                    <div class="user-email">${user.email}</div>
                                </div>
                            </div>
                        </td>
                        <td>${user.phone_number || 'N/A'}</td>
                        <td><span class="status-badge ${user.status.toLowerCase()}">${user.status}</span></td>
                        <td>${user.activity_count}</td>
                        <td>${new Date(user.created_at).toISOString().split('T')[0]}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn view" onclick="viewBoarderDetails(${user.user_id})">View</button>
                                <button class="action-btn ${user.status === 'Active' ? 'suspend' : 'approve'}" 
                                        onclick="${user.status === 'Active' ? 'suspendBoarder' : 'approveBoarder'}(${user.user_id})">
                                    ${user.status === 'Active' ? 'Suspend' : 'Approve'}
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            // Update owners table
            const ownersTable = document.querySelector('#owners-tab .table-body');
            if (ownersTable) {
                const owners = userData.users.filter(user => user.role === 'Owner');
                ownersTable.innerHTML = owners.map(user => `
                    <tr>
                        <td>
                            <div class="user-info">
                                <img src="${user.profile_picture || 'https://via.placeholder.com/40'}" alt="${user.full_name}">
                                <div>
                                    <div class="user-name">${user.full_name}</div>
                                    <div class="user-email">${user.email}</div>
                                </div>
                            </div>
                        </td>
                        <td>${user.phone_number || 'N/A'}</td>
                        <td><span class="status-badge ${user.status.toLowerCase()}">${user.status}</span></td>
                        <td>${user.activity_count}</td>
                        <td>${new Date(user.created_at).toISOString().split('T')[0]}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn view" onclick="viewOwnerDetails(${user.user_id})">View</button>
                                <button class="action-btn verify" onclick="viewDocuments('${user.user_id}')">Verify</button>
                                <button class="action-btn ${user.status === 'Active' ? 'suspend' : 'approve'}" 
                                        onclick="${user.status === 'Active' ? 'suspendOwner' : 'approveOwner'}(${user.user_id})">
                                    ${user.status === 'Active' ? 'Suspend' : 'Approve'}
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }
        
        // Update Boarding Houses Tables
        function updateBoardingHousesTables(boardingHousesData) {
            // Update the count
            const countElement = document.getElementById('boarding-houses-count');
            if (countElement) {
                countElement.textContent = `Total: ${boardingHousesData.boarding_houses.length} boarding houses`;
            }

            // Update the main table
            const boardingHousesTable = document.getElementById('boarding-houses-table-body');
            if (boardingHousesTable) {
                if (boardingHousesData.boarding_houses.length === 0) {
                    boardingHousesTable.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                <div class="no-data">
                                    <i class="fas fa-home"></i>
                                    <p>No boarding houses found</p>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    boardingHousesTable.innerHTML = boardingHousesData.boarding_houses.map(house => {
                        const initials = house.bh_name.substring(0, 2).toUpperCase();
                        const statusClass = house.status === 'Active' ? 'status-active' : 'status-inactive';
                        const registrationDate = new Date(house.bh_created_at).toISOString().split('T')[0];
                        
                        return `
                    <tr>
                        <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-small" style="background: #8D6E63; color: white;">${initials}</div>
                                        <div>
                                            <strong>${house.bh_name}</strong>
                                        </div>
                            </div>
                        </td>
                                <td>
                                    <div>
                                        <strong>${house.owner_name}</strong><br>
                                        <small>${house.owner_email}</small>
                                    </div>
                                </td>
                                <td>${house.bh_address}</td>
                                <td>${house.total_rooms} rooms</td>
                                <td><span class="status-badge-table ${statusClass}">${house.status}</span></td>
                                <td>${registrationDate}</td>
                                <td>
                                    <div class="action-buttons-container">
                                        <button class="action-btn" onclick="viewBoardingHouseDetails(${house.bh_id})">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="action-btn ${house.status === 'Active' ? 'danger' : 'success'}" 
                                                onclick="${house.status === 'Active' ? 'deactivateBoardingHouse' : 'activateBoardingHouse'}(${house.bh_id})">
                                            <i class="fas fa-${house.status === 'Active' ? 'ban' : 'check'}"></i> 
                                            ${house.status === 'Active' ? 'Deactivate' : 'Activate'}
                                </button>
                            </div>
                        </td>
                    </tr>
                        `;
                    }).join('');
                }
            }

            // Update the by-owner tab
            updateBoardingHousesByOwner(boardingHousesData);
        }

        // Update Boarding Houses by Owner
        function updateBoardingHousesByOwner(boardingHousesData) {
            const ownersContainer = document.getElementById('owners-boarding-houses');
            if (!ownersContainer) return;

            // Group boarding houses by owner
            const ownersMap = new Map();
            boardingHousesData.boarding_houses.forEach(house => {
                if (!ownersMap.has(house.owner_id)) {
                    ownersMap.set(house.owner_id, {
                        owner_id: house.owner_id,
                        owner_name: house.owner_name,
                        owner_email: house.owner_email,
                        owner_profile_picture: house.owner_profile_picture,
                        boarding_houses: []
                    });
                }
                ownersMap.get(house.owner_id).boarding_houses.push(house);
            });

            if (ownersMap.size === 0) {
                ownersContainer.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <div class="no-data">
                            <i class="fas fa-home"></i>
                            <p>No boarding houses found</p>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            ownersMap.forEach(owner => {
                const ownerInitials = owner.owner_name.split(' ').map(n => n[0]).join('').toUpperCase();
                const propertyCount = owner.boarding_houses.length;
                
                html += `
                    <div class="owner-section">
                        <div class="owner-header">
                            <div class="user-info-cell">
                                <div class="user-avatar-small">${ownerInitials}</div>
                                <div>
                                    <strong>${owner.owner_name}</strong><br>
                                    <small>${owner.owner_email}</small>
                                </div>
                            </div>
                            <span class="property-count">${propertyCount} ${propertyCount === 1 ? 'Property' : 'Properties'}</span>
                        </div>
                        <div class="boarding-houses-list">
                `;

                owner.boarding_houses.forEach(house => {
                    const statusClass = house.status === 'Active' ? 'status-active' : 'status-inactive';
                    const activateButtonText = house.status === 'Active' ? 'Deactivate' : 'Activate';
                    const activateButtonIcon = house.status === 'Active' ? 'ban' : 'check';
                    const activateButtonClass = house.status === 'Active' ? 'danger' : 'success';
                    const activateFunction = house.status === 'Active' ? 'deactivateBoardingHouse' : 'activateBoardingHouse';
                    html += `
                        <div class="boarding-house-item">
                            <div class="house-info">
                                <strong>${house.bh_name}</strong>
                                <p>${house.bh_address} • ${house.total_rooms} rooms</p>
                            </div>
                            <div class="house-status">
                                <span class="status-badge-table ${statusClass}">${house.status}</span>
                                <div class="action-buttons-container">
                                    <button class="action-btn" onclick="viewBoardingHouseDetails(${house.bh_id})">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn ${activateButtonClass}" onclick="${activateFunction}(${house.bh_id})">
                                        <i class="fas fa-${activateButtonIcon}"></i> ${activateButtonText}
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            });

            ownersContainer.innerHTML = html;
        }
        
        // Update Disputes Table
        function updateDisputesTable(disputesData) {
            const disputesTable = document.querySelector('#disputes-tab .table-body');
            if (disputesTable) {
                disputesTable.innerHTML = disputesData.disputes.map(dispute => `
                    <tr>
                        <td>
                            <div class="dispute-info">
                                <div class="dispute-title">${dispute.dispute_type}</div>
                                <div class="dispute-description">${dispute.dispute_description}</div>
                            </div>
                        </td>
                        <td>
                            <div class="user-info">
                                <div class="user-name">${dispute.complainant_name}</div>
                                <div class="user-email">${dispute.complainant_email}</div>
                            </div>
                        </td>
                        <td>
                            <div class="user-info">
                                <div class="user-name">${dispute.respondent_name}</div>
                                <div class="user-email">${dispute.respondent_email}</div>
                            </div>
                        </td>
                        <td>${dispute.property_name}</td>
                        <td><span class="status-badge ${dispute.dispute_status.toLowerCase()}">${dispute.dispute_status}</span></td>
                        <td>${new Date(dispute.dispute_date).toISOString().split('T')[0]}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn view" onclick="viewDisputeDetails(${dispute.dispute_id})">View</button>
                                <button class="action-btn assign" onclick="assignDispute(${dispute.dispute_id})">Assign</button>
                                <button class="action-btn resolve" onclick="resolveDispute(${dispute.dispute_id})">Resolve</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }
        
        // Update Flagged Users Table
        function updateFlaggedUsersTable(flaggedData) {
            const flaggedTable = document.querySelector('#flagged-tab .table-body');
            if (flaggedTable) {
                flaggedTable.innerHTML = flaggedData.flagged_users.map(user => `
                    <tr>
                        <td>
                            <div class="user-info">
                                <img src="${user.profile_picture || 'https://via.placeholder.com/40'}" alt="${user.full_name}">
                                <div>
                                    <div class="user-name">${user.full_name}</div>
                                    <div class="user-email">${user.email}</div>
                                </div>
                            </div>
                        </td>
                        <td>${user.role}</td>
                        <td><span class="status-badge ${user.status.toLowerCase()}">${user.status}</span></td>
                        <td>
                            <div class="flag-reason">
                                <div class="flag-title">${user.flag_reason}</div>
                                <div class="flag-description">${user.flag_description}</div>
                            </div>
                        </td>
                        <td>
                            <div class="flag-stats">
                                <div>Cancelled: ${user.cancelled_bookings}</div>
                                <div>Pending: ${user.pending_bookings}</div>
                                <div>Inactive: ${user.inactive_properties}</div>
                            </div>
                        </td>
                        <td>${new Date(user.created_at).toISOString().split('T')[0]}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn view" onclick="viewFlaggedDetails(${user.user_id})">View</button>
                                <button class="action-btn unflag" onclick="unflagUser(${user.user_id})">Unflag</button>
                                <button class="action-btn ${user.status === 'Active' ? 'suspend' : 'unsuspend'}" 
                                        onclick="${user.status === 'Active' ? 'suspendUser' : 'unsuspendUser'}(${user.user_id})">
                                    ${user.status === 'Active' ? 'Suspend' : 'Unsuspend'}
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }
        
        // Load System Notifications
        async function loadSystemNotifications() {
            const loadingElement = document.getElementById('system-notifications-loading');
            const containerElement = document.getElementById('system-notifications-container');
            
            // Always show loading indicator when starting to load
            if (loadingElement) {
                loadingElement.style.display = 'flex';
                loadingElement.style.flexDirection = 'column';
                loadingElement.style.alignItems = 'center';
                loadingElement.style.justifyContent = 'center';
                loadingElement.style.minHeight = '200px';
            }
            if (containerElement) {
                containerElement.style.display = 'none';
            }
            
            try {
                console.log('Loading system notifications...');
                const response = await fetch('../get_admin_notifications.php?action=system&limit=1000');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Add a small delay to show loading indicator (minimum 500ms for better UX)
                const startTime = Date.now();
                const minLoadTime = 500;
                
                if (data.success && data.data.system_notifications) {
                    const elapsed = Date.now() - startTime;
                    const remainingTime = Math.max(0, minLoadTime - elapsed);
                    
                    await new Promise(resolve => setTimeout(resolve, remainingTime));
                    
                    displaySystemNotifications(data.data.system_notifications);
                } else {
                    console.error('Error loading system notifications:', data.error || data.message);
                    if (loadingElement) loadingElement.style.display = 'none';
                    if (containerElement) {
                        containerElement.style.display = 'block';
                        containerElement.innerHTML = 
                            '<p style="color: rgba(255,255,255,0.7); padding: 2rem; text-align: center;">No system notifications found.</p>';
                    }
                }
            } catch (error) {
                console.error('Error loading system notifications:', error);
                if (loadingElement) loadingElement.style.display = 'none';
                if (containerElement) {
                    containerElement.style.display = 'block';
                    containerElement.innerHTML = 
                        '<p style="color: rgba(255,255,255,0.7); padding: 2rem; text-align: center;">Error loading system notifications. Please refresh the page.</p>';
                }
            }
        }
        
        // Load system notifications on page load if notifications section is visible
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for the page to fully render
            setTimeout(function() {
                // Check if we're on the notifications section
                const notificationsSection = document.getElementById('notifications-section');
                if (notificationsSection && notificationsSection.classList.contains('active')) {
                    const systemTab = document.getElementById('system-tab');
                    if (systemTab && systemTab.classList.contains('active')) {
                        loadSystemNotifications();
                    }
                }
            }, 500);
        });
        
        // Display System Notifications
        function displaySystemNotifications(notifications) {
            const loadingElement = document.getElementById('system-notifications-loading');
            const containerElement = document.getElementById('system-notifications-container');
            
            if (!containerElement) {
                console.error('System notifications container not found');
                return;
            }
            
            // Hide loading indicator with fade out effect
            if (loadingElement) {
                loadingElement.style.transition = 'opacity 0.3s ease-out';
                loadingElement.style.opacity = '0';
                setTimeout(() => {
                    loadingElement.style.display = 'none';
                    loadingElement.style.opacity = '1'; // Reset for next time
                }, 300);
            }
            
            // Show container with fade in effect
            containerElement.style.display = 'block';
            containerElement.style.opacity = '0';
            containerElement.style.transition = 'opacity 0.3s ease-in';
            setTimeout(() => {
                containerElement.style.opacity = '1';
            }, 50);
            
            if (!notifications || notifications.length === 0) {
                containerElement.innerHTML = 
                    '<p style="color: rgba(255,255,255,0.7); padding: 2rem; text-align: center;">No system notifications at this time.</p>';
                return;
            }
            
            // Display total count
            const totalCount = notifications.length;
            console.log(`Displaying ${totalCount} system notifications`);
            
            // Create container with minimal spacing - remove all padding/margin
            containerElement.style.padding = '0';
            containerElement.style.margin = '0';
            
            // Build HTML without header to remove gap - just show notifications directly
            let html = notifications.map(notif => {
                // Use getTimeAgo function for consistent time display
                // Prefer time_ago from PHP, but fallback to calculating from event_time
                let timeDisplay = 'Recently';
                if (notif.time_ago) {
                    timeDisplay = notif.time_ago;
                } else if (notif.event_time) {
                    timeDisplay = getTimeAgo(notif.event_time);
                }
                
                return `
                <div class="notification-item">
                    <div class="notification-icon" style="background: ${notif.icon_color || '#007bff'};">
                        <i class="fas fa-${notif.icon_name || 'bell'}"></i>
                    </div>
                    <div class="notification-content">
                        <h4>${notif.title || 'System Notification'}</h4>
                        <p>${notif.message || ''}</p>
                        ${notif.user_name ? `<div style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-top: 0.5rem;">
                            ${notif.user_name}${notif.email ? ' (' + notif.email + ')' : ''}${notif.role ? ' - ' + notif.role : ''}
                        </div>` : ''}
                        <div class="notification-time">${timeDisplay}</div>
                    </div>
                </div>
                `;
            }).join('');
            
            containerElement.innerHTML = html;
        }
        
        // Update Notifications Table
        function updateNotificationsTable(notificationsData) {
            // Update system notifications table
            const systemTable = document.querySelector('#system-tab .notification-list');
            if (systemTable && notificationsData.notifications) {
                systemTable.innerHTML = notificationsData.notifications.map(notification => `
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-${getNotificationIcon(notification.notif_type)}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">${notification.notif_title}</div>
                            <div class="notification-message">${notification.notif_message}</div>
                            <div class="notification-meta">
                                <span class="notification-type">${notification.notif_type}</span>
                                <span class="notification-user">${notification.user_name} (${notification.user_role})</span>
                                <span class="notification-time">${new Date(notification.notif_created_at).toLocaleString()}</span>
                            </div>
                        </div>
                        <div class="notification-status">
                            <span class="status-badge ${notification.notif_status}">${notification.notif_status}</span>
                        </div>
                    </div>
                `).join('');
            }
            
            // Update notification statistics
            if (notificationsData.statistics) {
                updateNotificationStats(notificationsData.statistics);
            }
        }
        
        // Get notification icon based on type
        function getNotificationIcon(type) {
            const icons = {
                'booking': 'calendar-check',
                'payment': 'credit-card',
                'announcement': 'bullhorn',
                'maintenance': 'tools',
                'general': 'bell'
            };
            return icons[type] || 'bell';
        }
        
        // Update notification statistics
        function updateNotificationStats(stats) {
            // Update unread count
            const unreadElement = document.querySelector('#unread-notifications-count');
            if (unreadElement) {
                unreadElement.textContent = stats.unread_count || 0;
            }
            
            // Update recent notifications count
            const recentElement = document.querySelector('#recent-notifications-count');
            if (recentElement) {
                recentElement.textContent = stats.recent_notifications || 0;
            }
        }
        
        // Send Notification Function
        async function sendNotification(notificationData) {
            try {
                // Show loading indicator
                const submitButton = document.querySelector('#notificationForm button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                
                const response = await fetch('../get_admin_notifications.php?action=send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(notificationData)
                });
                
                const data = await response.json();
                
                // Restore button
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (data.success) {
                    const message = `Notification sent successfully to ${data.data.sent_count} users!${data.data.failed_count > 0 ? ' (' + data.data.failed_count + ' failed)' : ''}`;
                    alert(message);
                    console.log('Notification sent:', data);
                    
                    // Clear form and refresh notifications list
                    document.getElementById('notificationForm').reset();
                    
                    // Reload system notifications to show the activity
                    loadSystemNotifications();
                } else {
                    alert('Error sending notification: ' + (data.message || data.error || 'Unknown error'));
                    console.error('Notification error:', data);
                }
            } catch (error) {
                console.error('Error sending notification:', error);
                alert('Error sending notification. Please check your connection and try again.');
                
                // Restore button in case of error
                const submitButton = document.querySelector('#notificationForm button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send Notification';
                }
            }
        }

        // Boarders Management Functions
        function filterBoarders(filter) {
            console.log('Filtering boarders by:', filter);
            const rows = document.querySelectorAll('#boarders-table-body tr');
            const noDataRow = document.getElementById('boarders-no-data');
            let hasVisibleRows = false;
            
            rows.forEach(row => {
                if (row.id === 'boarders-no-data') return; // Skip no-data row
                
                const status = row.getAttribute('data-status');
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'active':
                        show = status === 'active';
                        break;
                    case 'inactive':
                        show = status === 'inactive';
                        break;
                    case 'pending':
                        show = status === 'pending approval';
                        break;
                }
                
                row.style.display = show ? '' : 'none';
                if (show) hasVisibleRows = true;
            });
            
            // Show/hide no-data message
            if (noDataRow) {
                noDataRow.style.display = hasVisibleRows ? 'none' : '';
            }
            
            // Update active filter button
            document.querySelectorAll('#boarders-tab .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function viewBoarderDetails(boarderId) {
            alert(`Viewing details for boarder: ${boarderId}`);
        }

        function suspendBoarder(boarderId) {
            // Use the same suspend user modal
            suspendUser(boarderId);
        }

        function approveBoarder(boarderId) {
            if (confirm('Are you sure you want to approve this boarder?')) {
                alert(`Boarder ${boarderId} approved successfully!`);
            }
        }

        function rejectBoarder(boarderId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to reject this boarder?')) {
                    alert(`Boarder ${boarderId} rejected successfully!`);
                }
            }
        }

        // Owners Management Functions
        function filterOwners(filter) {
            console.log('Filtering owners by:', filter);
            const rows = document.querySelectorAll('#owners-table-body tr');
            const noDataRow = document.getElementById('owners-no-data');
            let hasVisibleRows = false;
            
            rows.forEach(row => {
                if (row.id === 'owners-no-data') return; // Skip no-data row
                
                const status = row.getAttribute('data-status');
                let show = false;
                
                switch(filter) {
                    case 'all':
                        show = true;
                        break;
                    case 'active':
                        show = status === 'active';
                        break;
                    case 'inactive':
                        show = status === 'inactive';
                        break;
                    case 'pending':
                        show = status === 'pending approval';
                        break;
                }
                
                row.style.display = show ? '' : 'none';
                if (show) hasVisibleRows = true;
            });
            
            // Show/hide no-data message
            if (noDataRow) {
                noDataRow.style.display = hasVisibleRows ? 'none' : '';
            }
            
            // Update active filter button
            document.querySelectorAll('#owners-tab .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function viewOwnerDetails(ownerId) {
            alert(`Viewing details for owner: ${ownerId}`);
        }

        function suspendOwner(ownerId) {
            // Use the same suspend user modal
            suspendUser(ownerId);
        }

        function approveOwner(ownerId) {
            if (confirm('Are you sure you want to approve this owner?')) {
                alert(`Owner ${ownerId} approved successfully!`);
            }
        }

        function rejectOwner(ownerId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to reject this owner?')) {
                    alert(`Owner ${ownerId} rejected successfully!`);
                }
            }
        }

        // Notifications Functions
        function clearNotificationForm() {
            document.getElementById('notificationForm').reset();
        }

        // Handle notification form submission
        const notificationForm = document.getElementById('notificationForm');
        if (notificationForm) {
            notificationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const recipients = this.querySelector('select[name="recipients"]').value;
            const notificationType = this.querySelector('select[name="notification_type"]').value;
                const title = this.querySelector('input[name="title"]').value.trim();
                const message = this.querySelector('textarea[name="message"]').value.trim();
                
                // Validate form
                if (!recipients) {
                    alert('Please select recipients');
                    return;
                }
                if (!title) {
                    alert('Please enter a subject');
                    return;
                }
                if (!message) {
                    alert('Please enter a message');
                    return;
                }
                
                // Format recipient display name
                const recipientDisplay = recipients === 'all' ? 'All Users' : 
                                       recipients === 'boarders' ? 'All Boarders' : 
                                       recipients === 'owners' ? 'All Owners' : 
                                       'Specific Users';
                
                if (confirm(`Send notification to ${recipientDisplay}?\n\nSubject: ${title}\n\nMessage: ${message.substring(0, 100)}${message.length > 100 ? '...' : ''}`)) {
                // Send notification using our API
                const notificationData = {
                    recipients: recipients,
                    notification_type: notificationType,
                    title: title,
                    message: message
                };
                
                    console.log('Sending notification:', notificationData);
                sendNotification(notificationData);
            }
        });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Notification Settings Functions
        function openNotificationSettings() {
            document.getElementById('notificationSettingsModal').style.display = 'block';
            loadNotificationSettings();
            
            // Ensure Save Settings button is disabled initially (will be enabled when switching to templates tab)
            const saveButton = document.getElementById('save-notification-settings-btn');
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.style.opacity = '0.5';
                saveButton.style.cursor = 'not-allowed';
            }
            
            // Switch to the first tab (Current Settings) when opening
            setTimeout(() => {
                switchSettingsTab('current');
            }, 100);
        }

        function closeNotificationSettings() {
            document.getElementById('notificationSettingsModal').style.display = 'none';
            
            // Ensure Save Settings button is disabled when closing (reset state)
            const saveButton = document.getElementById('save-notification-settings-btn');
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.style.opacity = '0.5';
                saveButton.style.cursor = 'not-allowed';
            }
            
            // Switch back to first tab when closing (so it opens on first tab next time)
            setTimeout(() => {
                switchSettingsTab('current');
            }, 50);
        }

        async function loadNotificationSettings() {
            try {
                const response = await fetch('../get_notification_settings.php?action=get_settings');
                const data = await response.json();
                
                if (data.success) {
                    populateNotificationSettings(data.settings);
                } else {
                    console.error('Error loading notification settings:', data.error);
                }
            } catch (error) {
                console.error('Error loading notification settings:', error);
            }
        }

        function populateNotificationSettings(settings) {
            // Display current settings
            displayCurrentSettings(settings);
            
            // Display channels
            displayChannels(settings.notification_channels || {});
            
            // Display notification types
            displayNotificationTypes(settings.notification_types || {});
            
            // Display notification messages (templates)
            displayNotificationMessages(settings);
        }
        
        function displayNotificationMessages(settings) {
            const container = document.getElementById('templates-container');
            if (!container) return;
            
            const templates = settings.templates || {};
            
            // Group templates by type
            const templatesByType = {};
            for (const [key, template] of Object.entries(templates)) {
                const type = template.type || 'general';
                if (!templatesByType[type]) {
                    templatesByType[type] = [];
                }
                templatesByType[type].push({
                    key: key,
                    title: template.title || key,
                    message: template.message || '',
                    type: type
                });
            }
            
            // Default templates structure if database is empty
            const defaultTemplates = {
                'booking': [
                    { key: 'booking_created', title: 'New Booking Request', message: 'You have a new booking request from {boarder_name} for {room_name}', type: 'booking' },
                    { key: 'booking_approved', title: 'Booking Approved', message: 'Your booking request for {room_name} has been approved!', type: 'booking' },
                    { key: 'booking_declined', title: 'Booking Declined', message: 'Your booking request for {room_name} has been declined.{reason}', type: 'booking' },
                    { key: 'booking_cancelled', title: 'Booking Cancelled', message: 'Booking for {room_name} has been cancelled.', type: 'booking' }
                ],
                'payment': [
                    { key: 'payment_received', title: 'Payment Received', message: 'Payment of ₱{amount} has been received{description}', type: 'payment' },
                    { key: 'payment_created', title: 'New Payment Pending', message: 'A new payment of ₱{amount} is pending{description}', type: 'payment' },
                    { key: 'payment_status_updated', title: 'Payment Status Updated', message: 'Your payment of ₱{amount} status has been updated to: {status}', type: 'payment' },
                    { key: 'payment_overdue', title: 'Payment Overdue', message: 'Your payment of ₱{amount} is overdue. Please settle it as soon as possible.', type: 'payment' }
                ],
                'maintenance': [
                    { key: 'maintenance_request', title: 'New Maintenance Request', message: '{boarder_name} has submitted a maintenance request for {room_name}: {title}', type: 'maintenance' },
                    { key: 'maintenance_status_updated', title: 'Maintenance Status Updated', message: 'Maintenance request status updated to: {status}', type: 'maintenance' },
                    { key: 'maintenance_completed', title: 'Maintenance Completed', message: 'Your maintenance request has been completed.', type: 'maintenance' },
                    { key: 'maintenance_feedback', title: 'Maintenance Feedback', message: 'Feedback received for maintenance request.', type: 'maintenance' }
                ],
                'announcement': [
                    { key: 'announcement_new', title: 'New Announcement', message: '{title}: {message}', type: 'announcement' },
                    { key: 'announcement_owner_response', title: 'Owner Response', message: 'Owner responded to your review.', type: 'announcement' }
                ],
                'registration': [
                    { key: 'registration_approved', title: 'Registration Approved', message: 'Your registration has been approved! You can now login to your account.', type: 'registration' },
                    { key: 'registration_rejected', title: 'Registration Rejected', message: 'Your registration has been rejected. Please contact support for more information.', type: 'registration' }
                ],
                'message': [
                    { key: 'message_new', title: 'New Message', message: 'New message from {sender_name}: {message_preview}', type: 'message' },
                    { key: 'message_group', title: 'New Group Message', message: 'New message in {group_name} from {sender_name}', type: 'message' }
                ],
                'security': [
                    { key: 'security_password_changed', title: 'Password Changed', message: 'Your password has been successfully changed.', type: 'security' },
                    { key: 'security_email_changed', title: 'Email Changed', message: 'Your email address has been successfully changed.', type: 'security' }
                ]
            };
            
            let html = '<div style="display: grid; gap: 20px;">';
            
            const typeColors = {
                'booking': '#2196F3',
                'payment': '#4CAF50',
                'maintenance': '#FF9800',
                'announcement': '#9C27B0',
                'registration': '#00BCD4',
                'message': '#009688',
                'security': '#F44336'
            };
            
            const typeIcons = {
                'booking': 'fa-calendar-check',
                'payment': 'fa-credit-card',
                'maintenance': 'fa-tools',
                'announcement': 'fa-bullhorn',
                'registration': 'fa-user-plus',
                'message': 'fa-comments',
                'security': 'fa-shield-alt'
            };
            
            // Use templates from database if available, otherwise use defaults
            const typesToDisplay = Object.keys(templatesByType).length > 0 ? templatesByType : defaultTemplates;
            
            for (const [type, templateList] of Object.entries(typesToDisplay)) {
                const color = typeColors[type] || '#5D4037';
                const icon = typeIcons[type] || 'fa-bell';
                const typeName = type.charAt(0).toUpperCase() + type.slice(1);
                
                html += `
                    <div class="message-group" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px; color: ${color};">
                            <i class="fas ${icon}" style="font-size: 1.5rem;"></i>
                            ${typeName} Notifications
                        </h4>
                        <div style="display: grid; gap: 15px;">
                `;
                
                templateList.forEach((template, index) => {
                    const templateKey = template.key || `${type}_${index}`;
                    const templateTitle = template.title || 'Template';
                    const templateMessage = template.message || '';
                    
                    html += `
                        <div style="padding: 15px; background: #f9f9f9; border-radius: 6px; border-left: 4px solid ${color};">
                            <div style="font-weight: 600; color: #333; margin-bottom: 10px;">
                                <label style="display: block; margin-bottom: 5px;">Title:</label>
                                <input type="text" 
                                       id="template_title_${templateKey}" 
                                       value="${templateTitle.replace(/"/g, '&quot;')}" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;"
                                       placeholder="Notification title">
                            </div>
                            <div style="margin-top: 10px;">
                                <label style="display: block; margin-bottom: 5px; color: #666; font-weight: 500;">Message Template:</label>
                                <textarea 
                                    id="template_message_${templateKey}" 
                                    style="width: 100%; min-height: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9em; font-family: inherit; resize: vertical;"
                                    placeholder="Enter notification message template. Use {variable_name} for dynamic values.">${templateMessage.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
                                <input type="hidden" id="template_key_${templateKey}" value="${templateKey}">
                                <input type="hidden" id="template_type_${templateKey}" value="${type}">
                                <small style="color: #888; font-size: 0.85em; display: block; margin-top: 5px;">
                                    Available variables: {boarder_name}, {room_name}, {amount}, {status}, {description}, {sender_name}, etc.
                                </small>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        function displayCurrentSettings(settings) {
            const container = document.getElementById('current-settings-container');
            if (!container) return;
            
            const systemStatus = settings.system_status || {};
            const channels = settings.notification_channels || {};
            const stats = channels.database?.stats || {};
            
            let html = `
                <div class="current-settings-summary">
                    <div class="setting-card" style="background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle"></i> System Status
                        </h4>
                        <p style="margin: 0; opacity: 0.9;">
                            Activity Notifications: ${systemStatus.activity_notifications_enabled ? '<strong>Enabled</strong>' : '<strong style="color: #ffeb3b;">Disabled</strong>'} | 
                            Notification Helper: ${systemStatus.notification_helper_enabled ? '<strong>Enabled</strong>' : '<strong style="color: #ffeb3b;">Disabled</strong>'} | 
                            Active Channels: <strong>${systemStatus.total_notification_methods || 0}</strong>
                        </p>
                    </div>
                    
                    <div class="settings-grid" style="display: grid; gap: 15px;">
                        <div class="setting-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-database" style="color: #2196F3;"></i> Database Notifications
                            </h4>
                            <p style="margin: 5px 0; color: #666;">
                                Status: <strong style="color: ${channels.database?.enabled ? '#4CAF50' : '#f44336'}">${channels.database?.status || 'Unknown'}</strong>
                            </p>
                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                                ${channels.database?.description || 'Notifications stored in database'}
                            </p>
                            ${stats.total_notifications ? `
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                    <p style="margin: 5px 0; font-size: 0.9em;">
                                        Total Notifications: <strong>${stats.total_notifications}</strong> | 
                                        Unread: <strong>${stats.unread_notifications || 0}</strong>
                                    </p>
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="setting-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-mobile-alt" style="color: #FF9800;"></i> FCM Push Notifications
                            </h4>
                            <p style="margin: 5px 0; color: #666;">
                                Status: <strong style="color: ${channels.fcm_push?.enabled ? '#4CAF50' : '#f44336'}">${channels.fcm_push?.status || 'Not configured'}</strong>
                            </p>
                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                                ${channels.fcm_push?.description || 'Firebase Cloud Messaging push notifications'}
                            </p>
                            ${channels.fcm_push?.service_account ? `
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                    <p style="margin: 5px 0; font-size: 0.9em;">
                                        Project ID: <strong>${channels.fcm_push.project_id || 'N/A'}</strong><br>
                                        Service Account: <strong>${channels.fcm_push.service_account.client_email || 'N/A'}</strong><br>
                                        Device Tokens: <strong>${channels.fcm_push.device_tokens_count || 0}</strong> active devices
                                    </p>
                                </div>
                            ` : ''}
                        </div>
                        
                        <div class="setting-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                            <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-envelope" style="color: #9E9E9E;"></i> Email Notifications
                            </h4>
                            <p style="margin: 5px 0; color: #666;">
                                Status: <strong style="color: #9E9E9E;">${channels.email?.status || 'Not implemented'}</strong>
                            </p>
                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                                ${channels.email?.description || 'Email notifications are not implemented yet'}
                            </p>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        }
        
        function displayChannels(channels) {
            const container = document.getElementById('channels-container');
            if (!container) return;
            
            let html = '<div class="channels-grid" style="display: grid; gap: 15px;">';
            
            // Database Channel
            if (channels.database) {
                const db = channels.database;
                html += `
                    <div class="channel-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                        <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-database" style="color: #2196F3; font-size: 1.5rem;"></i>
                            Database Notifications
                            <span style="margin-left: auto; padding: 4px 12px; background: ${db.enabled ? '#4CAF50' : '#f44336'}; color: white; border-radius: 20px; font-size: 0.8em; font-weight: normal;">
                                ${db.enabled ? 'ACTIVE' : 'INACTIVE'}
                            </span>
                        </h4>
                        <p style="color: #666; margin: 10px 0;">${db.description || 'Notifications stored in database'}</p>
                        ${db.stats ? `
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                <h5 style="margin: 0 0 10px 0;">Statistics</h5>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                    <div>
                                        <strong>Total:</strong> ${db.stats.total_notifications || 0}
                                    </div>
                                    <div>
                                        <strong>Unread:</strong> ${db.stats.unread_notifications || 0}
                                    </div>
                                </div>
                                ${db.stats.notification_types && Object.keys(db.stats.notification_types).length > 0 ? `
                                    <div style="margin-top: 10px;">
                                        <strong>By Type:</strong>
                                        <ul style="margin: 5px 0; padding-left: 20px;">
                                            ${Object.entries(db.stats.notification_types).map(([type, count]) => 
                                                `<li>${type}: ${count}</li>`
                                            ).join('')}
                                        </ul>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
            }
            
            // FCM Push Channel
            if (channels.fcm_push) {
                const fcm = channels.fcm_push;
                html += `
                    <div class="channel-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                        <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-mobile-alt" style="color: #FF9800; font-size: 1.5rem;"></i>
                            FCM Push Notifications
                            <span style="margin-left: auto; padding: 4px 12px; background: ${fcm.enabled ? '#4CAF50' : '#f44336'}; color: white; border-radius: 20px; font-size: 0.8em; font-weight: normal;">
                                ${fcm.enabled ? 'ACTIVE' : 'INACTIVE'}
                            </span>
                        </h4>
                        <p style="color: #666; margin: 10px 0;">${fcm.description || 'Firebase Cloud Messaging push notifications'}</p>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <div style="display: grid; gap: 10px;">
                                <div><strong>Project ID:</strong> ${fcm.project_id || 'N/A'}</div>
                                ${fcm.service_account ? `
                                    <div><strong>Service Account:</strong> ${fcm.service_account.client_email || 'N/A'}</div>
                                    <div><strong>Service Account File:</strong> ${fcm.service_account_file ? 'Exists' : 'Not found'}</div>
                                ` : ''}
                                <div><strong>Active Device Tokens:</strong> ${fcm.device_tokens_count || 0}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Email Channel
            if (channels.email) {
                const email = channels.email;
                html += `
                    <div class="channel-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; opacity: 0.6;">
                        <h4 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-envelope" style="color: #9E9E9E; font-size: 1.5rem;"></i>
                            Email Notifications
                            <span style="margin-left: auto; padding: 4px 12px; background: #9E9E9E; color: white; border-radius: 20px; font-size: 0.8em; font-weight: normal;">
                                NOT IMPLEMENTED
                            </span>
                        </h4>
                        <p style="color: #666; margin: 10px 0;">${email.description || 'Email notifications are not implemented yet'}</p>
                    </div>
                `;
            }
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        function displayNotificationTypes(types) {
            const container = document.getElementById('types-container');
            if (!container) return;
            
            let html = '<div class="types-grid" style="display: grid; gap: 15px;">';
            
            const typeIcons = {
                'booking': 'fa-calendar-check',
                'payment': 'fa-credit-card',
                'maintenance': 'fa-tools',
                'announcement': 'fa-bullhorn',
                'registration': 'fa-user-plus',
                'message': 'fa-comments',
                'security': 'fa-shield-alt'
            };
            
            const typeColors = {
                'booking': '#2196F3',
                'payment': '#4CAF50',
                'maintenance': '#FF9800',
                'announcement': '#9C27B0',
                'registration': '#00BCD4',
                'message': '#009688',
                'security': '#F44336'
            };
            
            for (const [type, config] of Object.entries(types)) {
                const icon = typeIcons[type] || 'fa-bell';
                const color = typeColors[type] || '#5D4037';
                
                html += `
                    <div class="type-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                        <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas ${icon}" style="color: ${color}; font-size: 1.5rem;"></i>
                            ${type.charAt(0).toUpperCase() + type.slice(1)} Notifications
                            <span style="margin-left: auto; padding: 4px 12px; background: ${config.enabled ? '#4CAF50' : '#f44336'}; color: white; border-radius: 20px; font-size: 0.8em; font-weight: normal;">
                                ${config.enabled ? 'ENABLED' : 'DISABLED'}
                            </span>
                        </h4>
                        <p style="color: #666; margin: 10px 0; font-size: 0.9em;">${config.description || 'Notification type'}</p>
                        ${config.methods && config.methods.length > 0 ? `
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                <strong>Available Methods:</strong>
                                <ul style="margin: 10px 0; padding-left: 20px;">
                                    ${config.methods.map(method => `<li style="margin: 5px 0;">${method}</li>`).join('')}
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                `;
            }
            
            html += '</div>';
            container.innerHTML = html;
        }

        async function saveNotificationSettings() {
            // Step 1: Show confirmation dialog
            const confirmSave = confirm('Are you sure you want to save these notification templates? This will update all notification messages in the system.');
            
            if (!confirmSave) {
                return; // User cancelled
            }
            
            // Step 2: Collect all template data
            const templates = {};
            const templateInputs = document.querySelectorAll('[id^="template_key_"]');
            
            templateInputs.forEach(input => {
                const key = input.value;
                const titleInput = document.getElementById(`template_title_${key}`);
                const messageInput = document.getElementById(`template_message_${key}`);
                const typeInput = document.getElementById(`template_type_${key}`);
                
                if (titleInput && messageInput && typeInput) {
                    templates[key] = {
                        title: titleInput.value.trim(),
                        message: messageInput.value.trim(),
                        type: typeInput.value
                    };
                }
            });
            
            const settings = {
                templates: templates
            };
            
            // Step 3: Show progress indicator
            const saveButton = document.getElementById('save-notification-settings-btn');
            const originalButtonText = saveButton ? saveButton.innerHTML : '';
            const originalButtonDisabled = saveButton ? saveButton.disabled : false;
            
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            // Create progress overlay
            const progressOverlay = document.createElement('div');
            progressOverlay.id = 'save-progress-overlay';
            progressOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                color: white;
            `;
            
            progressOverlay.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); text-align: center; min-width: 300px;">
                    <div style="font-size: 2.5rem; margin-bottom: 15px;">
                        <i class="fas fa-spinner fa-spin" style="color: #8D6E63;"></i>
                    </div>
                    <div style="font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 15px;">
                        Saving Templates...
                    </div>
                    <div style="width: 100%; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; margin-bottom: 10px;">
                        <div id="save-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #2196F3, #21CBF3); transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 0.9rem; color: #666;">
                        Please wait while we save your changes
                    </div>
                </div>
            `;
            
            document.body.appendChild(progressOverlay);
            
            // Animate progress bar
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                const progressBar = document.getElementById('save-progress-bar');
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                }
            }, 200);

            try {
                const response = await fetch('../get_notification_settings.php?action=save_settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settings)
                });
                
                // Complete progress bar
                const progressBar = document.getElementById('save-progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                }
                clearInterval(progressInterval);
                
                const data = await response.json();
                
                // Step 4: Show success message and remove progress
                setTimeout(() => {
                    if (progressOverlay.parentNode) {
                        progressOverlay.parentNode.removeChild(progressOverlay);
                    }
                
                if (data.success) {
                        // Show success notification
                        showNotification('Notification templates saved successfully!', 'success');
                        
                        // Reload settings to show updated templates
                        loadNotificationSettings();
                        
                        // Switch back to the first tab (Current Settings) after a short delay
                        setTimeout(() => {
                            switchSettingsTab('current');
                            document.querySelectorAll('.settings-tabs .tab').forEach(tab => {
                                tab.classList.remove('active');
                            });
                            const currentTab = document.querySelector('.settings-tabs .tab[onclick*="current"]');
                            if (currentTab) {
                                currentTab.classList.add('active');
                            }
                        }, 1000);
                } else {
                        showNotification('Error saving templates: ' + (data.error || 'Unknown error'), 'error');
                    }
                    
                    // Restore button
                    if (saveButton) {
                        saveButton.disabled = originalButtonDisabled;
                        saveButton.innerHTML = originalButtonText;
                    }
                }, 500);
                
            } catch (error) {
                clearInterval(progressInterval);
                
                // Remove progress overlay on error
                if (progressOverlay.parentNode) {
                    progressOverlay.parentNode.removeChild(progressOverlay);
                }
                
                // Restore button
                if (saveButton) {
                    saveButton.disabled = originalButtonDisabled;
                    saveButton.innerHTML = originalButtonText;
                }
                
                console.error('Error saving notification templates:', error);
                showNotification('Error saving templates. Please try again.', 'error');
            }
        }

        // User approval functions
        function approveUser(registrationId) {
            // Open document verification modal first
            viewDocuments(registrationId);
        }

        function rejectUser(registrationId) {
            // Open document verification modal first
            viewDocuments(registrationId);
        }

        function updatePendingCount(change) {
            const pendingCountElement = document.querySelector('.stat-content h3');
            const currentCount = parseInt(pendingCountElement.textContent);
            const newCount = Math.max(0, currentCount + change);
            pendingCountElement.textContent = newCount;
        }

        // Add visual emphasis for pending count
        document.addEventListener('DOMContentLoaded', function() {
            const pendingCount = <?php echo $pending_count; ?>;
            const pendingCard = document.querySelector('.stat-card:nth-child(3)');
            
            if (pendingCount > 0) {
                // Add pulsing animation for pending count
                pendingCard.style.animation = 'pulse 2s infinite';
                
                // Add notification badge
                const badge = document.createElement('div');
                badge.style.cssText = `
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: #e74c3c;
                    color: white;
                    border-radius: 50%;
                    width: 25px;
                    height: 25px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                    z-index: 10;
                    animation: bounce 1s infinite;
                `;
                badge.textContent = pendingCount;
                pendingCard.style.position = 'relative';
                pendingCard.appendChild(badge);
            }
        });

        function resendVerificationEmail(userId, email) {
            if (confirm(`Resend verification email to ${email}?`)) {
                // Simulate API call
                fetch('/api/resend-verification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        userId: userId,
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Verification email resent successfully!');
                    } else {
                        alert('Error resending email: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error resending email. Please try again.');
                });
            }
        }


        // This duplicate function is removed - using the one defined earlier that opens the modal

         // Initialize Charts with Real Data
         async function initializeCharts() {
             let dashboardData = null;
             
             try {
                 // Fetch dashboard data
                 const response = await fetch('../get_dashboard_data_simple.php');
                 const data = await response.json();
                 
                 if (!data.success) {
                     console.error('Error fetching dashboard data:', data.error);
                     return;
                 }
                 
                 dashboardData = data.data;
                 
                 // User Growth Chart (Dashboard)
                 const userGrowthCtx = document.getElementById('userGrowthChart');
                 if (userGrowthCtx && dashboardData && dashboardData.charts) {
                     const userGrowthData = dashboardData.charts.user_growth;
                     const labels = userGrowthData.map(item => {
                         const date = new Date(item.month + '-01');
                         return date.toISOString().split('T')[0].substring(5, 7) + '/' + date.toISOString().split('T')[0].substring(0, 4);
                     });
                     const values = userGrowthData.map(item => parseInt(item.new_users));
                     
                     new Chart(userGrowthCtx, {
                         type: 'line',
                         data: {
                             labels: labels,
                             datasets: [{
                                 label: 'New Users',
                                 data: values,
                                 borderColor: '#8D6E63',
                                 backgroundColor: 'rgba(141, 110, 99, 0.1)',
                                 tension: 0.4,
                                 fill: true
                             }]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             plugins: {
                                 legend: {
                                     display: false
                                 }
                             },
                             scales: {
                                 y: {
                                     beginAtZero: true,
                                     grid: {
                                         display: false
                                     }
                                 },
                                 x: {
                                     grid: {
                                         display: false
                                     }
                                 }
                             }
                         }
                     });
                 }
                 
                 // Update dashboard overview cards
                 if (dashboardData && dashboardData.overview) {
                     updateDashboardOverview(dashboardData.overview);
                 }
                 
                // Load recent activity from system notifications
                loadDashboardRecentActivity();
                 
             } catch (error) {
                 console.error('Error initializing charts:', error);
             }
         }
         
         // Update Dashboard Overview Cards
         function updateDashboardOverview(overview) {
             const elements = {
                 'total-users': overview.total_users,
                 'total-boarders': overview.total_boarders,
                 'total-owners': overview.total_owners,
                 'total-boarding-houses': overview.total_boarding_houses,
                 'total-bookings': overview.total_bookings,
                 'pending-bookings': overview.pending_bookings,
                 'confirmed-bookings': overview.confirmed_bookings
             };
             
             Object.entries(elements).forEach(([id, value]) => {
                 const element = document.getElementById(id);
                 if (element) {
                     element.textContent = value.toLocaleString();
                 }
             });
         }
         
        // Load Recent Activity from System Notifications for Dashboard
        async function loadDashboardRecentActivity() {
            const loadingElement = document.getElementById('recent-activity-loading');
            const containerElement = document.getElementById('recent-activity-container');
            
            // Show loading indicator
            if (loadingElement) {
                loadingElement.style.display = 'block';
            }
            if (containerElement) {
                containerElement.innerHTML = '';
            }
            
            try {
                // Fetch system notifications (limit to 10 most recent for dashboard)
                const response = await fetch('../get_admin_notifications.php?action=system&limit=10');
                const data = await response.json();
                
                if (data.success && data.data.system_notifications && data.data.system_notifications.length > 0) {
                    displayDashboardRecentActivity(data.data.system_notifications);
                } else {
                    // Show empty state
                    if (containerElement) {
                        containerElement.innerHTML = 
                            '<div style="text-align: center; padding: 2rem; color: rgba(0,0,0,0.5);">No recent activity</div>';
                    }
                }
            } catch (error) {
                console.error('Error loading dashboard recent activity:', error);
                if (containerElement) {
                    containerElement.innerHTML = 
                        '<div style="text-align: center; padding: 2rem; color: rgba(0,0,0,0.5);">Error loading recent activity</div>';
                }
            } finally {
                // Hide loading indicator
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
            }
        }
        
        // Display Recent Activity in Dashboard
        function displayDashboardRecentActivity(notifications) {
            const containerElement = document.getElementById('recent-activity-container');
            
            if (!containerElement) {
                console.error('Recent activity container not found');
                return;
            }
            
            // Map icon names to FontAwesome classes
            const iconMap = {
                'user-plus': 'fa-user-plus',
                'home': 'fa-home',
                'exclamation-triangle': 'fa-exclamation-triangle',
                'credit-card': 'fa-credit-card',
                'check-circle': 'fa-check-circle',
                'flag': 'fa-flag',
                'calendar-plus': 'fa-calendar-plus',
                'times-circle': 'fa-times-circle',
                'star': 'fa-star',
                'tools': 'fa-tools',
                'user-check': 'fa-user-check',
                'bell': 'fa-bell'
            };
            
            containerElement.innerHTML = notifications.map(notif => {
                // Use time_ago if available, otherwise calculate from event_time
                let timeDisplay = 'Recently';
                if (notif.time_ago) {
                    timeDisplay = notif.time_ago;
                } else if (notif.event_time) {
                    timeDisplay = getTimeAgo(notif.event_time);
                }
                
                // Get icon class
                const iconClass = iconMap[notif.icon_name] || 'fa-bell';
                
                // Convert hex color to rgba for background
                const hexColor = notif.icon_color || '#007bff';
                const r = parseInt(hexColor.slice(1, 3), 16);
                const g = parseInt(hexColor.slice(3, 5), 16);
                const b = parseInt(hexColor.slice(5, 7), 16);
                const bgColor = `rgba(${r}, ${g}, ${b}, 0.1)`;
                const iconColor = hexColor;
                
                return `
                     <div class="activity-item">
                        <div class="activity-icon" style="background: ${bgColor};">
                            <i class="fas ${iconClass}" style="color: ${iconColor};"></i>
                         </div>
                         <div class="activity-content">
                            <h4>${notif.title || 'System Activity'}</h4>
                            <p>${notif.message || ''}</p>
                         </div>
                        <div class="activity-time">${timeDisplay}</div>
                     </div>
                `;
            }).join('');
        }
        
        // Update Recent Activity (kept for backward compatibility if needed)
        function updateRecentActivity(activity) {
            // This function is kept for backward compatibility
            // But we now use loadDashboardRecentActivity() instead
            loadDashboardRecentActivity();
         }

                 // Revenue Chart (Dashboard) - Using booking trends as revenue proxy
                 const revenueCtx = document.getElementById('revenueChart');
                 if (revenueCtx && dashboardData && dashboardData.charts) {
                     const bookingTrendsData = dashboardData.charts.booking_trends;
                     const labels = bookingTrendsData.map(item => {
                         const date = new Date(item.month + '-01');
                         return date.toISOString().split('T')[0].substring(5, 7) + '/' + date.toISOString().split('T')[0].substring(0, 4);
                     });
                     const values = bookingTrendsData.map(item => parseInt(item.bookings));
                     
                     new Chart(revenueCtx, {
                         type: 'bar',
                         data: {
                             labels: labels,
                             datasets: [{
                                 label: 'Bookings',
                                 data: values,
                                 backgroundColor: '#A97A50',
                                 borderRadius: 4
                             }]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             plugins: {
                                 legend: {
                                     display: false
                                 }
                             },
                             scales: {
                                 y: {
                                     beginAtZero: true,
                                     grid: {
                                         display: false
                                     }
                                 },
                                 x: {
                                     grid: {
                                         display: false
                                     }
                                 }
                             }
                         }
                     });
                 }

                 // Property Occupancy Chart (Dashboard) - Using boarding houses by status
                 const propertyOccupancyCtx = document.getElementById('propertyOccupancyChart');
                if (propertyOccupancyCtx && dashboardData && dashboardData.charts && dashboardData.charts.boarding_houses_by_status) {
                     const statusData = dashboardData.charts.boarding_houses_by_status;
                     const labels = statusData.map(item => item.status);
                     const values = statusData.map(item => parseInt(item.count));
                     
                     new Chart(propertyOccupancyCtx, {
                         type: 'doughnut',
                         data: {
                             labels: labels,
                             datasets: [{
                                 data: values,
                                 backgroundColor: ['#8D6E63', '#A97A50', '#dc3545'],
                                 borderWidth: 0
                             }]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             plugins: {
                                 legend: {
                                     position: 'bottom',
                                     labels: {
                                         padding: 20,
                                         usePointStyle: true
                                     }
                                 }
                             }
                         }
                     });
                 }

             // User Analytics Chart (Analytics Section)
             const userAnalyticsCtx = document.getElementById('userAnalyticsChart');
             if (userAnalyticsCtx) {
                 new Chart(userAnalyticsCtx, {
                     type: 'line',
                     data: {
                         labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                        datasets: [{
                            label: 'Boarders',
                            data: [45, 52, 48, 61],
                            borderColor: '#2196F3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#2196F3',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }, {
                            label: 'Owners',
                            data: [12, 15, 18, 22],
                            borderColor: '#9C27B0',
                            backgroundColor: 'rgba(156, 39, 176, 0.1)',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#9C27B0',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 position: 'bottom'
                             }
                         },
                         scales: {
                             y: {
                                 beginAtZero: true
                             }
                         }
                     }
                 });
             }

             // Revenue Analytics Chart
             const revenueAnalyticsCtx = document.getElementById('revenueAnalyticsChart');
             if (revenueAnalyticsCtx) {
                 new Chart(revenueAnalyticsCtx, {
                     type: 'bar',
                     data: {
                         labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                        datasets: [{
                            label: 'Revenue (₱K)',
                            data: [180, 220, 195, 250],
                            backgroundColor: [
                                'rgba(76, 175, 80, 0.8)',
                                'rgba(76, 175, 80, 0.9)',
                                'rgba(76, 175, 80, 0.7)',
                                'rgba(76, 175, 80, 1)'
                            ],
                            borderColor: '#4CAF50',
                            borderWidth: 2,
                            borderRadius: 6
                        }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 display: false
                             }
                         },
                         scales: {
                             y: {
                                 beginAtZero: true
                             }
                         }
                     }
                 });
             }

             // Property Analytics Chart
             const propertyAnalyticsCtx = document.getElementById('propertyAnalyticsChart');
             if (propertyAnalyticsCtx) {
                 new Chart(propertyAnalyticsCtx, {
                     type: 'doughnut',
                     data: {
                         labels: ['Occupied', 'Available', 'Maintenance'],
                        datasets: [{
                            data: [1, 74, 0],
                            backgroundColor: ['#2196F3', '#4CAF50', '#FF9800'],
                            borderWidth: 3,
                            borderColor: '#fff',
                            hoverBorderWidth: 4
                        }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 position: 'bottom'
                             }
                         }
                     }
                 });
             }

             // Payment Analytics Chart
             const paymentAnalyticsCtx = document.getElementById('paymentAnalyticsChart');
             if (paymentAnalyticsCtx) {
                 new Chart(paymentAnalyticsCtx, {
                     type: 'bar',
                     data: {
                         labels: ['GCash', 'Bank Transfer', 'Cash', 'Credit Card'],
                        datasets: [{
                            label: 'Payment Methods',
                            data: [45, 30, 15, 10],
                            backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0'],
                            borderColor: '#fff',
                            borderWidth: 2,
                            borderRadius: 6
                        }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 display: false
                             }
                         },
                         scales: {
                             y: {
                                 beginAtZero: true
                             }
                         }
                     }
                 });
             }

             // Geographic Chart
             const geographicCtx = document.getElementById('geographicChart');
             if (geographicCtx) {
                 new Chart(geographicCtx, {
                     type: 'pie',
                     data: {
                         labels: ['Quezon City', 'Makati City', 'Manila City', 'Pasig City', 'Other Areas'],
                        datasets: [{
                            data: [23, 18, 15, 12, 21],
                            backgroundColor: ['#2196F3', '#9C27B0', '#E91E63', '#4CAF50', '#FF9800'],
                            borderWidth: 3,
                            borderColor: '#fff',
                            hoverBorderWidth: 4
                        }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 position: 'bottom'
                             }
                         }
                     }
                 });
             }

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadDashboardAnalytics();
            
            // Also load recent activity if dashboard section is active
            const dashboardSection = document.getElementById('dashboard-section');
            if (dashboardSection && dashboardSection.classList.contains('active')) {
                // Small delay to ensure DOM is ready
                setTimeout(() => {
                    loadDashboardRecentActivity();
                }, 100);
            }
        });

         // Re-initialize charts when switching to analytics section
         // Note: showSection function is already defined above

         // Real-time updates removed - using actual database counts

        // Report download functionality
        // Load boarding houses for filter dropdowns
        function loadBoardingHousesForFilters() {
            // Check if already loaded
            const paymentSelect = document.getElementById('paymentBoardingHouse');
            const rentalSelect = document.getElementById('rentalBoardingHouse');
            
            if (!paymentSelect || !rentalSelect) {
                return; // Elements not found yet
            }
            
            // Clear existing options (except "All")
            while (paymentSelect.children.length > 1) {
                paymentSelect.removeChild(paymentSelect.lastChild);
            }
            while (rentalSelect.children.length > 1) {
                rentalSelect.removeChild(rentalSelect.lastChild);
            }
            
            fetch('../get_boarding_houses_for_filter.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        data.data.forEach(bh => {
                            const option1 = document.createElement('option');
                            option1.value = bh.id;
                            option1.textContent = bh.name;
                            paymentSelect.appendChild(option1);
                            
                            const option2 = document.createElement('option');
                            option2.value = bh.id;
                            option2.textContent = bh.name;
                            rentalSelect.appendChild(option2);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading boarding houses:', error);
                });
        }

        function downloadPaymentReportPDF() {
            const reportBtn = document.getElementById('paymentReportBtn');
            const originalText = reportBtn.innerHTML;
            
            // Get filter values
            const startDate = document.getElementById('paymentStartDate').value || null;
            const endDate = document.getElementById('paymentEndDate').value || null;
            const boardingHouseId = document.getElementById('paymentBoardingHouse').value || null;
            
            // Show loading state
            reportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            reportBtn.disabled = true;
            
            // Build query string for preview
            const params = new URLSearchParams();
            params.append('action', 'payment_report_preview');
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (boardingHouseId) params.append('boarding_house_id', boardingHouseId);
            
            // Store download params for later use
            const downloadParams = new URLSearchParams();
            downloadParams.append('action', 'payment_report_pdf');
            if (startDate) downloadParams.append('start_date', startDate);
            if (endDate) downloadParams.append('end_date', endDate);
            if (boardingHouseId) downloadParams.append('boarding_house_id', boardingHouseId);
            
            // Set preview URL and download URL
            const previewUrl = '../generate_payment_report_pdf.php?' + params.toString();
            const downloadUrl = '../generate_payment_report_pdf.php?' + downloadParams.toString();
            
            // Show preview modal
            showPDFPreviewModal(previewUrl, downloadUrl, 'Payment Report');
            
            // Reset button
            setTimeout(() => {
                reportBtn.innerHTML = originalText;
                reportBtn.disabled = false;
            }, 500);
        }
        
        let currentDownloadUrl = '';
        
        function showPDFPreviewModal(previewUrl, downloadUrl, reportTitle) {
            const modal = document.getElementById('pdfPreviewModal');
            const frame = document.getElementById('pdfPreviewFrame');
            const title = modal.querySelector('h2');
            
            // Update modal content
            title.innerHTML = `<i class="fas fa-file-pdf"></i> ${reportTitle} - Preview`;
            frame.src = previewUrl;
            currentDownloadUrl = downloadUrl;
            
            // Show modal
            modal.style.display = 'flex';
        }
        
        function closePDFPreviewModal() {
            const modal = document.getElementById('pdfPreviewModal');
            const frame = document.getElementById('pdfPreviewFrame');
            if (modal) {
                modal.style.display = 'none';
                // Clear iframe src to stop loading
                frame.src = '';
            }
        }
        
        function downloadPDFFromPreview() {
            if (!currentDownloadUrl) return;
            
            const downloadLink = document.createElement('a');
            downloadLink.href = currentDownloadUrl;
            downloadLink.target = '_blank';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            setTimeout(() => {
                document.body.removeChild(downloadLink);
            }, 1000);
            showNotification('PDF download started!', 'success');
        }

        function downloadRentalReportPDF() {
            const reportBtn = document.getElementById('rentalReportBtn');
            const originalText = reportBtn.innerHTML;
            
            // Get filter values
            const startDate = document.getElementById('rentalStartDate').value || null;
            const endDate = document.getElementById('rentalEndDate').value || null;
            const boardingHouseId = document.getElementById('rentalBoardingHouse').value || null;
            
            // Show loading state
            reportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            reportBtn.disabled = true;
            
            // Build query strings for preview/download
            const previewParams = new URLSearchParams();
            previewParams.append('action', 'rental_report_preview');
            if (startDate) previewParams.append('start_date', startDate);
            if (endDate) previewParams.append('end_date', endDate);
            if (boardingHouseId) previewParams.append('boarding_house_id', boardingHouseId);
            
            const downloadParams = new URLSearchParams();
            downloadParams.append('action', 'rental_report_pdf');
            if (startDate) downloadParams.append('start_date', startDate);
            if (endDate) downloadParams.append('end_date', endDate);
            if (boardingHouseId) downloadParams.append('boarding_house_id', boardingHouseId);
            
            const previewUrl = '../generate_rental_report_pdf.php?' + previewParams.toString();
            const downloadUrl = '../generate_rental_report_pdf.php?' + downloadParams.toString();
            
            // Show preview modal matching payment flow
            showPDFPreviewModal(previewUrl, downloadUrl, 'Rental Report');
            
            // Reset button state after a short delay
            setTimeout(() => {
                reportBtn.innerHTML = originalText;
                reportBtn.disabled = false;
            }, 500);
        }

        // Database backup functionality
        function backupDatabase() {
            const backupBtn = document.getElementById('backupBtn');
            const originalText = backupBtn.innerHTML;
            
            // Show loading state
            backupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
            backupBtn.disabled = true;
            
            // Create a direct download link
            const downloadLink = document.createElement('a');
            downloadLink.href = '../backup_database.php?action=backup';
            downloadLink.download = 'boardease_backup_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.sql';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            
            // Trigger download
            downloadLink.click();
            
            // Clean up
            document.body.removeChild(downloadLink);
            
            // Reset button after a delay
            setTimeout(() => {
                backupBtn.innerHTML = originalText;
                backupBtn.disabled = false;
                
                // Show success message
                showNotification('Database backup created successfully! Check your Downloads folder.', 'success');
            }, 2000);
        }

        // Notification function for backup success
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-family: 'Poppins', sans-serif;
                font-size: 14px;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            `;
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(notification);
            
            // Remove notification after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }
    </script>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> User Details</h2>
                <button class="modal-close" onclick="closeUserDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading user details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Boarding House Details Modal -->
    <div id="boardingHouseDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-home"></i> Boarding House Details</h2>
                <button class="modal-close" onclick="closeBoardingHouseDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="boardingHouseDetailsContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading boarding house details...</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            height: 100dvh; /* Dynamic viewport height for mobile */
            min-height: 100%;
            min-height: 100dvh;
            background: linear-gradient(135deg, rgba(141, 110, 99, 0.8) 0%, rgba(141, 110, 99, 0.7) 50%, rgba(230, 218, 200, 0.6) 100%);
            backdrop-filter: blur(10px);
            animation: modalFadeIn 0.3s ease-out;
            overflow-y: auto;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to {
                opacity: 1;
                backdrop-filter: blur(10px);
            }
        }

        .modal-content {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            margin: 2% auto;
            padding: 0;
            border: 2px solid rgba(141, 110, 99, 0.5);
            border-radius: 25px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 
                0 20px 60px rgba(141, 110, 99, 0.4),
                0 10px 30px rgba(141, 110, 99, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            display: flex;
            flex-direction: column;
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }

        .document-modal {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
        }

        #rejectionReasonModal .modal-content {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            max-width: 600px;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: #E6DAC8;
            padding: 2rem;
            border-radius: 25px 25px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(141, 110, 99, 0.3);
            border-bottom: 2px solid rgba(230, 218, 200, 0.2);
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-close {
            background: linear-gradient(135deg, rgba(230, 218, 200, 0.2), rgba(230, 218, 200, 0.1));
            border: 2px solid rgba(230, 218, 200, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            color: #E6DAC8;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .modal-close:hover {
            background: linear-gradient(135deg, rgba(230, 218, 200, 0.3), rgba(230, 218, 200, 0.2));
            border-color: rgba(230, 218, 200, 0.5);
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 15px rgba(141, 110, 99, 0.3);
        }

        /* Enhanced Button Styling */
        .btn {
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: #E6DAC8;
            border: 2px solid rgba(230, 218, 200, 0.3);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(141, 110, 99, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(230, 218, 200, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(141, 110, 99, 0.4);
            border-color: rgba(230, 218, 200, 0.6);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-success {
            background: linear-gradient(135deg, #228B22 0%, #32CD32 100%);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #DC143C 0%, #FF6347 100%);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        /* Extended background for specific modals */
        #notificationSettingsModal .modal-content,
        #accountManagementModal .modal-content,
        #userDetailsModal .modal-content,
        #boardingHouseDetailsModal .modal-content {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            overflow: hidden !important;
            max-height: 85vh !important;
            display: flex !important;
            flex-direction: column !important;
        }

        #notificationSettingsModal .modal-body,
        #accountManagementModal .modal-body,
        #userDetailsModal .modal-body,
        #boardingHouseDetailsModal .modal-body {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            min-height: 0 !important;
            flex: 1 1 0% !important;
            height: 0 !important;
            -webkit-overflow-scrolling: touch;
            padding: 2rem;
            padding-bottom: 4rem !important;
            position: relative;
        }

        /* Ensure all content containers can expand */
        #notificationSettingsModal .modal-body > *,
        #accountManagementModal .modal-body > * {
            min-height: fit-content;
        }

        /* Ensure tab content has enough bottom space for status badges */
        #notificationSettingsModal .tab-content,
        #accountManagementModal .tab-content {
            padding-bottom: 2rem;
        }

        /* Ensure all containers inside tabs have proper spacing */
        #notificationSettingsModal #current-settings-container,
        #notificationSettingsModal #channels-container,
        #notificationSettingsModal #types-container,
        #notificationSettingsModal #templates-container,
        #accountManagementModal .admin-list,
        #accountManagementModal #security-events-container,
        #accountManagementModal #activity-list-container {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        #notificationSettingsModal .modal-footer,
        #accountManagementModal .modal-footer {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            border-top: 1px solid rgba(141, 110, 99, 0.3);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            flex-shrink: 0;
        }

        /* Extended background for admin modals */
        #addAdminModal .modal-content,
        #editAdminModal .modal-content {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
        }

        #addAdminModal .modal-body,
        #editAdminModal .modal-body {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
        }

        #addAdminModal .modal-footer,
        #editAdminModal .modal-footer {
            background: linear-gradient(145deg, #E6DAC8 0%, #F5F5DC 100%);
            border-top: 1px solid rgba(141, 110, 99, 0.3);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Loading Indicator Styles */
        .loading-indicator {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(145deg, rgba(230, 218, 200, 0.95) 0%, rgba(245, 245, 220, 0.95) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: 0 0 20px 20px;
        }

        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(141, 110, 99, 0.2);
            border-top: 4px solid #8D6E63;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        .loading-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #5D4037;
            margin-bottom: 1.5rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .loading-progress {
            width: 200px;
            height: 6px;
            background: rgba(141, 110, 99, 0.2);
            border-radius: 3px;
            overflow: hidden;
            margin: 0 auto;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            border-radius: 3px;
            animation: progress 2s ease-in-out infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }


        .user-profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }

        .profile-picture-container {
            flex-shrink: 0;
        }

        .user-profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #8D6E63;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .user-profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .user-role {
            margin: 0 0 1rem 0;
            color: #8D6E63;
            font-size: 1rem;
            font-weight: 500;
        }

        .status-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .verification-images-section {
            margin-top: 1.5rem;
        }

        .verification-images-section h4 {
            color: #8D6E63;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .image-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .image-item h5 {
            margin: 0 0 0.75rem 0;
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .verification-image {
            width: 100%;
            max-width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #8D6E63;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .verification-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(141, 110, 99, 0.3);
        }

        .no-image {
            width: 100%;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 6px;
            color: #666;
            font-style: italic;
        }

        /* Boarding House Details Modal Styles */
        .boarding-house-details {
            padding: 0;
        }

        .property-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            border-left: 4px solid #8D6E63;
        }

        .property-image {
            flex-shrink: 0;
        }

        .owner-profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #8D6E63;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .owner-profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(141, 110, 99, 0.3);
        }

        .property-info h3 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .property-address {
            margin: 0 0 1rem 0;
            color: #666;
            font-size: 1rem;
        }

        .property-address i {
            color: #8D6E63;
            margin-right: 0.5rem;
        }

        .owner-section, .statistics-section, .description-section, .rooms-section, .dates-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .owner-section h4, .statistics-section h4, .description-section h4, .rooms-section h4, .dates-section h4 {
            margin: 0 0 1rem 0;
            color: #8D6E63;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .owner-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .owner-details strong {
            display: block;
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .owner-details p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .owner-details i {
            color: #8D6E63;
            width: 16px;
            margin-right: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #8D6E63;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rooms-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .room-item {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #8D6E63;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .room-title h5 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .room-category {
            background: #8D6E63;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .room-price {
            text-align: right;
        }

        .price-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #8D6E63;
        }

        .price-period {
            color: #666;
            font-size: 0.9rem;
        }

        .room-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .detail-label i {
            color: #8D6E63;
            width: 14px;
        }

        .detail-value {
            color: #666;
            font-size: 0.9rem;
        }

        .room-description-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .room-description {
            margin: 0.5rem 0 0 0;
            font-style: italic;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Analytics Section Styles */
        .analytics-container {
            padding: 0;
            padding: 2rem; /* Reduced from 2.5rem (80%) */
            margin-top: 1.5rem;
            font-size: 0.8em; /* Scale down all text to 80% */
        }

        .analytics-overview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.6rem; /* Reduced from 2rem (80%) */
            margin-bottom: 2.4rem; /* Reduced from 3rem (80%) */
        }

        .analytics-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12.8px; /* Reduced from 16px (80%) */
            padding: 1.4rem 1.6rem; /* Reduced from 1.75rem 2rem (80%) */
            box-shadow: 0 6.4px 19.2px rgba(0, 0, 0, 0.08),
                        0 1.6px 6.4px rgba(0, 0, 0, 0.04),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            gap: 1.2rem; /* Reduced from 1.5rem (80%) */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(141, 110, 99, 0.08);
            border-left: 4px solid; /* Reduced from 5px (80%) */
            position: relative;
            overflow: hidden;
            min-height: 96px; /* Reduced from 120px (80%) */
        }

        /* Brown left border for each card - matching the theme */
        .analytics-card:nth-child(1) {
            border-left-color: #8D6E63;
        }

        .analytics-card:nth-child(2) {
            border-left-color: #A97A50;
        }

        .analytics-card:nth-child(3) {
            border-left-color: #8D6E63;
        }

        .analytics-card:nth-child(4) {
            border-left-color: #A97A50;
        }

        .analytics-card:nth-child(5) {
            border-left-color: #8D6E63;
        }

        .analytics-card:nth-child(6) {
            border-left-color: #A97A50;
        }

        .analytics-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, rgba(141, 110, 99, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .analytics-card:hover::before {
            transform: translateX(100%);
        }

        .analytics-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12),
                        0 4px 12px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 1);
        }

        .analytics-card .card-icon {
            width: 56px; /* Reduced from 70px (80%) */
            height: 56px; /* Reduced from 70px (80%) */
            border-radius: 12.8px; /* Reduced from 16px (80%) */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.44rem; /* Reduced from 1.8rem (80%) */
            color: white;
            box-shadow: 0 3.2px 9.6px rgba(0, 0, 0, 0.15),
                        0 1.6px 3.2px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .analytics-card .card-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .analytics-card:hover .card-icon::before {
            animation: shine 1.5s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        /* Different colors for each card icon */
        .analytics-card:nth-child(1) .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Purple-Blue - Total Users */
        }
        
        .analytics-card:nth-child(2) .card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); /* Pink-Red - Boarding Houses */
        }
        
        .analytics-card:nth-child(3) .card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); /* Blue-Cyan - Room Units */
        }
        
        .analytics-card:nth-child(4) .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); /* Green-Cyan - Total Bookings */
        }
        
        .analytics-card:nth-child(5) .card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); /* Pink-Yellow - Total Revenue */
        }
        
        .analytics-card:nth-child(6) .card-icon {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); /* Cyan-Purple - Messages */
        }

        .analytics-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2),
                        0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .analytics-card .card-content {
            flex: 1;
        }

        .analytics-card h3 {
            font-size: 1.8rem; /* Reduced from 2.25rem (80%) */
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 0.4rem 0; /* Reduced from 0.5rem (80%) */
            letter-spacing: -0.4px; /* Reduced from -0.5px (80%) */
            line-height: 1.2;
        }

        .analytics-card p {
            color: #4a5568;
            margin: 0 0 0.6rem 0; /* Reduced from 0.75rem (80%) */
            font-weight: 600;
            font-size: 0.76rem; /* Reduced from 0.95rem (80%) */
            text-transform: uppercase;
            letter-spacing: 0.4px; /* Reduced from 0.5px (80%) */
        }

        .analytics-card .card-subtitle {
            font-size: 0.7rem; /* Reduced from 0.875rem (80%) */
            color: #8D6E63;
            font-weight: 600;
            display: inline-block;
            padding: 0.2rem 0.6rem; /* Reduced from 0.25rem 0.75rem (80%) */
            background: rgba(141, 110, 99, 0.1);
            border-radius: 9.6px; /* Reduced from 12px (80%) */
            margin-top: 0.2rem; /* Reduced from 0.25rem (80%) */
        }

        .charts-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem; /* Reduced from 2.5rem (80%) */
            margin-bottom: 2.4rem; /* Reduced from 3rem (80%) */
        }

        /* Mobile responsive - display charts in 1 column */
        @media (max-width: 991.98px) {
            .charts-section {
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }

            .chart-container {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 100% !important;
            }
        }

        @media (max-width: 767.98px) {
            .charts-section {
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }

            .chart-container {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 100% !important;
                padding: 1.25rem !important;
                min-height: auto !important;
            }

            .chart-container h4 {
                font-size: 0.95rem !important;
                margin-bottom: 1rem !important;
            }

            .chart-container canvas {
                height: 220px !important;
                max-height: 220px !important;
                min-height: 220px !important;
            }
        }

        @media (max-width: 575.98px) {
            .charts-section {
                gap: 1.25rem !important;
            }

            .chart-container {
                padding: 1rem !important;
            }

            .chart-container h4 {
                font-size: 0.9rem !important;
                margin-bottom: 0.875rem !important;
            }

            .chart-container canvas {
                height: 200px !important;
                max-height: 200px !important;
                min-height: 200px !important;
            }
        }

        .chart-container {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px; /* Reduced from 20px (80%) */
            padding: 1.6rem; /* Reduced from 2rem (80%) */
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08),
                        0 3.2px 9.6px rgba(0, 0, 0, 0.04),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(141, 110, 99, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-height: 336px; /* Reduced from 420px (80%) */
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .chart-container:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12),
                        0 8px 20px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 1);
        }

        .chart-container h4 {
            color: #1a202c;
            margin: 0 0 1.4rem 0; /* Reduced from 1.75rem (80%) */
            font-size: 1rem; /* Reduced from 1.25rem (80%) */
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem; /* Reduced from 0.75rem (80%) */
            letter-spacing: -0.24px; /* Reduced from -0.3px (80%) */
            padding-top: 0.4rem; /* Reduced from 0.5rem (80%) */
        }

        .chart-container h4 i {
            color: #667eea;
            font-size: 1.12rem; /* Reduced from 1.4rem (80%) */
            filter: drop-shadow(0 1.6px 3.2px rgba(102, 126, 234, 0.3));
        }

        .chart-container canvas {
            max-width: 100%;
            height: 256px !important; /* Reduced from 320px (80%) */
            min-height: 256px !important; /* Reduced from 320px (80%) */
            border-radius: 9.6px; /* Reduced from 12px (80%) */
            transition: all 0.3s ease;
        }

        .chart-container:hover canvas {
            transform: scale(1.02);
        }

        .top-boarding-houses-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem; /* Reduced from 2.5rem (80%) */
            margin-bottom: 2.4rem; /* Reduced from 3rem (80%) */
        }

        /* Mobile responsive - display in 1 column */
        @media (max-width: 991.98px) {
            .top-boarding-houses-container {
                grid-template-columns: 1fr !important;
                gap: 1.5rem !important;
            }

            .top-performing-section {
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        @media (max-width: 767.98px) {
            .top-boarding-houses-container {
                gap: 1.25rem !important;
            }

            .top-performing-section {
                padding: 1.5rem !important;
            }
        }

        @media (max-width: 575.98px) {
            .top-boarding-houses-container {
                gap: 1rem !important;
            }

            .top-performing-section {
                padding: 1.25rem !important;
            }
        }

        .top-performing-section {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px; /* Reduced from 20px (80%) */
            padding: 2rem; /* Reduced from 2.5rem (80%) */
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08),
                        0 3.2px 9.6px rgba(0, 0, 0, 0.04),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(141, 110, 99, 0.08);
            position: relative;
            overflow: hidden;
        }

        .top-performing-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }

        .top-performing-section h4 {
            color: #1a202c;
            margin: 0 0 1.6rem 0; /* Reduced from 2rem (80%) */
            font-size: 1.12rem; /* Reduced from 1.4rem (80%) */
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem; /* Reduced from 0.75rem (80%) */
            letter-spacing: -0.24px; /* Reduced from -0.3px (80%) */
            padding-top: 0.4rem; /* Reduced from 0.5rem (80%) */
        }

        .top-performing-section h4 i {
            color: #f5576c;
            font-size: 1.2rem; /* Reduced from 1.5rem (80%) */
            filter: drop-shadow(0 1.6px 3.2px rgba(245, 87, 108, 0.3));
        }

        .top-performing-list {
            display: flex;
            flex-direction: column;
            gap: 1rem; /* Reduced from 1.25rem (80%) */
        }

        .top-performing-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.2rem; /* Reduced from 1.5rem (80%) */
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 11.2px; /* Reduced from 14px (80%) */
            border-left: 4px solid; /* Reduced from 5px (80%) */
            border-image: linear-gradient(135deg, #667eea, #764ba2) 1;
            box-shadow: 0 3.2px 9.6px rgba(0, 0, 0, 0.06),
                        0 1.6px 3.2px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .top-performing-item:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1),
                        0 4px 8px rgba(0, 0, 0, 0.06);
        }

        .top-performing-item .item-info h5 {
            margin: 0 0 0.4rem 0; /* Reduced from 0.5rem (80%) */
            color: #1a202c;
            font-size: 0.88rem; /* Reduced from 1.1rem (80%) */
            font-weight: 700;
            letter-spacing: -0.24px; /* Reduced from -0.3px (80%) */
        }

        .top-performing-item .item-info p {
            margin: 0;
            color: #718096;
            font-size: 0.72rem; /* Reduced from 0.9rem (80%) */
            font-weight: 500;
        }

        .top-performing-item .item-stats {
            text-align: right;
        }

        .top-performing-item .item-stats .stat-value {
            font-size: 1.12rem; /* Reduced from 1.4rem (80%) */
            font-weight: 700;
            color: #667eea;
            letter-spacing: -0.4px; /* Reduced from -0.5px (80%) */
        }

        .top-performing-item .item-stats .stat-label {
            font-size: 0.64rem; /* Reduced from 0.8rem (80%) */
            color: #a0aec0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px; /* Reduced from 0.5px (80%) */
        }

        /* Responsive Design for Analytics */
        @media (max-width: 1400px) {
            .analytics-overview {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .charts-section {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .analytics-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .charts-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .analytics-container {
                padding: 1.5rem;
            }

            .analytics-overview {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .top-boarding-houses-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .analytics-card {
                padding: 1.5rem;
                flex-direction: column;
                text-align: center;
            }
            
            .analytics-card .card-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .analytics-card h3 {
                font-size: 1.75rem;
            }

            .chart-container {
                padding: 1.5rem;
                min-height: 350px;
            }

            .chart-container h4 {
                font-size: 1.1rem;
            }

            .chart-container canvas {
                height: 280px !important;
                min-height: 280px !important;
            }

            .top-performing-section {
                padding: 1.5rem;
            }

            .top-performing-section h4 {
                font-size: 1.2rem;
            }
        }

        /* Extra small screens - ensure modal is fully scrollable */
        @media (max-width: 480px) {
            #notificationSettingsModal .modal-content,
            #accountManagementModal .modal-content {
                margin: 0.25rem !important;
                width: calc(100% - 0.5rem) !important;
                max-height: calc(100vh - 0.5rem) !important;
                height: calc(100vh - 0.5rem) !important;
            }

            #notificationSettingsModal .modal-header,
            #accountManagementModal .modal-header {
                padding: 0.75rem !important;
            }

            #notificationSettingsModal .modal-footer,
            #accountManagementModal .modal-footer {
                padding: 0.75rem !important;
            }

            #notificationSettingsModal .modal-body,
            #accountManagementModal .modal-body {
                padding: 0.75rem !important;
                padding-bottom: 3rem !important;
            }
        }

        @media (max-width: 480px) {
            .analytics-container {
                padding: 1rem;
            }

            .analytics-card {
                padding: 1.25rem;
            }
            
            .analytics-card h3 {
                font-size: 1.5rem;
            }

            .chart-container {
                padding: 1.25rem;
                min-height: 320px;
            }

            .chart-container canvas {
                height: 250px !important;
                min-height: 250px !important;
            }
        }

        .date-info p {
            margin: 0.5rem 0;
            color: #666;
        }

        .date-info strong {
            color: #2c3e50;
        }

        .user-details-section {
            margin-bottom: 2rem;
        }

        .user-details-section h3 {
            color: #8D6E63;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            border-bottom: 2px solid #8D6E63;
            padding-bottom: 0.5rem;
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #8D6E63;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            min-width: 0;
            overflow: hidden;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #333;
            font-size: 1rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            max-width: 100%;
            overflow: hidden;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #cfe2ff;
            color: #084298;
        }

        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .boarding-house-item, .booking-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid #8D6E63;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .item-title {
            font-weight: 600;
            color: #333;
        }

        .item-status {
            font-size: 0.8rem;
        }

        .item-details {
            color: #666;
            font-size: 0.9rem;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            
            .user-info-grid {
                grid-template-columns: 1fr;
            }
            
            .user-profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .user-profile-picture {
                width: 100px;
                height: 100px;
            }
            
            .profile-info h2 {
                font-size: 1.3rem;
            }
        }
    </style>

    <!-- Account Management Modal -->
    <div id="accountManagementModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h2><i class="fas fa-user-cog"></i> Account Management</h2>
                <button class="modal-close" onclick="closeAccountManagement()">&times;</button>
            </div>
            
            <div class="modal-body">
                <!-- Loading Indicator -->
                <div id="accountLoadingIndicator" class="loading-indicator" style="display: none;">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <div class="loading-text">Loading admin accounts...</div>
                        <div class="loading-progress">
                            <div class="progress-bar"></div>
                        </div>
                    </div>
                </div>

                <div class="account-tabs">
                    <div class="tab active" onclick="switchAccountTab('admins')">
                        <i class="fas fa-user-shield"></i> Admin Accounts
                    </div>
                    <div class="tab" onclick="switchAccountTab('security')">
                        <i class="fas fa-shield-alt"></i> Security
                    </div>
                    <div class="tab" onclick="switchAccountTab('activity')">
                        <i class="fas fa-history"></i> Activity Log
                    </div>
                </div>

                <!-- Admin Accounts Tab -->
                <div id="admins-tab" class="tab-content active">
                    <div class="tab-header">
                        <h3>Admin Accounts</h3>
                        <div class="admin-controls">
                            <div class="search-box">
                                <input type="text" id="adminSearch" placeholder="Search admins..." onkeyup="filterAdmins()">
                                <i class="fas fa-search"></i>
                            </div>
                            <button class="btn-modern btn-add" onclick="openAddAdminModal()">
                                <i class="fas fa-plus"></i> Add Admin
                            </button>
                        </div>
                    </div>
                    
                    <div class="admin-list">
                        <div class="admin-item">
                            <div class="admin-avatar">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="admin-info">
                                <h4>Super Admin</h4>
                                <p>admin@boardease.com</p>
                                <span class="admin-role">Super Administrator</span>
                            </div>
                            <div class="admin-status">
                                <span class="status-badge active">Active</span>
                            </div>
                            <div class="admin-actions">
                                <button class="btn-action btn-edit" onclick="editAdmin(1)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteAdmin(1)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="admin-item">
                            <div class="admin-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="admin-info">
                                <h4>Your Partner</h4>
                                <p>partner@boardease.com</p>
                                <span class="admin-role">Super Administrator</span>
                            </div>
                            <div class="admin-status">
                                <span class="status-badge active">Active</span>
                            </div>
                            <div class="admin-actions">
                                <button class="btn-action btn-edit" onclick="editAdmin(2)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteAdmin(2)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Security Tab -->
                <div id="security-tab" class="tab-content">
                    <h3>Security Events</h3>
                    
                    <!-- Security Statistics -->
                    <div id="security-stats-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <!-- Stats will be loaded here -->
                        </div>
                        
                    <!-- Security Events List -->
                    <div id="security-events-container">
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Loading security events...</p>
                            </div>
                    </div>
                </div>

                <!-- Activity Log Tab -->
                <div id="activity-tab" class="tab-content">
                    <h3>Activity Log</h3>
                    <div class="activity-filters">
                        <select id="activityFilter">
                            <option value="all">All Activities</option>
                            <option value="login">Login/Logout</option>
                            <option value="user">User Management</option>
                            <option value="system">System Changes</option>
                        </select>
                        <input type="date" id="activityDate" placeholder="Filter by date">
                    </div>
                    
                    <div class="activity-list" id="activity-list-container">
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Loading activity logs...</p>
                            </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-cancel" onclick="closeAccountManagement()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div id="addAdminModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Admin</h2>
                <button class="modal-close" onclick="closeAddAdminModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="addAdminForm">
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" id="adminName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address:</label>
                        <input type="email" id="adminEmail" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role:</label>
                        <select id="adminRole" required>
                            <option value="super_admin">Super Administrator</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" id="adminPassword" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" id="adminPasswordConfirm" required>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-cancel" onclick="closeAddAdminModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-modern btn-save" onclick="saveNewAdmin()">
                    <i class="fas fa-save"></i> Add Admin
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div id="editAdminModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit Admin Account</h2>
                <button class="modal-close" onclick="closeEditAdminModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="editAdminForm">
                    <input type="hidden" id="editAdminId">
                    
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" id="editAdminName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address:</label>
                        <input type="email" id="editAdminEmail" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role:</label>
                        <select id="editAdminRole" required>
                            <option value="super_admin">Super Administrator</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password (leave blank to keep current):</label>
                        <input type="password" id="editAdminPassword" placeholder="Enter new password or leave blank">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password:</label>
                        <input type="password" id="editAdminPasswordConfirm" placeholder="Confirm new password">
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-cancel" onclick="closeEditAdminModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-modern btn-save" onclick="saveEditAdmin()">
                    <i class="fas fa-save"></i> Update Admin
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal logout-modal" style="display: none;">
        <div class="modal-content logout-modal-content">
            <div class="modal-header logout-header">
                <div class="logout-icon-container">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h2>Confirm Logout</h2>
                <button class="modal-close" onclick="closeLogoutModal()">&times;</button>
            </div>
            
            <div class="modal-body logout-body">
                <div class="logout-warning">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Are you sure you want to logout?</h3>
                    <p>You will be redirected to the login page and will need to authenticate again to access the admin dashboard.</p>
                </div>
                
                <div class="logout-details">
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span>Session will be terminated</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-lock"></i>
                        <span>Re-authentication required</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer logout-footer">
                <button type="button" class="btn-modern btn-cancel" onclick="closeLogoutModal()">
                    <i class="fas fa-arrow-left"></i> Stay Logged In
                </button>
                <button type="button" class="btn-modern btn-logout" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout Now
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Settings Modal -->
    <div id="notificationSettingsModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-bell"></i> Notification Settings</h2>
                <button class="modal-close" onclick="closeNotificationSettings()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="settings-tabs">
                    <div class="tab active" onclick="switchSettingsTab('current')">
                        <i class="fas fa-info-circle"></i> Current Settings
                    </div>
                    <div class="tab" onclick="switchSettingsTab('channels')">
                        <i class="fas fa-broadcast-tower"></i> Channels
                    </div>
                    <div class="tab" onclick="switchSettingsTab('types')">
                        <i class="fas fa-bell"></i> Notification Types
                </div>
                    <div class="tab" onclick="switchSettingsTab('templates')">
                        <i class="fas fa-file-alt"></i> Templates
                            </div>
                        </div>
                        
                <!-- Current Settings Tab -->
                <div id="current-tab" class="tab-content active">
                    <h3><i class="fas fa-info-circle"></i> Current Notification Configuration</h3>
                    <p style="color: #666; margin-bottom: 20px;">This section shows what notification settings are currently active in the system.</p>
                    
                    <div id="current-settings-container">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #5D4037;"></i>
                            <p>Loading current settings...</p>
                        </div>
                    </div>
                </div>

                <!-- Templates Tab -->
                <div id="templates-tab" class="tab-content">
                    <h3>Notification Templates</h3>
                    <p style="color: #666; margin-bottom: 20px; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px;">
                        <i class="fas fa-info-circle"></i> Edit notification message templates. Use variables like {boarder_name}, {room_name}, {amount}, etc. in curly braces.
                    </p>
                    
                    <div class="template-section" id="templates-container">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #5D4037;"></i>
                            <p>Loading notification templates...</p>
                        </div>
                    </div>
                </div>

                <!-- Channels Tab -->
                <div id="channels-tab" class="tab-content">
                    <h3>Notification Channels</h3>
                    <p style="color: #666; margin-bottom: 20px;">Notification delivery methods currently configured in the system.</p>
                    
                    <div id="channels-container">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #5D4037;"></i>
                            <p>Loading channel information...</p>
                            </div>
                            </div>
                        </div>
                        
                <!-- Notification Types Tab -->
                <div id="types-tab" class="tab-content">
                    <h3>Notification Types</h3>
                    <p style="color: #666; margin-bottom: 20px;">Types of notifications currently implemented and active in the system.</p>
                    
                    <div id="types-container">
                        <div style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #5D4037;"></i>
                            <p>Loading notification types...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-cancel" onclick="closeNotificationSettings()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" id="save-notification-settings-btn" class="btn-modern btn-save" onclick="saveNotificationSettings()" disabled>
                    <i class="fas fa-save"></i> Save Templates
                </button>
            </div>
        </div>
    </div>

    <style>
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            flex-wrap: nowrap;
            scroll-behavior: smooth;
        }
        
        .settings-tabs .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: fit-content;
        }
        
        .settings-tabs .tab.active {
            border-bottom-color: #5D4037;
            color: #5D4037;
            background-color: rgba(93, 64, 55, 0.1);
            font-weight: 600;
        }
        
        .settings-grid {
            display: grid;
            gap: 20px;
        }
        
        .setting-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #D2B48C;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .setting-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        
        .setting-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .template-section {
            display: grid;
            gap: 20px;
        }
        
        .template-item {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .template-item h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .template-item textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            margin-top: 10px;
        }
        
        .channel-settings {
            display: grid;
            gap: 20px;
        }
        
        .channel-item {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .channel-item h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Modern Button Styles */
        .btn-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            outline: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover:before {
            left: 100%;
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        
        .btn-save {
            background: linear-gradient(135deg, #D2B48C, #CD853F);
            color: white;
            box-shadow: 0 4px 15px rgba(210, 180, 140, 0.3);
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, #CD853F, #B8860B);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(210, 180, 140, 0.4);
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-logout:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        /* Professional Logout Modal Styles */
        .logout-modal {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            transition: opacity 0.3s ease;
            opacity: 0;
        }

        .logout-modal.show {
            opacity: 1;
        }

        .logout-modal-content {
            max-width: 480px;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .logout-header {
            background: linear-gradient(135deg, #D2B48C, #CD853F);
            color: white;
            padding: 25px 30px;
            border-radius: 16px 16px 0 0;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .logout-icon-container {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .logout-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .logout-body {
            padding: 30px;
            background: white;
        }

        .logout-warning {
            text-align: center;
            margin-bottom: 25px;
        }

        .warning-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
            animation: warningPulse 1.5s ease-in-out infinite, warningShake 3s ease-in-out infinite;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        @keyframes warningPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
        }

        @keyframes warningShake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-2px);
            }
            75% {
                transform: translateX(2px);
            }
        }

        .logout-warning h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }

        .logout-warning p {
            margin: 0;
            color: #666;
            line-height: 1.5;
        }

        .logout-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #D2B48C;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            color: #555;
            font-size: 14px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item i {
            color: #D2B48C;
            width: 16px;
            text-align: center;
        }

        .logout-footer {
            background: #f8f9fa;
            padding: 20px 30px;
            border-radius: 0 0 16px 16px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .logout-footer .btn-modern {
            min-width: 140px;
            font-weight: 600;
        }

        /* Enhanced button animations */
        .logout-footer .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .logout-footer .btn-cancel:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        
        .btn-modern:active {
            transform: translateY(0);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 20px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
        }

        /* Account Management Styles */
        .account-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            flex-wrap: nowrap;
            scroll-behavior: smooth;
        }
        
        .account-tabs .tab {
            padding: 12px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: fit-content;
        }
        
        .account-tabs .tab.active {
            border-bottom-color: #5D4037;
            color: #5D4037;
            background-color: rgba(93, 64, 55, 0.1);
            font-weight: 600;
        }
        
        .tab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-box input {
            padding: 8px 35px 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 250px;
            font-size: 14px;
        }
        
        .search-box i {
            position: absolute;
            right: 12px;
            color: #999;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-add:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .admin-list {
            display: grid;
            gap: 15px;
        }
        
        .admin-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #D2B48C, #CD853F);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .admin-info {
            flex: 1;
        }
        
        .admin-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        
        .admin-info p {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .admin-role {
            background: #f8f9fa;
            color: #666;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .admin-status {
            margin-right: 15px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .admin-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #D2B48C;
            color: white;
        }
        
        .btn-edit:hover {
            background: #CD853F;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-toggle {
            background: #ffc107;
            color: white;
        }
        
        .btn-toggle:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        
        .security-settings {
            display: grid;
            gap: 15px;
        }
        
        .security-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        
        .security-info h4 {
            margin: 0 0 5px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .activity-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .activity-filters select,
        .activity-filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .activity-list {
            display: grid;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #D2B48C;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        
        .activity-content p {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 13px;
        }
        
        .activity-time {
            color: #999;
            font-size: 12px;
        }
    </style>

    <script>
        // Check admin session on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAdminSession();
        });

        function checkAdminSession() {
            fetch('../check_admin_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Not logged in or session expired
                        window.location.href = data.redirect || 'admin_login.php';
                    } else {
                        // Update admin info in UI
                        updateAdminInfo(data.admin);
                    }
                })
                .catch(error => {
                    console.error('Session check error:', error);
                    window.location.href = 'admin_login.php';
                });
        }

        function updateAdminInfo(admin) {
            // Update any admin info displays in the dashboard
            console.log('Logged in as:', admin.name);
        }

        function logout() {
            const modal = document.getElementById('logoutModal');
            modal.style.display = 'block';
            // Add fade-in animation
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function confirmLogout() {
            const logoutBtn = event.target;
            const originalText = logoutBtn.innerHTML;
            
            // Show loading state
            logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
            logoutBtn.disabled = true;
            
            // Add a small delay for better UX
            setTimeout(() => {
                window.location.href = '../admin_logout.php';
            }, 1000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const logoutModal = document.getElementById('logoutModal');
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        }

        function switchSettingsTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.settings-tabs .tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            const targetTabContent = document.getElementById(tabName + '-tab');
            if (targetTabContent) {
                targetTabContent.style.display = 'block';
                targetTabContent.classList.add('active');
            }
            
            // Add active class to the correct tab (find by onclick attribute)
            const targetTab = document.querySelector(`.settings-tabs .tab[onclick*="${tabName}"]`);
            if (targetTab) {
                targetTab.classList.add('active');
                // Scroll tab into view (centered) on mobile
                const tabsContainer = document.querySelector('.settings-tabs');
                if (tabsContainer) {
                    const containerWidth = tabsContainer.offsetWidth;
                    const tabLeft = targetTab.offsetLeft;
                    const tabWidth = targetTab.offsetWidth;
                    const scrollPosition = tabLeft - (containerWidth / 2) + (tabWidth / 2);
                    tabsContainer.scrollTo({
                        left: scrollPosition,
                        behavior: 'smooth'
                    });
                }
            }
            
            // Enable/Disable Save Settings button based on active tab
            const saveButton = document.getElementById('save-notification-settings-btn');
            if (saveButton) {
                if (tabName === 'templates') {
                    // Enable button only on templates tab
                    saveButton.disabled = false;
                    saveButton.style.opacity = '1';
                    saveButton.style.cursor = 'pointer';
                } else {
                    // Disable button on all other tabs (current, channels, types)
                    saveButton.disabled = true;
                    saveButton.style.opacity = '0.5';
                    saveButton.style.cursor = 'not-allowed';
                }
            }
            
            // Reload data if needed (for templates tab)
            if (tabName === 'templates') {
                loadNotificationSettings();
            }
        }

        // Account Management Functions
        function openAccountManagement() {
            document.getElementById('accountManagementModal').style.display = 'block';
            showAccountLoading();
            loadAdminAccounts();
        }

        function showAccountLoading() {
            const loadingIndicator = document.getElementById('accountLoadingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'flex';
            }
        }

        function hideAccountLoading() {
            const loadingIndicator = document.getElementById('accountLoadingIndicator');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
        }

        // Force refresh admin accounts (used after add/edit/delete operations)
        function refreshAdminAccounts() {
            adminAccountsLoaded = false; // Reset the loaded state
            currentAdmins = []; // Clear current data
            loadAdminAccounts(); // Reload from database
        }

        function closeAccountManagement() {
            document.getElementById('accountManagementModal').style.display = 'none';
        }

        function switchAccountTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('#accountManagementModal .tab-content').forEach(tab => {
                tab.style.display = 'none';
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('#accountManagementModal .account-tabs .tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').style.display = 'block';
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to the correct tab
            const targetTab = document.querySelector(`#accountManagementModal .account-tabs .tab[onclick="switchAccountTab('${tabName}')"]`);
            if (targetTab) {
                targetTab.classList.add('active');
                // Scroll tab into view (centered) on mobile
                const tabsContainer = document.querySelector('#accountManagementModal .account-tabs');
                if (tabsContainer) {
                    const containerWidth = tabsContainer.offsetWidth;
                    const tabLeft = targetTab.offsetLeft;
                    const tabWidth = targetTab.offsetWidth;
                    const scrollPosition = tabLeft - (containerWidth / 2) + (tabWidth / 2);
                    tabsContainer.scrollTo({
                        left: scrollPosition,
                        behavior: 'smooth'
                    });
                }
            }
            
            // Load data for specific tabs
            if (tabName === 'activity') {
                loadActivityLogs();
            } else if (tabName === 'security') {
                loadSecurityEvents();
            }
        }

        // Add Admin Functions
        function openAddAdminModal() {
            document.getElementById('addAdminModal').style.display = 'block';
        }

        function closeAddAdminModal() {
            document.getElementById('addAdminModal').style.display = 'none';
            document.getElementById('addAdminForm').reset();
        }

        function saveNewAdmin() {
            const name = document.getElementById('adminName').value;
            const email = document.getElementById('adminEmail').value;
            const role = document.getElementById('adminRole').value;
            const password = document.getElementById('adminPassword').value;
            const confirmPassword = document.getElementById('adminPasswordConfirm').value;

            if (password !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }

            if (name && email && role && password) {
                // Send data to backend
                const formData = new FormData();
                formData.append('name', name);
                formData.append('email', email);
                formData.append('role', role);
                formData.append('password', password);

                fetch('../add_admin_mysqli.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Admin account created successfully!', 'success');
                        closeAddAdminModal();
                        refreshAdminAccounts(); // Refresh the admin list
                    } else {
                        showNotification(data.message || 'Failed to create admin account', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error creating admin account', 'error');
                    console.error('Error:', error);
                });
            } else {
                showNotification('Please fill in all fields!', 'error');
            }
        }

        // Global variable to store current admins
        let currentAdmins = [];
        let adminAccountsLoaded = false; // Track if admin accounts have been loaded

        // Load admin accounts from database
        function loadAdminAccounts() {
            // Only load if not already loaded
            if (adminAccountsLoaded && currentAdmins.length > 0) {
                displayAdminAccounts(currentAdmins);
                hideAccountLoading();
                return;
            }

            fetch('../get_admin_accounts_mysqli.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentAdmins = data.admins; // Store for edit functionality
                        adminAccountsLoaded = true; // Mark as loaded
                        displayAdminAccounts(data.admins);
                    } else {
                        showNotification('Failed to load admin accounts: ' + (data.message || 'Unknown error'), 'error');
                        console.error('Admin accounts error:', data);
                    }
                })
                .catch(error => {
                    showNotification('Error loading admin accounts', 'error');
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Hide loading indicator after request completes (success or error)
                    setTimeout(() => {
                        hideAccountLoading();
                    }, 500); // Small delay to show the loading animation
                });
        }

        // Display admin accounts in the UI
        function displayAdminAccounts(admins) {
            const adminList = document.querySelector('.admin-list');
            adminList.innerHTML = '';

            admins.forEach(admin => {
                const adminItem = document.createElement('div');
                adminItem.className = 'admin-item';
                adminItem.innerHTML = `
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-info">
                        <h4>${admin.name}</h4>
                        <p>${admin.email}</p>
                        <span class="admin-role">${admin.role.replace('_', ' ').toUpperCase()}</span>
                    </div>
                    <div class="admin-status">
                        <span class="status-badge ${admin.status}">${admin.status.toUpperCase()}</span>
                    </div>
                    <div class="admin-actions">
                        <button class="btn-action btn-edit" onclick="editAdmin(${admin.id})" title="Edit Admin">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-action btn-toggle" onclick="toggleAdminStatus(${admin.id}, '${admin.status}')" title="Toggle Status">
                            <i class="fas fa-${admin.status === 'active' ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteAdmin(${admin.id})" title="Delete Admin">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                adminList.appendChild(adminItem);
            });
        }

        // Edit Admin Functions
        function editAdmin(adminId) {
            // Find the admin data
            const adminData = currentAdmins.find(admin => admin.id == adminId);
            if (!adminData) {
                showNotification('Admin data not found', 'error');
                return;
            }
            
            // Populate the edit form
            document.getElementById('editAdminId').value = adminData.id;
            document.getElementById('editAdminName').value = adminData.name;
            document.getElementById('editAdminEmail').value = adminData.email;
            document.getElementById('editAdminRole').value = adminData.role;
            document.getElementById('editAdminPassword').value = '';
            document.getElementById('editAdminPasswordConfirm').value = '';
            
            // Show the edit modal
            document.getElementById('editAdminModal').style.display = 'block';
        }

        function closeEditAdminModal() {
            document.getElementById('editAdminModal').style.display = 'none';
            document.getElementById('editAdminForm').reset();
        }

        function saveEditAdmin() {
            const adminId = document.getElementById('editAdminId').value;
            const name = document.getElementById('editAdminName').value;
            const email = document.getElementById('editAdminEmail').value;
            const role = document.getElementById('editAdminRole').value;
            const password = document.getElementById('editAdminPassword').value;
            const confirmPassword = document.getElementById('editAdminPasswordConfirm').value;

            // Validation
            if (!name || !email) {
                showNotification('Name and email are required!', 'error');
                return;
            }

            if (password && password !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }

            if (password && password.length < 6) {
                showNotification('Password must be at least 6 characters!', 'error');
                return;
            }

            // Send data to backend
            const formData = new FormData();
            formData.append('admin_id', adminId);
            formData.append('name', name);
            formData.append('email', email);
            formData.append('role', role);
            if (password) {
                formData.append('password', password);
            }

            fetch('../update_admin_mysqli.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Admin account updated successfully!', 'success');
                    closeEditAdminModal();
                    refreshAdminAccounts(); // Refresh the admin list
                    
                    // Reload activity logs and security events if those tabs are active
                    const accountTab = document.querySelector('.account-tab.active');
                    if (accountTab) {
                        const tabName = accountTab.getAttribute('data-tab');
                        if (tabName === 'activity') {
                            loadActivityLogs();
                        } else if (tabName === 'security') {
                            loadSecurityEvents();
                        }
                    }
                } else {
                    showNotification(data.message || 'Failed to update admin account', 'error');
                }
            })
            .catch(error => {
                showNotification('Error updating admin account', 'error');
                console.error('Error:', error);
            });
        }

        function deleteAdmin(adminId) {
            if (confirm('Are you sure you want to delete this admin account?')) {
                const formData = new FormData();
                formData.append('admin_id', adminId);

                fetch('../delete_admin_mysqli.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Admin account deleted successfully!', 'success');
                        refreshAdminAccounts(); // Refresh the admin list
                    } else {
                        showNotification(data.message || 'Failed to delete admin account', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error deleting admin account', 'error');
                    console.error('Error:', error);
                });
            }
        }

        // Load Activity Logs
        function loadActivityLogs() {
            const filter = document.getElementById('activityFilter')?.value || 'all';
            const date = document.getElementById('activityDate')?.value || null;
            const container = document.getElementById('activity-list-container');
            
            if (!container) return;
            
            // Show loading
            container.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Loading activity logs...</p>
                </div>
            `;
            
            let url = '../get_activity_logs.php?filter=' + filter + '&limit=100';
            if (date) {
                url += '&date=' + date;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivityLogs(data.activities);
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 2rem; color: #f44336;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Error loading activity logs: ${data.message || 'Unknown error'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading activity logs:', error);
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #f44336;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Error loading activity logs. Please try again.</p>
                        </div>
                    `;
                });
        }
        
        // Display Activity Logs
        function displayActivityLogs(activities) {
            const container = document.getElementById('activity-list-container');
            if (!container) return;
            
            if (!activities || activities.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No activity logs found</p>
                        <p style="font-size: 0.85rem; color: #999; margin-top: 0.5rem;">Activities will appear here as they occur</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            activities.forEach(activity => {
                const iconColor = activity.type === 'login' ? '#4CAF50' : 
                                 activity.type === 'user' ? '#2196F3' : 
                                 activity.type === 'system' ? '#FF9800' : '#9E9E9E';
                
                // Calculate time ago on client side if not provided
                let timeAgo = activity.time_ago || 'Unknown time';
                if (activity.time && !activity.time_ago) {
                    timeAgo = getTimeAgo(activity.time);
                }
                
                html += `
                    <div class="activity-item" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid ${iconColor}; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div class="activity-icon" style="width: 40px; height: 40px; border-radius: 50%; background: ${iconColor}; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="fas ${activity.icon || 'fa-circle'}"></i>
                        </div>
                        <div class="activity-content" style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0; font-size: 16px; color: #333; font-weight: 600;">${activity.title || 'Activity'}</h4>
                            <p style="margin: 0 0 5px 0; color: #666; font-size: 14px; line-height: 1.4;">${activity.description || 'No description'}</p>
                            ${activity.admin_name ? `<p style="margin: 0 0 5px 0; color: #999; font-size: 12px;"><i class="fas fa-user"></i> ${activity.admin_name}${activity.admin_email ? ' (' + activity.admin_email + ')' : ''}</p>` : ''}
                            ${activity.user_name ? `<p style="margin: 0 0 5px 0; color: #999; font-size: 12px;"><i class="fas fa-user"></i> ${activity.user_name}${activity.user_email ? ' (' + activity.user_email + ')' : ''}</p>` : ''}
                            <span class="activity-time" style="color: #999; font-size: 12px;"><i class="fas fa-clock"></i> ${timeAgo}</span>
                            ${activity.raw_time ? `<span style="color: #ccc; font-size: 10px; display: block; margin-top: 2px;">${activity.raw_time}</span>` : ''}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // JavaScript time ago function (for client-side calculation)
        function getTimeAgo(datetime) {
            if (!datetime || datetime === '0000-00-00 00:00:00') {
                return 'Unknown time';
            }
            
            try {
                const date = new Date(datetime);
                if (isNaN(date.getTime())) {
                    return 'Invalid time';
                }
                
                const now = new Date();
                const diff = Math.floor((now - date) / 1000); // Difference in seconds
                
                if (diff < 0) {
                    return 'Just now';
                }
                
                if (diff < 60) {
                    return 'Just now';
                } else if (diff < 3600) {
                    const minutes = Math.floor(diff / 60);
                    return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
                } else if (diff < 86400) {
                    const hours = Math.floor(diff / 3600);
                    return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
                } else if (diff < 2592000) {
                    const days = Math.floor(diff / 86400);
                    return days + ' day' + (days > 1 ? 's' : '') + ' ago';
                } else if (diff < 31536000) {
                    const months = Math.floor(diff / 2592000);
                    return months + ' month' + (months > 1 ? 's' : '') + ' ago';
                } else {
                    const years = Math.floor(diff / 31536000);
                    return years + ' year' + (years > 1 ? 's' : '') + ' ago';
                }
            } catch (e) {
                console.error('Error calculating time ago:', e);
                return 'Unknown time';
            }
        }
        
        // Load Security Events
        function loadSecurityEvents() {
            const container = document.getElementById('security-events-container');
            const statsContainer = document.getElementById('security-stats-container');
            
            if (!container) return;
            
            // Show loading
            container.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Loading security events...</p>
                </div>
            `;
            
            fetch('../get_security_events.php?limit=100')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySecurityStats(data.stats);
                        displaySecurityEvents(data.events);
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 2rem; color: #f44336;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Error loading security events: ${data.message || 'Unknown error'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading security events:', error);
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #f44336;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Error loading security events. Please try again.</p>
                        </div>
                    `;
                });
        }
        
        // Display Security Statistics
        function displaySecurityStats(stats) {
            const container = document.getElementById('security-stats-container');
            if (!container) return;
            
            if (!stats) {
                stats = {
                    total_logins: 0,
                    recent_logins: 0,
                    password_changes: 0,
                    email_changes: 0,
                    status_changes: 0
                };
            }
            
            const statsData = [
                { label: 'Total Logins', value: stats.total_logins || 0, icon: 'fa-sign-in-alt', color: '#4CAF50' },
                { label: 'Recent Logins (7d)', value: stats.recent_logins || 0, icon: 'fa-clock', color: '#2196F3' },
                { label: 'Password Changes', value: stats.password_changes || 0, icon: 'fa-lock', color: '#FF9800' },
                { label: 'Email Changes', value: stats.email_changes || 0, icon: 'fa-envelope', color: '#9C27B0' },
                { label: 'Status Changes', value: stats.status_changes || 0, icon: 'fa-user-cog', color: '#F44336' }
            ];
            
            let html = '';
            statsData.forEach(stat => {
                html += `
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; transition: transform 0.2s ease;">
                        <div style="font-size: 2rem; color: ${stat.color}; margin-bottom: 0.5rem;">
                            <i class="fas ${stat.icon}"></i>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 0.25rem;">
                            ${stat.value}
                        </div>
                        <div style="font-size: 0.9rem; color: #666;">
                            ${stat.label}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Display Security Events
        function displaySecurityEvents(events) {
            const container = document.getElementById('security-events-container');
            if (!container) return;
            
            if (!events || events.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-shield-alt" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No security events found</p>
                        <p style="font-size: 0.85rem; color: #999; margin-top: 0.5rem;">Security events will appear here as they occur</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div style="display: grid; gap: 10px;">';
            events.forEach(event => {
                const severityColor = event.severity === 'danger' ? '#F44336' :
                                     event.severity === 'warning' ? '#FF9800' :
                                     event.severity === 'info' ? '#2196F3' : '#9E9E9E';
                
                // Calculate time ago on client side if not provided
                let timeAgo = event.time_ago || 'Unknown time';
                if (event.time && !event.time_ago) {
                    timeAgo = getTimeAgo(event.time);
                }
                
                html += `
                    <div class="security-event-item" style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid ${severityColor}; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div class="security-icon" style="width: 40px; height: 40px; border-radius: 50%; background: ${severityColor}; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="fas ${event.icon || 'fa-circle'}"></i>
                        </div>
                        <div class="security-content" style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0; font-size: 16px; color: #333; font-weight: 600;">${event.title || 'Security Event'}</h4>
                            <p style="margin: 0 0 5px 0; color: #666; font-size: 14px; line-height: 1.4;">${event.description || 'No description'}</p>
                            ${event.admin_name ? `<p style="margin: 0 0 5px 0; color: #999; font-size: 12px;"><i class="fas fa-user-shield"></i> Admin: ${event.admin_name}${event.admin_email ? ' (' + event.admin_email + ')' : ''}</p>` : ''}
                            ${event.user_name ? `<p style="margin: 0 0 5px 0; color: #999; font-size: 12px;"><i class="fas fa-user"></i> User: ${event.user_name}${event.user_email ? ' (' + event.user_email + ')' : ''}</p>` : ''}
                            <span class="security-time" style="color: #999; font-size: 12px;"><i class="fas fa-clock"></i> ${timeAgo}</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        // Add event listeners for activity log filters
        document.addEventListener('DOMContentLoaded', function() {
            const activityFilter = document.getElementById('activityFilter');
            const activityDate = document.getElementById('activityDate');
            
            if (activityFilter) {
                activityFilter.addEventListener('change', loadActivityLogs);
            }
            
            if (activityDate) {
                activityDate.addEventListener('change', loadActivityLogs);
            }
        });

        function toggleAdminStatus(adminId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            
            if (confirm(`Are you sure you want to ${action} this admin account?`)) {
                const formData = new FormData();
                formData.append('admin_id', adminId);
                formData.append('status', newStatus);

                fetch('../toggle_admin_status_mysqli.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(`Admin account ${action}d successfully!`, 'success');
                        refreshAdminAccounts(); // Refresh the admin list
                        
                        // Reload activity logs and security events if those tabs are active
                        const accountTab = document.querySelector('.account-tab.active');
                        if (accountTab) {
                            const tabName = accountTab.getAttribute('data-tab');
                            if (tabName === 'activity') {
                                loadActivityLogs();
                            } else if (tabName === 'security') {
                                loadSecurityEvents();
                            }
                        }
                    } else {
                        showNotification(data.message || `Failed to ${action} admin account`, 'error');
                    }
                })
                .catch(error => {
                    showNotification(`Error ${action}ing admin account`, 'error');
                    console.error('Error:', error);
                });
            }
        }

        // Search and filter functionality
        function filterAdmins() {
            const searchTerm = document.getElementById('adminSearch').value.toLowerCase();
            const adminItems = document.querySelectorAll('.admin-item');
            
            adminItems.forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                const email = item.querySelector('p').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Security Functions
        function editPasswordPolicy() {
            showNotification('Password policy configuration coming soon!', 'info');
        }

        function editSessionTimeout() {
            showNotification('Session timeout configuration coming soon!', 'info');
        }

        function editIPRestrictions() {
            showNotification('IP restrictions configuration coming soon!', 'info');
        }

        function edit2FA() {
            showNotification('Two-factor authentication configuration coming soon!', 'info');
        }
    </script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>


