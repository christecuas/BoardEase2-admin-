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
    $sql = "SELECT id, role, first_name, middle_name, last_name, birth_date, phone, address, email, 
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
                "full_name" => trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']),
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
                   r.role, r.first_name, r.middle_name, r.last_name, r.phone, r.email, r.created_at,
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
                "full_name" => trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']),
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
                   r.role, r.first_name, r.middle_name, r.last_name, r.phone, r.email, r.created_at,
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
                "full_name" => trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']),
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoardEase Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #dee2e6 100%);
            color: #333;
            display: flex;
            min-height: 100vh;
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
            background: 
                radial-gradient(circle at 20% 80%, rgba(141, 110, 99, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(169, 122, 80, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(210, 180, 140, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #8D6E63 0%, #A97A50 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255,255,255,0.1);
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
            margin-left: 250px;
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            animation: fadeInUp 0.6s ease-out;
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
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
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
            background: linear-gradient(90deg, #8D6E63, #A97A50, #D2B48C);
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
            color: #333;
            margin-bottom: 0.5rem;
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
            color: #666;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: cardSlideIn 0.6s ease-out;
        }

        .stat-card::before {
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

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin: 0 auto 1rem;
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
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
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
            color: #666;
            font-size: 1rem;
            font-weight: 500;
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
            background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .priority-section h2 {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .pending-approvals {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            animation: cardSlideIn 0.6s ease-out;
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
            padding: 1.5rem;
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
            font-size: 1.3rem;
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .approval-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .approval-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .approval-actions {
            display: flex;
            gap: 0.5rem;
        }

        .approval-details {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.75rem;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .verification-verified {
            background: #d4edda;
            color: #155724;
        }

        .verification-pending {
            background: #fff3cd;
            color: #856404;
        }

        .verification-failed {
            background: #f8d7da;
            color: #721c24;
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
            color: #999;
            margin-top: 0.5rem;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 0;
            background: #f8f9fa;
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
            border-bottom-color: #8D6E63;
            color: #8D6E63;
            font-weight: 600;
            background: white;
        }

        .tab-content {
            display: none;
            padding: 0;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content .card-content {
            padding: 1.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .pending-approvals {
                grid-template-columns: 1fr;
            }

            .content-header {
                padding: 1rem;
            }

            .content-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #8D6E63;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
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
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
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
        }

        .filter-btn.active {
            background: #8D6E63;
            color: white;
        }

        .filter-btn:hover {
            background: #8D6E63;
            color: white;
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
        }

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
        }

        .owner-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid #8D6E63;
        }

        .owner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
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
        }

        .boarding-house-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .house-info {
            flex: 1;
        }

        .house-info strong {
            display: block;
            margin-bottom: 0.25rem;
            color: #333;
        }

        .house-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

         .house-status {
             display: flex;
             align-items: center;
             gap: 1rem;
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
             background: white;
             border-radius: 15px;
             box-shadow: 0 4px 15px rgba(0,0,0,0.1);
             overflow: hidden;
         }

         .analytics-card.full-width {
             grid-column: 1 / -1;
         }

         .analytics-header {
             background: linear-gradient(135deg, #8D6E63 0%, #A97A50 100%);
             color: white;
             padding: 1.5rem;
             display: flex;
             justify-content: space-between;
             align-items: center;
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
             padding: 1.5rem;
         }

         .chart-container {
             margin-bottom: 1.5rem;
             text-align: center;
         }

         .analytics-metrics {
             display: grid;
             grid-template-columns: repeat(3, 1fr);
             gap: 1rem;
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
             grid-template-columns: repeat(3, 1fr);
             gap: 2rem;
         }

         .analytics-overview-item {
             display: flex;
             flex-direction: column;
             align-items: center;
             text-align: center;
             padding: 1.5rem;
             background: #f8f9fa;
             border-radius: 12px;
             transition: transform 0.3s ease, box-shadow 0.3s ease;
         }

         .analytics-overview-item:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 25px rgba(0,0,0,0.15);
         }

         .analytics-overview-chart {
             width: 100%;
             height: 200px;
             margin-bottom: 1rem;
         }

         .analytics-overview-info h4 {
             font-size: 1.2rem;
             font-weight: 600;
             margin-bottom: 0.5rem;
             color: #333;
         }

         .analytics-overview-info p {
             font-size: 0.9rem;
             color: #666;
             margin-bottom: 0.75rem;
         }

         .analytics-overview-info .analytics-trend {
             font-size: 1rem;
             font-weight: 600;
             padding: 0.5rem 1rem;
             border-radius: 20px;
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

         /* Document Verification Modal Styling */
         .document-modal {
             background-color: #f8f9fa;
             margin: 2% auto;
             padding: 0;
             border: none;
             border-radius: 15px;
             width: 90%;
             max-width: 800px;
             max-height: 90vh;
             overflow-y: auto;
             box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
             display: flex;
             flex-direction: column;
         }

         .document-modal .modal-body {
             padding: 2rem;
             overflow-y: auto;
             flex: 1;
         }

         .document-modal .modal-content {
             margin: 0;
             width: 100%;
             max-width: none;
         }

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
             padding: 0.4rem 0.8rem;
             font-size: 0.8rem;
             min-width: auto;
             white-space: nowrap;
         }

         .approval-actions .action-btn i {
             font-size: 0.75rem;
             margin-right: 0.25rem;
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
        }

        /* Analytics Loading Styles */
        .analytics-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 2rem 0;
        }

        .analytics-loading .loading-spinner {
            flex-direction: column;
            gap: 1rem;
            font-size: 1.1rem;
            color: #8D6E63;
        }

        .analytics-loading .loading-spinner i {
            font-size: 2rem;
            color: #8D6E63;
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
             background: #6D4C41;
             border-color: rgba(255, 255, 255, 0.5);
         }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-shield-alt"></i> BoardEase Admin</h1>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" onclick="showSection('dashboard')">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="#" class="nav-item" onclick="showSection('user-management')">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="#" class="nav-item" onclick="showSection('notifications')">
                <i class="fas fa-bell"></i>
                Notifications
            </a>
             <a href="#" class="nav-item" onclick="showSection('boarding-houses')">
                 <i class="fas fa-home"></i>
                 Boarding Houses
             </a>
             <a href="#" class="nav-item" onclick="showSection('analytics')">
                 <i class="fas fa-chart-line"></i>
                 Analytics
             </a>
             <a href="#" class="nav-item" onclick="showSection('reports')">
                 <i class="fas fa-chart-bar"></i>
                 Reports
             </a>
            <a href="#" class="nav-item" onclick="showSection('settings')">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <a href="#" class="nav-item" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <div class="content-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening with your BoardEase platform.</p>
            </div>
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8D6E63, #A97A50);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_users_count; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_bh_count; ?></h3>
                    <p>Boarding Houses</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending Approvals</p>
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
        <div class="priority-section" style="margin-top: 3rem;">
            <h2><i class="fas fa-exclamation-circle"></i> Pending User Approvals - Action Required</h2>
            <div class="pending-approvals">
                <?php if (empty($pending_registrations)): ?>
                    <div class="no-data">No pending registrations</div>
                <?php else: ?>
                    <?php foreach ($pending_registrations as $registration): ?>
                        <?php 
                        $initials = strtoupper(substr($registration['first_name'], 0, 1) . substr($registration['last_name'], 0, 1));
                        $roleText = $registration['role'] === 'BH Owner' ? 'Owner Registration' : 'Boarder Registration';
                        $timeAgo = '';
                        if ($registration['created_at']) {
                            $now = new DateTime();
                            $created = new DateTime($registration['created_at']);
                            $diff = $now->diff($created);
                            if ($diff->days > 0) {
                                $timeAgo = $diff->days . ' days ago';
                            } elseif ($diff->h > 0) {
                                $timeAgo = $diff->h . ' hours ago';
                            } elseif ($diff->i > 0) {
                                $timeAgo = $diff->i . ' minutes ago';
                            } else {
                                $timeAgo = 'Just now';
                            }
                        }
                        ?>
                        <div class="approval-card" data-registration-id="<?php echo $registration['id']; ?>">
                    <div class="approval-header">
                        <div class="approval-user">
                                    <div class="user-avatar"><?php echo $initials; ?></div>
                            <div>
                                        <strong><?php echo htmlspecialchars($registration['full_name']); ?></strong><br>
                                        <small><?php echo $roleText; ?></small>
                            </div>
                        </div>
                        <div class="approval-actions">
                                    <button class="action-btn" onclick="viewDocuments(<?php echo $registration['id']; ?>)">
                                <i class="fas fa-id-card"></i> View ID
                            </button>
                                    <button class="action-btn success" onclick="approveUser(<?php echo $registration['id']; ?>)">
                                <i class="fas fa-check"></i> Approve
                            </button>
                                    <button class="action-btn danger" onclick="rejectUser(<?php echo $registration['id']; ?>)">
                                <i class="fas fa-times"></i> Reject
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
                     <div class="recent-activity">
                         <div class="activity-item">
                             <div class="activity-icon" style="background: #d4edda;">
                                 <i class="fas fa-check" style="color: #28a745;"></i>
                             </div>
                             <div class="activity-content">
                                 <h4>New boarding house registered</h4>
                                 <p>Sunset Boarding House by John Doe</p>
                             </div>
                             <div class="activity-time">2 min ago</div>
                         </div>
                         <div class="activity-item">
                             <div class="activity-icon" style="background: #fff3cd;">
                                 <i class="fas fa-exclamation" style="color: #ffc107;"></i>
                             </div>
                             <div class="activity-content">
                                 <h4>Payment dispute reported</h4>
                                 <p>Room 101 - Mike Johnson vs Jane Smith</p>
                             </div>
                             <div class="activity-time">15 min ago</div>
                         </div>
                         <div class="activity-item">
                             <div class="activity-icon" style="background: #f8d7da;">
                                 <i class="fas fa-flag" style="color: #dc3545;"></i>
                             </div>
                             <div class="activity-content">
                                 <h4>Account flagged for review</h4>
                                 <p>User: sarah.wilson@email.com</p>
                             </div>
                             <div class="activity-time">1 hour ago</div>
                         </div>
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
                        <table>
                             <thead>
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
                                
                                
                                // Show all boarders by default - active users + pending registrations only
                                $all_boarders = [];
                                $processed_emails = [];
                                
                                // First add active users
                                foreach ($active_boarders as $user) {
                                    $all_boarders[] = $user;
                                    $processed_emails[] = $user['email'];
                                }
                                
                                // Then add pending registrations (if not already processed)
                                foreach ($pending_boarders as $reg) {
                                    if (!in_array($reg['email'], $processed_emails)) {
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
                                            $actions = '
                                         <div class="action-buttons-container">
                                                    <button class="action-btn" onclick="viewUserDetails(' . $user['user_id'] . ')">
                                                 <i class="fas fa-eye"></i> View
                                             </button>
                                                    <button class="action-btn danger" onclick="suspendUser(' . $user['user_id'] . ')">
                                                 <i class="fas fa-ban"></i> Suspend
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
                
                <!-- Owners Tab Content -->
                <div class="tab-content" id="owners-tab">
                    <div class="table-filters">
                        <button class="filter-btn active" onclick="filterOwners('all')">All</button>
                        <button class="filter-btn" onclick="filterOwners('active')">Active</button>
                        <button class="filter-btn" onclick="filterOwners('inactive')">Inactive</button>
                        <button class="filter-btn" onclick="filterOwners('pending')">Pending Approval</button>
                    </div>
                    <div class="table-container">
                        <table>
                             <thead>
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
                                
                                // Show all owners by default - active users + pending registrations only
                                $all_owners = [];
                                $processed_emails = [];
                                
                                // First add active users
                                foreach ($active_owners as $user) {
                                    $all_owners[] = $user;
                                    $processed_emails[] = $user['email'];
                                }
                                
                                // Then add pending registrations (if not already processed)
                                foreach ($pending_owners as $reg) {
                                    if (!in_array($reg['email'], $processed_emails)) {
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
                                            $actions = '
                                         <div class="action-buttons-container">
                                                    <button class="action-btn" onclick="viewUserDetails(' . $user['user_id'] . ')">
                                                 <i class="fas fa-eye"></i> View
                                             </button>
                                                    <button class="action-btn danger" onclick="suspendUser(' . $user['user_id'] . ')">
                                                 <i class="fas fa-ban"></i> Suspend
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
                                     <td><?php echo $user['role'] === 'BH Owner' ? $user['properties_count'] . ' properties' : 'N/A'; ?></td>
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
                        <div class="notification-item">
                            <div class="notification-icon" style="background: #28a745;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="notification-content">
                                <h4>New User Registration</h4>
                                <p>David Lee registered as a boarder from De La Salle University</p>
                                <div class="notification-time">2 hours ago</div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon" style="background: #007bff;">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="notification-content">
                                <h4>New Boarding House Added</h4>
                                <p>City View Boarding House was registered by Robert Brown</p>
                                <div class="notification-time">4 hours ago</div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon" style="background: #ffc107;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Payment Issue Reported</h4>
                                <p>Payment dispute reported for Room 101 at Sunshine Boarding House</p>
                                <div class="notification-time">6 hours ago</div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon" style="background: #dc3545;">
                                <i class="fas fa-flag"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Account Flagged</h4>
                                <p>User sarah.wilson@email.com has been flagged for review</p>
                                <div class="notification-time">1 day ago</div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon" style="background: #6f42c1;">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Payment Completed</h4>
                                <p>Payment of ₱3,500 received from Mike Johnson for Room 101</p>
                                <div class="notification-time">1 day ago</div>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon" style="background: #20c997;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Maintenance Request Completed</h4>
                                <p>Plumbing issue in Room 205 has been resolved</p>
                                <div class="notification-time">2 days ago</div>
                            </div>
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
                        <table>
                            <thead>
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
                
                <!-- Top Performing Boarding Houses -->
                <div class="top-performing-section">
                    <h4><i class="fas fa-trophy"></i> Top Performing Boarding Houses</h4>
                    <div class="top-performing-list" id="top-boarding-houses">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports-section" class="content-section">
            <div class="content-header">
                <h1>Reports & Analytics</h1>
                <p>Generate and download various reports for system analysis and monitoring.</p>
            </div>
            
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-chart-bar"></i> Reports & Analytics</h3>
                    <span style="color: rgba(255,255,255,0.8);">Generate comprehensive system reports</span>
                </div>
                
                <div class="card-content">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div class="report-card">
                            <div class="report-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h4>Payment Reports</h4>
                            <p>Generate detailed payment transaction reports</p>
                            <button class="action-btn" id="paymentReportBtn" onclick="downloadPaymentReport()">
                                <i class="fas fa-download"></i> Download Payment Report
                            </button>
                        </div>
                        
                        <div class="report-card">
                            <div class="report-icon" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                                <i class="fas fa-home"></i>
                            </div>
                            <h4>Rental Reports</h4>
                            <p>View rental statistics and occupancy reports</p>
                            <button class="action-btn" id="rentalReportBtn" onclick="downloadRentalReport()">
                                <i class="fas fa-download"></i> Download Rental Report
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
                <span class="close" onclick="closeModal('documentModal')">&times;</span>
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
                                <label>Business Name:</label>
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
                                <input type="checkbox" id="photoMatch">
                                <span class="checkmark"></span>
                                Photo on ID appears to match user
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

                    <!-- Verification Notes -->
                    <div class="verification-notes">
                        <h3><i class="fas fa-sticky-note"></i> Verification Notes</h3>
                        <textarea id="verificationNotes" placeholder="Add any notes about the verification process..." rows="4"></textarea>
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
            <span class="close" onclick="closeModal('imageModal')">&times;</span>
            <img id="zoomedImage" src="" alt="Zoomed Document">
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
        }

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
            const reason = prompt('Please provide a reason for suspension:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to suspend this user?')) {
                    alert(`User ${userId} has been suspended. Reason: ${reason}`);
                    // Here you would update the database and refresh the table
                }
            }
        }

        function unsuspendUser(userId) {
            if (confirm('Are you sure you want to unsuspend this user?')) {
                alert(`User ${userId} has been unsuspended`);
                // Here you would update the database and refresh the table
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
            
            if (confirm('Are you sure you want to approve this registration?')) {
                approveUser(registrationId);
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
            
            if (confirm('Are you sure you want to reject this registration?')) {
                rejectUser(registrationId);
                closeModal('documentModal');
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
            document.getElementById('photoMatch').checked = false;
            document.getElementById('documentValid').checked = false;
            document.getElementById('informationComplete').checked = false;
            
            // Clear notes
            document.getElementById('verificationNotes').value = '';
            
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
            const notes = document.getElementById('verificationNotes').value;
            const nameMatch = document.getElementById('nameMatch').checked;
            const photoMatch = document.getElementById('photoMatch').checked;
            const documentValid = document.getElementById('documentValid').checked;
            const informationComplete = document.getElementById('informationComplete').checked;
            
            // Check if email is verified
            const statusText = document.getElementById('verificationStatus').textContent;
            if (!statusText.includes('verified successfully')) {
                alert('Please ensure email verification is completed before approving.');
                return;
            }
            
            if (!nameMatch || !photoMatch || !documentValid || !informationComplete) {
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
            const reason = prompt('Please provide a reason for deactivation:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to deactivate this boarding house?')) {
                    alert(`Boarding house ${houseId} deactivated successfully!`);
                }
            }
        }


        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').classList.add('active');
            
            // Add active class to clicked nav item
            event.target.classList.add('active');
            
            // Load data for specific sections
            switch(sectionName) {
                case 'user-management':
                    loadUserStatsData();
                    break;
                case 'boarding-houses':
                    loadBoardingHousesData();
                    break;
                case 'notifications':
                    loadNotificationsData();
                    break;
                case 'analytics':
                    console.log('Analytics section clicked');
                    loadAnalyticsData();
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
            
            const userInitials = (user.first_name.charAt(0) + user.last_name.charAt(0)).toUpperCase();
            const fullName = user.middle_name ? `${user.first_name} ${user.middle_name} ${user.last_name}` : `${user.first_name} ${user.last_name}`;
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
                            <div class="info-label">Valid ID Type</div>
                            <div class="info-value">${user.valid_id_type || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">ID Number</div>
                            <div class="info-value">${user.id_number || 'Not provided'}</div>
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
                    const statusClass = booking.status === 'confirmed' ? 'status-active' : 
                                     booking.status === 'pending' ? 'status-pending' : 'status-inactive';
                    html += `
                        <div class="booking-item">
                            <div class="item-header">
                                <div class="item-title">${booking.bh_name}</div>
                                <span class="status-badge ${statusClass}">${booking.status}</span>
                            </div>
                            <div class="item-details">
                                <strong>Check-in:</strong> ${new Date(booking.check_in_date).toLocaleDateString()}<br>
                                <strong>Check-out:</strong> ${new Date(booking.check_out_date).toLocaleDateString()}<br>
                                <strong>Amount:</strong> ₱${booking.total_amount}<br>
                                <strong>Booked:</strong> ${new Date(booking.created_at).toLocaleDateString()}
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
                const response = await fetch('../get_admin_notifications.php?action=list&type=all&status=all');
                const data = await response.json();
                
                if (data.success) {
                    updateNotificationsTable(data.data);
                } else {
                    console.error('Error loading notifications data:', data.error);
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
                        backgroundColor: ['#8D6E63', '#A1887F'],
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
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: data,
                        backgroundColor: '#8D6E63',
                        borderColor: '#8D6E63',
                        borderWidth: 1
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
                            borderColor: '#8D6E63',
                            backgroundColor: 'rgba(141, 110, 99, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Boarding Houses',
                            data: growthData.map(item => item.boarding_houses),
                            borderColor: '#A1887F',
                            backgroundColor: 'rgba(161, 136, 127, 0.1)',
                            tension: 0.4
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
        }
        
        // Update Top Performing Boarding Houses
        function updateTopPerformingBoardingHouses(boardingHouses) {
            const container = document.getElementById('top-boarding-houses');
            if (!container) return;
            
            if (boardingHouses.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">No boarding houses data available</p>';
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
                        <div style="font-size: 0.8rem; color: #666; margin-top: 0.25rem;">
                            ${bh.occupied_units}/${bh.total_units} units
                        </div>
                    </div>
                </div>
            `).join('');
            
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
                            borderColor: '#8D6E63',
                            backgroundColor: 'rgba(141, 110, 99, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Boarding Houses',
                            data: growthData.map(item => item.boarding_houses),
                            borderColor: '#A1887F',
                            backgroundColor: 'rgba(161, 136, 127, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Revenue (₱)',
                            data: growthData.map(item => item.revenue),
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
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
                        backgroundColor: ['#8D6E63', '#A1887F', '#D7CCC8'],
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
            } catch (error) {
                console.error('Error creating user distribution chart:', error);
            }
        }
        
        // Create Booking Status Chart
        function createBookingStatusChart(bookingData) {
            const ctx = document.getElementById('bookingStatusChart');
            if (!ctx) return;
            
            try {
                analyticsCharts.bookingStatusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(bookingData),
                    datasets: [{
                        label: 'Bookings',
                        data: Object.values(bookingData),
                        backgroundColor: '#8D6E63',
                        borderColor: '#8D6E63',
                        borderWidth: 1
                    }]
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
            } catch (error) {
                console.error('Error creating booking status chart:', error);
            }
        }
        
        // Create Payment Status Chart
        function createPaymentStatusChart(paymentData) {
            const ctx = document.getElementById('paymentStatusChart');
            if (!ctx) return;
            
            try {
                analyticsCharts.paymentStatusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(paymentData),
                    datasets: [{
                        label: 'Payments',
                        data: Object.values(paymentData),
                        backgroundColor: '#4CAF50',
                        borderColor: '#4CAF50',
                        borderWidth: 1
                    }]
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
            
            try {
                analyticsCharts.userLocationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: data,
                        backgroundColor: [
                            '#8D6E63',
                            '#A1887F', 
                            '#D7CCC8',
                            '#BCAAA4',
                            '#A1887F',
                            '#8D6E63',
                            '#795548',
                            '#6D4C41'
                        ],
                        borderColor: '#8D6E63',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
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
            
            try {
                analyticsCharts.bhLocationChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Boarding Houses',
                        data: data,
                        backgroundColor: [
                            '#8D6E63',
                            '#A1887F', 
                            '#D7CCC8',
                            '#BCAAA4',
                            '#A1887F',
                            '#8D6E63',
                            '#795548',
                            '#6D4C41'
                        ],
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
                const registrationDate = new Date(registration.created_at).toISOString().split('T')[0];
                const timeAgo = getTimeAgo(registration.created_at);
                
                html += `
                    <div class="approval-card" data-registration-id="${registration.id}">
                        <div class="approval-header">
                            <div class="approval-user">
                                <div class="user-avatar">${initials}</div>
                                <div>
                                    <strong>${registration.full_name}</strong><br>
                                    <small>${roleText}</small>
                                </div>
                            </div>
                            <div class="approval-actions">
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
            const now = new Date();
            const date = new Date(dateString);
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
            return `${Math.floor(diffInSeconds / 86400)} days ago`;
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
                                            <strong>${house.bh_name}</strong><br>
                                            <small>${house.bh_address}</small>
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
                const response = await fetch('../get_admin_notifications.php?action=send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(notificationData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Notification sent successfully to ${data.data.sent_count} users!`);
                    // Refresh notifications list
                    loadNotificationsData();
                } else {
                    alert('Error sending notification: ' + data.error);
                }
            } catch (error) {
                console.error('Error sending notification:', error);
                alert('Error sending notification. Please try again.');
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
            const reason = prompt('Please provide a reason for suspension:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to suspend this boarder?')) {
                    alert(`Boarder ${boarderId} suspended successfully!`);
                }
            }
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
            const reason = prompt('Please provide a reason for suspension:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to suspend this owner?')) {
                    alert(`Owner ${ownerId} suspended successfully!`);
                }
            }
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
        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const recipients = this.querySelector('select[name="recipients"]').value;
            const notificationType = this.querySelector('select[name="notification_type"]').value;
            const title = this.querySelector('input[name="title"]').value;
            const message = this.querySelector('textarea[name="message"]').value;
            
            if (confirm(`Send notification to ${recipients}?\n\nSubject: ${title}\nMessage: ${message}`)) {
                // Send notification using our API
                const notificationData = {
                    recipients: recipients,
                    notification_type: notificationType,
                    title: title,
                    message: message
                };
                
                sendNotification(notificationData);
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Notification Settings Functions
        function openNotificationSettings() {
            document.getElementById('notificationSettingsModal').style.display = 'block';
            resetNotificationSettings();
            loadNotificationSettings();
        }

        function closeNotificationSettings() {
            document.getElementById('notificationSettingsModal').style.display = 'none';
            resetNotificationSettings(); // Reset form when closing
        }

        function resetNotificationSettings() {
            // Reset all form elements to default values
            document.getElementById('email_notifications').checked = false;
            document.getElementById('push_notifications').checked = false;
            
            // Reset notification types
            document.getElementById('booking_notifications').checked = false;
            document.getElementById('payment_notifications').checked = false;
            document.getElementById('maintenance_notifications').checked = false;
            document.getElementById('announcement_notifications').checked = false;
            
            // Reset templates
            document.getElementById('booking_template').value = '';
            document.getElementById('payment_template').value = '';
            document.getElementById('maintenance_template').value = '';
            document.getElementById('announcement_template').value = '';
            
            // Reset channel settings
            document.getElementById('smtp_server').value = '';
            document.getElementById('smtp_port').value = '';
            document.getElementById('smtp_email').value = '';
            document.getElementById('fcm_server_key').value = '';
            document.getElementById('fcm_sender_id').value = '';
            
            // Reset to first tab and make sure it's active
            setTimeout(() => {
                switchSettingsTab('preferences');
            }, 10);
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
            // Populate notification preferences
            document.getElementById('email_notifications').checked = settings.email_notifications || false;
            document.getElementById('push_notifications').checked = settings.push_notifications || false;
            
            // Populate notification types
            document.getElementById('booking_notifications').checked = settings.booking_notifications || false;
            document.getElementById('payment_notifications').checked = settings.payment_notifications || false;
            document.getElementById('maintenance_notifications').checked = settings.maintenance_notifications || false;
            document.getElementById('announcement_notifications').checked = settings.announcement_notifications || false;
            
            // Populate templates
            document.getElementById('booking_template').value = settings.booking_template || '';
            document.getElementById('payment_template').value = settings.payment_template || '';
            document.getElementById('maintenance_template').value = settings.maintenance_template || '';
            document.getElementById('announcement_template').value = settings.announcement_template || '';
        }

        async function saveNotificationSettings() {
            const settings = {
                email_notifications: document.getElementById('email_notifications').checked,
                push_notifications: document.getElementById('push_notifications').checked,
                booking_notifications: document.getElementById('booking_notifications').checked,
                payment_notifications: document.getElementById('payment_notifications').checked,
                maintenance_notifications: document.getElementById('maintenance_notifications').checked,
                announcement_notifications: document.getElementById('announcement_notifications').checked,
                booking_template: document.getElementById('booking_template').value,
                payment_template: document.getElementById('payment_template').value,
                maintenance_template: document.getElementById('maintenance_template').value,
                announcement_template: document.getElementById('announcement_template').value
            };

            try {
                const response = await fetch('../get_notification_settings.php?action=save_settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settings)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Notification settings saved successfully!', 'success');
                    closeNotificationSettings();
                    resetNotificationSettings(); // Reset form after saving
                } else {
                    showNotification('Error saving settings: ' + data.error, 'error');
                }
            } catch (error) {
                console.error('Error saving notification settings:', error);
                showNotification('Error saving settings. Please try again.', 'error');
            }
        }

        // User approval functions
        function approveUser(registrationId) {
            if (confirm('Are you sure you want to approve this registration? The user will be moved to the users table.')) {
                const formData = new FormData();
                formData.append('registration_id', registrationId);
                formData.append('action', 'approve');

                fetch('../approved_registration.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Registration approved successfully! User has been added to the system.');
                        // Refresh the page to show updated data
                        window.location.reload();
                    } else {
                        alert('Error approving registration: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error approving registration. Please try again.');
                });
            }
        }

        function rejectUser(registrationId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to reject this registration?')) {
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
                            alert('Registration rejected successfully!');
                            // Refresh the page to show updated data
                            window.location.reload();
                        } else {
                            alert('Error rejecting registration: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error rejecting registration. Please try again.');
                    });
                }
            }
        }

        function updatePendingCount(change) {
            const pendingCountElement = document.querySelector('.stat-content h3');
            const currentCount = parseInt(pendingCountElement.textContent);
            const newCount = Math.max(0, currentCount + change);
            pendingCountElement.textContent = newCount;
        }

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


        function suspendUser(userId) {
            const reason = prompt('Please provide a reason for suspension:');
            if (reason && reason.trim() !== '') {
                if (confirm('Are you sure you want to suspend this user? They will not be able to access their account.')) {
                    // Simulate API call
                    fetch('/api/suspend-user', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            userId: userId,
                            reason: reason,
                            action: 'suspend'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('User suspended successfully!');
                            // Update the UI to show suspended status
                            location.reload(); // Simple way to refresh the page
                        } else {
                            alert('Error suspending user: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error suspending user. Please try again.');
                    });
                }
            }
        }

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
                 
                 // Update recent activity
                 if (dashboardData && dashboardData.recent_activity) {
                     updateRecentActivity(dashboardData.recent_activity);
                 }
                 
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
         
         // Update Recent Activity
         function updateRecentActivity(activity) {
             // Update recent users
             const recentUsersContainer = document.querySelector('#recent-users-container');
             if (recentUsersContainer && activity.recent_users) {
                 recentUsersContainer.innerHTML = activity.recent_users.map(user => `
                     <div class="activity-item">
                         <div class="activity-avatar">
                             <img src="${user.profile_picture || 'https://via.placeholder.com/40'}" alt="${user.full_name}">
                         </div>
                         <div class="activity-content">
                             <div class="activity-title">${user.full_name}</div>
                             <div class="activity-subtitle">${user.role} • ${new Date(user.created_at).toISOString().split('T')[0]}</div>
                         </div>
                     </div>
                 `).join('');
             }
             
             // Update recent bookings
             const recentBookingsContainer = document.querySelector('#recent-bookings-container');
             if (recentBookingsContainer && activity.recent_bookings) {
                 recentBookingsContainer.innerHTML = activity.recent_bookings.map(booking => `
                     <div class="activity-item">
                         <div class="activity-content">
                             <div class="activity-title">${booking.boarder_name}</div>
                             <div class="activity-subtitle">${booking.boarding_house_name} • ${booking.booking_status}</div>
                             <div class="activity-date">${new Date(booking.booking_date).toISOString().split('T')[0]}</div>
                         </div>
                     </div>
                 `).join('');
             }
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
                             borderColor: '#8D6E63',
                             backgroundColor: 'rgba(141, 110, 99, 0.1)',
                             tension: 0.4
                         }, {
                             label: 'Owners',
                             data: [12, 15, 18, 22],
                             borderColor: '#A97A50',
                             backgroundColor: 'rgba(169, 122, 80, 0.1)',
                             tension: 0.4
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
                             backgroundColor: ['#8D6E63', '#A97A50', '#dc3545'],
                             borderWidth: 0
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
                             backgroundColor: ['#8D6E63', '#A97A50', '#28a745', '#007bff'],
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
                             backgroundColor: ['#8D6E63', '#A97A50', '#6c757d', '#28a745', '#007bff'],
                             borderWidth: 0
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
        });

         // Re-initialize charts when switching to analytics section
         // Note: showSection function is already defined above

         // Real-time updates removed - using actual database counts

        // Report download functionality
        function downloadPaymentReport() {
            const reportBtn = document.getElementById('paymentReportBtn');
            const originalText = reportBtn.innerHTML;
            
            // Show loading state
            reportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Report...';
            reportBtn.disabled = true;
            
            // Create download link
            const downloadLink = document.createElement('a');
            downloadLink.href = '../generate_payment_report.php?action=payment_report';
            downloadLink.download = 'payment_report_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.csv';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            
            // Trigger download
            downloadLink.click();
            
            // Clean up
            document.body.removeChild(downloadLink);
            
            // Reset button after a delay
            setTimeout(() => {
                reportBtn.innerHTML = originalText;
                reportBtn.disabled = false;
                
                // Show success message
                showNotification('Payment report generated successfully! Check your Downloads folder.', 'success');
            }, 2000);
        }

        function downloadRentalReport() {
            const reportBtn = document.getElementById('rentalReportBtn');
            const originalText = reportBtn.innerHTML;
            
            // Show loading state
            reportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Report...';
            reportBtn.disabled = true;
            
            // Create download link
            const downloadLink = document.createElement('a');
            downloadLink.href = '../generate_rental_report.php?action=rental_report';
            downloadLink.download = 'rental_report_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.csv';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            
            // Trigger download
            downloadLink.click();
            
            // Clean up
            document.body.removeChild(downloadLink);
            
            // Reset button after a delay
            setTimeout(() => {
                reportBtn.innerHTML = originalText;
                reportBtn.disabled = false;
                
                // Show success message
                showNotification('Rental report generated successfully! Check your Downloads folder.', 'success');
            }, 2000);
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
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #f8f9fa;
            margin: 2% auto;
            padding: 0;
            border: none;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #8D6E63, #6D4C41);
            color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
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
        }

        .analytics-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .analytics-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .analytics-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .analytics-card .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(135deg, #8D6E63, #A1887F);
        }

        .analytics-card .card-content {
            flex: 1;
        }

        .analytics-card h3 {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0 0 0.25rem 0;
        }

        .analytics-card p {
            color: #666;
            margin: 0 0 0.5rem 0;
            font-weight: 500;
        }

        .analytics-card .card-subtitle {
            font-size: 0.85rem;
            color: #8D6E63;
            font-weight: 600;
        }

        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-container h4 {
            color: #2c3e50;
            margin: 0 0 1rem 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container h4 i {
            color: #8D6E63;
        }

        .chart-container canvas {
            max-width: 100%;
            height: auto;
        }

        .top-performing-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .top-performing-section h4 {
            color: #2c3e50;
            margin: 0 0 1.5rem 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .top-performing-section h4 i {
            color: #8D6E63;
        }

        .top-performing-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .top-performing-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #8D6E63;
        }

        .top-performing-item .item-info h5 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
            font-size: 1rem;
        }

        .top-performing-item .item-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .top-performing-item .item-stats {
            text-align: right;
        }

        .top-performing-item .item-stats .stat-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #8D6E63;
        }

        .top-performing-item .item-stats .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        /* Responsive Design for Analytics */
        @media (max-width: 768px) {
            .analytics-overview {
                grid-template-columns: 1fr;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .analytics-card {
                padding: 1rem;
            }
            
            .analytics-card .card-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .analytics-card h3 {
                font-size: 1.5rem;
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
                <span class="close" onclick="closeAccountManagement()">&times;</span>
            </div>
            
            <div class="modal-body">
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
                    <h3>Security Settings</h3>
                    <div class="security-settings">
                        <div class="security-item">
                            <div class="security-info">
                                <h4><i class="fas fa-lock"></i> Password Policy</h4>
                                <p>Configure password requirements and expiration</p>
                            </div>
                            <button class="btn-modern btn-edit" onclick="editPasswordPolicy()">
                                <i class="fas fa-edit"></i> Configure
                            </button>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4><i class="fas fa-clock"></i> Session Timeout</h4>
                                <p>Set automatic logout after inactivity</p>
                            </div>
                            <button class="btn-modern btn-edit" onclick="editSessionTimeout()">
                                <i class="fas fa-edit"></i> Configure
                            </button>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4><i class="fas fa-ban"></i> IP Restrictions</h4>
                                <p>Restrict admin access to specific IP addresses</p>
                            </div>
                            <button class="btn-modern btn-edit" onclick="editIPRestrictions()">
                                <i class="fas fa-edit"></i> Configure
                            </button>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h4>
                                <p>Enable 2FA for enhanced security</p>
                            </div>
                            <button class="btn-modern btn-edit" onclick="edit2FA()">
                                <i class="fas fa-edit"></i> Configure
                            </button>
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
                    
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Admin Login</h4>
                                <p>Super Admin logged in from 192.168.1.100</p>
                                <span class="activity-time">2 hours ago</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-content">
                                <h4>User Created</h4>
                                <p>New user "John Doe" was created</p>
                                <span class="activity-time">4 hours ago</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Database Backup</h4>
                                <p>Automatic database backup completed</p>
                                <span class="activity-time">1 day ago</span>
                            </div>
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
                <span class="close" onclick="closeAddAdminModal()">&times;</span>
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
                <span class="close" onclick="closeEditAdminModal()">&times;</span>
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
                <span class="close" onclick="closeLogoutModal()">&times;</span>
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
                <span class="close" onclick="closeNotificationSettings()">&times;</span>
            </div>
            
            <div class="modal-body">
                <div class="settings-tabs">
                    <div class="tab active" onclick="switchSettingsTab('preferences')">
                        <i class="fas fa-cog"></i> Preferences
                    </div>
                    <div class="tab" onclick="switchSettingsTab('templates')">
                        <i class="fas fa-file-alt"></i> Templates
                    </div>
                    <div class="tab" onclick="switchSettingsTab('channels')">
                        <i class="fas fa-broadcast-tower"></i> Channels
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div id="preferences-tab" class="tab-content active">
                    <h3>Notification Preferences</h3>
                    <div class="settings-grid">
                        <div class="setting-item">
                            <label class="switch">
                                <input type="checkbox" id="email_notifications">
                                <span class="slider"></span>
                            </label>
                            <div class="setting-info">
                                <h4>Email Notifications</h4>
                                <p>Send notifications via email</p>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <label class="switch">
                                <input type="checkbox" id="push_notifications">
                                <span class="slider"></span>
                            </label>
                            <div class="setting-info">
                                <h4>Push Notifications</h4>
                                <p>Send push notifications to mobile devices</p>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <!-- Templates Tab -->
                <div id="templates-tab" class="tab-content">
                    <h3>Notification Templates</h3>
                    <div class="template-section">
                        <div class="template-item">
                            <h4><i class="fas fa-calendar-check"></i> Booking Notifications</h4>
                            <label>
                                <input type="checkbox" id="booking_notifications">
                                Enable booking notifications
                            </label>
                            <textarea id="booking_template" placeholder="Enter booking notification template...">New booking request from {{user_name}} for {{room_name}} at {{boarding_house_name}}. Please review and approve.</textarea>
                        </div>
                        
                        <div class="template-item">
                            <h4><i class="fas fa-credit-card"></i> Payment Notifications</h4>
                            <label>
                                <input type="checkbox" id="payment_notifications">
                                Enable payment notifications
                            </label>
                            <textarea id="payment_template" placeholder="Enter payment notification template...">Payment of ₱{{amount}} received from {{user_name}} for {{room_name}}. Payment method: {{payment_method}}.</textarea>
                        </div>
                        
                        <div class="template-item">
                            <h4><i class="fas fa-tools"></i> Maintenance Notifications</h4>
                            <label>
                                <input type="checkbox" id="maintenance_notifications">
                                Enable maintenance notifications
                            </label>
                            <textarea id="maintenance_template" placeholder="Enter maintenance notification template...">Maintenance request from {{user_name}}: {{description}}. Status: {{status}}.</textarea>
                        </div>
                        
                        <div class="template-item">
                            <h4><i class="fas fa-bullhorn"></i> Announcement Notifications</h4>
                            <label>
                                <input type="checkbox" id="announcement_notifications">
                                Enable announcement notifications
                            </label>
                            <textarea id="announcement_template" placeholder="Enter announcement notification template...">{{title}}: {{message}}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Channels Tab -->
                <div id="channels-tab" class="tab-content">
                    <h3>Notification Channels</h3>
                    <div class="channel-settings">
                        <div class="channel-item">
                            <h4><i class="fas fa-envelope"></i> Email Configuration</h4>
                            <div class="form-group">
                                <label>SMTP Server:</label>
                                <input type="text" id="smtp_server" placeholder="smtp.gmail.com">
                            </div>
                            <div class="form-group">
                                <label>SMTP Port:</label>
                                <input type="number" id="smtp_port" placeholder="587">
                            </div>
                            <div class="form-group">
                                <label>Email Address:</label>
                                <input type="email" id="smtp_email" placeholder="admin@boardease.com">
                            </div>
                        </div>
                        
                        <div class="channel-item">
                            <h4><i class="fas fa-mobile-alt"></i> Push Notifications</h4>
                            <div class="form-group">
                                <label>FCM Server Key:</label>
                                <input type="text" id="fcm_server_key" placeholder="Your FCM server key">
                            </div>
                            <div class="form-group">
                                <label>FCM Sender ID:</label>
                                <input type="text" id="fcm_sender_id" placeholder="Your FCM sender ID">
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-cancel" onclick="closeNotificationSettings()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-modern btn-save" onclick="saveNotificationSettings()">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </div>
    </div>

    <style>
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .settings-tabs .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .settings-tabs .tab.active {
            border-bottom-color: #D2B48C;
            color: #D2B48C;
            background-color: rgba(210, 180, 140, 0.1);
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
        }
        
        .account-tabs .tab {
            padding: 12px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .account-tabs .tab.active {
            border-bottom-color: #D2B48C;
            color: #D2B48C;
            background-color: rgba(210, 180, 140, 0.1);
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
            document.getElementById(tabName + '-tab').style.display = 'block';
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to the correct tab (find by onclick attribute)
            const targetTab = document.querySelector(`.settings-tabs .tab[onclick="switchSettingsTab('${tabName}')"]`);
            if (targetTab) {
                targetTab.classList.add('active');
            }
        }

        // Account Management Functions
        function openAccountManagement() {
            document.getElementById('accountManagementModal').style.display = 'block';
            loadAdminAccounts();
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
                        loadAdminAccounts(); // Refresh the admin list
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

        // Load admin accounts from database
        function loadAdminAccounts() {
            fetch('../get_admin_accounts_mysqli.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentAdmins = data.admins; // Store for edit functionality
                        displayAdminAccounts(data.admins);
                    } else {
                        showNotification('Failed to load admin accounts: ' + (data.message || 'Unknown error'), 'error');
                        console.error('Admin accounts error:', data);
                    }
                })
                .catch(error => {
                    showNotification('Error loading admin accounts', 'error');
                    console.error('Error:', error);
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
                    loadAdminAccounts(); // Refresh the admin list
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
                        loadAdminAccounts(); // Refresh the admin list
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
                        loadAdminAccounts(); // Refresh the admin list
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
</body>
</html>


