<?php
require"auth.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #4cae4c;
        }
        .message {
            text-align: center;
            color: green;
        }
    </style>
    <script>
        function validateForm() {
            var password = document.forms["branchForm"]["password"].value;
            if (password.length < 6) {
                alert("Password must be at least 6 characters long.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Branch Registration</h2>

        <?php
        require "domain_root.php";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $company_name = $_POST['company_name'];
            $company_code = $_POST['company_code'];
            $branch_name = $_POST['branch_name'];
            $branch_code = $_POST['branch_code'];
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $phone_no = $_POST['phone_no'];
            $created_at = date("Y-m-d H:i:s");

            // Check if company_name and company_code match from users table
            $check_query = "SELECT * FROM users WHERE company_name = ? AND company_code = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ss", $company_name, $company_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Insert into branches table
                $insert_query = "INSERT INTO branches (company_name, company_code, branch_name, branch_code, username, password, MYSQL_DB, status, created_at, phone_no) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)";
                $db_name = $branch_name . "_" . $branch_code;
                $stmt_insert = $conn->prepare($insert_query);
                $stmt_insert->bind_param("sssssssss", $company_name, $company_code, $branch_name, $branch_code, $username, $password, $db_name, $created_at, $phone_no);
                
                if ($stmt_insert->execute()) {
                    // Create new database for the branch
                    $create_db_query = "CREATE DATABASE $db_name";
                    if ($conn->query($create_db_query) === TRUE) {
                        // Import tables from realtime90.sql
                        $sql_file = 'path_to/realtime.sql';
                        $command = "mysql -u root -p'root' $db_name < $sql_file";
                        system($command, $output);

                        // Create directory based on company and branch details
                        $dir_name = "{$company_name}_{$company_code}_{$branch_name}_{$branch_code}";
                        $target_dir = __DIR__ . "/$dir_name";

                        if (!is_dir($target_dir)) {
                            mkdir($target_dir, 0755, true);
                        }

                        // Copy files and folders from root to the new directory
                        $folders_to_copy = ['commands', 'log', 'main', 'report', 'src', 'static', 'vendor'];
                        foreach ($folders_to_copy as $folder) {
                            shell_exec("cp -r " . __DIR__ . "/$folder $target_dir/");
                        }

                        // Copy all PHP files from root
                        $php_files = glob(__DIR__ . '/*.php');
                        foreach ($php_files as $file) {
                            copy($file, "$target_dir/" . basename($file));
                        }

                        echo "<div class='message'>Branch created successfully! Redirecting...</div>";
                        echo "<script>setTimeout(function() { window.location.href = 'index.php'; }, 3000);</script>";
                    } else {
                        echo "Error creating database: " . $conn->error;
                    }
                } else {
                    echo "Error inserting data: " . $conn->error;
                }
            } else {
                echo "<script>alert('Company name or code does not match!');</script>";
            }
        }
        ?>

        <form name="branchForm" method="POST" onsubmit="return validateForm()">
            <label>Company Name:</label>
            <input type="text" name="company_name" required>
            
            <label>Company Code:</label>
            <input type="text" name="company_code" required>
            
            <label>Branch Name:</label>
            <input type="text" name="branch_name" required>
            
            <label>Branch Code:</label>
            <input type="text" name="branch_code" required>
            
            <label>Username:</label>
            <input type="text" name="username" required>
            
            <label>Password:</label>
            <input type="password" name="password" required>
            
            <label>Phone Number:</label>
            <input type="text" name="phone_no" required>
            
            <input type="submit" value="Register">
        </form>
    </div>
</body>
</html>
