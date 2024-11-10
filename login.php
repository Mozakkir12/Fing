<?php
// config.php - Database connection settings
require "domain_root.php";

// Login logic
// লগইন সফল হলে
session_start();
$_SESSION['MYSQL_DB'] = $row['MYSQL_DB']; // $row হল ডাটাবেজ থেকে প্রাপ্ত তথ্য

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['login_type'];
    $company_name = $_POST['company_name'];
    $company_code = $_POST['company_code'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($type === 'company') {
        $sql = "SELECT MYSQL_DB, status FROM users WHERE company_name = ? AND company_code = ? AND username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $company_name, $company_code, $username, $password);
    } elseif ($type === 'branch') {
        $branch_name = $_POST['branch_name'];
        $branch_code = $_POST['branch_code'];
        $sql = "SELECT MYSQL_DB, status FROM branches WHERE company_name = ? AND company_code = ? AND branch_name = ? AND branch_code = ? AND username = ? AND password = ?";
        $stmt->bind_param('ssssss', $company_name, $company_code, $branch_name, $branch_code, $username, $password);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $db_name = $row['MYSQL_DB'];
        $folder = $type === 'company'
            ? "{$company_name}_{$company_code}"
            : "{$company_name}_{$company_code}_{$branch_name}_{$branch_code}";

        if ($row['status'] === 'Deactive') {
            header("Location: activation.php");
            exit();
        } else {
    
        
         // Create 'main/db/[folder]/[MYSQL_DB].php'
$dbDir = "main/db/{$folder}";
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// স্বয়ংক্রিয়ভাবে IP ঠিকানা সেট করা হচ্ছে
$ipAddress = gethostbyname(gethostname()); // বর্তমান মেশিনের IP ঠিকানা

$dbFileContent = <<<PHP
<?php
define('SERVER_IP', '192.168.110.53');
define('SERVER_PORT', '7788');

define('MAX_THREADS', 32);

define('MYSQL_HOST', 'localhost:3306');
define('MYSQL_DB', '$db_name');
define('MYSQL_PORT', '3306');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'root');


/*
try{
	$pdoConn = new PDO("mysql:host=".MYSQL_HOST,MYSQL_USER,MYSQL_PASS);
	$pdoConn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$pdoConn->exec("use ".MYSQL_DB.";");
}catch(PDOException $e){
	echo $e->getMessage();
}
*/
?>

PHP;

file_put_contents("{$dbDir}/config.php", $dbFileContent);


            // Create 'main/config/[folder]/config1.php'
            $configDir = "main/config/{$folder}";
            if (!is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }
            $configFileContent = <<<PHP
<?php
\$host = 'localhost:3306';
\$dbname = '{$db_name}';
\$username = 'root';
\$password = 'root';
\$conn = new mysqli(\$host, \$username, \$password, \$dbname);
if (\$conn->connect_error) {
    die("Connection failed: " . \$conn->connect_error);
}
?>
PHP;
            file_put_contents("{$folder}/main/config/config1.php", $configFileContent);

            // Create 'main/db/[folder]/pdo.php' with the required content
            $pdoFileContent = <<<PHP
<?php
require "config.php";

class PDOTool extends PDO
{
    protected \$tabName = ''; //储存表名
    protected \$sql = '';//存储最后执行的sql语句
    
    protected \$limit = '';//存储limit条件
    protected \$order = '';//存储order排序条件
    protected \$field = '*';//存储要查询的字段
    
    protected \$allFields = [];//存储当前表的所有字段

    public function __construct(\$tabName)
    {
        parent::__construct('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB.';charset=utf8;port='.MYSQL_PORT,MYSQL_USER,MYSQL_PASS);
        \$this->tabName = \$tabName;
    }

    protected function getFields()
    {
        \$sql = "desc {\$this->tabName}";
        \$stmt = \$this->query(\$sql);
    }

    public function add(\$data)
    {
        if (empty(\$data))
            return;

        \$keys = join(',',array_keys(\$data));
        \$vals = join("','",\$data);
        \$sql = "insert into {\$this->tabName}({\$keys}) values('{\$vals}')";
        \$this->sql = \$sql;
        return \$this->exec2(\$sql);
    }
    
    public function delete(\$where)
    {
        \$sql = "delete from {\$this->tabName} {\$where}";
        return (int)\$this->exec2(\$sql);
    }

    public function save(\$data,\$where)
    {
        \$str = '';
        foreach (\$data as \$k=>\$v) {
            \$str .= "`\$k`='\$v',";
        }
        \$str=substr(\$str,0,-1);
        if (empty(\$str)) {
            return;
        }
        \$str = rtrim(\$str,'');
        \$sql = "update {\$this->tabName} set \$str {\$where}";
        \$this->sql = \$sql;
        return (int)\$this->exec2(\$sql);
    }
    
    public static function writeLog(\$save,\$type="SQL: ")
    {
        \$logFilePath = './log/debug.txt';
        if (is_readable(\$logFilePath)) {
            \$size = filesize(\$logFilePath);
            if(\$size>1024*1024*2) //2M
            {
                \$logFilePath2 = './log/debug2.txt';
                if (is_readable(\$logFilePath2))
                    unlink(\$logFilePath2);
                rename(\$logFilePath,\$logFilePath2);
            }
        }

        if(strlen(\$save)>200)
            \$save=substr(\$save,0,200);
        file_put_contents(\$logFilePath,  \$type.\$save." [".date("Y-m-d H:i:s")."]\r\n",FILE_APPEND);
    }

    public function exec2(\$sql)
    {
        \$save=\$sql;
        \$this->writeLog(\$save,"Exc: ");
        \$ret= (int)\$this->exec(\$sql);
        return \$ret;
    }
    
    public function select(\$where,\$bSave=true)
    {
        \$sql = "select {\$this->field} from {\$this->tabName} {\$where} {\$this->order} {\$this->limit}";
        \$this->sql = \$sql;
        if(\$bSave)
        {
            \$this->writeLog(\$sql);
        }
        \$stmt = \$this->query(\$sql);
        if (\$stmt) {
            return \$stmt->fetchAll(2);
        }
        return [];
    }

    public function find(\$id)
    {
        \$sql = "select {\$this->field} from {\$this->tabName} where id={\$id} limit 1";
        \$this->sql = \$sql;
        \$stmt = \$this->query(\$sql);
        if (\$stmt) {
            return \$stmt->fetch(2);
        }
        return [];
    }

    public function count(\$where)
    {
        \$sql = "select count(*) from {\$this->tabName} {\$where} limit 1";
        \$this->sql = \$sql;
        \$stmt = \$this->query(\$sql);
        if (\$stmt) {
            return (int)\$stmt->fetch()[0];
        }
        return 0;
    }

    public function _sql()
    {
        return \$this->sql;
    }

    public function limit(\$str)
    {
        \$this->limit = 'limit '.\$str;
        return \$this;
    }

    public function tableName(\$str)
    {
        if(\$this->tabName!=\$str)
        {
            \$this->tabName = \$str;
        }
        \$this->order ='';
        \$this->field='*';
        return \$this;
    }

    public function order(\$str)
    {
        \$this->order = 'order by '.\$str;
        return \$this;
    }

    public function field(\$str)
    {
        \$this->field = \$str;
        return \$this;
    }
}
?>
PHP;
            file_put_contents("{$folder}/main/db/pdo.php", $pdoFileContent);

            $_SESSION['MYSQL_DB'] = $db_name;
            header("Location: {$folder}/index.php");
            exit();
        }
    } else {
        echo "<p style='color:red;'>Invalid login credentials!</p>";
    }
}

if (isset($_SESSION['MYSQL_DB'])) {
    $db_name = $_SESSION['MYSQL_DB'];
    $folder = "{$company_name}_{$company_code}" . ($type === 'branch' ? "_{$branch_name}_{$branch_code}" : "");
    $dbFile = "{$folder}/main/db/config.php";
    $configFile = "{$folder}/main/config/config1.php";
    $pdoFile = "{$folder}/main/db/pdo.php";

    if (file_exists($dbFile)) unlink($dbFile);
    if (file_exists($configFile)) unlink($configFile);
    if (file_exists($pdoFile)) unlink($pdoFile);
    session_destroy();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company & Branch Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); width: 400px; }
        .login-container h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .login-container button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .login-container button:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="login_type">Login Type</label>
                <select name="login_type" id="login_type" required>
                    <option value="company">Company Login</option>
                    <option value="branch">Branch Login</option>
                </select>
            </div>
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" name="company_name" id="company_name" required>
            </div>
            <div class="form-group">
                <label for="company_code">Company Code</label>
                <input type="text" name="company_code" id="company_code" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group" id="branch-fields" style="display: none;">
                <label for="branch_name">Branch Name</label>
                <input type="text" name="branch_name" id="branch_name">
                <label for="branch_code">Branch Code</label>
                <input type="text" name="branch_code" id="branch_code">
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        document.getElementById('login_type').addEventListener('change', function() {
            var branchFields = document.getElementById('branch-fields');
            branchFields.style.display = this.value === 'branch' ? 'block' : 'none';
        });
    </script>
</body>
</html>
