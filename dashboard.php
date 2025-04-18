<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user_type === 'patient') {
    // Fetch latest appointment for the patient
    $stmt = $conn->prepare("
        SELECT appointments.id, appointments.appointment_date, appointments.status, users.name AS doctor_name, users.profile_pic
        FROM appointments
        JOIN users ON appointments.doctor_id = users.id
        WHERE appointments.patient_id = ?
        ORDER BY appointments.appointment_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_appointment = $result->fetch_assoc();
    $stmt->close();

    // Fetch latest health metrics for the patient
    $stmt = $conn->prepare("
        SELECT temperature, blood_pressure, glucose_level, spo2_level
        FROM health_metrics
        WHERE user_id = ?
        ORDER BY timestamp DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_health_metrics = $result->fetch_assoc();
    $stmt->close();
} elseif ($user_type === 'doctor') {
    // Fetch latest pending appointments for the doctor
    $stmt = $conn->prepare("
        SELECT appointments.id, appointments.appointment_date, users.name AS patient_name, users.profile_pic
        FROM appointments
        JOIN users ON appointments.patient_id = users.id
        WHERE appointments.doctor_id = ? AND appointments.status = 'pending'
        ORDER BY appointments.appointment_date DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_appointments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch latest accepted appointment for the doctor
    $stmt = $conn->prepare("
        SELECT appointments.id, appointments.appointment_date, appointments.patient_id, users.name AS patient_name, users.profile_pic
        FROM appointments
        JOIN users ON appointments.patient_id = users.id
        WHERE appointments.doctor_id = ? AND appointments.status = 'accepted'
        ORDER BY appointments.appointment_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_accepted_appointment = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
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
                        <a class="nav-link dropdown-toggle hidden-arrow d-flex align-items-center" href="#"
                            id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-expanded="false">
                            <img src="assets/avatar.png" class="rounded-circle" height="22" alt="Avatar" loading="lazy">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="dashboard.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- Navbar -->

        <!-- Sidebar -->
        <nav id="sidebarMenu" class="sidebar">
            <div class="position-sticky">
                <div class="list-group list-group-flush mx-3 mt-4">
                    <a href="dashboard.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-home fa-fw me-3"></i><span>Home</span>
                    </a>
                    <?php if ($user['user_type'] == 'doctor'): ?>
                        <!-- Doctor-specific menu items -->
                        <a href="appointments/doctor.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span>
                        </a>
                        <a href="p_records/patient_records.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-folder-open fa-fw me-3"></i><span>Patient Records</span>
                        </a>
                        <a href="prescription.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-prescription fa-fw me-3"></i><span>Prescription</span>
                        </a>
                        <a href="availability.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-clock fa-fw me-3"></i><span>Availability</span>
                        </a>
                        <a href="message/mess.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-comments fa-fw me-3"></i><span>Messages</span>
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-cog fa-fw me-3"></i><span>Settings</span>
                        </a>
                        <a href="history.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-history fa-fw me-3"></i><span>History</span>
                        </a>

                    <?php else: ?>
                        <!-- Patient-specific menu items -->
                        <a href="appointments/patient.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span>
                        </a>
                        <!-- Medical Records Sub-Menu -->
                        <a class="list-group-item list-group-item-action py-2 ripple" data-toggle="collapse"
                            href="#medicalRecordsSubMenu">
                            <i class="fas fa-file-medical fa-fw me-3"></i><span>Medical Records</span>
                        </a>
                        <!-- Sub-menu for Medical Records -->
                        <div id="medicalRecordsSubMenu" class="collapse" data-parent="#sidebarMenu">
                            <a href="medrecords/health_records.php"
                                class="list-group-item list-group-item-action py-2 ripple sub-item">
                                <i class="fas fa-heartbeat fa-fw me-3"></i><span>Health Records</span>
                            </a>
                            <a href="prescription.php" class="list-group-item list-group-item-action py-2 ripple sub-item">
                                <i class="fas fa-prescription-bottle fa-fw me-3"></i><span>Prescriptions</span>
                            </a>
                        </div>
                        <a href="health_tracking.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-heartbeat fa-fw me-3"></i><span>Health Tracking</span>
                        </a>
                        <a href="message/mess.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-comments fa-fw me-3"></i><span>Messages</span>
                        </a>
                        <a href="ai.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-robot fa-fw me-3"></i><span>AI chat</span>
                        </a>

                        <a href="settings.php" class="list-group-item list-group-item-action py-2 ripple">
                            <i class="fas fa-cog fa-fw me-3"></i><span>Settings</span>
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="list-group-item list-group-item-action py-2 ripple">
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
        <div class="container">
            <!-- Common Profile Container -->
            <div class="profile-container">
                <!-- Pen Icon (Edit Button) -->
                <a href="settings.php" class="edit-icon">
                    <i class="fas fa-pen"></i>
                </a>
                <!-- Profile Picture -->
                <img src="<?= htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture">
                <!-- User Name -->
                <h3><?= htmlspecialchars($user['name']); ?></h3>
            </div>

            <?php if ($user_type === 'patient'): ?>
                <!-- Patient View -->
                <!-- Appointments Container -->
                <div class="card">
                    <div class="card-body">
                        <h3>Appointment</h3>
                        <?php if ($latest_appointment): ?>
                            <img src="<?= htmlspecialchars($latest_appointment['profile_pic']); ?>" alt="Doctor">
                            <h5 class="card-title"><?= htmlspecialchars($latest_appointment['doctor_name']); ?></h5>
                            <p class="card-text">
                                <strong>Date:</strong> <?= htmlspecialchars($latest_appointment['appointment_date']); ?><br>
                                <span class="status-badge status-<?= htmlspecialchars($latest_appointment['status']); ?>">
                                    <?= ucfirst(htmlspecialchars($latest_appointment['status'])); ?>
                                </span>
                            </p>
                        <?php else: ?>
                            <p class="text-center text-muted">No appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Health Metrics Container -->
                <div class="health-metrics-container">
                    <h3>Health Metrics</h3>
                    <div class="row">
                        <?php if ($latest_health_metrics): ?>
                            <!-- Temperature Card -->
                            <div class="col-6 col-md-3">
                                <div class="metric-card">
                                    <img src="assets/icons/tempreture.png" alt="Temperature Icon">
                                    <h5>Temperature</h5>
                                    <p><?= htmlspecialchars($latest_health_metrics['temperature']); ?>°C</p>
                                </div>
                            </div>

                            <!-- Blood Pressure Card -->
                            <div class="col-6 col-md-3">
                                <div class="metric-card">
                                    <img src="assets/icons/bloodpresure.png" alt="Blood Pressure Icon">
                                    <h5>Blood Pressure</h5>
                                    <p><?= htmlspecialchars($latest_health_metrics['blood_pressure']); ?></p>
                                </div>
                            </div>

                            <!-- Glucose Level Card -->
                            <div class="col-6 col-md-3">
                                <div class="metric-card">
                                    <img src="assets/icons/glucose.png" alt="Glucose Level Icon">
                                    <h5>Glucose Level</h5>
                                    <p><?= htmlspecialchars($latest_health_metrics['glucose_level']); ?> mg/dL</p>
                                </div>
                            </div>

                            <!-- SpO2 Level Card -->
                            <div class="col-6 col-md-3">
                                <div class="metric-card">
                                    <img src="assets/icons/spo2.png" alt="SpO2 Level Icon">
                                    <h5>SpO2 Level</h5>
                                    <p><?= htmlspecialchars($latest_health_metrics['spo2_level']); ?>%</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center text-muted">No health metrics found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($user_type === 'doctor'): ?>
                <!-- Doctor View -->
                <!-- Pending Appointments Container -->
                <div class="card">
                    <div class="card-body">
                        <h3>Pending Appointments</h3>
                        <?php if (!empty($pending_appointments)): ?>
                            <?php foreach ($pending_appointments as $appointment): ?>
                                <div class="card">
                                    <img src="<?= htmlspecialchars($appointment['profile_pic']); ?>" alt="Patient">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($appointment['patient_name']); ?></h5>
                                        <p class="card-text">
                                            <strong>Date:</strong> <?= htmlspecialchars($appointment['appointment_date']); ?>
                                        </p>
                                        <a href="accept_appointment.php?id=<?= $appointment['id']; ?>"
                                            class="btn btn-success">Accept</a>
                                        <a href="decline_appointment.php?id=<?= $appointment['id']; ?>"
                                            class="btn btn-danger">Decline</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">No pending appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Accepted Appointments Container -->
                <div class="card">
                    <div class="card-body">
                        <h3>Accepted Appointment</h3>
                        <?php if ($latest_accepted_appointment): ?>
                            <div class="card">
                                <img src="<?= htmlspecialchars($latest_accepted_appointment['profile_pic']); ?>" alt="Patient">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($latest_accepted_appointment['patient_name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Date:</strong>
                                        <?= htmlspecialchars($latest_accepted_appointment['appointment_date']); ?>
                                    </p>
                                    <a href="message/mess.php?user_id=<?= htmlspecialchars($latest_accepted_appointment['patient_id']); ?>"
                                        class="btn btn-primary">Message</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No accepted appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

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