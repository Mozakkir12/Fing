<?php
include 'main/config/config1.php'; // Database configuration file
require"auth.php";

 // সেশন চেক ফাংশন কল
$message = ""; // বার্তা প্রদর্শনের জন্য
$error = false; // ত্রুটি চিহ্নিত করার জন্য

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $branch_name = $_POST['branch_name'];

    // SQL insert query
    $sql = "INSERT INTO dupbr (branch_name) VALUES ('$branch_name')";
    if ($conn->query($sql) === TRUE) {
        $message = "Data inserted successfully!";
    } else {
        $message = "Error: " . $conn->error;
        $error = true;
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Branch</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 400px; margin: 0 auto; padding: 20px; }
        input[type="text"] { width: 100%; padding: 8px; margin: 10px 0; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .message { color: green; margin-top: 10px; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Insert Branch</h2>
        <form id="branchForm" method="POST" onsubmit="return validateForm();">
            <label for="branch_name">Branch Name:</label>
            <input type="text" id="branch_name" name="branch_name" required>
            <button type="submit">Insert</button>
            <div id="responseMessage">
                <?php 
                    if ($message) {
                        echo $error ? "<span class='error'>$message</span>" : "<span class='message'>$message</span>";
                    }
                ?>
            </div>
        </form>
    </div>

    <script>
        function validateForm() {
            const branchName = document.getElementById('branch_name').value;
            const responseMessage = document.getElementById('responseMessage');
            responseMessage.innerHTML = ""; // পুরনো বার্তা মুছে ফেলে

            if (branchName.trim() === "") {
                responseMessage.innerHTML = "<span class='error'>Please enter a branch name.</span>";
                return false;
            }

            return true; // ফর্ম সাবমিট করতে দেয়
        }
    </script>
</body>
</html>
