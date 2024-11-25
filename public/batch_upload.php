<?php
require_once '../includes/db_connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    try {
        // Retrieve a database connection
        $pdo = getDBConnection();

        // Validate file upload
        $file = $_FILES['csv_file'];
        $allowedFileTypes = ['text/csv', 'application/vnd.ms-excel'];
        
        if (!in_array($file['type'], $allowedFileTypes)) {
            throw new Exception("Invalid file type. Please upload a CSV file.");
        }

        // Open the CSV file
        $handle = fopen($file['tmp_name'], 'r');
        
        // Skip the header row if it exists
        fgetcsv($handle);

        // Prepare the SQL statement for batch insertion
        $sql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        // Counter for successful and failed imports
        $successCount = 0;
        $failedCount = 0;

        // Process each row
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Ensure we have the correct number of columns
            if (count($data) >= 5) {
                try {
                    // Sanitize input
                    $first_name = htmlspecialchars(trim($data[0]));
                    $last_name = htmlspecialchars(trim($data[1]));
                    $department = htmlspecialchars(trim($data[2]));
                    $job_role = htmlspecialchars(trim($data[3]));
                    $phone_number = htmlspecialchars(trim($data[4]));

                    // Execute the statement with the provided data
                    $stmt->execute([$first_name, $last_name, $department, $job_role, $phone_number]);
                    $successCount++;
                } catch (PDOException $e) {
                    $failedCount++;
                    error_log("Error inserting row: " . $e->getMessage());
                }
            } else {
                $failedCount++;
            }
        }

        fclose($handle);

        // Prepare success message
        $message = "CSV Import Complete. 
                    Successful imports: $successCount. 
                    Failed imports: $failedCount.";

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Employee Upload</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .upload-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
        }
        .message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .success { background-color: #dff0d8; color: #3c763d; }
        .error { background-color: #f2dede; color: #a94442; }
    </style>
</head>
<body>
<header>
    <img src="https://mrhmoab.org/wp-content/uploads/2020/11/MRHLogoColor2015-website-colors-3.png" alt="Moab Regional Hospital Logo" class="logo">
    <h1>Hospital Employee Directory</h1>
</header>
<div class="container">
    <h1>Bulk Employee Upload</h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="add_employee.php">Add Employee</a></li>
            <li><a href="bulk_upload.php">Bulk Upload</a></li>
            <li><a href="search.php">Search Employees</a></li>
        </ul>
    </nav>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="upload-container">
        <h2>Upload CSV File</h2>
        <form action="bulk_upload.php" method="post" enctype="multipart/form-data">
            <p>CSV should have columns: First Name, Last Name, Department, Job Role, Phone Number</p>
            <input type="file" name="csv_file" accept=".csv" required>
            <input type="submit" value="Upload and Import">
        </form>
    </div>
</div>
</body>
</html>