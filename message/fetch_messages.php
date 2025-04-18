<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$selectedUserId = $_POST['user_id'];

// Fetch all messages between the two users
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
    OR (sender_id = ? AND receiver_id = ?)
    ORDER BY timestamp ASC
");
$stmt->bind_param("iiii", $user_id, $selectedUserId, $selectedUserId, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Determine the date of the first message (for display at the top)
if (!empty($messages)) {
    $firstMessageDate = date('d M', strtotime($messages[0]['timestamp']));
    echo "<div class='chat-date'>$firstMessageDate</div>";
}

foreach ($messages as $message) {
    $messageClass = $message['sender_id'] == $user_id ? 'sent' : 'received';
    $messageContent = htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8');

    echo "<div class='message $messageClass'>";
    
    // Display message if exists
    if (!empty($messageContent)) {
        echo "<p>$messageContent</p>";
    }
    
    // Display photo if exists
    if (!empty($message['photo'])) {
        $photoPath = htmlspecialchars($message['photo'], ENT_QUOTES, 'UTF-8');
        echo "<img src='$photoPath' alt='Photo' class='message-photo' />";
    }

    echo "</div>";
}
?>