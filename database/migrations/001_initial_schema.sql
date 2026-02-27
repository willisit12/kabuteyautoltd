-- 001_initial_schema.sql
-- Initial setup for normalized taxonomy and advanced specifications

-- 1. Taxonomy Tables
CREATE TABLE IF NOT EXISTS makes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    logo_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS body_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Advanced Specifications (EAV Pattern)
CREATE TABLE IF NOT EXISTS car_spec_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    order_index INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS car_spec_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT NULL,
    order_index INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES car_spec_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS car_spec_values (
    car_id INT NOT NULL,
    spec_def_id INT NOT NULL,
    value TEXT NOT NULL,
    PRIMARY KEY (car_id, spec_def_id),
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (spec_def_id) REFERENCES car_spec_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Customer Engagement
CREATE TABLE IF NOT EXISTS favorites (
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, car_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    status ENUM('PENDING', 'PAID', 'SHIPPED', 'COMPLETED', 'CANCELLED') DEFAULT 'PENDING',
    amount DECIMAL(15, 2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (car_id) REFERENCES cars(id)
) ENGINE=InnoDB;

-- 4. Schema Refinement (Adding new columns to existing tables)
-- Adding columns as NULLABLE first to allow data migration
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL,
MODIFY COLUMN role ENUM('admin', 'user', 'customer') DEFAULT 'customer';

ALTER TABLE cars 
ADD COLUMN IF NOT EXISTS make_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS body_type_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS engine_capacity VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS drive_train VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS seats INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS doors INT DEFAULT NULL,
ADD CONSTRAINT fk_car_make FOREIGN KEY (make_id) REFERENCES makes(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_car_body_type FOREIGN KEY (body_type_id) REFERENCES body_types(id) ON DELETE SET NULL;
