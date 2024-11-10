<?php
require"auth.php";
// Database connection
require"main/config/config1.php";


// Handle delete operation
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM person WHERE id = $delete_id";
    $conn->query($delete_sql);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle edit operation
if (isset($_POST['edit'])) {
    $edit_id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $roll_id = intval($_POST['roll_id']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $dept_name = $conn->real_escape_string($_POST['dept_name']);
    $shift_code = $conn->real_escape_string($_POST['shift_code']);
    $shift_name = $conn->real_escape_string($_POST['shift_name']);
    $type = $conn->real_escape_string($_POST['type']);
    
    // Update person table
    $update_person_sql = "UPDATE person SET name='$name', roll_id='$roll_id', start_time='$start_time', end_time='$end_time' WHERE id=$edit_id";
    $conn->query($update_person_sql);
    
    // Update branch table (assuming the branch id is linked with person id)
    $update_branch_sql = "UPDATE branch SET branch_name='$branch_name' WHERE id=$edit_id";
    $conn->query($update_branch_sql);
    
    // Update department table (assuming the department id is linked with person id)
    $update_dept_sql = "UPDATE depertment SET dept_name='$dept_name' WHERE id=$edit_id";
    $conn->query($update_dept_sql);
    
    // Update shift_manage table (assuming the shift code is linked with person id)
    $update_shift_sql = "UPDATE shift_manage SET shift_code='$shift_code', shift_name='$shift_name', type='$type' WHERE id=$edit_id";
    $conn->query($update_shift_sql);
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch data using JOIN queries
$sql = "
    SELECT 
        p.id AS person_id, p.name, p.roll_id, p.start_time, p.end_time,
        b.branch_name,
        d.dept_name,
        s.shift_code, s.shift_name, s.type
    FROM 
        person p
    LEFT JOIN 
        branch b ON p.id = b.id
    LEFT JOIN 
        depertment d ON p.id = d.id
    LEFT JOIN 
        shift_manage s ON p.id = s.id
";
$result = $conn->query($sql);

// Fetch branch names for the select box
$branches = $conn->query("SELECT branch_name FROM branch");
$departments = $conn->query("SELECT dept_name FROM depertment");
$shifts = $conn->query("SELECT shift_code FROM shift_manage");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Person Manage Form</title>
    <style>
        /* CSS styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 80%;
            margin: auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        td {
            text-align: center;
        }
        .btn {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .edit-form {
            display: none;
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Person Manage Form</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Branch Name</th>
                    <th>Department Name</th>
                    <th>Shift Code</th>
                    <th>Shift Name</th>
                    <th>Shift Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Check roll_id and display as User or Manager
                        $role = ($row['roll_id'] == 0) ? 'User' : 'Manager';
                        echo "<tr>
                                <td>{$row['person_id']}</td>
                                <td>{$row['name']}</td>
                                <td>{$role}</td>
                                <td>{$row['start_time']}</td>
                                <td>{$row['end_time']}</td>
                                <td>{$row['branch_name']}</td>
                                <td>{$row['dept_name']}</td>
                                <td>{$row['shift_code']}</td>
                                <td>{$row['shift_name']}</td>
                                <td>{$row['type']}</td>
                                <td>
                                    <button class='edit-btn' data-id='{$row['person_id']}' data-name='{$row['name']}' data-roll='{$row['roll_id']}' data-start='{$row['start_time']}' data-end='{$row['end_time']}' data-branch='{$row['branch_name']}' data-dept='{$row['dept_name']}' data-shiftcode='{$row['shift_code']}' data-shiftname='{$row['shift_name']}' data-type='{$row['type']}'>Edit</button>
                                    <a href='?delete={$row['person_id']}' onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='11'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="edit-form" id="editForm">
            <h3>Edit Person</h3>
            <form method="POST">
                <input type="hidden" name="id" id="editId" required>
                <label>Name:</label>
                <input type="text" name="name" id="editName" required>
                <label>Roll ID:</label>
                <select name="roll_id" id="editRoll" required>
                    <option value="0">User</option>
                    <option value="1">Manager</option>
                </select>
                <label>Start Time:</label>
                <input type="datetime-local" name="start_time" id="editStartTime" required>
                <label>End Time:</label>
                <input type="datetime-local" name="end_time" id="editEndTime" required>
                <label>Branch Name:</label>
                <select name="branch_name" id="editBranchName" required>
                    <?php 
                    // Fetching branch names dynamically
                    $branches->data_seek(0); // Reset pointer
                    while ($branch = $branches->fetch_assoc()) { ?>
                        <option value="<?php echo $branch['branch_name']; ?>"><?php echo $branch['branch_name']; ?></option>
                    <?php } ?>
                </select>
                <label>Department Name:</label>
                <select name="dept_name" id="editDeptName" required>
                    <?php 
                    // Fetching department names dynamically
                    $departments->data_seek(0); // Reset pointer
                    while ($department = $departments->fetch_assoc()) { ?>
                        <option value="<?php echo $department['dept_name']; ?>"><?php echo $department['dept_name']; ?></option>
                    <?php } ?>
                </select>
                <label>Shift Code:</label>
                <select name="shift_code" id="editShiftCode" required>
                    <?php 
                    // Fetching shift codes dynamically
                    $shifts->data_seek(0); // Reset pointer
                    while ($shift = $shifts->fetch_assoc()) { ?>
                        <option value="<?php echo $shift['shift_code']; ?>"><?php echo $shift['shift_code']; ?></option>
                    <?php } ?>
                </select>
                <label>Shift Name:</label>
                <select name="shift_name" id="editShiftName" required>
                    <option value="Day">Day</option>
                    <option value="Night">Night</option>
                </select>
                <label>Shift Type:</label>
                <select name="type" id="editType" required>
                    <option value="Fixed">Fixed</option>
                    <option value="General">General</option>
                </select>
                <button type="submit" name="edit" class="btn">Update</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('editId').value = this.dataset.id;
                document.getElementById('editName').value = this.dataset.name;
                document.getElementById('editRoll').value = this.dataset.roll;
                document.getElementById('editStartTime').value = this.dataset.start;
                document.getElementById('editEndTime').value = this.dataset.end;
                document.getElementById('editBranchName').value = this.dataset.branch;
                document.getElementById('editDeptName').value = this.dataset.dept;
                document.getElementById('editShiftCode').value = this.dataset.shiftcode;
                document.getElementById('editShiftName').value = this.dataset.shiftname;
                document.getElementById('editType').value = this.dataset.type;
                document.getElementById('editForm').style.display = 'block';
            });
        });
    </script>
</body>
</html>
 