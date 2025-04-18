<?php
session_start();
include '../db.php';

// Redirect if not logged in as a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch doctors and their available slots
$doctors = [];
$sql = "
    SELECT u.id AS doctor_id, u.name, u.specialization, u.profile_pic, GROUP_CONCAT(s.available_slot SEPARATOR ', ') AS available_slots
    FROM users u
    LEFT JOIN availability s ON u.id = s.doctor_id
    WHERE u.user_type = 'doctor'
    GROUP BY u.id
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result === false) {
    die("Execution Error: " . $stmt->error);
}
$doctors = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch patient's appointments (initial rendering; AJAX refresh will update these)
$appointments = [];
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_date, a.status, u.id AS doctor_id, u.name AS doctor_name, u.profile_pic AS doctor_profile_pic
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle booking appointments via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['doctor_id']) && isset($_POST['appointment_date'])) {
    $doctor_id = intval($_POST['doctor_id']);
    $appointment_date = $conn->real_escape_string($_POST['appointment_date']);

    // Validate the date and time format (expects "Y-m-d H:i:s")
    if (DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date) !== false) {
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $user_id, $doctor_id, $appointment_date);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error booking appointment: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid date or time format!']);
    }
    exit;
}

// Function to format slots
function formatSlots($slots) {
    if (empty($slots)) {
        return 'No slots available';
    }
    $slotsArray = explode(', ', $slots);
    return date("h:i A", strtotime($slotsArray[0])); // Return the first slot in 12-hour format
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link rel="icon" type="image/png" href="../mc.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/patient.css">
</head>
<body>
    <!-- Navbar -->
    <header>
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
                            <li><a class="dropdown-item" href="../settings.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
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
                <a href="../dashboard.php" class="list-group-item list-group-item-action py-2 ripple">
                    <i class="fas fa-home fa-fw me-3"></i><span>Home</span>
                </a>
                <a href="../appointments/patient.php" class="list-group-item list-group-item-action py-2 ripple">
                    <i class="fas fa-calendar-check fa-fw me-3"></i><span>Appointments</span>
                </a>
                <a class="list-group-item list-group-item-action py-2 ripple" data-toggle="collapse" href="#medicalRecordsSubMenu">
                    <i class="fas fa-file-medical fa-fw me-3"></i><span>Medical Records</span>
                </a>
                <div id="medicalRecordsSubMenu" class="collapse" data-parent="#sidebarMenu">
                    <a href="../medrecords/health_records.php" class="list-group-item list-group-item-action py-2 ripple sub-item">
                        <i class="fas fa-heartbeat fa-fw me-3"></i><span>Health Records</span>
                    </a>
                    <a href="../prescription.php" class="list-group-item list-group-item-action py-2 ripple sub-item">
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

    <!-- Backdrop for sidebar on mobile -->
    <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main>
        <div class="container mt-5">
            <div class="tab-container">
                <!-- Alert for success or error messages -->
                <div id="alertContainer" class="alert">
                    <strong id="alertMessage"></strong>
                </div>

                <!-- Tab Buttons -->
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="book">Book Appointment</button>
                    <button class="tab-button" data-tab="status">Appointment Status</button>
                </div>

                <!-- Book Appointment Tab -->
                <div id="book" class="tab-content active">
                    <h3 class="text-center">Available Doctors</h3>
                    <div class="row">
                        <?php if (!empty($doctors)): ?>
                            <?php foreach ($doctors as $doctor): ?>
                                <div class="col-md-4">
                                    <div class="doctor-card text-center">
                                        <img src="<?= htmlspecialchars(!empty($doctor['profile_pic']) ? '../'.$doctor['profile_pic'] : '../assets/avatar.png'); ?>" alt="Doctor">
                                        <h5><?= htmlspecialchars($doctor['name']); ?></h5>
                                        <p><?= htmlspecialchars($doctor['specialization']); ?></p>
                                        <p><strong>Next Available Slot:</strong> 
                                            <?= (!empty($doctor['available_slots'])) ? date("h:i A", strtotime(explode(', ', $doctor['available_slots'])[0])) : "No slots available"; ?>
                                        </p>
                                        <button class="btn btn-primary" data-toggle="modal" data-target="#appointmentModal<?= $doctor['doctor_id']; ?>">Book Appointment</button>
                                    </div>
                                </div>

                                <!-- Appointment Modal -->
                                <div class="modal fade" id="appointmentModal<?= $doctor['doctor_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="appointmentModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="appointmentModalLabel">Book Appointment with <?= htmlspecialchars($doctor['name']); ?></h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form id="appointmentForm<?= $doctor['doctor_id']; ?>" method="POST">
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="appointment_date">Select Date</label>
                                                        <input type="date" name="appointment_date" class="form-control" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="appointment_time">Select Time</label>
                                                        <select name="appointment_time" class="form-control" required>
                                                            <?php 
                                                            if (!empty($doctor['available_slots'])) {
                                                                $slots = explode(', ', $doctor['available_slots']);
                                                                foreach ($slots as $slot): ?>
                                                                    <option value="<?= htmlspecialchars($slot); ?>"><?= htmlspecialchars(date("h:i A", strtotime($slot))); ?></option>
                                                                <?php endforeach;
                                                            } else { ?>
                                                                <option>No slots available</option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <input type="hidden" name="doctor_id" value="<?= $doctor['doctor_id']; ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-primary" onclick="bookAppointment(<?= $doctor['doctor_id']; ?>)">Confirm</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center text-muted">No doctors available at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Appointment Status Tab -->
                <div id="status" class="tab-content">
                    <div id="appointmentsContainer">
                        <!-- This container will be refreshed via AJAX from refresh_p.php -->
                        <?php
                        // Initial rendering for today's appointments
                        $today = date('Y-m-d');
                        $filteredAppointments = array_filter($appointments, function($appt) use ($today) {
                            return strpos($appt['appointment_date'], $today) === 0;
                        });
                        if (!empty($filteredAppointments)) {
                            echo '<div class="row">';
                            foreach ($filteredAppointments as $appointment) {
                                ?>
                                <div class="col-md-4">
                                    <div class="doctor-card text-center">
                                        <img src="<?= htmlspecialchars(!empty($appointment['doctor_profile_pic']) ? '../'.$appointment['doctor_profile_pic'] : '../assets/avatar.png'); ?>" alt="Doctor">
                                        <h5><?= htmlspecialchars($appointment['doctor_name']); ?></h5>
                                        <p>
                                            <strong>Date:</strong> <?= htmlspecialchars($appointment['appointment_date']); ?><br>
                                            <span class="status-badge status-<?= htmlspecialchars($appointment['status']); ?>">
                                                <?= ucfirst(htmlspecialchars($appointment['status'])); ?>
                                            </span>
                                        </p>
                                        <?php if ($appointment['status'] === 'accepted'): ?>
                                            <a href="../message/mess.php?user_id=<?= $appointment['doctor_id']; ?>" class="btn btn-primary btn-sm">Message</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="col-12"><p class="text-center text-muted">No appointments found for today.</p></div>';
                        }
                        ?>
                    </div>
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
        $(".tab-button").click(function () {
            const tabId = $(this).data("tab");
            $(".tab-button").removeClass("active");
            $(".tab-content").removeClass("active");
            $(this).addClass("active");
            $("#" + tabId).addClass("active");
        });
        // Book Appointment function using AJAX
        function bookAppointment(doctorId) {
            var form = $('#appointmentForm' + doctorId);
            var date = form.find('input[name="appointment_date"]').val();
            var time = form.find('select[name="appointment_time"]').val();
            var appointmentDateTime = date + ' ' + time;
            var formData = form.serializeArray();
            formData.push({ name: 'appointment_date', value: appointmentDateTime });
            $.ajax({
                url: '', // Current page URL
                type: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function(response) {
                    var alertMessage = $('#alertMessage');
                    var alertContainer = $('#alertContainer');
                    if (response.success) {
                        alertMessage.text(response.message);
                        alertContainer.removeClass('alert-danger').addClass('alert-success').show();
                        // Refresh appointments after booking
                        refreshAppointments();
                    } else {
                        alertMessage.text(response.message);
                        alertContainer.removeClass('alert-success').addClass('alert-danger').show();
                    }
                    $('#appointmentModal' + doctorId).modal('hide');
                },
                error: function(xhr, status, error) {
                    var alertMessage = $('#alertMessage');
                    var alertContainer = $('#alertContainer');
                    alertMessage.text('An error occurred while booking the appointment.');
                    alertContainer.removeClass('alert-success').addClass('alert-danger').show();
                    $('#appointmentModal' + doctorId).modal('hide');
                }
            });
        }
        // AJAX for refreshing appointments
        function refreshAppointments() {
            $.ajax({
                url: 'refresh_p.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $("#appointmentsContainer").html(response.html);
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
