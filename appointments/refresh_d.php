<?php
session_start();
include '../db.php';

// Check if user is logged in as a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Fetch appointments for this doctor
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

// Filter pending appointments
$pendingAppointments = array_filter($appointments, function($a) {
    return $a['status'] === 'pending';
});

// Filter accepted appointments for today
$today = date('Y-m-d');
$acceptedAppointments = array_filter($appointments, function ($appointment) use ($today) {
    // Assuming appointment_date is stored as "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS"
    return $appointment['status'] === 'accepted' && strpos($appointment['appointment_date'], $today) === 0;
});

// Build HTML for pending appointments
ob_start();
if (empty($pendingAppointments)) {
    echo '<p class="text-center text-muted">No pending appointments found.</p>';
} else {
    foreach ($pendingAppointments as $appointment) {
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
$pending_html = ob_get_clean();

// Build HTML for accepted appointments (today only)
ob_start();
if (empty($acceptedAppointments)) {
    echo '<p class="text-center text-muted">No accepted appointments found for today.</p>';
} else {
    foreach ($acceptedAppointments as $appointment) {
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
$accepted_html = ob_get_clean();

echo json_encode([
    'success' => true,
    'pending_html' => $pending_html,
    'accepted_html' => $accepted_html
]);
?>
