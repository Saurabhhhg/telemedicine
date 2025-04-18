<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

function getGeminiResponse($user_input)
{
    $api_key = "AIzaSyDnR4FMs1OX7a4otiqE1LLU9gfRSW8Kpyg";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$api_key";

    $data = json_encode(["contents" => [["parts" => [["text" => $user_input]]]]]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($response, true);

    if (!$response_data) {
        return "Error: Invalid response from AI.";
    }

    if (isset($response_data['error'])) {
        return "Error: " . $response_data['error']['message'];
    }

    $ai_response = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? "Sorry, I couldn't process your request.";
    return nl2br($ai_response);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_input'])) {
        $user_input = htmlspecialchars($_POST['user_input']);
        $ai_response = getGeminiResponse($user_input);
        echo json_encode(['ai_response' => $ai_response]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/ai.css">
</head>

<body>
    <!-- Navbar -->
    <header>
        <nav id="main-navbar" class="navbar navbar-expand-lg navbar-light fixed-top">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand" href="#" style="font-size: 1.5rem; font-weight: bold; color: white;">
                    MedConnect AI
                </a>
                <ul class="navbar-nav ms-auto d-flex flex-row">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle hidden-arrow d-flex align-items-center" href="#"
                            id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-expanded="false">
                            <img src="assets/avatar.png" class="rounded-circle" height="22" alt="Avatar" loading="lazy">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="settings.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Sidebar -->
    <nav id="sidebarMenu" class="sidebar">
        <div class="position-sticky">
            <div class="list-group list-group-flush mx-3 mt-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-home fa-fw me-3"></i><span>Home</span></a>
                <a href="appointments/patient.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span></a>
                <a class="list-group-item list-group-item-action py-2 ripple" data-toggle="collapse"
                    href="#medicalRecordsSubMenu"><i class="fas fa-file-medical fa-fw me-3"></i><span>Medical
                        Records</span></a>
                <div id="medicalRecordsSubMenu" class="collapse" data-parent="#sidebarMenu">
                    <a href="medrecords/health_records.php"
                        class="list-group-item list-group-item-action py-2 ripple sub-item"><i
                            class="fas fa-heartbeat fa-fw me-3"></i><span>Health Records</span></a>
                    <a href="prescription.php" class="list-group-item list-group-item-action py-2 ripple sub-item"><i
                            class="fas fa-prescription-bottle fa-fw me-3"></i><span>Prescriptions</span></a>
                </div>
                <a href="health_tracking.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-heartbeat fa-fw me-3"></i><span>Health Tracking</span></a>
                <a href="message/mess.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-comments fa-fw me-3"></i><span>Messages</span></a>
                <a href="ai.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-robot fa-fw me-3"></i><span>AI chat</span></a>
                <a href="settings.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-cog fa-fw me-3"></i><span>Settings</span></a>
                <a href="logout.php" class="list-group-item list-group-item-action py-2 ripple"><i
                        class="fas fa-sign-out-alt fa-fw me-3"></i><span>Logout</span></a>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <main>
        <div class="chat-container">
            <div class="messages-area" id="messages-area">
                <!-- Messages will be loaded here -->
            </div>
            <div class="input-area">
                <form id="chat-form">
                    <div class="input-wrapper">
                        <input type="text" id="user-input" placeholder="Type your message..." required>
                        <button type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Add initial welcome message
        $(document).ready(function () {
            const welcomeMessage = `
                <div class="message ai-message">
                    Hello! I'm your AI health assistant. How can I help you today?
                </div>
            `;
            $('#messages-area').append(welcomeMessage);
        });

        // Handle chat form submission
        $('#chat-form').on('submit', function (e) {
            e.preventDefault();

            const userInput = $('#user-input').val().trim();
            if (!userInput) return;

            // Add user message
            const userMessage = `
                <div class="message user-message">
                    ${userInput}
                </div>
            `;
            $('#messages-area').append(userMessage);

            // Clear input
            $('#user-input').val('');

            // Scroll to bottom
            $('#messages-area').scrollTop($('#messages-area')[0].scrollHeight);

            // Send to server
            $.ajax({
                url: '',
                method: 'POST',
                data: { user_input: userInput },
                dataType: 'json',
                success: function (response) {
                    // Add AI response
                    const aiMessage = `
                        <div class="message ai-message">
                            ${response.ai_response}
                        </div>
                    `;
                    $('#messages-area').append(aiMessage);
                    $('#messages-area').scrollTop($('#messages-area')[0].scrollHeight);
                },
                error: function () {
                    const errorMessage = `
                        <div class="message ai-message">
                            Sorry, I encountered an error processing your request.
                        </div>
                    `;
                    $('#messages-area').append(errorMessage);
                    $('#messages-area').scrollTop($('#messages-area')[0].scrollHeight);
                }
            });
        });
    </script>
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.querySelector('#sidebarBackdrop');
            sidebar.classList.toggle('active');
            backdrop.classList.toggle('active');
        }

        // Close sidebar when backdrop is clicked
        document.getElementById('sidebarBackdrop').addEventListener('click', function () {
            toggleSidebar();
        });
    </script>
</body>
</html>