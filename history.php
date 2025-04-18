<?php
include 'db.php';
session_start();

// Check if the user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: login.php"); // Redirect to login if not a doctor
    exit;
}

$doctor_id = $_SESSION['user_id']; // Get the logged-in doctor's ID

// Fetch all accepted appointments for the logged-in doctor
$historyQuery = "
    SELECT users.id AS patient_id, users.name AS patient_name, users.profile_pic, 
           users.username, users.contact, users.email, users.blood_group, 
           users.address, users.age, appointments.appointment_date, appointments.status
    FROM appointments
    JOIN users ON appointments.patient_id = users.id
    WHERE appointments.doctor_id = '$doctor_id'
    AND appointments.status = 'accepted'
    ORDER BY appointments.appointment_date DESC -- Sort by date (newest first)
";

$historyResult = mysqli_query($conn, $historyQuery);

// Check if the query was successful
if (!$historyResult) {
    die("SQL Error: " . mysqli_error($conn));
}

$appointments = [];
while ($row = mysqli_fetch_assoc($historyResult)) {
    $appointments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History</title>
    <link rel="icon" type="image/png" href="mc.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/history.css">
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
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="header">
                <h1>Appointment History</h1>
            </div>

            <!-- Appointment List -->
            <div class="appointment-list">
                <?php if (!empty($appointments)): ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="appointment-item" data-bs-toggle="modal" data-bs-target="#patientModal" 
                             data-patient-name="<?= htmlspecialchars($appointment['patient_name']); ?>"
                             data-username="<?= htmlspecialchars($appointment['username']); ?>"
                             data-contact="<?= htmlspecialchars($appointment['contact']); ?>"
                             data-email="<?= htmlspecialchars($appointment['email']); ?>"
                             data-blood-group="<?= htmlspecialchars($appointment['blood_group']); ?>"
                             data-address="<?= htmlspecialchars($appointment['address']); ?>"
                             data-age="<?= htmlspecialchars($appointment['age']); ?>">
                            <!-- Profile Picture -->
                            <img src="<?php echo !empty($appointment['profile_pic']) ? htmlspecialchars($appointment['profile_pic']) : 'assets/avatar.png'; ?>" 
                                 alt="Profile Picture" 
                                 class="profile-pic">
                            <!-- Patient Info -->
                            <div class="patient-info">
                                <div class="patient-name"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                <div class="appointment-date">
                                    <?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($appointment['appointment_date']))); ?>
                                </div>
                            </div>
                            <!-- Message Button -->
                            <a href="message/mess.php?user_id=<?= $appointment['patient_id']; ?>" class="btn btn-primary btn-sm message-btn">Message</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- No Appointments Message -->
                    <div class="no-appointments">
                        <p>No appointments history found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Patient Details Modal -->
        <div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="patientModalLabel">Patient Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Name:</div>
                            <div class="col-8" id="modalPatientName"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Username:</div>
                            <div class="col-8" id="modalUsername"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Contact:</div>
                            <div class="col-8" id="modalContact"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Email:</div>
                            <div class="col-8" id="modalEmail"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Blood Group:</div>
                            <div class="col-8" id="modalBloodGroup"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Address:</div>
                            <div class="col-8" id="modalAddress"></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-4 fw-bold">Age:</div>
                            <div class="col-8" id="modalAge"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript to Populate Modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('patientModal');
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const patientName = button.getAttribute('data-patient-name');
                const username = button.getAttribute('data-username');
                const contact = button.getAttribute('data-contact');
                const email = button.getAttribute('data-email');
                const bloodGroup = button.getAttribute('data-blood-group');
                const address = button.getAttribute('data-address');
                const age = button.getAttribute('data-age');

                // Update modal content
                document.getElementById('modalPatientName').textContent = patientName;
                document.getElementById('modalUsername').textContent = username;
                document.getElementById('modalContact').textContent = contact;
                document.getElementById('modalEmail').textContent = email;
                document.getElementById('modalBloodGroup').textContent = bloodGroup;
                document.getElementById('modalAddress').textContent = address;
                document.getElementById('modalAge').textContent = age;
            });
        });

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