<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedConnect - Connecting Care</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6a11cb;
            /* Deep purple-blue */
            --secondary-color: #2575fc;
            /* Vibrant blue */
            --accent-color: #4c70ff;
            /* A blend of primary and secondary */
            --text-dark: #1A3C40;
            --text-light: #ffffff;
            --card-background: rgba(255, 255, 255, 0.97);
            --shadow-color: rgba(0, 109, 119, 0.2);
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: #f0f4f8;
            /* Light cool gray background */
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Container and Popup */
        .container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            margin: 0 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .popup {
            background: var(--card-background);
            padding: 3rem;
            border-radius: 30px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.15);
            text-align: center;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transform: scale(0.95);
            animation: popIn 1s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
        }

        /* Logo Styles */
        .logo-container {
            position: relative;
            width: 200px;
            height: 200px;

            margin: 0 auto 2rem;
            animation: float 3s ease-in-out infinite;
        }

        .logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }


        .logo-glow {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 120%;
            height: 120%;
            background: radial-gradient(circle, rgba(0, 109, 119, 0.15) 0%, transparent 70%);
            z-index: -1;
        }

        /* Typography */
        h1 {
            font-size: 2.8rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 700;
            opacity: 0;
            transform: translateY(20px);
            animation: textAppear 0.8s ease-out forwards 0.5s;
        }

        .tagline {
            font-size: 1.3rem;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: textAppear 0.8s ease-out forwards 0.8s;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            animation: textAppear 0.8s ease-out forwards 1.1s;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            color: var(--text-light);
            animation: textAppear 0.8s ease-out forwards 1.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        /* Footer */
        footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            text-align: center;
            color: var(--text-dark);
            font-size: 0.9rem;
            opacity: 0;
            animation: fadeIn 1s ease-out forwards 1.5s;
        }

        /* Animations */
        @keyframes popIn {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes textAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .popup {
                padding: 2rem;
                margin: 1rem;
            }

            h1 {
                font-size: 2.2rem;
            }

            .tagline {
                font-size: 1.1rem;
            }

            .btn {
                padding: 0.8rem 2rem;
            }

            .logo-container {
                width: 200px;
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <!-- Main Content -->
    <div class="container">
        <div class="popup">
            <!-- Logo -->
            <div class="logo-container">
                <img src="logo.png" alt="MedConnect Logo" class="logo">
                <div class="logo-glow"></div>
            </div>


            <!-- Welcome Text -->
            <h1>Welcome to MedConnect</h1>
            <p class="tagline">A Smart Telemedicine solution for Remote Healthcare</p>

            <!-- Buttons -->
            <div class="button-group">
                <a href="signup.php" class="btn btn-primary">Get Started</a>
                <a href="login.php" class="btn btn-secondary">Existing User</a>
            </div>
        </div>

        
    </div>
</body>

</html>