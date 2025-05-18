<?php
// db.php - Database connection file

try {
    // Adjust the path to your SQLite database as needed
    $dbPath = __DIR__ . '/website.db'; // __DIR__ points to the root folder
    $conn = new PDO("sqlite:$dbPath");

    //$conn = new PDO("sqlite:./website.db");  // Use relative path if the database is in the same directory
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Enable exception mode for errors
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // Fetch results as associative arrays
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();  // Exit the script if the connection fails
}
?>

