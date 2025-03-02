<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$servername = "sql311.infinityfree.com";
$db_username = "if0_38295275";
$db_password = "u6ZE12JXaOa5No";
$dbname = "if0_38295275_canteendb";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Set timezone and get current timestamp
date_default_timezone_set('Asia/Kolkata');
$requested_date = date("Y-m-d");
$requested_time = date("H:i:s");

// Fetch menu item names
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Menu' AND COLUMN_NAME != 'MenuDate'";
$result = $conn->query($query);

$menu_items = [];
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row['COLUMN_NAME'];
}

// Get today's menu status
$status_query = "SELECT * FROM Menu WHERE MenuDate = ?";
$status_stmt = $conn->prepare($status_query);
$status_stmt->bind_param("s", $requested_date);
$status_stmt->execute();
$status_result = $status_stmt->get_result();
$menu_status = $status_result->fetch_assoc();
$status_stmt->close();

// Initialize feedback with -1 for all items
$feedback = [];
foreach ($menu_items as $item) {
    $feedback[$item] = ($menu_status && isset($menu_status[$item]) && $menu_status[$item] == 1) ? 0 : -1;
}

// Process user feedback
if (!empty($_POST)) {
    foreach ($_POST as $item => $rating) {
        if ($item === 'menu_date') continue; // Skip menu_date
        if (array_key_exists($item, $feedback)) { // Ensure item exists before updating
            $feedback[$item] = (int)$rating; // Store actual user rating
        }
    }
}

// Prepare dynamic query for inserting feedback
$columns = implode(", ", array_keys($feedback));
$placeholders = rtrim(str_repeat("?, ", count($feedback)), ", ");
$values = array_values($feedback);
array_unshift($values, $requested_date, $requested_time); // Add date & time

$insert_query = "INSERT INTO canteenfeedbacksystem (feedback_date, feedback_time, $columns) 
                 VALUES (" . str_repeat("?, ", count($values) - 1) . "?)";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param(str_repeat("s", 2) . str_repeat("i", count($feedback)), ...$values);

if ($stmt->execute()) {
    header("Location: ../public/thanks.html");
    exit();
} else {
    die(json_encode(["status" => "error", "message" => "Failed to submit feedback."]));
}

$stmt->close();
$conn->close();
?>
