<?php
// config1.php - Database connection file for realtime90 database
require "domain_root.php";

$successMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $company_name = $_POST['company_name'];
    $company_code = $_POST['company_code'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone_no = $_POST['phone_no'];
    $created_at = date("Y-m-d H:i:s");
    $status = 'Deactive';
    $mysql_db = $company_name . '_' . $company_code;
    $folder_name = $company_name . '_' . $company_code;

    // Check if company_code is unique
    $check_sql = "SELECT * FROM users WHERE company_code = '$company_code'";
    $result = $conn->query($check_sql);
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Company code already exists']);
        exit;
    }

    // Insert data into users table (realtime90 database)
    $sql = "INSERT INTO users (company_name, company_code, username, password, email, MYSQL_DB, status, created_at, phone_no)
            VALUES ('$company_name', '$company_code', '$username', '$password', '$email', '$mysql_db', '$status', '$created_at', '$phone_no')";
    
    if ($conn->query($sql) === TRUE) {
        // Create a new database for the company
        $create_db_sql = "CREATE DATABASE $mysql_db";
        if ($conn->query($create_db_sql) === TRUE) {
            try {
                // Connect to the new database
                $pdo = new PDO("mysql:host=localhost:3306;dbname=$mysql_db", "root", "root");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Import the structure from realtime90.sql file
                $sql_file = $_SERVER['DOCUMENT_ROOT'] . '/realtime.sql';
                $sql_content = file_get_contents($sql_file);

                // Break the SQL content into individual statements
                $queries = explode(";", $sql_content);

                // Execute each query individually
                foreach ($queries as $query) {
                    $trimmed_query = trim($query);
                    if (!empty($trimmed_query)) {
                        $pdo->exec($trimmed_query . ";");
                    }
                }

                // Create folder and copy files upon successful registration
                $source_dir = $_SERVER['DOCUMENT_ROOT'];
                $destination_dir = $source_dir . '/' . $folder_name;

                if (!file_exists($destination_dir)) {
                    mkdir($destination_dir, 0777, true); // Create the main folder
                }

                // Define folders and files to copy
                $folders_to_copy = ['commands', 'log', 'main', 'report', 'src', 'static', 'vendor'];
                $php_files = glob($source_dir . '/*.php'); // Copy all .php files in root directory

                // Function to copy files and folders recursively
                function copy_recursive($src, $dst) {
                    $dir = opendir($src);
                    mkdir($dst, 0777, true);
                    while (false !== ($file = readdir($dir))) {
                        if (($file != '.') && ($file != '..')) {
                            if (is_dir($src . '/' . $file)) {
                                copy_recursive($src . '/' . $file, $dst . '/' . $file);
                            } else {
                                copy($src . '/' . $file, $dst . '/' . $file);
                            }
                        }
                    }
                    closedir($dir);
                }

                // Copy each folder in $folders_to_copy
                foreach ($folders_to_copy as $folder) {
                    $src_folder = $source_dir . '/' . $folder;
                    $dest_folder = $destination_dir . '/' . $folder;
                    if (file_exists($src_folder)) {
                        copy_recursive($src_folder, $dest_folder);
                    }
                }

                // Copy each .php file in root directory
                foreach ($php_files as $file) {
                    copy($file, $destination_dir . '/' . basename($file));
                }

                // Success message
                $successMessage = "Registration successful! Redirecting to activation page...";
                
                // Redirect to activation.php after 3 seconds
                header("refresh:3; url=activation.php");
            } catch (PDOException $e) {
                echo "Error importing tables: " . $e->getMessage();
            }
        } else {
            echo "Error creating database: " . $conn->error;
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message {
            text-align: center;
            color: green;
            font-weight: bold;
        }
        .error-message {
            color: red;
        }
        #suggestions {
            list-style: none;
            padding: 0;
        }
        #suggestions li {
            background-color: #eee;
            margin: 2px;
            padding: 5px;
            cursor: pointer;
        }
    </style>
    <script>
        function checkCompanyCode() {
            var company_code = document.getElementById("company_code").value;
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "check_company_code.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    var errorMessage = document.getElementById("companyCodeError");
                    var suggestionsBox = document.getElementById("suggestions");
                    if (response.status === "error") {
                        errorMessage.innerHTML = "Company code already exists";
                        suggestionsBox.innerHTML = "";
                        response.suggestions.forEach(function (suggestion) {
                            var li = document.createElement("li");
                            li.textContent = suggestion;
                            li.onclick = function () {
                                document.getElementById("company_code").value = suggestion;
                                errorMessage.innerHTML = "";
                                suggestionsBox.innerHTML = "";
                            };
                            suggestionsBox.appendChild(li);
                        });
                    } else {
                        errorMessage.innerHTML = "";
                        suggestionsBox.innerHTML = "";
                    }
                }
            };
            xhr.send("company_code=" + company_code);
        }
    </script>
</head>
<body>

<h2>Company Registration</h2>

<form method="POST" action="">
    <label for="company_name">Company Name:</label>
    <input type="text" id="company_name" name="company_name" required>

    <label for="company_code">Company Code:</label>
    <input type="text" id="company_code" name="company_code" required onkeyup="checkCompanyCode()">
    <div class="error-message" id="companyCodeError"></div>
    <ul id="suggestions"></ul>

    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="phone_no">Phone No:</label>
    <input type="text" id="phone_no" name="phone_no" required>

    <input type="submit" value="Register">
    		<a href="login.php">login</</a>
</form>

<div class="message">
    <?php echo $successMessage; ?>
</div>

</body>
</html>
