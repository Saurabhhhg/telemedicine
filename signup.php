<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_type = $_POST['user_type'];
    $username = $_POST['username'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Additional fields for patients
    if ($user_type == 'patient') {
        $height = $_POST['height'];
        $weight = $_POST['weight'];
    }

    // Additional fields for doctors
    if ($user_type == 'doctor') {
        $specialization = $_POST['specialization'];
        $qualification = $_POST['qualification'];
    }

    // Insert data into the database
    if ($user_type == 'patient') {
        $sql = "INSERT INTO users (user_type, username, name, age, gender, height, weight, password) 
                VALUES ('$user_type', '$username', '$name', $age, '$gender', $height, $weight, '$password')";
    } elseif ($user_type == 'doctor') {
        $sql = "INSERT INTO users (user_type, username, name, age, gender, specialization, qualification, password) 
                VALUES ('$user_type', '$username', '$name', $age, '$gender', '$specialization', '$qualification', '$password')";
    }

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Signup successful! Please login.'); window.location.href = 'login.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - MedConnect</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link rel="stylesheet" href="styles/signup.css">
    <script>
        function showFields() {
            const userType = document.getElementById("user_type").value;
            const patientFields = document.getElementById("patient_fields");
            const doctorFields = document.getElementById("doctor_fields");

            if (userType === "patient") {
                patientFields.style.display = "block";
                doctorFields.style.display = "none";
            } else if (userType === "doctor") {
                patientFields.style.display = "none";
                doctorFields.style.display = "block";
            } else {
                patientFields.style.display = "none";
                doctorFields.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Signup</h2>
        <form method="POST" action="">
            <!-- Common Fields -->
            <div class="form-group">
                <label for="user_type">User Type</label>
                <select class="form-control" id="user_type" name="user_type" onchange="showFields()" required>
                    <option value="">Select User Type</option>
                    <option value="patient">Patient</option>
                    <option value="doctor">Doctor</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" class="form-control" id="age" name="age" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <!-- Patient-Specific Fields -->
            <div id="patient_fields" style="display: none;">
                <div class="form-group">
                    <label for="height">Height (cm)</label>
                    <input type="number" class="form-control" id="height" name="height">
                </div>
                <div class="form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" class="form-control" id="weight" name="weight">
                </div>
            </div>

            <!-- Doctor-Specific Fields -->
            <div id="doctor_fields" style="display: none;">
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <input type="text" class="form-control" id="specialization" name="specialization">
                </div>
                <div class="form-group">
                    <label for="qualification">Qualification</label>
                    <input type="text" class="form-control" id="qualification" name="qualification">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Signup</button>
        </form>
        <div class="text-center">
            <small>Already have an account? <a href="login.php">Login here</a></small>
        </div>
    </div>
</body>
</html>