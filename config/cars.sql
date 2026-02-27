-- Williams Auto Seed Data
-- Synced with config/database.sql schema

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks for clean insert
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `car_images`;
TRUNCATE TABLE `cars`;

-- Dumping data for table `cars`
-- Columns: id, slug, make, model, year, price, mileage, vin, color, fuel_type, transmission, body_type, condition, description, features, featured, walkaround_video_url, view_count, average_rating, review_count, location, status, sold_at, created_at, updated_at

INSERT INTO `cars` (`id`, `slug`, `make`, `model`, `year`, `price`, `mileage`, `vin`, `color`, `fuel_type`, `transmission`, `body_type`, `condition`, `description`, `features`, `featured`, `view_count`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, '2023-tesla-model-3', 'Tesla', 'Model 3', 2023, 42990.00, 8500, 'VIN-T3-001', 'Pearl White', 'ELECTRIC', 'AUTOMATIC', 'SEDAN', 'EXCELLENT', 'Like-new Tesla Model 3 with full self-driving capability. Autopilot, premium interior, and all the latest features. One owner, meticulously maintained.', NULL, 1, 11, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 16:34:50'),
(2, '2022-bmw-3-series', 'BMW', '3 Series', 2022, 38500.00, 15200, 'VIN-B3-002', 'Jet Black', 'GASOLINE', 'AUTOMATIC', 'SEDAN', 'EXCELLENT', 'Luxury sports sedan with premium package. Navigation, leather seats, sunroof, and advanced safety features. Certified pre-owned with warranty.', NULL, 1, 10, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 16:20:21'),
(3, '2021-toyota-camry', 'Toyota', 'Camry', 2021, 26750.00, 22000, 'VIN-TC-003', 'Silver Sky', 'HYBRID', 'AUTOMATIC', 'SEDAN', 'EXCELLENT', 'Fuel-efficient hybrid with excellent reliability. Clean CarFax, single owner. Perfect commuter car with modern tech and safety features.', NULL, 1, 8, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 16:21:24'),
(4, '2023-honda-civic', 'Honda', 'Civic', 2023, 24990.00, 5000, 'VIN-HC-004', 'Sonic Gray', 'GASOLINE', 'CVT', 'SEDAN', 'EXCELLENT', 'Sporty and efficient. Brand new condition with warranty remaining. Apple CarPlay, Android Auto, adaptive cruise control, and lane keeping assist.', NULL, 0, 1, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 07:07:51'),
(5, '2022-ford-f-150', 'Ford', 'F-150', 2022, 45900.00, 18000, 'VIN-FF-005', 'Oxford White', 'GASOLINE', 'AUTOMATIC', 'TRUCK', 'EXCELLENT', 'Powerful workhorse with XLT package. Towing package, backup camera, and premium sound system. Perfect for work or weekend adventures.', NULL, 0, 1, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 16:34:30'),
(6, '2021-mercedes-e-class', 'Mercedes-Benz', 'E-Class', 2021, 52900.00, 12000, 'VIN-ME-006', 'Obsidian Black', 'GASOLINE', 'AUTOMATIC', 'SEDAN', 'EXCELLENT', 'Executive sedan with every luxury option. Premium leather, Burmester sound system, advanced driver assistance, and pristine condition.', NULL, 1, 2, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 16:19:47'),
(7, '2022-mazda-cx-5', 'Mazda', 'CX-5', 2022, 31500.00, 14000, 'VIN-MC-007', 'Deep Crystal Blue', 'GASOLINE', 'AUTOMATIC', 'SUV', 'EXCELLENT', 'Stylish compact SUV with Grand Touring package. All-wheel drive, premium interior, and excellent fuel economy. Perfect family vehicle.', NULL, 0, 0, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2025-12-21 06:25:16'),
(8, '2023-audi-a4', 'Audi', 'A4', 2023, 44990.00, 6000, 'VIN-AA-008', 'Glacier White', 'GASOLINE', 'AUTOMATIC', 'SEDAN', 'EXCELLENT', 'Premium luxury sedan with Quattro all-wheel drive. Virtual cockpit, premium plus package, and immaculate condition with full warranty.', NULL, 0, 1, 'Toronto', 'AVAILABLE', '2025-12-21 06:25:16', '2026-01-21 19:33:46'),
(10, '2024-geely-binyue-l', 'Geely', 'Binyue L', 2024, 13485.00, 3000, 'VIN-GB-010', 'White', 'ELECTRIC', 'AUTOMATIC', 'SUV', 'EXCELLENT', '🚗 Geely Binyue L\r\n* Registration Date: August 2024\r\n* Mileage: 3,000 kilometers\r\n* Engine: 1.5T\r\n* Transmission: Automatic, likely a 7-speed DCT or CVT\r\n* Exterior/Interior Color: White exterior / Black interior\r\n* Seating Capacity: 5 seats', NULL, 1, 15, 'Toronto', 'AVAILABLE', '2025-12-21 08:39:32', '2025-12-29 07:16:55'),
(11, '2024-geely-binyue-l-v2', 'Geely Binyue L', 'L', 2024, 13485.00, 3000, 'VIN-GB-011', 'White', 'GASOLINE', 'AUTOMATIC', 'SUV', 'EXCELLENT', 'New Car', NULL, 1, 1, 'Toronto', 'AVAILABLE', '2026-01-21 19:32:25', '2026-01-21 19:34:01'),
(12, '2024-geely-binyue-l-v3', 'Geely Binyue L', 'L', 2024, 13485.00, 3000, 'VIN-GB-012', 'White', 'GASOLINE', 'AUTOMATIC', 'SUV', 'EXCELLENT', 'New', NULL, 1, 2, 'Toronto', 'AVAILABLE', '2026-01-21 20:46:09', '2026-01-21 20:03:16');


-- Dumping data for table `car_images`
-- Columns: id, car_id, url, order, type

INSERT INTO `car_images` (`id`, `car_id`, `url`, `order`, `type`) VALUES
(8, 4, 'uploads/honda-civic-1.jpg', 1, 'PHOTO'),
(9, 5, 'uploads/ford-f150-1.jpg', 1, 'PHOTO'),
(10, 6, 'uploads/mercedes-eclass-1.jpg', 1, 'PHOTO'),
(11, 7, 'uploads/mazda-cx5-1.jpg', 1, 'PHOTO'),
(12, 8, 'uploads/audi-a4-1.jpg', 1, 'PHOTO'),
(13, 10, 'uploads/car_10_6947a434d8035.jpeg', 0, 'PHOTO'),
(14, 10, 'uploads/car_10_6947a434f0cee.jpeg', 1, 'PHOTO'),
(15, 10, 'uploads/car_10_6947a43511d15.jpeg', 2, 'PHOTO'),
(16, 10, 'uploads/car_10_6947a4352a03d.jpeg', 3, 'PHOTO'),
(17, 1, 'uploads/car_1_6947b7fb5cc8b.jpg', 3, 'PHOTO'),
(18, 1, 'uploads/car_1_6947b7fb8eb0b.jpg', 4, 'PHOTO'),
(19, 1, 'uploads/car_1_6947b7fb9ff64.webp', 5, 'PHOTO'),
(20, 2, 'uploads/car_2_6947b90bae7ed.jpg', 2, 'PHOTO'),
(21, 2, 'uploads/car_2_6947b90bc94bb.jpg', 3, 'PHOTO'),
(22, 2, 'uploads/car_2_6947b90be6aff.jpg', 4, 'PHOTO'),
(23, 3, 'uploads/car_3_6947ba4f6a892.webp', 2, 'PHOTO'),
(24, 3, 'uploads/car_3_6947ba4f823ef.jpg', 3, 'PHOTO'),
(25, 11, 'uploads/car_11_69711bb90cad5.jpeg', 0, 'PHOTO'),
(26, 11, 'uploads/car_11_69711bb91fc1b.jpeg', 1, 'PHOTO'),
(27, 11, 'uploads/car_11_69711bb92e46d.jpeg', 2, 'PHOTO'),
(28, 11, 'uploads/car_11_69711bb93dde9.jpeg', 3, 'PHOTO'),
(29, 11, 'uploads/car_11_69711bb94e916.jpeg', 4, 'PHOTO'),
(30, 11, 'uploads/car_11_69711bb95e91a.jpeg', 5, 'PHOTO'),
(31, 11, 'uploads/car_11_69711bb96ea77.jpeg', 6, 'PHOTO'),
(32, 11, 'uploads/car_11_69711bb986af4.jpeg', 7, 'PHOTO'),
(33, 12, 'uploads/car_12_69712d01e2413.jpeg', 0, 'PHOTO'),
(34, 12, 'uploads/car_12_69712d0216ed1.jpeg', 1, 'PHOTO'),
(35, 12, 'uploads/car_12_69712d0236320.jpeg', 2, 'PHOTO'),
(36, 12, 'uploads/car_12_69712d02572ce.jpeg', 3, 'PHOTO');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
