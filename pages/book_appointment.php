<?php
// Backwards-compatible redirect for older links expecting book_appointment.php
require_once __DIR__ . '/../includes/header.php';

// Prefer the add_appointment page which contains the booking form
redirect('add_appointment.php');
?>
