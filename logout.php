<?php
session_start(); // সেশন শুরু করুন
session_destroy(); // সেশন ধংস করুন

// ফাইলগুলো মুছে দিন
    if (file_exists($configFile)) {
        unlink($configFile); // config ফাইল মুছে দিন
    }
    if (file_exists($dbFile)) {
        unlink($dbFile); // db ফাইল মুছে দিন
    }
    
    if (file_exists($dbCode)) {
        unlink($dbCode);
    }
// লগইন পৃষ্ঠায় নিয়ে যান
header("Location: ../login.php");
exit();
?>
