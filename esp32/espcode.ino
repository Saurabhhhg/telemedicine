#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <MAX30105.h>
#include <Adafruit_MLX90614.h>

// WiFi credentials
const char* ssid = "TP-Link_2762";
const char* password = "99667672";

// Firebase details: Use only the domain here (without "https://")
const char* firebaseHost = "esp32-8d27d-default-rtdb.firebaseio.com";

// Sensor objects
MAX30105 particleSensor;
Adafruit_MLX90614 mlx = Adafruit_MLX90614();

// ECG and sensor buffer variables
#define ECG_PIN 34
#define ECG_BUFFER_SIZE 30  
int ecgBuffer[ECG_BUFFER_SIZE];
int ecgIndex = 0;
bool ecgBufferFull = false;
unsigned long lastEcgTime = 0;
const unsigned long ECG_SAMPLE_INTERVAL = 100; // Sample every 100ms

// MAX30105 variables for heart rate and SpO2 calculation
const uint32_t IR_THRESHOLD = 50000;
unsigned long lastPeakTime = 0;
float heartRate = 0;
float spo2Level = 0;
bool wasAboveThreshold = false;

// Use onboard LED for WiFi indication
#ifndef LED_BUILTIN
#define LED_BUILTIN 2
#endif

unsigned long previousBlinkTime = 0;
const unsigned long blinkInterval = 500;  // 500ms blink interval

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  // Initialize onboard LED for indication (onboard blue LED)
  pinMode(LED_BUILTIN, OUTPUT);
  digitalWrite(LED_BUILTIN, LOW);
  
  // Initialize I2C (ESP32 default pins: SDA=21, SCL=22)
  Wire.begin(21, 22);
  
  // Initialize MAX30105 sensor
  Serial.println("Initializing MAX30105 sensor...");
  if (!particleSensor.begin(Wire, I2C_SPEED_STANDARD)) {
    Serial.println("Failed to initialize MAX30105 sensor. Check wiring & sensor type.");
    while (1);
  }
  particleSensor.setup(0x1F, 4, 2, 400, 411, 4096);
  Serial.println("MAX30105 sensor initialized successfully");
  
  // Initialize MLX90614 sensor
  if (!mlx.begin()) {
    Serial.println("Error connecting to MLX90614 sensor");
    while (1);
  }
  Serial.println("MLX90614 sensor initialized successfully");
  
  // Connect to WiFi
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  while(WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected to WiFi");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  unsigned long currentMillis = millis();
  
  // Blink onboard blue LED if WiFi is connected
  if(WiFi.status() == WL_CONNECTED) {
    if (currentMillis - previousBlinkTime >= blinkInterval) {
      previousBlinkTime = currentMillis;
      digitalWrite(LED_BUILTIN, !digitalRead(LED_BUILTIN));
    }
  } else {
    digitalWrite(LED_BUILTIN, LOW);
  }
  
  // Update sensor values from MAX30105
  uint32_t redValue = particleSensor.getRed();
  uint32_t irValue = particleSensor.getIR();
  unsigned long currentTime = millis();
  
  // Simple peak detection for heart rate estimation
  if (irValue > IR_THRESHOLD) {
    if (!wasAboveThreshold && (currentTime - lastPeakTime > 250)) {
      unsigned long peakInterval = currentTime - lastPeakTime;
      lastPeakTime = currentTime;
      heartRate = 60000.0 / peakInterval;
    }
    wasAboveThreshold = true;
  } else {
    wasAboveThreshold = false;
  }
  
  // Estimate SpO2 using a rough red/IR ratio
  if (irValue > 0) {
    float ratio = (float)redValue / (float)irValue;
    spo2Level = 110.0 - 25.0 * ratio;
  }
  
  // Sample the ECG sensor and store readings in a circular buffer
  if (currentTime - lastEcgTime >= ECG_SAMPLE_INTERVAL) {
    int ecgReading = analogRead(ECG_PIN);
    ecgBuffer[ecgIndex] = ecgReading;
    ecgIndex++;
    if (ecgIndex >= ECG_BUFFER_SIZE) {
      ecgIndex = 0;
      ecgBufferFull = true;
    }
    lastEcgTime = currentTime;
  }
  
  // Push sensor data to Firebase every 5 seconds
  static unsigned long lastPush = 0;
  if (currentTime - lastPush > 5000) {
    pushDataToFirebase();
    lastPush = currentTime;
  }
}

void pushDataToFirebase() {
  if(WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    // Construct the URL using the firebaseHost domain
    String url = String("https://") + firebaseHost + "/esp32.json";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    
    // Prepare JSON payload
    StaticJsonDocument<300> doc;
    doc["tempreture"] = mlx.readObjectTempC();
    int count = ecgBufferFull ? ECG_BUFFER_SIZE : ecgIndex;
    JsonArray ecgArray = doc.createNestedArray("ecg_data");
    for (int i = 0; i < count; i++) {
      ecgArray.add(ecgBuffer[i]);
    }
    doc["spo2_level"] = spo2Level;
    doc["heart_rate"] = heartRate;
    
    String payload;
    serializeJson(doc, payload);
    
    int httpResponseCode = http.PUT(payload);
    Serial.print("Firebase response code: ");
    Serial.println(httpResponseCode);
    Serial.print("Payload: ");
    Serial.println(payload);
    
    http.end();
  } else {
    Serial.println("WiFi not connected!");
  }
}
