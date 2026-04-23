<?php
// Include configuration (use absolute path relative to this file)
require_once __DIR__ . '/../config/config.php';

// Log page access
logAction("PAGE_ACCESS", "Accessed: " . basename($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $page_title ?? 'Dashboard'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/ZIM.ico">

    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="has-fixed-navbar">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-transparent">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <?php
                // Prefer a white logo for the dark navbar if available
                $logoRelPath = '../assets/images/logo_white.png';
                $logoAbsPath = __DIR__ . '/../assets/images/logo_white.png';
                if (file_exists($logoAbsPath)):
                ?>
                    <img src="<?php echo $logoRelPath; ?>" alt="<?php echo SITE_NAME; ?>" style="height:34px;object-fit:contain;margin-right:8px;">
                <?php else: ?>
                    <i class="fas fa-hospital-alt me-2"></i>
                <?php endif; ?>
                <span class="ms-1"><?php echo SITE_NAME; ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>

                    <?php $role = strtolower($_SESSION['role'] ?? ''); ?>

                    <?php if ($role === 'doctor'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/prescriptions.php"><i class="fas fa-file-prescription"></i> Prescriptions</a>
                        </li>
                    <?php elseif ($role === 'receptionist'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                        </li>
                    <?php else: ?>
                        <!-- Admin and other roles: full access -->
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <?php endif; ?>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()):
                        // fetch user's profile picture if present
                        $waitingCount = 0;
                        $avatarUrl = '../assets/images/default_avatar.png';
                        if (defined('DB_OK') && DB_OK && isset($db) && $db instanceof PDO) {
                            try {
                                $cntQ = $db->prepare("SELECT COUNT(*) as cnt FROM waiting_list WHERE status = 'waiting'");
                                $cntQ->execute(); $cntR = $cntQ->fetch(PDO::FETCH_ASSOC);
                                $waitingCount = intval($cntR['cnt']);
                            } catch (Throwable $e) { $waitingCount = 0; }

                            try {
                                $uqq = $db->prepare('SELECT profile_picture, first_name, last_name FROM users WHERE user_id = :id LIMIT 1');
                                $uqq->bindParam(':id', 
                                    $_SESSION['user_id']);
                                $uqq->execute();
                                $urow = $uqq->fetch(PDO::FETCH_ASSOC);
                                if (!empty($urow['profile_picture']) && file_exists(__DIR__ . '/../uploads/users/' . $urow['profile_picture'])) {
                                    $avatarUrl = '../uploads/users/' . $urow['profile_picture'];
                                }
                                $profileFullName = trim(($urow['first_name'] ?? '') . ' ' . ($urow['last_name'] ?? ''));
                            } catch (Throwable $e) { $profileFullName = $_SESSION['username']; }
                        }
                    ?>
                        <li class="nav-item dropdown position-relative me-2">
                            <a class="nav-link position-relative" href="#" id="notificationBell" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if ($waitingCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill" style="position:absolute;top:4px;right:0;"><?php echo $waitingCount; ?></span>
                                <?php endif; ?>
                                <!-- Generic notification count (JS-updated) -->
                                <span id="notifCountBadge" class="badge bg-danger rounded-pill" style="position:absolute;top:4px;right:18px;display:none;">0</span>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="notificationBell" style="min-width:320px;max-width:420px;">
                                <div class="d-flex align-items-center justify-content-between px-2 mb-2">
                                    <strong>Notifications</strong>
                                    <button id="markAllReadBtn" class="btn btn-sm btn-link">Mark all read</button>
                                </div>
                                <div id="notifDropdownContent" style="max-height:320px;overflow-y:auto;overflow-x:hidden;">
                                    <div class="text-center text-muted small p-3">No notifications</div>
                                </div>
                                <div class="dropdown-footer text-center mt-2">
                                    <a href="../pages/notifications.php" class="small">View all notifications</a>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo $avatarUrl; ?>" alt="avatar" style="width:30px;height:30px;object-fit:cover;border-radius:50%;margin-right:8px;">
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                <li>
                                    <div class="dropdown-header text-muted">
                                        <small>
                                            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                                            <br>Role: <span class="badge bg-info"><?php echo htmlspecialchars($role); ?></span>
                                        </small>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../pages/session_details.php"><i class="fas fa-info-circle"></i> Account & Session Info</a></li>
                                <li><a class="dropdown-item" href="../pages/my_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                                <li>
                                    <div class="dropdown-item d-flex align-items-center justify-content-between">
                                        <div><i class="fas fa-adjust me-2"></i>Theme</div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" id="themeToggle" aria-label="Toggle light/dark theme">
                                        </div>
                                    </div>
                                </li>
                                <?php if (in_array($role, ['admin','root'])): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../pages/admin_tools.php"><i class="fas fa-tools"></i> Tools</a></li>
                                    <li><a class="dropdown-item" href="../pages/employees.php"><i class="fas fa-users"></i> Employees</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../process.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4 <?php echo function_exists('pageFadeIn') ? pageFadeIn() : ''; ?>">
        <!-- Server flash injection disabled to avoid automatic toasts outside the bell dropdown -->
        <script>window.__CSRF_TOKEN = '<?php echo csrf_token(); ?>';</script>
        <script src="../assets/js/notifications.js"></script>
        