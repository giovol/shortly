<?php
include 'config.php';

function getOriginalURL($shortCode) {
    // Debugging statement to check the input
    error_log("getOriginalURL called with shortCode: $shortCode");

    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return false;
    }

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT original_url FROM urls WHERE short_code = ?");
    $stmt->bind_param("s", $shortCode);
    $stmt->execute();
    $stmt->bind_result($originalUrl);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    // Return the original URL or false if not found
    return $originalUrl ? $originalUrl : false;
}

if (isset($_GET['code'])) {
    $shortCode = $_GET['code'];
    try {
        $originalUrl = getOriginalURL($shortCode);

        // Debugging statement to check the return value
        error_log("getOriginalURL returned: $originalUrl");

        if ($originalUrl) {
            header("Location: $originalUrl");
            exit();
        } else {
            echo "Invalid URL";
        }
    } catch (Exception $e) {
        error_log("Error fetching original URL: " . $e->getMessage());
        echo "An error occurred. Please try again later.";
    }
} else {
    echo "No short code provided!";
}
?>