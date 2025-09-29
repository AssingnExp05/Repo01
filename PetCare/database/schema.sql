-- PetCare Database Schema
-- MySQL Database for Pet Adoption Platform

CREATE DATABASE IF NOT EXISTS petcare_db;
USE petcare_db;

-- Users table (admin, shelter, adopter)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'shelter', 'adopter') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shelters table (additional info for shelter users)
CREATE TABLE shelters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shelter_name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50),
    description TEXT,
    website VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pets table
CREATE TABLE pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shelter_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    species ENUM('dog', 'cat', 'bird', 'rabbit', 'other') NOT NULL,
    breed VARCHAR(50),
    age INT,
    gender ENUM('male', 'female') NOT NULL,
    size ENUM('small', 'medium', 'large') NOT NULL,
    color VARCHAR(50),
    description TEXT,
    health_status VARCHAR(100),
    vaccination_status ENUM('up_to_date', 'partial', 'not_vaccinated') DEFAULT 'not_vaccinated',
    adoption_fee DECIMAL(10,2),
    status ENUM('available', 'adopted', 'pending', 'not_available') DEFAULT 'available',
    photo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shelter_id) REFERENCES shelters(id) ON DELETE CASCADE
);

-- Adoption requests table
CREATE TABLE adoption_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    adopter_id INT NOT NULL,
    shelter_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    notes TEXT,
    admin_notes TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (adopter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shelter_id) REFERENCES shelters(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Vaccinations table
CREATE TABLE vaccinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    vaccine_name VARCHAR(100) NOT NULL,
    vaccination_date DATE NOT NULL,
    next_due_date DATE,
    veterinarian VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
);

-- Care guides table
CREATE TABLE care_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    species ENUM('dog', 'cat', 'bird', 'rabbit', 'other') NOT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, email, password, user_type, first_name, last_name) 
VALUES ('admin', 'admin@petcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User');

-- Insert sample shelter
INSERT INTO users (username, email, password, user_type, first_name, last_name, phone, address) 
VALUES ('shelter1', 'shelter@petcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'shelter', 'Happy', 'Shelter', '555-0123', '123 Pet Street, City, State 12345');

INSERT INTO shelters (user_id, shelter_name, license_number, description) 
VALUES (2, 'Happy Pet Shelter', 'SHELTER001', 'A loving home for abandoned pets');

-- Insert sample adopter
INSERT INTO users (username, email, password, user_type, first_name, last_name, phone, address) 
VALUES ('adopter1', 'adopter@petcare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'adopter', 'John', 'Doe', '555-0456', '456 Adopter Lane, City, State 12345');

-- Insert sample pets
INSERT INTO pets (shelter_id, name, species, breed, age, gender, size, color, description, health_status, vaccination_status, adoption_fee, status) 
VALUES 
(1, 'Buddy', 'dog', 'Golden Retriever', 3, 'male', 'large', 'Golden', 'Friendly and energetic dog who loves to play fetch', 'Healthy', 'up_to_date', 150.00, 'available'),
(1, 'Whiskers', 'cat', 'Persian', 2, 'female', 'small', 'White', 'Calm and affectionate cat, perfect for families', 'Healthy', 'up_to_date', 100.00, 'available'),
(1, 'Charlie', 'dog', 'Beagle', 1, 'male', 'medium', 'Brown and White', 'Young and playful puppy, needs training', 'Healthy', 'partial', 200.00, 'available');

-- Insert sample vaccinations
INSERT INTO vaccinations (pet_id, vaccine_name, vaccination_date, next_due_date, veterinarian) 
VALUES 
(1, 'Rabies', '2024-01-15', '2025-01-15', 'Dr. Smith'),
(1, 'DHPP', '2024-01-15', '2025-01-15', 'Dr. Smith'),
(2, 'Rabies', '2024-02-01', '2025-02-01', 'Dr. Johnson'),
(2, 'FVRCP', '2024-02-01', '2025-02-01', 'Dr. Johnson');

-- Insert sample care guides
INSERT INTO care_guides (species, title, content, created_by) 
VALUES 
('dog', 'Basic Dog Care', 'Dogs need daily exercise, proper nutrition, regular vet checkups, and lots of love. Make sure to provide fresh water, quality food, and a safe environment.', 1),
('cat', 'Cat Care Essentials', 'Cats are independent but still need attention. Provide clean litter boxes, scratching posts, toys, and regular veterinary care.', 1);