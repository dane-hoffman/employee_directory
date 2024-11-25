<?php
require_once '../includes/db_connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    try {
        // Get PDO connection using existing function
        $pdo = getDBConnection();
        
        // Get PostgreSQL connection string from environment variables
        $db_host = getenv('POSTGRES_HOST');
        $db_name = getenv('POSTGRES_DB');
        $db_user = getenv('POSTGRES_USER');
        $db_password = getenv('POSTGRES_PASSWORD');
        $db_port = getenv('POSTGRES_PORT') ?: '5432';
        
        // Create PostgreSQL connection string
        $pgConnection = pg_connect("host=$db_host port=$db_port dbname=$db_name user=$db_user password=$db_password");
        
        if (!$pgConnection) {
            throw new Exception("Could not establish PostgreSQL connection for COPY operation");
        }

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
            error_log("Invalid file type: " . $file['type']);
            throw new Exception("Invalid file type. Please upload a CSV file. Received type: " . $file['type']);
        }

        // Create a temporary table for the import
        $createTempTable = "CREATE TEMP TABLE temp_employees (
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            department VARCHAR(50),
            job_role VARCHAR(50),
            phone_number VARCHAR(20)
        )";
        
        pg_query($pgConnection, $createTempTable) or throw new Exception("Failed to create temporary table: " . pg_last_error());

        // Debug: Check file contents
        $fileContents = file_get_contents($file['tmp_name']);
        error_log("First 100 characters of file: " . substr($fileContents, 0, 100));

        // Open the CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception("Failed to open CSV file");
        }

        // Read and validate header row
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception("Failed to read CSV header");
        }
        error_log("CSV Headers: " . implode(", ", $header));

        // Use PostgreSQL's COPY command
        $copyResult = pg_copy_from($pgConnection, 'temp_employees', $handle, ',', '\N');
        
        if (!$copyResult) {
            throw new Exception("COPY command failed: " . pg_last_error());
        }

        fclose($handle);

        // Insert from temporary table to main table
        $insertSql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number)
                     SELECT first_name, last_name, department, job_role, phone_number
                     FROM temp_employees";
        
        $insertResult = pg_query($pgConnection, $insertSql);
        
        if ($insertResult) {
            // Get count of inserted rows
            $countSql = "SELECT COUNT(*) FROM temp_employees";
            $countResult = pg_query($pgConnection, $countSql);
            $count = pg_fetch_result($countResult, 0, 0);
            
            $message = "Successfully imported $count employees from CSV file.";
            error_log("Successfully imported $count employees");
        } else {
            throw new Exception("Failed to insert data from temporary table: " . pg_last_error());
        }

        // Clean up
        pg_query($pgConnection, "DROP TABLE IF EXISTS temp_employees");
        pg_close($pgConnection);

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