<?php
session_start();
$_SESSION['calcforadvisors_subscriber_id'] = null;
$_SESSION['calcforadvisors_subscriber_email'] = null;
$_SESSION['calcforadvisors_subscriber_plan'] = null;
$_SESSION['calcforadvisors_subscriber_status'] = null;
unset($_SESSION['calcforadvisors_subscriber_id'], $_SESSION['calcforadvisors_subscriber_email'], $_SESSION['calcforadvisors_subscriber_plan'], $_SESSION['calcforadvisors_subscriber_status']);
header('Location: index.html');
exit;
