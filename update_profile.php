<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize inputs
    $name        = isset($_POST['name']) ? trim($_POST['name']) : '';
    $gender      = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $age         = isset($_POST['age']) ? intval($_POST['age']) : 0;
    $height      = isset($_POST['height']) ? floatval($_POST['height']) : 0.0;
    $weight      = isset($_POST['weight']) ? floatval($_POST['weight']) : 0.0;
    $email       = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contact     = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $address     = isset($_POST['address']) ? trim($_POST['address']) : '';
    $blood_group = isset($_POST['blood_group']) ? trim($_POST['blood_group']) : '';

    // Validate email; if invalid, fallback to a default value
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'ex@mail.com';
    }

    // Handle profile picture upload
    $profile_pic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_pics/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName    = basename($_FILES['profile_pic']['name']);
        $fileSize    = $_FILES['profile_pic']['size'];
        $fileType    = $_FILES['profile_pic']['type'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize     = 5 * 1024 * 1024; // 5 MB

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            $fileExt     = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('profile_', true) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $profile_pic = $destination;
            }
        }
    }

    // Prepare SQL for updating the user's profile
    if ($profile_pic !== null) {
        $sql = "UPDATE users
                SET name = ?, gender = ?, age = ?, height = ?, weight = ?, email = ?, contact = ?, address = ?, blood_group = ?, profile_pic = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        // Bind parameters: s: name, s: gender, i: age, d: height, d: weight, s: email, s: contact, s: address, s: blood_group, s: profile_pic, i: user_id
        $stmt->bind_param('ssiddsssssi', $name, $gender, $age, $height, $weight, $email, $contact, $address, $blood_group, $profile_pic, $user_id);
    } else {
        $sql = "UPDATE users
                SET name = ?, gender = ?, age = ?, height = ?, weight = ?, email = ?, contact = ?, address = ?, blood_group = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        // Bind parameters: s: name, s: gender, i: age, d: height, d: weight, s: email, s: contact, s: address, s: blood_group, i: user_id
        $stmt->bind_param('ssiddssssi', $name, $gender, $age, $height, $weight, $email, $contact, $address, $blood_group, $user_id);
    }

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    // Redirect back to settings page
    header('Location: settings.php');
    exit();
}
?>
