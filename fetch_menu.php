<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database credentials
$servername = "sql311.infinityfree.com";
$db_username = "if0_38295275";
$db_password = "u6ZE12JXaOa5No";
$dbname = "if0_38295275_canteendb";

// Create database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

// Get date from request, default to today's date
date_default_timezone_set('Asia/Kolkata');
$requested_date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

// Fetch column names dynamically (excluding 'MenuDate')
$query = "SHOW COLUMNS FROM Menu WHERE Field != 'MenuDate'";
$result = $conn->query($query);

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = "`" . $row['Field'] . "`";  // Add backticks to prevent SQL errors
}

// Handle empty column list
if (empty($columns)) {
    die(json_encode(["status" => "error", "message" => "No valid menu items found"]));
}

$colString = implode(", ", $columns);

// Fetch menu where status = 1 for the requested date
$stmt = $conn->prepare("SELECT $colString FROM Menu WHERE `MenuDate` = ?");
$stmt->bind_param("s", $requested_date);

if (!$stmt->execute()) {
    die(json_encode(["status" => "error", "message" => $stmt->error]));
}

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$items = [];
if ($row) {
    foreach ($columns as $column) {
        $cleanColumn = trim($column, "`"); // Remove backticks for JSON response
        if ($row[$cleanColumn] == 1) { // Check if the item is available (1 means available)
            $items[] = ["name" => $cleanColumn];
        }
    }
}

// Close statement and connection
$stmt->close();
$conn->close();

// Return JSON response
echo json_encode($items);
?>
