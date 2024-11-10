<?php
require"auth.php";
require "main/config/config1.php"; // Database configuration file
 // সেশন চেক ফাংশন কল
$message = ""; // বার্তা প্রদর্শনের জন্য ভেরিয়েবল
$error = false; // ত্রুটি চিহ্নিত করার জন্য

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dept_name = $_POST['dept_name'];

    // SQL ইনসার্ট কুয়েরি
    $sql = "INSERT INTO dupdept (dept_name) VALUES ('$dept_name')";
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
    <title>Insert Department</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 400px; margin: 0 auto; padding: 20px; }
        input[type="text"] { width: 100%; padding: 8px; margin: 10px 0; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; }
        .message { color: green; margin-top: 10px; }
        .error { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Insert Department</h2>
        <form id="deptForm" method="POST" onsubmit="return validateForm();">
            <label for="dept_name">Department Name:</label>
            <input type="text" id="dept_name" name="dept_name" required>
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
            const deptName = document.getElementById('dept_name').value;
            const responseMessage = document.getElementById('responseMessage');
            responseMessage.innerHTML = ""; // পুরনো বার্তা মুছে ফেলে

            if (deptName.trim() === "") {
                responseMessage.innerHTML = "<span class='error'>Please enter a department name.</span>";
                return false;
            }

            return true; // ফর্ম সাবমিট করতে দেয়
        }
    </script>
</body>
</html>
