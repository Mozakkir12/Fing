<?php
require __DIR__ . '/vendor/autoload.php'; // Twilio SDK ফাইলটি ইনক্লুড করুন

use Twilio\Rest\Client;

// Twilio Account Details
$account_sid = 'ACcd7703beccca6a4f38f35a0b0fcdf747';  // Twilio থেকে প্রাপ্ত Account SID
$auth_token = 'c478bd20ffb688aaee1153b85f452bc4';    // Twilio থেকে প্রাপ্ত Auth Token
$twilio_number = '+14049944396'; // Twilio এর নম্বর

// SMS পাঠানোর ফাংশন
function sendSMS($phone, $message) {
    global $account_sid, $auth_token, $twilio_number;

    // Twilio Client তৈরি
    $client = new Client($account_sid, $auth_token);

    try {
        // SMS পাঠানো হচ্ছে
        $client->messages->create(
            $phone, // যাকে SMS পাঠাবেন
            [
                'from' => $twilio_number, // Twilio নম্বর
                'body' => 'প্রিয় গ্রাহক আপনার কোম্পানি $comphany_name কোড $comphany_code টকা    $payable_amount মেয়াদ $offer_for সফলভাবে অ্যাকটিভ হয়েছে।

সাহায্যের জন্য 01994762347 নাম্বারে কল করুন।

ধন্যবাদ। '// বার্তা
            ]
        );
        return true;
    } catch (Exception $e) {
        // যদি কোনো সমস্যা হয়
        error_log("Error sending SMS: " . $e->getMessage());
        return false;
    }
}

// ডাটাবেসের সাথে সংযোগ করুন
require "domain_root.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ফর্ম থেকে ডেটা সংগ্রহ
    $customer_name = $_POST['customer_name'];
    $company_name = $_POST['company_name'];
    $company_code = $_POST['company_code'];
    $payment_type = $_POST['payment_type'];
    $phone_no = $_POST['phone_no'];
    $payable_amount = $_POST['payable_amount'];
    $transaction_id = $_POST['transaction_id'];
    $offer_for = $_POST['offer_for'];
    $expire_date = $_POST['expire_date'];

    // Company validation
 
        // Insert data into `buying` table
        $insert_sql = "INSERT INTO buying (customer_name, company_name, company_code, payment_type, phone_no, payable_amount, transaction_id, offer_for, expire_date,status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssssssss", $customer_name, $company_name, $company_code, $payment_type, $phone_no, $payable_amount, $transaction_id, $offer_for, $expire_date);

        if ($insert_stmt->execute()) {
            // SMS পাঠানো হচ্ছে
            $message = "Your offer purchase is successful for $company_name ($company_code). Paid amount: $payable_amount.";
            if (sendSMS($phone_no, $message)) {
                echo "<div style='color: green;'>Success! The offer has been purchased successfully and SMS sent to $phone_no.</div>";
            } else {
                echo "<div style='color: orange;'>Purchase successful but SMS could not be sent.</div>";
            }
        } else {
            echo "<div style='color: red;'>Error! Could not complete the purchase.</div>";
        }
    } else {
        echo "<div style='color: red;'>Error! Invalid Company Name or Company Code.</div>";
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Activation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            text-align: center;
        }
        .offer-box {
            display: inline-block;
            width: 200px;
            height: 200px;
            border: 1px solid #333;
            margin: 10px;
            padding: 20px;
            cursor: pointer;
            background-color: #f4f4f4;
        }
        .offer-box:hover {
            background-color: #ddd;
        }
        .form-container {
            display: none;
            margin-top: 20px;
        }
        input, select {
            padding: 10px;
            margin: 5px;
            width: 100%;
        }
        button {
            padding: 10px 20px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Select Your Offer</h2>

    <div class="offer-box" onclick="showForm(500, 'One Month', 30)">
        <h3>One Month</h3>
        <p>Price: 500 BDT</p>
    </div>

    <div class="offer-box" onclick="showForm(3000, 'Six Months', 180)">
        <h3>Six Months</h3>
        <p>Price: 3000 BDT</p>
    </div>

    <div class="offer-box" onclick="showForm(6000, 'One Year', 365)">
        <h3>One Year</h3>
        <p>Price: 6000 BDT</p>
    </div>

    <div class="form-container" id="purchaseForm">
        <form method="POST">
            <input type="text" name="customer_name" placeholder="Customer Name" required>
            <input type="text" name="company_name" placeholder="Company Name" required>
            <input type="text" name="company_code" placeholder="Company Code" required>
            <select name="payment_type" required>
                <option value="" disabled selected>Select Payment Type</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Mobile Payment">Mobile Payment</option>
            </select>
            <input type="text" name="phone_no" placeholder="Phone No." required>
            <input type="text" name="payable_amount" id="payable_amount" readonly>
            <input type="text" name="transaction_id" placeholder="Transaction ID" required>
            <input type="text" name="offer_for" id="offer_for" readonly>
            <input type="hidden" name="expire_date" id="expire_date">
            <button type="submit">Purchase Offer</button>
        </form>
    </div>
</div>

<script>
function showForm(price, offer, days) {
    document.getElementById('payable_amount').value = price;
    document.getElementById('offer_for').value = offer;

    // Calculate the expire date based on today's date and the offer duration
    const today = new Date();
    const expireDate = new Date(today);
    expireDate.setDate(today.getDate() + parseInt(days));
    const formattedExpireDate = expireDate.toISOString().split('T')[0];
    document.getElementById('expire_date').value = formattedExpireDate;

    document.getElementById('purchaseForm').style.display = 'block';
}
</script>

</body>
</html>
