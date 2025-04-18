<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch data based on user type
if ($user_type === 'doctor') {
    // Fetch patients for the doctor
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.profile_pic 
        FROM users u 
        JOIN appointments a ON u.id = a.patient_id 
        WHERE a.doctor_id = ?
        GROUP BY u.id
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Handle prescription creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $patient_id = intval($_POST['patient_id']);
        $medication_name = $conn->real_escape_string($_POST['medication_name']);
        $dosage = $conn->real_escape_string($_POST['dosage']);
        $frequency = $conn->real_escape_string($_POST['frequency']);
        $duration = $conn->real_escape_string($_POST['duration']);
        $instructions = $conn->real_escape_string($_POST['instructions']);
        $notes = $conn->real_escape_string($_POST['notes']);
        $prescription_date = date('Y-m-d H:i:s');

        // Debugging: Print form data
        error_log("Form Data: " . print_r($_POST, true));

        $stmt = $conn->prepare("
            INSERT INTO prescriptions (doctor_id, patient_id, medication_name, dosage, frequency, duration, instructions, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iisssssss", $user_id, $patient_id, $medication_name, $dosage, $frequency, $duration, $instructions, $notes, $prescription_date);

        if ($stmt->execute()) {
            // Redirect to prevent form resubmission on refresh
            header("Location: prescription.php");
            exit;
        } else {
            error_log("Error creating prescription: " . $stmt->error);
            echo "<script>alert('Error creating prescription: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }

    // Check if a patient is selected via URL
    $selected_patient_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $selected_patient = null;
    if ($selected_patient_id) {
        foreach ($patients as $patient) {
            if ($patient['id'] === $selected_patient_id) {
                $selected_patient = $patient;
                break;
            }
        }
    }
} elseif ($user_type === 'patient') {
    // Fetch prescriptions for the patient
    $stmt = $conn->prepare("
        SELECT p.*, u.name AS doctor_name, u.profile_pic AS doctor_profile_pic 
        FROM prescriptions p 
        JOIN users u ON p.doctor_id = u.id 
        WHERE p.patient_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page with Sidebar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/prescription.css">
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
                    <?php if ($user_type === 'doctor'): ?>
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
                        <a class="list-group-item list-group-item-action py-2 ripple" data-toggle="collapse" href="#medicalRecordsSubMenu">
                            <i class="fas fa-file-medical fa-fw me-3"></i><span>Medical Records</span>
                        </a>
                        <!-- Sub-menu for Medical Records -->
                        <div id="medicalRecordsSubMenu" class="collapse" data-parent="#sidebarMenu">
                            <a href="medrecords/health_records.php" class="list-group-item list-group-item-action py-2 ripple sub-item">
                                <i class="fas fa-heartbeat fa-fw me-3"></i><span>Health Records</span>
                            </a>
                            <a href="prescriptions.php" class="list-group-item list-group-item-action py-2 ripple sub-item">
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
    <div class="container mt-5">
        <h2>Prescriptions</h2>
        <?php if ($user_type === 'doctor'): ?>
            <!-- Doctor: List of Patients -->
            <div class="patient-list <?= $selected_patient ? '' : 'active'; ?>">
                <h3>Select a Patient</h3>
                <div class="row">
                    <?php foreach ($patients as $patient): ?>
                        <div class="col-md-4">
                            <a href="prescription.php?user_id=<?= $patient['id']; ?>" class="text-decoration-none">
                                <div class="patient-card">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($patient['profile_pic'] ?? 'assets/avatar.png'); ?>" alt="Profile">
                                        <h5><?= htmlspecialchars($patient['name']); ?></h5>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Doctor: Prescription Form -->
            <div class="prescription-form <?= $selected_patient ? 'active' : ''; ?>">
                <?php if ($selected_patient): ?>
                    <h3>Write Prescription for <?= htmlspecialchars($selected_patient['name']); ?></h3>
                    <form id="prescription-form" method="POST">
                        <input type="hidden" name="patient_id" value="<?= $selected_patient['id']; ?>">
                        <div class="form-group">
                            <label for="medication_name">Medication Name</label>
                            <input type="text" name="medication_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="dosage">Dosage</label>
                            <input type="text" name="dosage" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="frequency">Frequency</label>
                            <input type="text" name="frequency" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration</label>
                            <input type="text" name="duration" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="instructions">Instructions</label>
                            <textarea name="instructions" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Prescription</button>
                        <a href="prescription.php" class="btn btn-secondary">Back to Patients</a>
                    </form>
                <?php endif; ?>
            </div>
        <?php elseif ($user_type === 'patient'): ?>
            <!-- Patient: View Prescriptions -->
            <div class="row">
                <?php if (empty($prescriptions)): ?>
                    <p>No prescriptions found.</p>
                <?php else: ?>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <div class="col-md-4">
                            <div class="prescription-card">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?= htmlspecialchars($prescription['doctor_profile_pic'] ?? 'assets/avatar.png'); ?>" alt="Doctor">
                                    <h5><?= htmlspecialchars($prescription['doctor_name']); ?></h5>
                                    <a href="generate_pdf.php?prescription_id=<?= $prescription['id']; ?>" class="ml-auto" title="Download Prescription">
                                        <i class="fas fa-download"></i> <!-- FontAwesome download icon -->
                                    </a>
                                </div>
                                <!-- Table for Prescription Details -->
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>Date</th>
                                            <td><?= htmlspecialchars($prescription['updated_at']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Medication</th>
                                            <td><?= htmlspecialchars($prescription['medication_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Dosage</th>
                                            <td><?= htmlspecialchars($prescription['dosage']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Frequency</th>
                                            <td><?= htmlspecialchars($prescription['frequency']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Duration</th>
                                            <td><?= htmlspecialchars($prescription['duration']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Instructions</th>
                                            <td><?= htmlspecialchars($prescription['instructions']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Notes</th>
                                            <td><?= htmlspecialchars($prescription['notes']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // AJAX for prescription submission
        $(document).ready(function () {
            $('#prescription-form').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'prescription.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        console.log(response); // Debugging: Print the response
                        alert('Prescription created successfully!');
                        window.location.href = 'prescription.php';
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText); // Debugging: Print the error
                        alert('Error creating prescription.');
                    }
                });
            });
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