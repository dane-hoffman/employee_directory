<?php
require_once '../includes/db_connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    try {
        // Get PDO connection using existing function
        $pdo = getDBConnection();
        
        // Validate file upload
        $file = $_FILES['csv_file'];
        
        // Debug: Log file information
        error_log("Uploaded file type: " . $file['type']);
        error_log("Uploaded file size: " . $file['size']);
        
        // Accept more MIME types for CSV files
        $allowedFileTypes = [
            'text/csv',
            'application/vnd.ms-excel',
            'application/csv',
            'text/plain'
        ];
        
        if (!in_array($file['type'], $allowedFileTypes) && !empty($file['type'])) {
            throw new Exception("Invalid file type. Please upload a CSV file. Received type: " . $file['type']);
        }

        // Open and validate the CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception("Failed to open CSV file");
        }

        // Read and validate header row
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception("Failed to read CSV header");
        }
        
        // Expected headers
        $expectedHeaders = ['first_name', 'last_name', 'department', 'job_role', 'phone_number'];
        if ($header !== $expectedHeaders) {
            throw new Exception("Invalid CSV format. Please make sure your headers match the required format.");
        }

        // Prepare the insert statement
        $sql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        // Initialize counters
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            try {
                if (count($row) !== 5) {
                    throw new Exception("Invalid number of columns in row");
                }

                // Sanitize input
                $sanitizedRow = array_map('htmlspecialchars', $row);
                
                // Execute insert
                if ($stmt->execute($sanitizedRow)) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Failed to insert row: " . implode(", ", $row);
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Error in row: " . implode(", ", $row) . " - " . $e->getMessage();
            }
        }

        fclose($handle);

        // Generate appropriate message
        if ($errorCount === 0) {
            $message = "Successfully imported $successCount employees.";
        } else {
            $message = "Imported $successCount employees with $errorCount errors.\n";
            $message .= "Errors:\n" . implode("\n", $errors);
        }

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        error_log("CSV Import Error: " . $e->getMessage());
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
        }
        .message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .success { background-color: #dff0d8; color: #3c763d; }
        .error { background-color: #f2dede; color: #a94442; }
        .csv-template {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff;
            border-radius: 4px;
        }
        .instructions {
            margin: 20px 0;
            padding: 15px;
            background-color: #fff;
            border-radius: 4px;
            border-left: 4px solid var(--primary-color);
        }
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
        
        <div class="instructions">
            <h3>Important Instructions:</h3>
            <ol>
                <li>Your CSV file must have exactly these column headers (case-sensitive):
                    <code>first_name,last_name,department,job_role,phone_number</code>
                </li>
                <li>Make sure there are no empty lines at the end of the file</li>
                <li>All fields except phone_number are required</li>
                <li>Maximum length for each field is 50 characters</li>
            </ol>
        </div>

        <form action="bulk_upload.php" method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <input type="submit" value="Upload and Import">
        </form>
        
        <div class="csv-template">
            <h3>Example CSV Format:</h3>
            <pre>first_name,last_name,department,job_role,phone_number
John,Doe,IT,Developer,555-0123
Jane,Smith,HR,Manager,555-0124</pre>
            <p>Note: Save your CSV file with UTF-8 encoding for best results.</p>
        </div>
    </div>
</div>
</body>
</html>