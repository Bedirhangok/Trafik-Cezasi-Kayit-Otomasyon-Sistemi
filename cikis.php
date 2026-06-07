<?php
session_start();
// Tüm oturum değişkenlerini boşalt
session_unset();
// Oturumu tamamen yok et
session_destroy();
// Login sayfasına yönlendir
header("Location: login.php");
exit;
?>