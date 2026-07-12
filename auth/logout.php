<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/session_bootstrap.php';
rb_session_start();

rb_session_clear_remember_cookies();
session_unset();
session_destroy();

header('Location: ../');
exit();
