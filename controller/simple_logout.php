<?php
session_start();
session_destroy();
header("Location: ../view/login.php?success=logged_out");
exit();
?>