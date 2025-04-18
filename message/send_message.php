<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$receiver_id = $_POST['user_id'];
$message = isset($_POST['message']) ? htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8') : '';

// Check if a photo was uploaded
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
    }
    $photoName = uniqid() . '_' . basename($_FILES['photo']['name']); // Unique filename
    $photoPath = $uploadDir . $photoName;

    // Check if the file is an image
    $validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($_FILES['photo']['type'], $validTypes)) {
        // Move the file to the uploads directory
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
            echo "Photo uploaded successfully.";
        } else {
            echo "Error uploading photo.";
            exit;
        }
    } else {
        echo "Invalid photo format.";
        exit;
    }
}

// Validate if either message or photo is present
if (empty($message) && empty($photoPath)) {
    echo "No message or photo provided.";
    exit;
}

// Insert the message (and photo if available) into the database
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, photo) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $user_id, $receiver_id, $message, $photoPath);
if ($stmt->execute()) {
    echo "Message sent.";
} else {
    echo "Error sending message.";
    error_log("Error sending message: " . $stmt->error);
}
$stmt->close();
?>