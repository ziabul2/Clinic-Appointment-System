<?php
// Default landing: redirect to dashboard when logged in, otherwise go to login page
require_once __DIR__ . '/config/config.php';
if (isLoggedIn()) {
	header('Location: pages/dashboard.php');
} else {
	header('Location: pages/login.php');
}
exit;
