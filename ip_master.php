<?php
session_start();
$mysql_db = isset($_SESSION['MYSQL_DB']) ? $_SESSION['MYSQL_DB'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip = $_POST['ip'];
    $port = $_POST['port'];

    $dbFileContent = <<<PHP
<?php
define('SERVER_IP', '$ip');
define('SERVER_PORT', '$port');

define('MAX_THREADS', 32);

define('MYSQL_HOST', 'localhost:3306');
define('MYSQL_DB', '$mysql_db');
define('MYSQL_PORT', '3306');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'root');

/*
try{
    \$pdoConn = new PDO("mysql:host=".MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
    \$pdoConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdoConn->exec("use ".MYSQL_DB.";");
} catch(PDOException \$e) {
    echo \$e->getMessage();
}
*/
?>
PHP;

    // Config file path
    $file_path = 'main/db/config.php';
    if (file_put_contents($file_path, $dbFileContent)) {
        $message = "Ip Address and Port set successfully!";
    } else {
        $message = "Ip Address not set Successfully";
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config File Creator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        input[type="submit"] {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .message {
            color: green;
            margin-top: 15px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

    <h1>Config File Creator</h1>
    <form method="post">
        <label for="ip">Ip Address:</label>
        <input type="text" id="ip" name="ip" placeholder="192.168.0.1" 
               pattern="^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$" 
               title="দয়া করে একটি বৈধ IPv4 ঠিকানা প্রবেশ করুন (যেমন: 192.168.0.1)" required>

        <label for="port">Port:</label>
        <input type="text" id="port" name="port" value="7788" readonly>

        <input type="submit" value="Create Config File">
    </form>

    <?php if (isset($message)): ?>
        <div class="<?php echo isset($error) ? 'error' : 'message'; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
</body>
</html>
