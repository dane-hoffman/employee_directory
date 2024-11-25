<?php
require_once '../includes/db_connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if file was uploaded
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        try {
            // Retrieve a database connection
            $pdo = getDBConnection();

            // Begin a transaction for bulk insert
            $pdo->beginTransaction();

            // Open the uploaded CSV file
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

            // Skip the header row if it exists
            $headers = fgetcsv($file);

            // Prepare the SQL statement for insertion
            $sql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            // Counters for tracking
            $total_rows = 0;
            $successful_imports = 0;
            $failed_imports = 0;

            // Read and process each row
            while (($data = fgetcsv($file)) !== FALSE) {
                $total_rows++;

                // Ensure the row has the correct number of columns
                if (count($data) >= 5) {
                    // Sanitize input
                    $first_name = htmlspecialchars(trim($data[0]));
                    $last_name = htmlspecialchars(trim($data[1]));
                    $department = htmlspecialchars(trim($data[2]));
                    $job_role = htmlspecialchars(trim($data[3]));
                    $phone_number = htmlspecialchars(trim($data[4]));

                    try {
                        // Execute the statement
                        $stmt->execute([$first_name, $last_name, $department, $job_role, $phone_number]);
                        $successful_imports++;
                    } catch (PDOException $e) {
                        // Log individual row insertion errors
                        error_log("Error inserting row: " . $e->getMessage());
                        $failed_imports++;
                    }
                } else {
                    // Row does not have enough columns
                    $failed_imports++;
                }
            }

            // Commit the transaction
            $pdo->commit();

            // Close the file
            fclose($file);

            // Prepare success message
            $message = "CSV Import Complete: 
                Total Rows: {$total_rows}
                Successful Imports: {$successful_imports}
                Failed Imports: {$failed_imports}";

        } catch (Exception $e) {
            // Rollback the transaction in case of major error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Log and display error
            error_log("CSV Import Error: " . $e->getMessage());
            $message = "An error occurred during CSV import. Please check the file format and try again.";
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Handle single employee addition (keeping the existing single employee add functionality)
        try {
            // Retrieve a database connection
            $pdo = getDBConnection();

            // Sanitize and validate user input
            $first_name = htmlspecialchars($_POST['first_name']);
            $last_name = htmlspecialchars($_POST['last_name']);
            $department = htmlspecialchars($_POST['department']);
            $job_role = htmlspecialchars($_POST['job_role']);
            $phone_number = htmlspecialchars($_POST['phone_number']);

            // SQL query to insert data into the employees table
            $sql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            // Execute the statement with the provided data
            $stmt->execute([$first_name, $last_name, $department, $job_role, $phone_number]);

            // Success message
            $message = "Employee added successfully!";
        } catch (PDOException $e) {
            // Log and display an error message
            error_log("Error adding employee: " . $e->getMessage());
            $message = "An error occurred while adding the employee. Please try again later.";
        }
    }
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

    <?php if (!empty($message)): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <div class="form-section">
        <h2>Add Single Employee</h2>
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

    <div class="form-section">
        <h2>Bulk Import from CSV</h2>
        <form action="add_employee.php" method="post" enctype="multipart/form-data">
            <label for="csv_file">CSV File:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>

            <p class="csv-instructions">
                CSV File Format: 
                First Name, Last Name, Department, Job Role, Phone Number
                <br>
                Example: John, Doe, Cardiology, Nurse, 555-1234
            </p>

            <input type="submit" value="Import CSV">
        </form>
    </div>
</div>
</body>
</html>