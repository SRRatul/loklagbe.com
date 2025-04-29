-- Create database
CREATE DATABASE IF NOT EXISTS loklagbe;
USE loklagbe;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  profile_image VARCHAR(255) DEFAULT NULL,
  role ENUM('user', 'admin', 'provider') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User addresses table
CREATE TABLE IF NOT EXISTS user_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  street VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  country VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  is_default BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service categories table
CREATE TABLE IF NOT EXISTS service_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  icon VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10, 2),
  image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE SET NULL
);

-- Service providers table
CREATE TABLE IF NOT EXISTS service_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  bio TEXT,
  experience INT,
  rating DECIMAL(3, 2) DEFAULT 0,
  is_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Provider services table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS provider_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  service_id INT NOT NULL,
  price DECIMAL(10, 2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider_id INT NOT NULL,
  service_id INT NOT NULL,
  booking_date DATE NOT NULL,
  booking_time TIME NOT NULL,
  status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  price DECIMAL(10, 2) NOT NULL,
  address TEXT NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  user_id INT NOT NULL,
  provider_id INT NOT NULL,
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE
);

-- Insert sample data for service categories
INSERT INTO service_categories (name, description, icon) VALUES
('Home Repair', 'Professional repair services for all your household needs', 'fa-tools'),
('Cleaning', 'Expert cleaning services for homes and offices', 'fa-broom'),
('Electrical', 'Certified electricians for all electrical work', 'fa-bolt'),
('Plumbing', 'Reliable plumbing services for any water-related issues', 'fa-tint'),
('Painting', 'Professional painting services for interior and exterior', 'fa-paint-roller'),
('Transport', 'Reliable transportation services for goods and people', 'fa-car');

-- Insert sample services
INSERT INTO services (category_id, name, description, price) VALUES
(1, 'Furniture Repair', 'Fix broken furniture and restore its functionality', 500.00),
(1, 'Door Repair', 'Fix door hinges, handles, and locks', 300.00),
(2, 'Home Deep Cleaning', 'Thorough cleaning of your entire home', 2000.00),
(2, 'Office Cleaning', 'Professional cleaning services for offices', 3000.00),
(3, 'Electrical Wiring', 'Installation and repair of electrical wiring', 800.00),
(3, 'Fan Installation', 'Installation of ceiling and wall fans', 400.00),
(4, 'Pipe Leakage Repair', 'Fix leaking pipes and prevent water damage', 500.00),
(4, 'Toilet Repair', 'Fix toilet issues and replace parts if needed', 600.00),
(5, 'Interior Painting', 'Professional painting for interior walls', 1500.00),
(5, 'Exterior Painting', 'Weather-resistant painting for exterior walls', 2000.00),
(6, 'Furniture Moving', 'Safe transportation of furniture within the city', 1000.00),
(6, 'Home Shifting', 'Complete home relocation services', 5000.00);

-- Insert admin user
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin User', 'admin@loklagbe.com', '01700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample service providers
INSERT INTO service_providers (user_id, bio, experience, rating, is_verified) VALUES
(1, 'Experienced service provider with expertise in multiple areas', 5, 4.8, TRUE);

-- Insert provider services
INSERT INTO provider_services (provider_id, service_id, price) VALUES
(1, 1, 500.00),
(1, 2, 300.00),
(1, 3, 2000.00),
(1, 4, 3000.00),
(1, 5, 800.00),
(1, 6, 400.00),
(1, 7, 500.00),
(1, 8, 600.00),
(1, 9, 1500.00),
(1, 10, 2000.00),
(1, 11, 1000.00),
(1, 12, 5000.00);

-- Add function to check if user is admin
DELIMITER //
CREATE FUNCTION IF NOT EXISTS is_admin(user_id INT) RETURNS BOOLEAN
BEGIN
    DECLARE user_role VARCHAR(20);
    SELECT role INTO user_role FROM users WHERE id = user_id;
    RETURN user_role = 'admin';
END //
DELIMITER ;

