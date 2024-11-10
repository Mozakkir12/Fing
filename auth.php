<?php
session_start(); // সেশন শুরু করুন

// ফাইলের নাম যা তৈরি হয়েছিল
$configFile = '{$folder}/main/config/' . $_SESSION['MYSQL_DB'] . '.php';
$dbFile = '{$folder}/main/db/' . $_SESSION['MYSQL_DB'] . '.php';
$dbCode = '{$folder}/main/config/pdo.php';


// চেক করুন কি ব্যবহারকারী লগইন করেছেন
if (isset($_SESSION['MYSQL_DB'])) {
    // যদি লগইন থাকে
    // এখানে username সেশন থেকে নেওয়া হচ্ছে
    echo '<a href="logout.php">Logout</a>'; // লগআউট লিংক
} else {
    // যদি লগইন না থাকে
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
    exit(); // নিশ্চিত করুন যে পরবর্তী কোডটি আর চালানো হবে না
}
?>
