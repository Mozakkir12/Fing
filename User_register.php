<?php
require "main/config/config1.php";

// ফর্ম সাবমিট হলে ডাটাবেসে ডাটা insert করা হবে
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $roll_id = $_POST['roll_id'] == 'User' ? 0 : 1; // User হলে 0, Manager হলে 1
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $dept_name = $_POST['dept_name'];
    $branch_name = $_POST['branch_name'];
    $shift_name = $_POST['shift_name'];
    $shift_code = $_POST['shift_code'];
    $type = $_POST['type'];

    // person টেবিলে ডাটা insert করা হচ্ছে
    $sql_person = "INSERT INTO person (id, name, roll_id, start_time, end_time)
                   VALUES ('$id', '$name', '$roll_id', '$start_time', '$end_time')";

    if ($conn->query($sql_person) === TRUE) {
        echo "Person information inserted successfully!<br>";
    } else {
        echo "Error: " . $sql_person . "<br>" . $conn->error;
    }

    // depertment টেবিলে dept_name insert করা হচ্ছে
    $sql_dept = "INSERT INTO depertment (id,dept_name) VALUES ('$id', '$dept_name')";
    if ($conn->query($sql_dept) === TRUE) {
        
    } else {
        echo "Error: " . $sql_dept . "<br>" . $conn->error;
    }

    // branch টেবিলে branch_name insert করা হচ্ছে
    $sql_branch = "INSERT INTO branch (id,branch_name) VALUES ('$id', '$branch_name')";
    if ($conn->query($sql_branch) === TRUE) {
        
    } else {
        echo "Error: " . $sql_branch . "<br>" . $conn->error;
    }

    // shift_manage টেবিলে shift_name, shift_code, type insert করা হচ্ছে
    $sql_shift = "INSERT INTO shift_manage (id,shift_name, shift_code, type) 
                  VALUES ('$id', '$shift_name', '$shift_code', '$type')";
    if ($conn->query($sql_shift) === TRUE) {
        echo "Shift information inserted successfully!<br>";
    } else {
        echo "Error: " . $sql_shift . "<br>" . $conn->error;
    }
}

// department এবং branch টেবিল থেকে ডেটা আনা হচ্ছে
$departments = $conn->query("SELECT dept_name FROM dupdept");
$branches = $conn->query("SELECT branch_name FROM dupbr");

// কানেকশন ক্লোজ করা হচ্ছে
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Person Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Insert Person, Department, Branch, and Shift Information</h2>
    <form method="POST" action="">
        <label for="id">ID:</label>
        <input type="text" id="id" name="id" required>

        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="roll_id">Roll ID:</label>
        <select id="roll_id" name="roll_id" required>
            <option value="User">User</option>
            <option value="Manager">Manager</option>
        </select>

        <label for="start_time">Start Time:</label>
        <input type="datetime-local" id="start_time" name="start_time" required>

        <label for="end_time">End Time:</label>
        <input type="datetime-local" id="end_time" name="end_time" required>

        <label for="dept_name">Department Name:</label>
        <select id="dept_name" name="dept_name" required>
            <option value="">Select Department</option>
            <?php
            if ($departments->num_rows > 0) {
                while ($row = $departments->fetch_assoc()) {
                    echo "<option value='" . $row['dept_name'] . "'>" . $row['dept_name'] . "</option>";
                }
            }
            ?>
        </select>

        <label for="branch_name">Branch Name:</label>
        <select id="branch_name" name="branch_name" required>
            <option value="">Select Branch</option>
            <?php
            if ($branches->num_rows > 0) {
                while ($row = $branches->fetch_assoc()) {
                    echo "<option value='" . $row['branch_name'] . "'>" . $row['branch_name'] . "</option>";
                }
            }
            ?>
        </select>

        <label for="shift_name">Shift Name:</label>
        <select id="shift_name" name="shift_name" required>
            <option value="Day">Day</option>
            <option value="Night">Night</option>
        </select>

        <label for="shift_code">Shift Code:</label>
        <input type="text" id="shift_code" name="shift_code" required>

        <label for="type">Shift Type:</label>
        <select id="type" name="type" required>
            <option value="Fixed">Fixed</option>
            <option value="General">General</option>
        </select>

        <button type="submit">Submit</button>
    </form>
</div>

</body>
</html>
