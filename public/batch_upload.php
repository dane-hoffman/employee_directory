<?php
require_once '../includes/db_connection.php';

$message = '';

// Add initial debugging
error_log("Script started - Request Method: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST request detected");
    
    // Debug FILES array
    error_log("FILES array contents: " . print_r($_FILES, true));
    
    if (!isset($_FILES['csv_file'])) {
        $message = "Error: No file uploaded";
        error_log("No file upload detected in _FILES array");
    } else if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        );
        $errorMessage = isset($uploadErrors[$_FILES['csv_file']['error']]) 
            ? $uploadErrors[$_FILES['csv_file']['error']]
            : 'Unknown upload error';
        $message = "Error: " . $errorMessage;
        error_log("File upload error: " . $errorMessage);
    } else {
        try {
            // Get PDO connection using existing function
            $pdo = getDBConnection();
            error_log("Database connection established");
            
            // Validate file upload
            $file = $_FILES['csv_file'];
            
            // Debug: Log file information
            error_log("Uploaded file type: " . $file['type']);
            error_log("Uploaded file size: " . $file['size']);
            error_log("Uploaded file tmp_name: " . $file['tmp_name']);
            
            // Accept more MIME types for CSV files
            $allowedFileTypes = [
                'text/csv',
                'application/vnd.ms-excel',
                'application/csv',
                'text/plain',
                'application/octet-stream' // Added for broader compatibility
            ];
            
            if (!in_array($file['type'], $allowedFileTypes) && !empty($file['type'])) {
                throw new Exception("Invalid file type. Please upload a CSV file. Received type: " . $file['type']);
            }

            // Check if file exists and is readable
            if (!is_readable($file['tmp_name'])) {
                throw new Exception("Unable to read uploaded file");
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
            
            error_log("CSV Headers found: " . implode(", ", $header));
            
            // Expected headers
            $expectedHeaders = ['first_name', 'last_name', 'department', 'job_role', 'phone_number'];
            if ($header !== $expectedHeaders) {
                throw new Exception("Invalid CSV format. Expected: " . implode(", ", $expectedHeaders) . ". Got: " . implode(", ", $header));
            }

            // Begin transaction
            $pdo->beginTransaction();
            error_log("Transaction started");

            // Prepare the insert statement
            $sql = "INSERT INTO employees (first_name, last_name, department, job_role, phone_number) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            // Initialize counters
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $rowNumber = 1; // To track which row we're processing

            // Process each row
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                try {
                    if (count($row) !== 5) {
                        throw new Exception("Invalid number of columns in row $rowNumber");
                    }

                    // Debug row data
                    error_log("Processing row $rowNumber: " . implode(", ", $row));

                    // Sanitize input
                    $sanitizedRow = array_map('htmlspecialchars', $row);
                    
                    // Execute insert
                    if ($stmt->execute($sanitizedRow)) {
                        $successCount++;
                        error_log("Successfully inserted row $rowNumber");
                    } else {
                        $errorCount++;
                        $errors[] = "Failed to insert row $rowNumber: " . implode(", ", $row);
                        error_log("Failed to insert row $rowNumber");
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Error in row $rowNumber: " . implode(", ", $row) . " - " . $e->getMessage();
                    error_log("Error processing row $rowNumber: " . $e->getMessage());
                }
            }

            fclose($handle);

            if ($errorCount === 0) {
                // If no errors, commit the transaction
                $pdo->commit();
                $message = "Successfully imported $successCount employees.";
                error_log("Transaction committed - $successCount records imported successfully");
            } else {
                // If there were errors, rollback
                $pdo->rollBack();
                $message = "Import failed. Found $errorCount errors:\n" . implode("\n", $errors);
                error_log("Transaction rolled back due to errors");
            }

        } catch (Exception $e) {
            // Rollback transaction if active
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
                error_log("Transaction rolled back due to exception");
            }
            
            $message = "Error: " . $e->getMessage();
            error_log("CSV Import Error: " . $e->getMessage());
        }
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