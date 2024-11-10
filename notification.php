<?php
// Database Connection
$servername = "localhost:3306";
$username = "root";
$password = "root";
$dbname = "domain_root";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get new notifications count
$new_notifications_result = $conn->query("SELECT COUNT(*) AS new_notifications FROM buying WHERE status = 'new'");
$new_notifications = $new_notifications_result->fetch_assoc()['new_notifications'];

// Get notifications
$notifications_result = $conn->query("SELECT `id`, `customer_name`, `company_name`, `company_code`, `payment_type`, `phone_no`, `payable_amount`, `transaction_id`, `offer_for`, `expire_date` FROM buying WHERE status = 'new'");

// Calculate total payable amounts
$daily_total_result = $conn->query("SELECT SUM(payable_amount) AS daily_total FROM buying WHERE DATE(expire_date) = CURDATE()");
$weekly_total_result = $conn->query("SELECT SUM(payable_amount) AS weekly_total FROM buying WHERE YEARWEEK(expire_date, 1) = YEARWEEK(CURDATE(), 1)");
$monthly_total_result = $conn->query("SELECT SUM(payable_amount) AS monthly_total FROM buying WHERE MONTH(expire_date) = MONTH(CURDATE()) AND YEAR(expire_date) = YEAR(CURDATE())");

$daily_total = $daily_total_result->fetch_assoc()['daily_total'];
$weekly_total = $weekly_total_result->fetch_assoc()['weekly_total'];
$monthly_total = $monthly_total_result->fetch_assoc()['monthly_total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Example</title>
     <style>
        body {
            font-family: Arial, sans-serif;
        }
        #notificationButton {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #notificationSidebar {
            position: fixed;
            right: -300px;
            top: 0;
            width: 300px;
            height: 100%;
            background-color: #f1f1f1;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.5);
            transition: right 0.3s;
            overflow-y: auto;
            padding: 20px;
            display: none; /* Sidebar hide করার জন্য */
        }
        #notificationSidebar.active {
            right: 0;
            display: block; /* Sidebar প্রদর্শন করার জন্য */
        }
        .notification {
            background-color: #e7f1ff;
            border: 1px solid #007bff;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .total-box {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<button id="notificationButton">Notifications (<span id="notificationCount"><?php echo $new_notifications; ?></span>)</button>

<div id="notificationSidebar">
    <h2>Notifications</h2>
    <div id="notificationList">
        <?php while ($row = $notifications_result->fetch_assoc()): ?>
            <div class="notification" data-id="<?php echo $row['id']; ?>">
                <p>ID: <?php echo $row['id']; ?></p>
                <p>Name: <?php echo $row['customer_name']; ?></p>
                <p>Company: <?php echo $row['company_name']; ?> (<?php echo $row['company_code']; ?>)</p>
                <p>Payment Type: <?php echo $row['payment_type']; ?></p>
                <p>Phone: <?php echo $row['phone_no']; ?></p>
                <p>Amount: <?php echo $row['payable_amount']; ?></p>
                <p>Transaction ID: <?php echo $row['transaction_id']; ?></p>
                <p>Offer: <?php echo $row['offer_for']; ?></p>
                <p>Expire Date: <?php echo $row['expire_date']; ?></p>
                <button onclick="confirmPayment(<?php echo $row['id']; ?>, '<?php echo $row['company_name']; ?>', '<?php echo $row['company_code']; ?>')">Confirm</button>
                <button onclick="cancelPayment(<?php echo $row['id']; ?>)">Cancel</button>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="total-box">
    <h3>Today's Total Payable Amount: <?php echo $daily_total; ?></h3>
</div>
<div class="total-box">
    <h3>This Week's Total Payable Amount: <?php echo $weekly_total; ?></h3>
</div>
<div class="total-box">
    <h3>This Month's Total Payable Amount: <?php echo $monthly_total; ?></h3>
</div>

<script>
    document.getElementById('notificationButton').addEventListener('click', function() {
        const sidebar = document.getElementById('notificationSidebar');
        sidebar.classList.toggle('active');
    });

    function updateStatus(id, status, companyName = '', companyCode = '') {
        fetch(`update_status.php?id=${id}&status=${status}&company_name=${companyName}&company_code=${companyCode}`)
            .then(response => response.text())
            .then(data => {
                alert(data);
                const notificationElement = document.querySelector(`.notification[data-id='${id}']`);
                if (notificationElement) {
                    notificationElement.remove();
                }
                const notificationCount = document.getElementById('notificationCount');
                notificationCount.textContent = parseInt(notificationCount.textContent) - 1;
            });
    }

    function confirmPayment(id, companyName, companyCode) {
        updateStatus(id, 'Conframed', companyName, companyCode);
    }

    function cancelPayment(id) {
        updateStatus(id, 'inactive');
    }
</script>

</body>
</html>
