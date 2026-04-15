<?php
require_once 'config.php';

// Destroy all session variables
session_destroy();

// Redirect to landing page
header('Location: ../../index.php');
exit();
?>
