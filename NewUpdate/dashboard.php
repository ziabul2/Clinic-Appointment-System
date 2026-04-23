<?php
// dashboard.php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get user details
$stmt = $pdo->prepare("SELECT u.*, d.first_name, d.last_name, d.specialization 
                      FROM users u 
                      LEFT JOIN doctors d ON u.doctor_id = d.doctor_id 
                      WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get dashboard statistics based on role
switch ($user_role) {
    case 'admin':
        $stats = getAdminStats($pdo);
        break;
    case 'doctor':
        $stats = getDoctorStats($pdo, $user_id);
        break;
    case 'receptionist':
        $stats = getReceptionistStats($pdo);
        break;
    case 'patient':
        $stats = getPatientStats($pdo, $user_id);
        break;
    default:
        $stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clinic Management System</title>
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin: 5px 0;
        }
        
        .sidebar-menu a {
            color: #bdc3c7;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #34495e;
            color: white;
            border-left: 4px solid #3498db;
        }
        
        .main-content {
            background: #ecf0f1;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .recent-activity {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-admin { background: #e74c3c; color: white; }
        .badge-doctor { background: #3498db; color: white; }
        .badge-receptionist { background: #f39c12; color: white; }
        .badge-patient { background: #27ae60; color: white; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🏥 ClinicMS</h2>
                <p>Welcome, <?php echo htmlspecialchars($user['first_name'] ?? $user['username']); ?></p>
                <span class="role-badge badge-<?php echo $user_role; ?>"><?php echo $user_role; ?></span>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                
                <?php if (in_array($user_role, ['admin', 'receptionist'])): ?>
                <li><a href="appointments.php">📅 Appointments</a></li>
                <li><a href="patients.php">👥 Patients</a></li>
                <?php endif; ?>
                
                <?php if ($user_role === 'doctor'): ?>
                <li><a href="my_appointments.php">📅 My Appointments</a></li>
                <li><a href="medical_records.php">📋 Medical Records</a></li>
                <li><a href="prescriptions.php">💊 Prescriptions</a></li>
                <?php endif; ?>
                
                <?php if ($user_role === 'patient'): ?>
                <li><a href="my_appointments.php">📅 My Appointments</a></li>
                <li><a href="medical_history.php">🏥 Medical History</a></li>
                <li><a href="prescriptions.php">💊 Prescriptions</a></li>
                <?php endif; ?>
                
                <?php if ($user_role === 'admin'): ?>
                <li><a href="doctors.php">👨‍⚕️ Doctors</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="billing.php">💰 Billing</a></li>
                <li><a href="reports.php">📈 Reports</a></li>
                <li><a href="settings.php">⚙️ Settings</a></li>
                <?php endif; ?>
                
                <li><a href="chatbot_ui.php">🤖 AI Assistant</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1>Dashboard</h1>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-icon"><?php echo $stat['icon']; ?></div>
                    <div class="stat-number"><?php echo $stat['value']; ?></div>
                    <div class="stat-label"><?php echo $stat['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h3>Recent Activity</h3>
                <?php echo getRecentActivity($pdo, $user_role, $user_id); ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Statistics functions
function getAdminStats($pdo) {
    $stats = [];
    
    // Total Patients
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $stats[] = ['icon' => '👥', 'value' => $stmt->fetchColumn(), 'label' => 'Total Patients'];
    
    // Total Doctors
    $stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
    $stats[] = ['icon' => '👨‍⚕️', 'value' => $stmt->fetchColumn(), 'label' => 'Doctors'];
    
    // Today's Appointments
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
    $stats[] = ['icon' => '📅', 'value' => $stmt->fetchColumn(), 'label' => "Today's Appointments"];
    
    // Pending Payments
    $stmt = $pdo->query("SELECT COUNT(*) FROM billing WHERE payment_status = 'pending'");
    $stats[] = ['icon' => '💰', 'value' => $stmt->fetchColumn(), 'label' => 'Pending Payments'];
    
    return $stats;
}

function getDoctorStats($pdo, $user_id) {
    $stats = [];
    
    // Get doctor ID
    $stmt = $pdo->prepare("SELECT doctor_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doctor_id = $stmt->fetchColumn();
    
    // Today's Appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
    $stmt->execute([$doctor_id]);
    $stats[] = ['icon' => '📅', 'value' => $stmt->fetchColumn(), 'label' => "Today's Appointments"];
    
    // Total Patients
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $stats[] = ['icon' => '👥', 'value' => $stmt->fetchColumn(), 'label' => 'My Patients'];
    
    // Pending Prescriptions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_records WHERE doctor_id = ? AND follow_up_date IS NOT NULL");
    $stmt->execute([$doctor_id]);
    $stats[] = ['icon' => '💊', 'value' => $stmt->fetchColumn(), 'label' => 'Follow-ups'];
    
    return $stats;
}

function getRecentActivity($pdo, $role, $user_id) {
    $html = '';
    
    switch ($role) {
        case 'admin':
            $stmt = $pdo->query("SELECT * FROM appointments ORDER BY created_at DESC LIMIT 5");
            break;
        case 'doctor':
            $stmt = $pdo->prepare("SELECT a.* FROM appointments a 
                                 WHERE a.doctor_id = (SELECT doctor_id FROM users WHERE user_id = ?)
                                 ORDER BY a.created_at DESC LIMIT 5");
            $stmt->execute([$user_id]);
            break;
        case 'patient':
            $stmt = $pdo->prepare("SELECT a.* FROM appointments a 
                                 WHERE a.patient_id = (SELECT patient_id FROM users WHERE user_id = ?)
                                 ORDER BY a.created_at DESC LIMIT 5");
            $stmt->execute([$user_id]);
            break;
        default:
            return '<p>No recent activity</p>';
    }
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activities)) {
        return '<p>No recent activity</p>';
    }
    
    foreach ($activities as $activity) {
        $html .= "<div style='padding: 10px; border-bottom: 1px solid #eee;'>
                    <strong>Appointment #{$activity['appointment_id']}</strong><br>
                    <small>Date: {$activity['appointment_date']} at {$activity['appointment_time']}</small>
                 </div>";
    }
    
    return $html;
}
?>