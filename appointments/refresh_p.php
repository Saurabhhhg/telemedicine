<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch patient's appointments
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

// Filter appointments to only include those for the current date
$today = date('Y-m-d');
$appointments = array_filter($appointments, function($appt) use ($today) {
    // This assumes appointment_date is formatted as "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS"
    return strpos($appt['appointment_date'], $today) === 0;
});

// Build HTML snippet for appointments
ob_start();
if (!empty($appointments)) {
    echo '<div class="row">';
    foreach ($appointments as $appointment) {
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
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
?>
