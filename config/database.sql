-- Database: williams_auto
CREATE DATABASE IF NOT EXISTS williams_auto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE williams_auto;

-- Users (Admin only)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    role ENUM('admin') DEFAULT 'admin',
    avatar_url VARCHAR(255),
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Cars
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year YEAR NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    mileage INT NOT NULL,
    vin VARCHAR(17) UNIQUE,
    color VARCHAR(50),
    fuel_type ENUM('GASOLINE','DIESEL','ELECTRIC','HYBRID','PLUGIN_HYBRID'),
    transmission ENUM('AUTOMATIC','MANUAL','CVT'),
    body_type ENUM('SEDAN','SUV','TRUCK','COUPE','HATCHBACK','VAN','CONVERTIBLE','WAGON'),
    `condition` ENUM('EXCELLENT','VERY_GOOD','GOOD','FAIR'),
    description TEXT,
    features JSON,
    featured BOOLEAN DEFAULT FALSE,
    walkaround_video_url VARCHAR(255),
    view_count INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    review_count INT DEFAULT 0,
    location VARCHAR(100),
    status ENUM('AVAILABLE','RESERVED','SOLD','ARCHIVED') DEFAULT 'AVAILABLE',
    sold_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Car Images
CREATE TABLE IF NOT EXISTS car_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    `order` INT NOT NULL,
    type ENUM('PHOTO','INTERIOR','EXTERIOR') DEFAULT 'PHOTO',
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Inquiries
CREATE TABLE IF NOT EXISTS  inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    message TEXT NOT NULL,
    status ENUM('PENDING','REPLIED','ARCHIVED') DEFAULT 'PENDING',
    replied_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE SET NULL
);

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT,
    name VARCHAR(100),
    location VARCHAR(100),
    rating TINYINT NOT NULL,
    comment TEXT,
    image_url VARCHAR(255),
    approved BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE SET NULL
);

-- Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details JSON,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Site Settings (Singleton)
CREATE TABLE IF NOT EXISTS site_settings (
    id VARCHAR(20) PRIMARY KEY DEFAULT 'global',
    site_name VARCHAR(100) DEFAULT 'Williams Auto',
    tagline VARCHAR(255) DEFAULT 'Toronto’s Trusted Source for Hand-Picked Used Cars',
    hero_title VARCHAR(255),
    hero_subtitle VARCHAR(255),
    hero_video_url VARCHAR(255),
    hero_image_url VARCHAR(255),
    total_cars_sold INT DEFAULT 0,
    years_experience INT DEFAULT 10,
    satisfaction_rate DECIMAL(5,2) DEFAULT 98.50,
    about_text TEXT,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    whatsapp_number VARCHAR(20),
    address TEXT,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO site_settings (id) VALUES ('global') ON DUPLICATE KEY UPDATE id=id;

-- Insert test inquiries
INSERT INTO inquiries (car_id, name, email, phone, message, status) VALUES
(1, 'John Smith', 'john.smith@example.com', '416-555-0198', 'I am very interested in this vehicle. Is it still available for a test drive this weekend?', 'PENDING'),
(2, 'Sarah Williams', 'sarah.w@example.com', '416-555-0122', 'Could you provide the Carfax report for this one? I am looking to purchase within the week.', 'REPLIED'),
(3, 'Michael Johnson', 'mjohnson88@example.com', '905-555-0145', 'Are you firm on the price or is there some room for negotiation? I have cash ready.', 'PENDING');

-- Insert test testimonials 
INSERT INTO testimonials (car_id, name, location, rating, comment, approved) VALUES
(1, 'David Miller', 'Toronto, ON', 5, 'Exceptional experience from start to finish. The team at Williams Auto made buying my dream car incredibly smooth and transparent. No hidden fees, just honest business.', TRUE),
(2, 'Jessica Chen', 'Markham, ON', 5, 'I was nervous about buying a used luxury vehicle, but their inspection process and warranty options put my mind at ease. The car runs perfectly.', TRUE),
(3, 'Robert Taylor', 'Mississauga, ON', 4, 'Great selection of high-end vehicles. The sales staff was knowledgeable without being pushy. Overall a very premium buying experience.', TRUE);
