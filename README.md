# 💊 Telemedicine - Remote Health Monitoring System

**Demo Website** 👉 [medconnect.42web.io](http://medconnect.42web.io)

Telemedicine is an IoT-based web application that allows doctors and patients to interact remotely and monitor real-time health data. The system uses ESP32 with medical sensors to capture patient vitals and uploads them to Firebase. The PHP-based web app fetches this data, stores it in MySQL, and presents it in a user-friendly interface.

## 🩺 Key Features

- 📡 **ESP32 + Sensors Integration**  
  Collects real-time health data using sensors like:
  - MAX30100 – Heart rate & SpO2
  - MLX90614 – Temperature
  - AD8232 – ECG

- ☁️ **Firebase Integration**  
  Sensor data is sent to Firebase Realtime Database.

- 🖥️ **Web Dashboard (PHP + MySQL)**  
  - Displays vital health stats
  - Shows ECG graph using Chart.js
  - Stores data securely in MySQL

- 💬 **Chat System (Doctor ↔️ Patient)**  
  - Real-time chat with photo sharing
  - Patient can only see doctors & vice versa

- 📝 **Prescriptions & Appointments**
  - Doctor can write prescriptions
  - Patients can view prescriptions and book appointments

- 🤖 **AI Chatbot (Gemini API)**  
  - Answers basic health queries

## 📦 Tech Stack

- **Frontend**: HTML, CSS, Bootstrap, AJAX, JavaScript  
- **Backend**: PHP, MySQL  
- **IoT**: ESP32, MAX30100, MLX90614, AD8232  
- **Database**: Firebase (for sensor input), MySQL (for storage)  
- **Others**: Chart.js, Gemini API, Soft UI Dashboard

## 🛠️ Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/Saurabhhhg/telemedicine.git
