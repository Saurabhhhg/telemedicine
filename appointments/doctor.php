<?php
session_start();
include '../db.php';

// Check if user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Handle appointment actions (accept or decline)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $appointment_id = intval($_GET['id']);
    if ($action === 'accept' || $action === 'decline') {
        $new_status = ($action === 'accept') ? 'accepted' : 'declined';
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $appointment_id);
        $stmt->execute();
        $stmt->close();
        header("Location: doctor.php");
        exit;
    }
}

// Fetch initial appointments (for page load)
$stmt = $conn->prepare("SELECT appointments.id, appointments.appointment_date, appointments.status, 
                               users.id AS patient_id, users.name AS patient_name, users.profile_pic
                        FROM appointments 
                        JOIN users ON appointments.patient_id = users.id
                        WHERE appointments.doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$today = date('Y-m-d');
$pending_appointments = array_filter($appointments, function ($appointment) {
    return $appointment['status'] === 'pending';
});
$accepted_appointments = array_filter($appointments, function ($appointment) use ($today) {
    return $appointment['status'] === 'accepted' && strpos($appointment['appointment_date'], $today) === 0;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="icon" type="image/png" href="../mc.png">
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../styles/doctor.css">
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
        <!-- Navbar -->

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
        <div class="container mt-5">
            <h3>Appointments</h3>
            <div class="tab-container">
                <!-- Tab Buttons -->
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="pending">Pending</button>
                    <button class="tab-button" data-tab="accepted">Today’s Accepted</button>
                </div>

                <!-- Pending Appointments Tab -->
                <div id="pending" class="tab-content active">
                    <?php
                    // Initial rendering for pending appointments
                    if (empty($pending_appointments)) {
                        echo '<p class="text-center text-muted">No pending appointments found.</p>';
                    } else {
                        foreach ($pending_appointments as $appointment) {
                            ?>
                            <div class="appointment-card">
                                <img src="<?= htmlspecialchars(!empty($appointment['profile_pic']) ? '../'.$appointment['profile_pic'] : '../assets/avatar.png'); ?>" class="profile-photo" alt="Profile pic">
                                <div class="appointment-details">
                                    <h5><?= htmlspecialchars($appointment['patient_name']); ?></h5>
                                    <p>
                                        <strong>Date:</strong> <?= htmlspecialchars($appointment['appointment_date']); ?><br>
                                        <span class="status-badge status-pending">Pending</span>
                                    </p>
                                </div>
                                <div>
                                    <a href="doctor.php?action=accept&id=<?= $appointment['id']; ?>" class="btn btn-success btn-sm">Accept</a>
                                    <a href="doctor.php?action=decline&id=<?= $appointment['id']; ?>" class="btn btn-danger btn-sm">Decline</a>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <!-- Accepted Appointments Tab -->
                <div id="accepted" class="tab-content">
                    <?php
                    // Initial rendering for today's accepted appointments
                    if (empty($accepted_appointments)) {
                        echo '<p class="text-center text-muted">No accepted appointments found for today.</p>';
                    } else {
                        foreach ($accepted_appointments as $appointment) {
                            ?>
                            <div class="appointment-card">
                                <img src="<?= htmlspecialchars(!empty($appointment['profile_pic']) ? '../'.$appointment['profile_pic'] : '../assets/avatar.png'); ?>" class="profile-photo" alt="Profile pic">
                                <div class="appointment-details">
                                    <h5><?= htmlspecialchars($appointment['patient_name']); ?></h5>
                                    <p>
                                        <strong>Date:</strong> <?= htmlspecialchars($appointment['appointment_date']); ?><br>
                                        <span class="status-badge status-accepted">Accepted</span>
                                    </p>
                                </div>
                                <div>
                                    <a href="../message/mess.php?user_id=<?= $appointment['patient_id']; ?>" class="btn btn-primary btn-sm">Message</a>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
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
        document.getElementById('sidebarBackdrop').addEventListener('click', function () {
            toggleSidebar();
        });

        // Tab Switching Logic using jQuery
        $(document).ready(function () {
            $(".tab-button").click(function () {
                const tabId = $(this).data("tab");
                $(".tab-button").removeClass("active");
                $(".tab-content").removeClass("active");
                $(this).addClass("active");
                $("#" + tabId).addClass("active");
            });
        });

        // AJAX for refreshing appointments
        function refreshAppointments() {
            $.ajax({
                url: 'refresh_d.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update pending appointments
                        $("#pending").html(response.pending_html);
                        // Update accepted appointments (today's accepted)
                        $("#accepted").html(response.accepted_html);
                    } else {
                        console.error("Error: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error refreshing appointments:", error);
                }
            });
        }
        // Refresh appointments every 10 seconds
        setInterval(refreshAppointments, 10000);
    </script>
</body>
</html>
