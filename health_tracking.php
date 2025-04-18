<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle POST request to store sensor data from ESP32 (auto-track)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['auto_track'])) {
    $temperature = $_POST['temperature'];
    $ecg_data = $_POST['ecg_data'];
    $spo2_level = $_POST['spo2_level'];
    $heart_rate = $_POST['heart_rate'];

    $check_sql = "SELECT id FROM health_metrics WHERE user_id = ? AND DATE(timestamp) = CURDATE() LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $sql = "UPDATE health_metrics 
                SET temperature = ?, ecg_data = ?, spo2_level = ?, heart_rate = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsddi", $temperature, $ecg_data, $spo2_level, $heart_rate, $id);
    } else {
        $sql = "INSERT INTO health_metrics (user_id, temperature, ecg_data, spo2_level, heart_rate)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsdd", $user_id, $temperature, $ecg_data, $spo2_level, $heart_rate);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manual_entry'])) {
    $temperature = $_POST['temperature'];
    $blood_pressure = $_POST['blood_pressure'];
    $stress_level = $_POST['stress_level'];
    $ecg_data = $_POST['ecg_data'];
    $glucose_level = $_POST['glucose_level'];
    $spo2_level = $_POST['spo2_level'];
    $heart_rate = $_POST['heart_rate'];

    $check_sql = "SELECT * FROM health_metrics WHERE user_id = ? AND DATE(timestamp) = CURDATE() LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $temperature = ($temperature !== '') ? $temperature : $row['temperature'];
        $blood_pressure = ($blood_pressure !== '') ? $blood_pressure : $row['blood_pressure'];
        $stress_level = ($stress_level !== '') ? $stress_level : $row['stress_level'];
        $ecg_data = ($ecg_data !== '') ? $ecg_data : $row['ecg_data'];
        $glucose_level = ($glucose_level !== '') ? $glucose_level : $row['glucose_level'];
        $spo2_level = ($spo2_level !== '') ? $spo2_level : $row['spo2_level'];
        $heart_rate = ($heart_rate !== '') ? $heart_rate : $row['heart_rate'];

        $sql = "UPDATE health_metrics 
                SET temperature = ?, blood_pressure = ?, stress_level = ?, ecg_data = ?, glucose_level = ?, spo2_level = ?, heart_rate = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsssdiii", $temperature, $blood_pressure, $stress_level, $ecg_data, $glucose_level, $spo2_level, $heart_rate, $id);
    } else {
        $sql = "INSERT INTO health_metrics (user_id, temperature, blood_pressure, stress_level, ecg_data, glucose_level, spo2_level, heart_rate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idsssdii", $user_id, $temperature, $blood_pressure, $stress_level, $ecg_data, $glucose_level, $spo2_level, $heart_rate);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Data submitted successfully!'); window.location.href = 'health_tracking.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

$data_query = $conn->query("SELECT * FROM health_metrics WHERE user_id = $user_id ORDER BY timestamp DESC LIMIT 1");
$health_data = $data_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records</title>
    <link rel="icon" type="image/png" href="mc.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/htrack.css">
</head>

<body>
    <div class="container-fluid py-4">
        <h1 class="text-center mb-4">Health Monitoring</h1>
        <div class="text-center mb-4">
            <button id="autoTrackBtn" class="btn btn-primary">Auto Track</button>
            <button id="manualEntryBtn" class="btn btn-secondary">Manual Entry</button>
        </div>

        <div id="autoTrackSection">
            <?php if ($health_data): ?>
                <div class="text-center mb-4">
                    <h2>Real-Time Health Monitoring</h2>
                </div>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-4">
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/tempreture.png" alt="Temperature Icon">
                                </div>
                                <div>
                                    <h5 class="card-title" id="temperatureValue">
                                        <?php echo htmlspecialchars($health_data['temperature'] ?? 'N/A'); ?> °C
                                    </h5>
                                    <p class="card-text">Temperature</p>
                                    <input type="number" step="0.01" class="form-control manual-input" id="temperatureInput"
                                        placeholder="Enter Temperature">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/spo2.png" alt="SpO2 Icon">
                                </div>
                                <div>
                                    <h5 class="card-title" id="spo2Value">
                                        <?php echo htmlspecialchars($health_data['spo2_level'] ?? 'N/A'); ?>%
                                    </h5>
                                    <p class="card-text">SpO2 Level</p>
                                    <input type="number" class="form-control manual-input" id="spo2Input"
                                        placeholder="Enter SpO2 Level">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/heart.png" alt="Heart Rate Icon">
                                </div>
                                <div>
                                    <h5 class="card-title" id="heartRateValue">
                                        <?php echo htmlspecialchars($health_data['heart_rate'] ?? 'N/A'); ?> BPM
                                    </h5>
                                    <p class="card-text">Heart Rate</p>
                                    <input type="number" class="form-control manual-input" id="heartRateInput"
                                        placeholder="Enter Heart Rate">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12 col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon">
                                        <img src="assets/icons/ecg.png" alt="ECG Icon">
                                    </div>
                                    <h4 class="card-title mb-0">ECG Data</h4>
                                </div>
                                <canvas id="ecgChart" style="width: 100%; height: 200px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    No health data available for this user.
                </div>
            <?php endif; ?>
        </div>

        <div id="manualEntrySection" style="display: none;">
            <form method="POST" action="">
                <input type="hidden" name="manual_entry" value="1">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/tempreture.png" alt="Temperature Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">Temperature (°C)</h5>
                                    <input type="number" step="0.01" class="form-control" name="temperature"
                                        placeholder="Enter Temperature">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/bloodpresure.png" alt="Blood Pressure Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">Blood Pressure (mmHg)</h5>
                                    <input type="text" class="form-control" name="blood_pressure"
                                        placeholder="Enter Blood Pressure">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/spo2.png" alt="SpO2 Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">SpO2 Level (%)</h5>
                                    <input type="number" class="form-control" name="spo2_level"
                                        placeholder="Enter SpO2 Level">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/stress.png" alt="Stress Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">Stress Level</h5>
                                    <select class="form-control" name="stress_level">
                                        <option value="">Select Stress Level</option>
                                        <option value="Low">Low</option>
                                        <option value="Medium">Medium</option>
                                        <option value="High">High</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/glucose.png" alt="Glucose Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">Glucose Level (mg/dL)</h5>
                                    <input type="number" step="0.01" class="form-control" name="glucose_level"
                                        placeholder="Enter Glucose Level">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/ecg.png" alt="ECG Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">ECG Data</h5>
                                    <input type="text" class="form-control" name="ecg_data"
                                        placeholder="Enter ECG Data">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon">
                                    <img src="assets/icons/heart.png" alt="Heart Rate Icon">
                                </div>
                                <div>
                                    <h5 class="card-title">Heart Rate (BPM)</h5>
                                    <input type="number" class="form-control" name="heart_rate"
                                        placeholder="Enter Heart Rate">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

        <div id="connectionError" class="connection-error" style="display: none;">
            Failed to connect to ESP32. Please check the connection.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle between Auto-Track and Manual Entry
        document.getElementById('autoTrackBtn').addEventListener('click', function () {
            document.getElementById('autoTrackSection').style.display = 'block';
            document.getElementById('manualEntrySection').style.display = 'none';
            this.classList.add('btn-primary');
            this.classList.remove('btn-secondary');
            document.getElementById('manualEntryBtn').classList.remove('btn-primary');
            document.getElementById('manualEntryBtn').classList.add('btn-secondary');
        });
        
        document.getElementById('manualEntryBtn').addEventListener('click', function () {
            document.getElementById('autoTrackSection').style.display = 'none';
            document.getElementById('manualEntrySection').style.display = 'block';
            this.classList.add('btn-primary');
            this.classList.remove('btn-secondary');
            document.getElementById('autoTrackBtn').classList.remove('btn-primary');
            document.getElementById('autoTrackBtn').classList.add('btn-secondary');
        });

        // ECG Data Chart
        var ctx = document.getElementById('ecgChart').getContext('2d');
        var ecgData = <?php echo isset($health_data['ecg_data']) ? json_encode(explode(',', $health_data['ecg_data'])) : json_encode([]); ?>;
        var ecgChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Array.from({ length: ecgData.length }, (_, i) => i),
                datasets: [{
                    label: 'ECG Data',
                    data: ecgData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { title: { display: true, text: 'Time' } },
                    y: { title: { display: true, text: 'Amplitude' } }
                }
            }
        });

        function fetchData() {
            fetch('fetch_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('heartRateValue').textContent = data.heart_rate + ' BPM';
                        document.getElementById('temperatureValue').textContent = data.temperature + ' °C';
                        document.getElementById('spo2Value').textContent = data.spo2_level + '%';

                        if (data.ecg_data) {
                            ecgChart.data.datasets[0].data = data.ecg_data.split(',').map(Number);
                            ecgChart.update();
                        }
                        
                        document.getElementById('connectionError').style.display = 'none';

                        var formData = new FormData();
                        formData.append('auto_track', '1');
                        formData.append('temperature', data.temperature);
                        formData.append('ecg_data', data.ecg_data);
                        formData.append('spo2_level', data.spo2_level);
                        formData.append('heart_rate', data.heart_rate);

                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(result => {
                            console.log("Auto-track data stored:", result);
                        })
                        .catch(error => {
                            console.error("Error storing auto-track data:", error);
                        });
                    } else {
                        document.getElementById('connectionError').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.getElementById('connectionError').style.display = 'block';
                });
        }

        setInterval(fetchData, 1000);
    </script>
</body>
</html>