<?php
require_once '../includes/db_connection.php';

$search_results = [];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    try {
        $pdo = getDBConnection(); // Get the PDO connection
        $search = '%' . $_GET['search'] . '%';
        $sql = "SELECT * FROM employees WHERE first_name LIKE ? OR last_name LIKE ? OR department LIKE ? OR job_role LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$search, $search, $search, $search]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        $error_message = "An error occurred while searching. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Employees</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header>
        <img src="https://mrhmoab.org/wp-content/uploads/2020/11/MRHLogoColor2015-website-colors-3.png" alt="Moab Regional Hospital Logo" class="logo">
        <h1>Hospital Employee Directory</h1>
    </header>
    <div class="container">
        <h1>Search Employees</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="add_employee.php">Add Employee</a></li>
                <li><a href="search.php">Search Employees</a></li>
            </ul>
        </nav>
        <form action="search.php" method="get">
            <label for="search">Search:</label>
            <input type="text" id="search" name="search" required>
            <input type="submit" value="Search">
        </form>

        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <?php if (!empty($search_results)): ?>
            <table>
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Department</th>
                        <th>Job Role</th>
                        <th>Phone Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($search_results as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['department']); ?></td>
                            <td><?php echo htmlspecialchars($employee['job_role']); ?></td>
                            <td><?php echo htmlspecialchars($employee['phone_number']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])): ?>
            <p>No results found.</p>
        <?php endif; ?>
    </div>
</body>
</html>