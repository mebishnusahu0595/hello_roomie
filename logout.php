<?php
session_start();
session_unset();
session_destroy();

// Redirect to the new public homepage
header("Location: index.php");
exit();
?>