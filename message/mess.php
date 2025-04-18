<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch all users except the current user
$chatUsers = $conn->query("SELECT id, name, contact, profile_pic FROM users WHERE id != $user_id AND ((user_type = 'doctor' AND '$user_type' = 'patient') OR (user_type = 'patient' AND '$user_type' = 'doctor'))");
if (!$chatUsers) {
    die("Error fetching chat users: " . $conn->error);
}

// Get selected user ID from GET parameter
$selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$selectedUser = null;

// Verify the selected user exists
if ($selectedUserId) {
    $selectedUserQuery = $conn->query("SELECT id, name, contact, profile_pic FROM users WHERE id = $selectedUserId");
    if ($selectedUserQuery->num_rows > 0) {
        $selectedUser = $selectedUserQuery->fetch_assoc();
    } else {
        die("Invalid user selected.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="icon" type="image/png" href="../mc.png">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }
        .chat-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
        }
        .chat-header {
            background: #8FE3F7; /* Updated color */
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-list {
            width: 100%;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            display: <?php echo $selectedUser ? 'none' : 'block'; ?>;
        }
        .list-group-item {
            border: none;
            padding: 15px 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .list-group-item.active {
            background-color: #3498db;
            color: #ffffff;
            border-radius: 8px;
        }
        .list-group-item:hover {
            background-color: #ecf0f1;
        }
        .list-group-item img {
            border-radius: 50%;
            margin-right: 10px;
        }
        .chat-box {
            display: <?php echo $selectedUser ? 'flex' : 'none'; ?>;
            flex-direction: column;
            height: 100%;
            flex-grow: 1;
            background-color: #ecf0f1;
            position: relative;
        }
        .messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: #f4f4f9;
            display: flex;
            flex-direction: column;
        }
        .message img {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 10px;
        }
        .input-group {
            position: sticky;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            padding: 15px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        .message {
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 70%;
            word-wrap: break-word;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .message.sent {
            align-self: flex-end;
            background-color: #d1e7dd;
            color: #333333;
        }
        .message.received {
            align-self: flex-start;
            background-color: #ffffff;
            color: #333333;
        }
        #photoInput {
            display: none;
        }
        .btn-light {
            color: #6c757d;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: rgb(255, 255, 255);
            border: none;
            transition: background-color 0.3s ease;
            border-radius: 50%;
            padding: 10px;
        }
        .btn-light:hover {
            background-color: #e2e6ea;
            color: #495057;
        }
        .btn-primary {
            color: #6c757d;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: rgb(255, 255, 255);
            border: none;
            transition: background-color 0.3s ease;
            border-radius: 50%;
            padding: 10px;
        }
        .btn-primary:hover {
            background-color: #e2e6ea;
            color: #495057;
        }
        @media (max-width: 768px) {
            .user-list {
                display: <?php echo $selectedUser ? 'none' : 'block'; ?>;
            }
            .chat-box {
                display: <?php echo $selectedUser ? 'flex' : 'none'; ?>;
            }
        }
    </style>
</head>
<body>
<div class="chat-container">
    <!-- Chat Header -->
    <div class="chat-header d-flex align-items-center justify-content-between">
        <?php
            $profilePic = $selectedUser && $selectedUser['profile_pic'] 
                ? '../' . $selectedUser['profile_pic'] 
                : '../assets/icons/team.png';
        ?>
        <img src="<?php echo htmlspecialchars($profilePic); ?>" class="rounded-circle mr-2" width="40" height="40" alt="User">
        <strong><?php echo $selectedUser ? htmlspecialchars($selectedUser['name']) : 'Select a user to chat'; ?></strong>
        <div class="d-flex align-items-center">
            <?php if ($selectedUser): ?>
                <?php if (!empty($selectedUser['contact'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($selectedUser['contact']); ?>" class="btn btn-sm btn-primary mr-2">
                        <i class="fas fa-video"></i>
                    </a>
                <?php else: ?>
                    <button class="btn btn-sm btn-secondary mr-2" disabled>
                        <i class="fas fa-video"></i>
                    </button>
                <?php endif; ?>
                <?php if (!empty($selectedUser['contact'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($selectedUser['contact']); ?>" class="btn btn-sm btn-primary mr-2">
                        <i class="fas fa-phone"></i>
                    </a>
                <?php else: ?>
                    <button class="btn btn-sm btn-secondary mr-2" disabled>
                        <i class="fas fa-phone"></i>
                    </button>
                <?php endif; ?>

                <!-- Dropdown Menu for Doctors -->
                <?php if ($user_type === 'doctor'): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="../prescription.php?user_id=<?php echo $selectedUser['id']; ?>">
                                <i class="fas fa-file-medical-alt"></i> Write Prescription
                            </a>
                            <a class="dropdown-item" href="../p_records/patient_health_records.php?patient_id=<?php echo $selectedUser['id']; ?>">
                                <i class="fas fa-heartbeat fa-fw me-3"></i> Health Records
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="d-flex" style="flex-grow: 1;">
        <!-- User List -->
        <div class="user-list">
            <h5>Chat Users</h5>
            <div class="list-group">
                <?php while ($user = $chatUsers->fetch_assoc()): ?>
                    <?php
                        $profilePic = $user['profile_pic'] 
                            ? '../' . $user['profile_pic'] 
                            : '../assets/avatar.png';
                    ?>
                    <a href="mess.php?user_id=<?php echo $user['id']; ?>" 
                       class="list-group-item list-group-item-action d-flex align-items-center <?php echo $user['id'] == $selectedUserId ? 'active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($profilePic); ?>" class="rounded-circle mr-2" width="40" height="40" alt="User">
                        <div>
                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                            <div class="small text-muted">Online</div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Chat Box -->
        <div class="chat-box">
            <?php if ($selectedUser): ?>
                <div class="messages" id="messages">
                    <!-- Messages will be loaded dynamically -->
                </div>
                <form id="chatForm" enctype="multipart/form-data" class="input-group">
                    <label for="photoInput" class="btn btn-light">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <input type="file" name="photo" id="photoInput" accept="image/*">
                    <input type="text" name="message" id="messageInput" class="form-control" placeholder="Type a message" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <div class="p-3">Select a user to start chatting.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        const userId = <?php echo $selectedUserId !== null ? $selectedUserId : 'null'; ?>;

        function fetchMessages() {
            if (userId) {
                $.ajax({
                    url: "fetch_messages.php",
                    method: "POST",
                    data: { user_id: userId },
                    success: function (data) {
                        $("#messages").html(data);
                        $("#messages").scrollTop($("#messages")[0].scrollHeight);
                    },
                    error: function (xhr, status, error) {
                        console.error("Error fetching messages:", xhr.responseText);
                    }
                });
            }
        }

        fetchMessages();
        setInterval(fetchMessages, 2000);

        $("#chatForm").submit(function (e) {
            e.preventDefault();

            const message = $("#messageInput").val().trim();
            const photo = $("#photoInput")[0].files[0];

            if (!message && !photo) {
                alert("Please enter a message or attach a photo.");
                return;
            }

            const formData = new FormData(this);
            formData.append("user_id", userId);

            $.ajax({
                url: "send_message.php",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    $("#messageInput").val("");
                    $("#photoInput").val("");
                    fetchMessages();
                },
                error: function (xhr, status, error) {
                    console.error("Error sending message:", xhr.responseText);
                }
            });
        });
    });
</script>
</body>
</html>
