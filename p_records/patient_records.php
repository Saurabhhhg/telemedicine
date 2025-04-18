<?php
include '../db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION['user_id']; // Get the logged-in doctor's ID



// Fetch patients with accepted appointments for the logged-in doctor on or after the current date
$patientQuery = "
    SELECT DISTINCT users.id, users.name, users.profile_pic
    FROM appointments
    JOIN users ON appointments.patient_id = users.id
    WHERE appointments.doctor_id = '$doctor_id'
    AND appointments.status = 'accepted'
    AND DATE(appointments.appointment_date) >= CURDATE() -- Include today and future dates
    ORDER BY users.name ASC
";

$patientResult = mysqli_query($conn, $patientQuery);

// Check if the query was successful
if (!$patientResult) {
    die("SQL Error: " . mysqli_error($conn));
}

$patients = [];
while ($row = mysqli_fetch_assoc($patientResult)) {
    $patients[] = $row;
}
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
    <link rel="stylesheet" href="../styles/patient_r.css">
</head>
<body>
    <!-- Main Navigation -->
    <header>
        <!-- Navbar -->
        <nav id="main-navbar" class="navbar navbar-expand-lg navbar-light fixed-top">
            <div class="container-fluid">
                <!-- Toggle button -->
                <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Brand -->
                <a class="navbar-brand" href="#" style="font-size: 1.5rem; font-weight: bold; color: white;">
                    MedConnect
                </a>

                <!-- Right links -->
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

        <!-- Backdrop for sidebar on mobile -->
        <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    </header>

    <!-- Main Content -->
    <main>
     <div class="container-fluid py-4">
        <!-- Header -->
        <div class="header">
            <h1>Patient Records</h1>
        </div>

        <!-- Patient List -->
        <?php if (!empty($patients)): ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($patients as $patient): ?>
                    <div class="col">
                        <div class="card h-100" onclick="window.location.href='patient_health_records.php?patient_id=<?php echo $patient['id']; ?>'">
                            <div class="card-body d-flex align-items-center">
                                <!-- Profile Picture -->
                                <img src="<?php echo !empty($patient['profile_pic']) ? '../' . htmlspecialchars($patient['profile_pic']) : '../assets/avatar.png'; ?>" 
                                     alt="Profile Picture" 
                                     class="profile-pic">
                                <!-- Patient Name -->
                                <h5 class="card-title"><?php echo htmlspecialchars($patient['name']); ?></h5>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Appointments Message -->
            <div class="no-appointments">
                <p>You don't have any appointments today or in the future.</p>
            </div>
        <?php endif; ?>
    </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ECG Data Chart
        var ctx = document.getElementById('ecgChart').getContext('2d');
        var ecgData = <?php echo json_encode(explode(',', $healthData[0]['ecg_data'])); ?>;
        var ecgChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({length: ecgData.length}, (_, i) => i),
                datasets: [{
                    label: 'ECG Data',
                    data: ecgData,
                    borderColor: '#007bff',
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        min: Math.min(...ecgData) - 1,
                        max: Math.max(...ecgData) + 1
                    }
                }
            }
        });

        // Stress Level (GSR) Data Chart
        var stressCtx = document.getElementById('stressChart').getContext('2d');
        var stressData = <?php echo json_encode(explode(',', $healthData[0]['stress_level'])); ?>;
        var stressChart = new Chart(stressCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: stressData.length}, (_, i) => i),
                datasets: [{
                    label: 'Stress Level (GSR)',
                    data: stressData,
                    borderColor: '#dc3545',
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        min: Math.min(...stressData) - 1,
                        max: Math.max(...stressData) + 1
                    }
                }
            }
        });
    </script>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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