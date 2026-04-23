<?php
$page_title = 'Session & Account Details';
require_once '../includes/header.php';
if (!isLoggedIn()) redirect('../index.php');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = strtolower($_SESSION['role'] ?? 'unknown');

// Fetch full user info
$q = $db->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
$q->bindParam(':id', $user_id);
$q->execute();
$user = $q->fetch(PDO::FETCH_ASSOC);

// Get client info
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$browser = 'Unknown';
$os = 'Unknown';

// Simple browser/OS detection
if (strpos($user_agent, 'Chrome') !== false) $browser = 'Google Chrome';
elseif (strpos($user_agent, 'Firefox') !== false) $browser = 'Mozilla Firefox';
elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Microsoft Edge';
elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) $browser = 'Internet Explorer';
else $browser = 'Other';

if (strpos($user_agent, 'Windows') !== false) $os = 'Windows';
elseif (strpos($user_agent, 'Mac') !== false) $os = 'macOS';
elseif (strpos($user_agent, 'Linux') !== false) $os = 'Linux';
elseif (strpos($user_agent, 'Android') !== false) $os = 'Android';
elseif (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false) $os = 'iOS';
else $os = 'Other';

$login_time = $_SESSION['login_time'] ?? 'Unknown';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-circle"></i> Session & Account Details</h1>
    <div>
        <a href="my_profile.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow mb-3">
            <div class="card-header bg-light">Account Information</div>
            <div class="card-body">
                <p><strong>Username:</strong> <code><?php echo htmlspecialchars($username); ?></code></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></p>
                <p><strong>Role:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($role); ?></span></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? 'Not set'); ?></p>
                <p><strong>Account Created:</strong> <?php echo htmlspecialchars($user['created_at'] ?? 'Unknown'); ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow mb-3">
            <div class="card-header bg-light">Session & Device Information</div>
            <div class="card-body">
                <p><strong>Current Session ID:</strong> <code><?php echo htmlspecialchars(session_id()); ?></code></p>
                <p><strong>Client IP Address:</strong> <code><?php echo htmlspecialchars($ip); ?></code></p>
                <p><strong>Browser:</strong> <?php echo htmlspecialchars($browser); ?></p>
                <p><strong>Operating System:</strong> <?php echo htmlspecialchars($os); ?></p>
                <p><strong>User Agent:</strong></p>
                <pre style="background:#f5f5f5;padding:8px;border-radius:4px;font-size:11px;overflow-x:auto;"><?php echo htmlspecialchars($user_agent); ?></pre>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php';
