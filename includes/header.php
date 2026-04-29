<?php
// Include configuration (use absolute path relative to this file)
require_once __DIR__ . '/../config/config.php';

// Log page access
$current_page = basename($_SERVER['PHP_SELF']);
logAction("PAGE_ACCESS", "Accessed: " . $current_page);

// Session Validation & Auto-Logout
if (isLoggedIn() && isset($_SESSION['user_id'])) {
    $session_timeout = 3600; // 1 hour
    $current_time = time();

    // Check for inactivity timeout
    if (isset($_SESSION['last_activity_time']) && ($current_time - $_SESSION['last_activity_time']) > $session_timeout) {
        // Mark session as auto_logged_out
        if (isset($_SESSION['login_log_id'])) {
            try {
                $stmt = $db->prepare("UPDATE user_logins SET status = 'auto_logged_out', logout_time = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW()) WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['login_log_id']]);
            } catch (Exception $e) {}
        }
        
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'You have been logged out due to inactivity.';
        redirect(SITE_URL . '/pages/login.php');
    }

    // Verify session status in DB (if it was killed by an admin)
    if (isset($_SESSION['login_log_id'])) {
        try {
            $stmt = $db->prepare("SELECT status FROM user_logins WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['login_log_id']]);
            $login_status = $stmt->fetchColumn();
            
            if ($login_status === 'killed') {
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['error'] = 'Your session was terminated by an administrator.';
                redirect(SITE_URL . '/pages/login.php');
            }
        } catch (Exception $e) {}
    }

    // Update session activity time
    $_SESSION['last_activity_time'] = $current_time;

    // Update last activity in database
    try {
        $up_act = $db->prepare("UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE user_id = :uid");
        $up_act->execute(['uid' => $_SESSION['user_id']]);
    } catch (Exception $e) {
        // Silent fail
    }
}
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
<body class="<?php echo ($hide_nav ?? false) ? '' : 'has-fixed-navbar'; ?>">
    <!-- Navigation -->
    <?php if (!($hide_nav ?? false)): ?>
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
                    <?php $role = strtolower($_SESSION['role'] ?? ''); ?>
                    
                    <?php if ($role === 'doctor'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>" href="../pages/appointments.php">Appointments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'medicine_search.php' ? 'active' : ''; ?>" href="../pages/medicine_search.php">Medicines</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>" href="../pages/patients.php">Patients</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="../pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        
                        <?php if ($role === 'receptionist'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>" href="../pages/patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>" href="../pages/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                            </li>
                        <?php else: ?>
                            <!-- Admin and other roles: full access -->
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>" href="../pages/patients.php"><i class="fas fa-user-injured"></i> Patients</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'doctors.php' ? 'active' : ''; ?>" href="../pages/doctors.php"><i class="fas fa-user-md"></i> Doctors</a>
                            </li>
                            <li class="nav-item">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>" href="../pages/appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item ms-md-2 border-start ps-md-3 border-secondary d-none d-lg-block">
                            <a class="nav-link position-relative <?php echo $current_page == 'messenger.php' ? 'active text-white' : 'text-light'; ?>" href="../pages/messenger.php">
                                <i class="fab fa-whatsapp me-1"></i> Messenger
                                <span id="chatGlobalBadge" class="badge bg-danger rounded-pill ms-1" style="display:none;">0</span>
                            </a>
                        </li>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link position-relative <?php echo $current_page == 'messenger.php' ? 'active' : ''; ?>" href="../pages/messenger.php">
                                <i class="fab fa-whatsapp"></i> Messenger
                                <span id="chatGlobalBadgeMobile" class="badge bg-danger rounded-pill ms-1" style="display:none;">0</span>
                            </a>
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
                        <?php if (in_array($role, ['admin', 'root'])): ?>
                        <li class="nav-item me-2">
                            <a class="nav-link" href="../pages/manage_announcements.php" title="Manage Announcements">
                                <i class="fas fa-bullhorn"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li class="nav-item dropdown position-relative me-2">
                            <a class="nav-link position-relative" href="#" id="notificationBell" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <!-- Generic notification count (JS-updated) -->
                                <span id="notifCountBadge" class="badge bg-danger rounded-pill" style="position:absolute;top:4px;right:0;display:none;">0</span>
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
                                    <li><a class="dropdown-item" href="../pages/manage_announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
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
    <?php endif; ?>

    <!-- Main Content -->
    <div class="<?php echo $container_class ?? 'container mt-4'; ?> <?php echo function_exists('pageFadeIn') ? pageFadeIn() : ''; ?>">
        <!-- Server flash injection disabled to avoid automatic toasts outside the bell dropdown -->
        <script>
            window.__CSRF_TOKEN = '<?php echo csrf_token(); ?>';
            <?php if (isset($_SESSION['success'])): ?>
                window.__FLASH = { success: '<?php echo addslashes($_SESSION['success']); ?>', toast: true };
                <?php unset($_SESSION['success']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                window.__FLASH = { error: '<?php echo addslashes($_SESSION['error']); ?>', toast: true };
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </script>
        <?php
        // Inject user settings from JSON database
        $user_settings_js = '{}';
        if (isLoggedIn()) {
            $settings_file = __DIR__ . '/../private/user_settings.json';
            if (file_exists($settings_file)) {
                $settings_data = json_decode(file_get_contents($settings_file), true);
                $uid = $_SESSION['user_id'];
                if (isset($settings_data[$uid])) {
                    $user_settings_js = json_encode($settings_data[$uid]);
                }
            }
        }
        ?>
        <script>
            window.__USER_SETTINGS = <?php echo $user_settings_js; ?>;
            // Sync localStorage with server settings on load
            (function() {
                for (let key in window.__USER_SETTINGS) {
                    localStorage.setItem(key, window.__USER_SETTINGS[key]);
                }
            })();
        </script>
        <script src="../assets/js/notifications.js"></script>
        