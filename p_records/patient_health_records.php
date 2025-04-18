<?php
include '../db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = isset($_GET['patient_id']) ? mysqli_real_escape_string($conn, $_GET['patient_id']) : null;
if (!$patient_id) {
    header("Location: patient_records.php");
    exit;
}

// Fetch patient details
$patientQuery = "SELECT name, profile_pic, age, height, weight, contact, email, blood_group FROM users WHERE id = '$patient_id'";
$patientResult = mysqli_query($conn, $patientQuery);
$patient = mysqli_fetch_assoc($patientResult);

// Fetch health records for the selected patient (all records but we use only the latest)
$healthQuery = "
    SELECT 
        temperature, blood_pressure, stress_level, glucose_level, spo2_level, ecg_data, heart_rate, timestamp
    FROM health_metrics 
    WHERE user_id = '$patient_id'
    ORDER BY timestamp DESC
";
$healthResult = mysqli_query($conn, $healthQuery);
$healthData = [];
while ($row = mysqli_fetch_assoc($healthResult)) {
    $healthData[] = $row;
}

// Use only the latest record
$latestRecord = !empty($healthData) ? $healthData[0] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Records</title>
    <link rel="icon" type="image/png" href="../mc.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/patient_h_r.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Main Navigation -->
    <header>
        <!-- Navbar -->
        <nav id="main-navbar" class="navbar navbar-expand-lg navbar-light fixed-top">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand" href="#" style="font-size: 1.5rem; font-weight: bold; color: white;">MedConnect</a>
                <ul class="navbar-nav ms-auto d-flex flex-row">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle hidden-arrow d-flex align-items-center" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-expanded="false">
                            <img src="../assets/avatar.png" class="rounded-circle" height="22" alt="Avatar" loading="lazy">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="dashboard.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- Sidebar -->
        <nav id="sidebarMenu" class="sidebar">
            <div class="position-sticky">
                <div class="list-group list-group-flush mx-3 mt-4">
                    <a href="../dashboard.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-home fa-fw me-3"></i><span>Home</span>
                    </a>
                    <a href="../appointments/doctor.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span>
                    </a>
                    <a href="../p_records/patient_records.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-folder-open fa-fw me-3"></i><span>Patient Records</span>
                    </a>
                    <a href="../prescription.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-prescription fa-fw me-3"></i><span>Prescription</span>
                    </a>
                    <a href="../availability.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-clock fa-fw me-3"></i><span>Availability</span>
                    </a>
                    <a href="../message/mess.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-comments fa-fw me-3"></i><span>Messages</span>
                    </a>
                    <a href="../settings.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-cog fa-fw me-3"></i><span>Settings</span>
                    </a>
                    <a href="../history.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-history fa-fw me-3"></i><span>History</span>
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-sign-out-alt fa-fw me-3"></i><span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>
        <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    </header>

    <!-- Main Content -->
    <main>
    <div class="container-fluid py-4">
        <!-- Header with Patient Name -->
        <div class="header">
            <h1><?php echo htmlspecialchars($patient['name']); ?>'s Health Records</h1>
        </div>

        <!-- Patient Info (Name, Contact, Email) -->
        <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
            <!-- Patient Information Card -->
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Patient Information</h5>
                        <p class="card-text">
                            <strong>Name:</strong> <?php echo htmlspecialchars($patient['name']); ?><br>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($patient['contact']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <!-- Blood Group Card -->
            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon icon-danger">
                            <img src="../assets/icons/bloodg.png" alt="Blood Group Icon">
                        </div>
                        <div>
                            <h5 class="card-title">Blood Group</h5>
                            <p class="card-text"><?php echo htmlspecialchars($patient['blood_group']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Info (Age, Height, Weight) -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
            <!-- Age Card -->
            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon icon-warning">
                            <img src="../assets/icons/age.png" alt="Age Icon">
                        </div>
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($patient['age']); ?></h5>
                            <p class="card-text">Age</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Height Card -->
            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon icon-success">
                            <img src="../assets/icons/height.png" alt="Height Icon">
                        </div>
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($patient['height']); ?> cm</h5>
                            <p class="card-text">Height</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Weight Card -->
            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon icon-danger">
                            <img src="../assets/icons/weight.png" alt="Weight Icon">
                        </div>
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($patient['weight']); ?> kg</h5>
                            <p class="card-text">Weight</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Metrics Cards (Using only the Latest Record) -->
        <?php if ($latestRecord): ?>
            <!-- First row: Temperature, Blood Pressure, SpO₂ Level -->
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                <!-- Temperature Card -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon icon-primary">
                                <img src="../assets/icons/tempreture.png" alt="Temperature Icon">
                            </div>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['temperature']); ?> °C</h5>
                                <p class="card-text">Temperature</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Blood Pressure Card -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon icon-warning">
                                <img src="../assets/icons/bloodpresure.png" alt="Blood Pressure Icon">
                            </div>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['blood_pressure']); ?> mmHg</h5>
                                <p class="card-text">Blood Pressure</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SpO₂ Level Card -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon icon-success">
                                <img src="../assets/icons/spo2.png" alt="SpO2 Icon">
                            </div>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['spo2_level']); ?>%</h5>
                                <p class="card-text">SpO₂ Level</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Second row: Heart Rate, Stress Level, Glucose Level -->
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                <!-- Heart Rate Card -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon icon-info">
                                <img src="../assets/icons/heart.png" alt="Heart Rate Icon">
                            </div>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['heart_rate']); ?> BPM</h5>
                                <p class="card-text">Heart Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stress Level Card -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon icon-danger">
                                <img src="../assets/icons/stress.png" alt="Stress Icon">
                            </div>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['stress_level']); ?></h5>
                                <p class="card-text">Stress Level</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Glucose Level Card -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon">
                                <img src="../assets/icons/glucose.png" alt="Glucose Icon">
                            </div>
                            <div>
                                <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['glucose_level']); ?> mg/dL</h5>
                                <p class="card-text">Glucose Level</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ECG Data Chart -->
        <?php if ($latestRecord): ?>
            <div class="row mb-4">
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon icon-info">
                                    <img src="../assets/icons/ecg.png" alt="ECG Icon" style="width: 40px; height: 40px;">
                                </div>
                                <h4 class="card-title mb-0">ECG Data</h4>
                            </div>
                            <canvas id="ecgChart" style="width: 100%; height: 200px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    </main>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize ECG Chart using latestRecord's ecg_data
        <?php if ($latestRecord): ?>
        var ctx = document.getElementById('ecgChart').getContext('2d');
        var ecgData = <?php echo json_encode(explode(',', $latestRecord['ecg_data'])); ?>;
        var ecgChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({length: ecgData.length}, (_, i) => i),
                datasets: [{
                    label: 'ECG Data',
                    data: ecgData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { title: { display: true, text: 'Time' } },
                    y: { title: { display: true, text: 'Amplitude' } }
                }
            }
        });
        <?php endif; ?>

        // Sidebar toggle functions
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.querySelector('#sidebarBackdrop');
            sidebar.classList.toggle('active');
            backdrop.classList.toggle('active');
        }
        document.getElementById('sidebarBackdrop').addEventListener('click', function () {
            toggleSidebar();
        });
    </script>
</body>
</html>
