<?php
/**
 * student/logout.php
 */
require_once __DIR__ . '/../includes/session.php';
session_unset();
session_destroy();
header('Location: ' . base_url('student/login.php'));
exit;
