<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Set your Firebase domain (without the protocol or path)
$firebaseHost = "esp32-8d27d-default-rtdb.firebaseio.com";
// Build the complete URL with HTTPS and the .json extension
$url = "https://" . $firebaseHost . "/esp32.json";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);          // Overall timeout 5 seconds
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);     // Connection timeout 5 seconds

// Disable SSL certificate verification for testing (remove in production)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['status' => 'error', 'message' => curl_error($ch)]);
    exit;
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    echo json_encode(['status' => 'error', 'message' => "Failed to fetch data, HTTP code $http_code"]);
    exit;
}

// Decode the JSON returned by Firebase
$espData = json_decode($response, true);
if ($espData === null) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON from endpoint']);
    exit;
}

// Debug: Uncomment the next line to see the raw data in your response
// error_log(print_r($espData, true));

// Transform keys to match the JavaScript expectations
$result = [
    'status'      => 'success',
    'heart_rate'  => isset($espData['heart_rate']) ? $espData['heart_rate'] : null,
    'spo2_level'  => isset($espData['spo2_level']) ? $espData['spo2_level'] : null,
    'temperature' => isset($espData['tempreture']) ? $espData['tempreture'] : null,
    'ecg_data'    => isset($espData['ecg_data']) && is_array($espData['ecg_data']) 
                        ? implode(',', $espData['ecg_data']) 
                        : (isset($espData['ecg_data']) ? $espData['ecg_data'] : "")
];

echo json_encode($result);
?>
