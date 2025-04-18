<?php
session_start();
include 'db.php';

// Redirect if not logged in or not a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Fetch user data for the sidebar
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// ---------------------------------------------------------
// Auto-Delete Booked Slots for Today
// ---------------------------------------------------------
$today = date('Y-m-d');

// First, fetch all booked slots for today from appointments
$bookedSlots = [];
$stmt = $conn->prepare("SELECT TIME(appointment_date) as booked_time FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?");
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookedSlots[] = $row['booked_time'];
}
$stmt->close();

// Delete each booked slot from availability table
if (!empty($bookedSlots)) {
    foreach ($bookedSlots as $bSlot) {
        $stmt = $conn->prepare("DELETE FROM availability WHERE doctor_id = ? AND available_slot = ?");
        $stmt->bind_param("is", $doctor_id, $bSlot);
        $stmt->execute();
        $stmt->close();
    }
}

// ---------------------------------------------------------
// Fetch existing slots (after auto-deletion)
$slots = [];
$stmt = $conn->prepare("SELECT id, available_slot FROM availability WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$slots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle slot addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['slot'])) {
    $slot = $conn->real_escape_string($_POST['slot']);

    // Check if the slot already exists
    $stmt = $conn->prepare("SELECT id FROM availability WHERE doctor_id = ? AND available_slot = ?");
    $stmt->bind_param("is", $doctor_id, $slot);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "Slot already exists!";
    } else {
        // Insert new slot
        $stmt = $conn->prepare("INSERT INTO availability (doctor_id, available_slot) VALUES (?, ?)");
        $stmt->bind_param("is", $doctor_id, $slot);

        if ($stmt->execute()) {
            $message = "Slot added successfully!";
        } else {
            $message = "Error adding slot: " . $stmt->error;
        }
    }
    $stmt->close();
}

// Handle slot deletion via GET parameter
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM availability WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        header("Location: availability.php");
        exit;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Availability</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/availability.css">
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
                <a class="navbar-brand" href="#" style="font-size: 1.5rem; font-weight: bold; color: white;">MedConnect</a>
                <!-- Right links -->
                <ul class="navbar-nav ms-auto d-flex flex-row">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle hidden-arrow d-flex align-items-center" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-expanded="false">
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
                    <a href="appointments/doctor.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span>
                    </a>
                    <a href="p_records/patient_records.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-folder-open fa-fw me-3"></i><span>Patient Records</span>
                    </a>
                    <a href="prescription.php" class="list-group-item list-group-item-action py-2 ripple">
                        <i class="fas fa-prescription fa-fw me-3"></i><span>Prescription</span>
                    </a>
                    <a href="availability.php" class="list-group-item list-group-item-action py-2 ripple active">
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
        <div class="container mt-5">
            <h2>Manage Availability</h2>
            <?php if (isset($message)): ?>
                <div class="alert alert-info"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="mb-4">
                <div class="form-group">
                    <label for="slot">Add Available Slot (HH:MM:SS)</label>
                    <input type="time" id="slot" name="slot" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Slot</button>
            </form>

            <h3>Available Slots</h3>
            <ul class="list-group">
                <?php foreach ($slots as $slot): ?>
                    <li class="list-group-item">
                        <span><?= htmlspecialchars($slot['available_slot']); ?></span>
                        <a href="?delete_id=<?= $slot['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
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
    </script>
</body>
</html>
