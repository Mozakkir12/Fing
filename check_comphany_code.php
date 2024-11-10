<?php
require "domain_root.php";

$company_code = $_POST['company_code'];
$sql = "SELECT * FROM users WHERE company_code = '$company_code'";
$result = $conn->query($sql);

$response = [];
if ($result->num_rows > 0) {
    $response['status'] = 'error';
    $suggestions = [];
    for ($i = 1; $i <= 3; $i++) {
        $suggestions[] = $company_code . '_' . $i;
    }
    $response['suggestions'] = $suggestions;
} else {
    $response['status'] = 'success';
}
echo json_encode($response);
?>
