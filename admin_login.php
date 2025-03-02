<?php
// Start the session
session_start();

// Database connection settings
$servername = "sql311.infinityfree.com";
$db_username = "if0_38295275";
$db_password = "u6ZE12JXaOa5No";
$dbname = "if0_38295275_canteendb";

// Create a connection to the database
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the input values
    $input_username = trim($_POST['name']);
    $input_password = trim($_POST['password']);

    // Query to get the user details from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a user with the given username exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Use password_verify() if passwords are hashed in the database
        if ($input_password === $row['password']) {

            // Store session variables
            $_SESSION['username'] = $input_username;
            $_SESSION['role'] = $row['role'];
            
            // Ensure session data is saved before redirecting
            session_write_close();

            // Debugging - Print session data (remove this in production)
            // print_r($_SESSION); exit;

            // Redirect based on the role
            if ($row['role'] === "staff") {
                header("Location: ../public/set_menu.html");
            } else {
                header("Location: ../public/feedback_chart.html");
            }
            exit;
        } else {
            echo "<h2>Invalid password!</h2>";
        }
    } else {
        echo "<h2>Admin not found!</h2>";
    }

    // Close the prepared statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>
