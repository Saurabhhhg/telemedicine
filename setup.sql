

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,              -- Unique user ID
    user_type ENUM('patient', 'doctor') NOT NULL,   -- User type (either 'patient' or 'doctor')
    username VARCHAR(255) NOT NULL UNIQUE,          -- Username (unique for each user)
    name VARCHAR(255) NOT NULL,                     -- Full name of the user
    email VARCHAR(255),                             -- Email address of the user
    age INT NOT NULL,                               -- Age of the user
    gender ENUM('male', 'female', 'other') NOT NULL,-- Gender of the user
    password VARCHAR(255) NOT NULL,                 -- Hashed password
    height INT,                                     -- Height for patients (nullable)
    weight INT,                                     -- Weight for patients (nullable)
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') DEFAULT NULL, -- Blood group of the user
    contact VARCHAR(20),                            -- Contact number
    address TEXT,                                   -- Address of the user
    profile_pic VARCHAR(255) DEFAULT 'assets/avatar.png', -- Profile picture (default avatar)
    specialization VARCHAR(255),                   -- Specialization for doctors (nullable)
    qualification VARCHAR(255),                    -- Qualification for doctors (nullable)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp for when the record is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Timestamp for updates
);




CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,  -- ID of the sender (user who sends the message)
    receiver_id INT NOT NULL,  -- ID of the receiver (user who receives the message)
    message TEXT,  -- The text message sent
    photo VARCHAR(255),  -- Path to the photo (if any)
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when the message was sent
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE health_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,  -- ID of the user (patient)
    temperature DECIMAL(5,2) NOT NULL,  -- Temperature in Celsius
    blood_pressure VARCHAR(20) NOT NULL,  -- Blood pressure (systolic/diastolic)
    stress_level ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Low',  -- Stress level categories
    ecg_data TEXT NOT NULL,  -- ECG data (comma-separated values)
    glucose_level DECIMAL(5,2) NOT NULL,  -- Glucose level
    spo2_level INT NOT NULL,  -- SpO2 level (percentage)
    heart_rate INT NOT NULL,  -- Heart rate in beats per minute (BPM)
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp of the entry
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE  -- Link to the users table
);


CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    available_slot TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,              -- ID of the prescribing doctor
    patient_id INT NOT NULL,             -- ID of the patient
    appointment_id INT,                  -- Optional: Link to an appointment
    medication_name VARCHAR(255) NOT NULL, -- Name of the medication
    dosage VARCHAR(100) NOT NULL,        -- Dosage (e.g., "500mg")
    frequency VARCHAR(100) NOT NULL,     -- Frequency (e.g., "Twice a day")
    duration VARCHAR(100) NOT NULL,      -- Duration (e.g., "7 days")
    instructions TEXT,                   -- Additional instructions
    notes TEXT,                          -- Doctor's notes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Timestamp of creation
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Timestamp of updates
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);


