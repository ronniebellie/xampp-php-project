<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_website";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user input
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$message = $_POST['message'];

// Prepare and execute SQL query
$sql = "INSERT INTO users (first_name, last_name, email, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $first_name, $last_name, $email, $message);

// Check if the data was inserted successfully
if ($stmt->execute()) {
    $confirmation_message = "Thank you, $first_name! Your message has been received.";
} else {
    $confirmation_message = "Error: " . $stmt->error;
}

// Close connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        h2 {
            color: #28a745;
        }
        p {
            font-size: 16px;
            color: #555;
        }
        a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            padding: 10px 15px;
            border-radius: 5px;
        }
        a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Success!</h2>
        <p><?php echo $confirmation_message; ?></p>
        <a href="form.php">Submit Another Message</a>
    </div>
</body>
</html>