<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database Connection
$servername = "sql311.infinityfree.com";
$db_username = "if0_38295275";
$db_password = "u6ZE12JXaOa5No";
$dbname = "if0_38295275_canteendb";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}

// Get parameters
$selected_date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
$selected_hour = isset($_GET['hour']) ? $_GET['hour'] : null;
$chart_type = isset($_GET['type']) ? $_GET['type'] : "bar_chart"; // Default to bar chart

// Fetch menu item names for the selected date
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Menu' AND COLUMN_NAME != 'MenuDate'";
$result = $conn->query($query);

$menu_items = [];
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row['COLUMN_NAME'];
}

// Ensure menu items are not empty
if (empty($menu_items)) {
    die(json_encode(["error" => "No menu items found in the database."]));
}

// Initialize feedback tracking for each menu item
$feedback_data = [];
foreach ($menu_items as $item) {
    $feedback_data[$item] = ["3" => 0, "2" => 0, "1" => 0, "0" => 0]; // Include all rating categories
}

// Fetch feedback from database based on date and optional hour
$query = "SELECT * FROM canteenfeedbacksystem WHERE feedback_date = ?";
$params = [$selected_date];
$param_types = "s";

if ($selected_hour) {
    $query .= " AND HOUR(feedback_time) = ?";
    $params[] = $selected_hour;
    $param_types .= "i";
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    die(json_encode(["error" => "Failed to prepare SQL statement"]));
}
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Check if any feedback exists
if ($result->num_rows === 0) {
    die(json_encode(["error" => "No feedback data found for this date"]));
}

// Process feedback data
while ($row = $result->fetch_assoc()) {
    foreach ($menu_items as $item) {
        if (isset($row[$item])) {
            $rating = $row[$item];
            if ($rating != -1 && isset($feedback_data[$item][$rating])) { // Ignore -1 ratings
                $feedback_data[$item][$rating]++;
            }
        }
    }
}

$stmt->close();
$conn->close();

// Return data based on chart type
switch ($chart_type) {
    case "bar_chart": // Stacked Bar Chart
        echo json_encode($feedback_data);
        break;

    case "line_chart": // Trend Over Time
        echo json_encode(["message" => "Line chart logic will be implemented next."]);
        break;

    case "leaderboard": // Top & Bottom Rated Items
        $leaderboard = [];
        foreach ($feedback_data as $item => $ratings) {
            $total_score = ($ratings["3"] * 3) + ($ratings["2"] * 2) + ($ratings["1"] * 1);
            $total_votes = $ratings["3"] + $ratings["2"] + $ratings["1"];
            $average_rating = $total_votes > 0 ? round($total_score / $total_votes, 2) : 0;
            $leaderboard[$item] = $average_rating;
        }
        arsort($leaderboard);
        echo json_encode($leaderboard);
        break;
    
    case "percentage":
        echo json_encode(["message" => "percentage logic will be implemented next."]);
        break;

    case "heatmap":
    // Fetch feedback grouped by date and hour
    $query = "SELECT feedback_date, HOUR(feedback_time) AS feedback_hour, 
                     SUM(IF(rating = 3, 3, 0)) AS score_3,
                     SUM(IF(rating = 2, 2, 0)) AS score_2,
                     SUM(IF(rating = 1, 1, 0)) AS score_1,
                     COUNT(*) AS total_votes
              FROM canteenfeedbacksystem
              WHERE feedback_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
              GROUP BY feedback_date, feedback_hour
              ORDER BY feedback_date, feedback_hour";
    
    $result = $conn->query($query);

    $heatmap_data = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['feedback_date'];
        $hour = $row['feedback_hour'];
        $total_votes = $row['total_votes'];
        $total_score = $row['score_3'] + $row['score_2'] + $row['score_1'];
        
        // Calculate average rating for intensity
        $avg_rating = $total_votes > 0 ? round($total_score / $total_votes, 2) : 0;
        
        $heatmap_data[] = [
            "date" => $date,
            "hour" => $hour,
            "average_rating" => $avg_rating,
            "intensity" => round(($avg_rating / 3) * 100) // Normalize to 100 for frontend color mapping
        ];
    }

    echo json_encode($heatmap_data);
    break;


    default:
        echo json_encode(["error" => "Invalid chart type"]);
}
?>
