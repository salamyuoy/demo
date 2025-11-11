<?php
session_start();
session_unset();
session_destroy();

// перенаправляем обратно на главную
header('Location: главная.php');
exit;