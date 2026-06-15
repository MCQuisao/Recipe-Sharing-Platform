<?php
session_start();
session_unset();
session_destroy();

header("Location: /Recipe Sharing Platform/sign up/singup.html");
exit;
?>