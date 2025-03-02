<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "sql311.infinityfree.com";
$db_username = "if0_38295275";
$db_password = "u6ZE12JXaOa5No";
$dbname = "if0_38295275_canteendb";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}
 date_default_timezone_set('Asia/Kolkata');
$today_date = date("Y-m-d"); // Get today's date


// Add item when "+" is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name = $_POST['item_name'];

    // Check if today's date exists in Menu table
    $check_date = $conn->prepare("SELECT * FROM Menu WHERE MenuDate = ?");
    $check_date->bind_param("s", $today_date);
    $check_date->execute();
    $result = $check_date->get_result();

    if ($result->num_rows == 0) {
        // Insert new row with today's date
        $insert_date = $conn->prepare("INSERT INTO Menu (MenuDate) VALUES (?)");
        $insert_date->bind_param("s", $today_date);
        $insert_date->execute();
    }

    // Update the item column to 1
    $update_item = $conn->prepare("UPDATE Menu SET `$item_name` = 1 WHERE MenuDate = ?");
    $update_item->bind_param("s", $today_date);

    if ($update_item->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add item"]);
    }
    exit;
}

// Remove item when "-" is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_item'])) {
    $item_name = $_POST['item_name'];

    // Update the item column to 0
    $update_item = $conn->prepare("UPDATE Menu SET `$item_name` = 0 WHERE MenuDate = ?");
    $update_item->bind_param("s", $today_date);

    if ($update_item->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove item"]);
    }
    exit;
}

// Fetch all selected items
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $result = $conn->query("SELECT * FROM Menu WHERE MenuDate = '$today_date'");
    $items = [];

    if ($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if ($key !== "MenuDate" && $value == 1) {
                $items[] = $key; // Collect item names with value 1
            }
        }
    }

    echo json_encode($items);
    exit;
}

$conn->close();
?>
