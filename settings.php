<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Settings</title>
    <link rel="icon" type="image/png" href="mc.png">
    <!-- Font Awesome and Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Settings CSS -->
    <link rel="stylesheet" href="styles/settings.css">
</head>

<body>
    <!-- Main Navigation -->
    <header>
        <!-- Navbar -->
        <nav id="main-navbar" class="navbar navbar-expand-lg navbar-light fixed-top">
            <div class="container-fluid">
                <!-- Toggle button for sidebar -->
                <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <!-- Brand -->
                <a class="navbar-brand" href="#"
                    style="font-size: 1.5rem; font-weight: bold; color: white;">MedConnect</a>
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
                        <a href="patient_records.php" class="list-group-item list-group-item-action py-2 ripple">
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
                        <a class="list-group-item list-group-item-action py-2 ripple" data-toggle="collapse"
                            href="#medicalRecordsSubMenu">
                            <i class="fas fa-file-medical fa-fw me-3"></i><span>Medical Records</span>
                        </a>
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
        <!-- Sidebar Backdrop for Mobile -->
        <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="container pt-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4>My Profile</h4>
                </div>
                <div class="card-body">
                    <form action="update_profile.php" method="POST" id="profileForm" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Profile Picture Section -->
                            <div class="col-lg-4 text-center mb-4">
                                <img id="profilePicPreview"
                                    src="<?= htmlspecialchars($user['profile_pic'] ?: 'assets/avatar.png'); ?>"
                                    class="profile-picture" alt="Profile Picture">
                                <h5 class="mt-3"><?= htmlspecialchars($user['name']); ?></h5>
                                <small class="text-muted"><?= ucfirst(htmlspecialchars($user['user_type'])); ?></small>
                                <div class="mt-3" style="display: none;" id="profilePicUpload">
                                    <label for="profilePic" class="form-label"><strong>Upload Picture:</strong></label>
                                    <input type="file" class="form-control" id="profilePic" name="profile_pic">
                                </div>
                                <button type="button" class="btn btn-link" id="changePicBtn"
                                    onclick="toggleProfilePicUpload()">Change Picture</button>
                            </div>
                            <!-- Profile Details Section -->
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label for="name"><strong>Name</strong></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= htmlspecialchars($user['name']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="gender"><strong>Gender</strong></label>
                                    <input type="text" class="form-control" id="gender" name="gender"
                                        value="<?= htmlspecialchars($user['gender']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="age"><strong>Age</strong></label>
                                    <input type="number" class="form-control" id="age" name="age"
                                        value="<?= htmlspecialchars($user['age']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="height"><strong>Height (cm)</strong></label>
                                    <input type="number" class="form-control" id="height" name="height"
                                        value="<?= htmlspecialchars($user['height']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="weight"><strong>Weight (kg)</strong></label>
                                    <input type="number" class="form-control" id="weight" name="weight"
                                        value="<?= htmlspecialchars($user['weight']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="email"><strong>Email</strong></label>
                                    <!-- Read-only email shown for user -->
                                    <input type="email" class="form-control" id="email"
                                        value="<?= htmlspecialchars($user['email']); ?>" readonly>
                                    <!-- Hidden email input ensures email is submitted -->
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']); ?>">
                                </div>



                                <div class="form-group">
                                    <label for="contact"><strong>Contact</strong></label>
                                    <input type="text" class="form-control" id="contact" name="contact"
                                        value="<?= htmlspecialchars($user['contact']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="address"><strong>Address</strong></label>
                                    <input type="text" class="form-control" id="address" name="address"
                                        value="<?= htmlspecialchars($user['address'] ?? ''); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="blood_group"><strong>Blood Group</strong></label>
                                    <input type="text" class="form-control" id="blood_group" name="blood_group"
                                        value="<?= htmlspecialchars($user['blood_group'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        <!-- Edit / Save Buttons -->
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-warning" id="editBtn"
                                onclick="toggleEdit()">Edit</button>
                            <button type="submit" class="btn btn-primary" id="saveBtn" style="display: none;">Save
                                Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS and Dependencies -->
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

        // Toggle Edit and Save functionality for profile form
        function toggleEdit() {
            const inputs = document.querySelectorAll('#profileForm input');
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');

            inputs.forEach(input => {
                // Do not toggle the hidden email input
                if (input.type !== 'hidden') {
                    input.readOnly = !input.readOnly;
                }
            });

            if (editBtn.style.display !== 'none') {
                editBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';
            } else {
                editBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
            }
        }

        // Toggle profile picture upload section
        function toggleProfilePicUpload() {
            const profilePicUpload = document.getElementById("profilePicUpload");
            profilePicUpload.style.display = profilePicUpload.style.display === "none" ? "block" : "none";
        }
    </script>
</body>

</html>