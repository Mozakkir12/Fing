<?php

$servername = "localhost:3306";
$username = "root";
$password = "root";
$dbname = "domain_root";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];
$status = $_GET['status'];
$company_name = $_GET['company_name'];
$company_code = $_GET['company_code'];

if ($status == 'Conframed') {
    $update_user_status = "UPDATE users SET status = 'Active' WHERE company_name = '$company_name' AND company_code = '$company_code'";
    $conn->query($update_user_status);
}

$update_buying_status = "UPDATE buying SET status = '$status' WHERE id = $id";
if ($conn->query($update_buying_status) === TRUE) {
    echo "Status updated successfully.";
} else {
    echo "Error updating status: " . $conn->error;
}

$conn->close();
?>
