<?php
include '../db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Fetch all unique dates from the health_metrics table for the logged-in user
$dateQuery = "SELECT DISTINCT DATE(timestamp) as record_date FROM health_metrics WHERE user_id = '$user_id' ORDER BY record_date DESC";
$dateResult = mysqli_query($conn, $dateQuery);

// Initialize variables
$selectedDate = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : null;
$healthData = [];
$userDetails = [
    'name' => 'Patient', // Default value
    'age' => 'N/A',
    'height' => 'N/A',
    'weight' => 'N/A'
];

// Fetch health data and user details for the selected date
if ($selectedDate) {
    $dataQuery = "
        SELECT 
            users.name, users.age, users.height, users.weight,
            health_metrics.temperature, health_metrics.blood_pressure, 
            health_metrics.stress_level, health_metrics.glucose_level, 
            health_metrics.spo2_level, health_metrics.ecg_data,
            health_metrics.heart_rate
        FROM health_metrics 
        JOIN users ON health_metrics.user_id = users.id 
        WHERE DATE(health_metrics.timestamp) = '$selectedDate'
        AND health_metrics.user_id = '$user_id'
    ";
    $dataResult = mysqli_query($conn, $dataQuery);
    while ($row = mysqli_fetch_assoc($dataResult)) {
        $healthData[] = $row;
    }

    // Fetch user details (assuming the first record belongs to the user)
    if (!empty($healthData)) {
        $userDetails = [
            'name' => $healthData[0]['name'],
            'age' => $healthData[0]['age'],
            'height' => $healthData[0]['height'],
            'weight' => $healthData[0]['weight']
        ];
    }
}

// Use only the latest record for displaying metrics
$latestRecord = !empty($healthData) ? $healthData[0] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page with Sidebar</title>
    <link rel="icon" type="image/png" href="../mc.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/health_records.css">
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
                <a class="navbar-brand" href="#"
                    style="font-size: 1.5rem; font-weight: bold; color: white;">MedConnect</a>
                <ul class="navbar-nav ms-auto d-flex flex-row">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle hidden-arrow d-flex align-items-center" href="#"
                            id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-expanded="false">
                            <img src="../assets/avatar.png" class="rounded-circle" height="22" alt="Avatar"
                                loading="lazy">
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
        <!-- Sidebar -->
        <nav id="sidebarMenu" class="sidebar">
            <div class="position-sticky">
                <div class="list-group list-group-flush mx-3 mt-4">
                    <a href="../dashboard.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-home fa-fw me-3"></i><span>Home</span>
                    </a>
                    <a href="../appointments/patient.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span>
                    </a>
                    <a class="list-group-item list-group-item-action py-2 ripple" data-toggle="collapse"
                        href="#medicalRecordsSubMenu">
                        <i class="fas fa-file-medical fa-fw me-3"></i><span>Medical Records</span>
                    </a>
                    <div id="medicalRecordsSubMenu" class="collapse" data-parent="#sidebarMenu">
                        <a href="../medrecords/health_records.php"
                            class="list-group-item list-group-item-action py-2 ripple sub-item">
                            <i class="fas fa-heartbeat fa-fw me-3"></i><span>Health Records</span>
                        </a>
                        <a href="../prescription.php"
                            class="list-group-item list-group-item-action py-2 ripple sub-item">
                            <i class="fas fa-prescription-bottle fa-fw me-3"></i><span>Prescriptions</span>
                        </a>
                    </div>
                    <a href="../health_tracking.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-heartbeat fa-fw me-3"></i><span>Health Tracking</span>
                    </a>
                    <a href="../message/mess.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-comments fa-fw me-3"></i><span>Messages</span>
                    </a>
                    <a href="../ai.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-robot fa-fw me-3"></i><span>AI chat</span>
                    </a>
                    <a href="../settings.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-cog fa-fw me-3"></i><span>Settings</span>
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
                <h1><?php echo htmlspecialchars($userDetails['name']); ?>'s Health Records</h1>
            </div>

            <!-- Date Picker (Only show if no date is selected) -->
            <?php if (!$selectedDate): ?>
                <div class="date-picker">
                    <?php while ($row = mysqli_fetch_assoc($dateResult)): ?>
                        <div class="date-item" onclick="window.location.href='?date=<?php echo $row['record_date']; ?>'">
                            <?php echo date('M j', strtotime($row['record_date'])); ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <?php if ($selectedDate && !empty($healthData)): ?>
                <!-- Download PDF Button -->
                <div class="text-right mb-3">
                     <a href="download_record.php?date=<?php echo $selectedDate; ?>" class="btn btn-primary">
                          <i class="fas fa-download"></i> Download PDF
                     </a>
                </div>
                <!-- Patient Info (Age, Height, Weight) -->
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                    <!-- Age Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="../assets/icons/age.png" alt="Age Icon">
                                </div>
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($userDetails['age']); ?></h5>
                                    <p class="card-text">Age</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Height Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="../assets/icons/height.png" alt="Height Icon">
                                </div>
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($userDetails['height']); ?> cm</h5>
                                    <p class="card-text">Height</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Weight Card -->
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="../assets/icons/weight.png" alt="Weight Icon">
                                </div>
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($userDetails['weight']); ?> kg</h5>
                                    <p class="card-text">Weight</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($latestRecord): ?>
                    <!-- Health Metrics Cards (Two rows with three cards each) -->
                    <!-- First Row: Temperature, Blood Pressure, SpO₂ Level -->
                    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                        <!-- Temperature Card -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon">
                                        <img src="../assets/icons/tempreture.png" alt="Temperature Icon">
                                    </div>
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['temperature']); ?> °C
                                        </h5>
                                        <p class="card-text">Temperature</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Blood Pressure Card -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon">
                                        <img src="../assets/icons/bloodpresure.png" alt="Blood Pressure Icon">
                                    </div>
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['blood_pressure']); ?>
                                            mmHg</h5>
                                        <p class="card-text">Blood Pressure</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- SpO₂ Level Card -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon">
                                        <img src="../assets/icons/spo2.png" alt="SpO₂ Icon">
                                    </div>
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['spo2_level']); ?>%
                                        </h5>
                                        <p class="card-text">SpO₂ Level</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Second Row: Heart Rate, Stress Level, Glucose Level -->
                    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                        <!-- Heart Rate Card -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon">
                                        <img src="../assets/icons/heart.png" alt="Heart Rate Icon">
                                    </div>
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['heart_rate']); ?> BPM
                                        </h5>
                                        <p class="card-text">Heart Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Stress Level Card -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon">
                                        <img src="../assets/icons/stress.png" alt="Stress Icon">
                                    </div>
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['stress_level']); ?>
                                        </h5>
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
                                        <h5 class="card-title"><?php echo htmlspecialchars($latestRecord['glucose_level']); ?>
                                            mg/dL</h5>
                                        <p class="card-text">Glucose Level</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ECG and Stress Level Charts -->
                <div class="row mb-4">
                    <!-- ECG Data Chart Card -->
                    <div class="col-12 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon">
                                        <img src="../assets/icons/ecg.png" alt="ECG Icon"
                                            style="width: 40px; height: 40px;">
                                    </div>
                                    <h4 class="card-title mb-0">ECG Data</h4>
                                </div>
                                <canvas id="ecgChart" style="width: 100%; height: 200px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($selectedDate && empty($healthData)): ?>
                <div class="alert alert-warning text-center">
                    No health records found for the selected date.
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
                    labels: Array.from({ length: ecgData.length }, (_, i) => i),
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