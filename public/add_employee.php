<?php
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $department = $_POST['department'];
    $job_role = $_POST['job_role'];
    $phone_number = $_POST['phone_number'];

    $sql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number) VALUES (?, ?, ?, ?, ?)";
    //$stmt = $pdo->prepare($sql);
    $stmt->execute([$first_name, $last_name, $department, $job_role, $phone_number]);

    $message = "Employee added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
        <img src="https://mrhmoab.org/wp-content/uploads/2020/11/MRHLogoColor2015-website-colors-3.png" alt="Moab Regional Hospital Logo" class="logo">
        <h1>Hospital Employee Directory</h1>
    </header>
    <div class="container">
        <h1>Add Employee</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="add_employee.php">Add Employee</a></li>
                <li><a href="search.php">Search Employees</a></li>
            </ul>
        </nav>
        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>
        <form action="add_employee.php" method="post">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required>

            <label for="department">Department:</label>
            <input type="text" id="department" name="department" required>

            <label for="job_role">Job Role:</label>
            <input type="text" id="job_role" name="job_role" required>

            <label for="phone_number">Phone Number:</label>
            <input type="tel" id="phone_number" name="phone_number" required>

            <input type="submit" value="Add Employee">
        </form>
    </div>
</body>
</html>