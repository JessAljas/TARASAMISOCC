-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 12:48 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finalsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `agency`
--

CREATE TABLE `agency` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agency`
--

INSERT INTO `agency` (`id`, `fullname`, `email`, `password`, `profile_image`, `phone_number`, `created_at`) VALUES
(1, 'Travel Bee', 'agency01@gmail.com', '$2y$10$aW3h2DhjdtWGjGU5wyWk0etsDsKhXGWN9LbWD3l3.19xlyOax1pom', 'uploads/1723841884557.jpg', '093182861', '2025-09-14 14:39:45');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `tourists_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `pax` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `completed_booking`
--

CREATE TABLE `completed_booking` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `pax` int(11) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `mode_of_payment` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `service_fee` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `checkout_url` varchar(255) DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `dateadded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('tourist','agency','spot_owner','admin','tourism_officer') NOT NULL,
  `receiver_role` enum('tourist','agency','spot_owner','admin','tourism_officer') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('unread','read') NOT NULL DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `sender_id`, `sender_role`, `receiver_role`, `subject`, `message`, `created_at`, `status`) VALUES
(41, 4, 'tourist', 'agency', 'customer@gmail.com', 'I regret that I am unable to follow your proposed schedule on Nov. 25, 2025, as I have an important matter on that day. I kindly request that the schedule be adjusted November 30,2025. Thank you for your understanding', '2025-10-18 13:57:59', 'read'),
(42, 9, 'spot_owner', 'agency', 'jj@gmail.com', 'hiii', '2025-10-20 05:34:19', 'unread'),
(43, 9, 'spot_owner', 'agency', 'jj@gmail.com', 'hh', '2025-10-22 16:44:43', 'unread');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary`
--

CREATE TABLE `itinerary` (
  `id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `destination_name` varchar(255) DEFAULT NULL,
  `time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activity_type` enum('pickup','travel','arrival','lunch','dropoff') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itinerary`
--

INSERT INTO `itinerary` (`id`, `package_id`, `destination_name`, `time`, `created_at`, `activity_type`) VALUES
(374, 15, 'Pickup location not set', '08:00:00', '2025-10-08 01:03:16', 'arrival'),
(375, 15, 'Drop-off at Drop-off location not set', '17:00:00', '2025-10-08 01:03:16', ''),
(613, 28, 'Pick-up to Zamboanga City', '08:00:00', '2025-10-09 05:29:36', 'travel'),
(614, 28, 'Lake Duminagat', '08:30:00', '2025-10-09 05:29:36', 'travel'),
(615, 28, 'Lake Duminagat', '09:30:00', '2025-10-09 05:29:36', 'arrival'),
(616, 28, 'Piduan Falls', '09:50:00', '2025-10-09 05:29:36', 'travel'),
(617, 28, 'Piduan Falls', '10:50:00', '2025-10-09 05:29:36', 'arrival'),
(618, 28, 'Piduan Falls', '12:00:00', '2025-10-09 05:29:36', 'lunch'),
(619, 28, 'Asenso Global Gardens (AGG)', '13:20:00', '2025-10-09 05:29:36', 'travel'),
(620, 28, 'Asenso Global Gardens (AGG)', '14:20:00', '2025-10-09 05:29:36', 'arrival'),
(621, 28, 'Asenso Ozamiz Wellness Park', '14:40:00', '2025-10-09 05:29:36', 'travel'),
(622, 28, 'Asenso Ozamiz Wellness Park', '15:40:00', '2025-10-09 05:29:36', 'arrival'),
(623, 28, 'Drop-off to Zamboanga City', '18:30:00', '2025-10-09 05:29:36', 'travel'),
(635, 30, 'Pickup at Municipality of Plaridel', '08:00:00', '2025-10-09 05:55:46', 'travel'),
(636, 30, 'Travel to Bawbawon Islands', '08:15:00', '2025-10-09 05:55:46', 'travel'),
(637, 30, 'Visit Bawbawon Islands', '08:30:00', '2025-10-09 05:55:46', 'arrival'),
(638, 30, 'Travel to Highland Resort & Eco Park', '10:45:00', '2025-10-09 05:55:46', 'travel'),
(639, 30, 'Visit Highland Resort & Eco Park', '11:00:00', '2025-10-09 05:55:46', 'arrival'),
(640, 30, 'Municipality of Tudela', '12:00:00', '2025-10-09 05:55:46', 'lunch'),
(641, 30, 'Travel to St. John the Baptist Church with Divine  Mercy relic', '13:20:00', '2025-10-09 05:55:46', 'travel'),
(642, 30, 'Visit St. John the Baptist Church with Divine  Mercy relic', '13:45:00', '2025-10-09 05:55:46', 'arrival'),
(643, 30, 'Travel to Asenso Ozamiz Wellness Park', '14:30:00', '2025-10-09 05:55:46', 'travel'),
(644, 30, 'Visit Asenso Ozamiz Wellness Park', '15:20:00', '2025-10-09 05:55:46', 'arrival'),
(645, 30, 'Drop-off at Municipality of Plaridel', '17:00:00', '2025-10-09 05:55:46', 'travel');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `dropoff_location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `image4` varchar(255) DEFAULT NULL,
  `inclusion1` varchar(255) DEFAULT NULL,
  `inclusion2` varchar(255) DEFAULT NULL,
  `inclusion3` varchar(255) DEFAULT NULL,
  `inclusion4` varchar(255) DEFAULT NULL,
  `exclusion1` varchar(255) DEFAULT NULL,
  `exclusion2` varchar(255) DEFAULT NULL,
  `exclusion3` varchar(255) DEFAULT NULL,
  `exclusion4` varchar(255) DEFAULT NULL,
  `posted_by_type` enum('agency','admin') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `closed_dates` text DEFAULT NULL,
  `lunch_location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `title`, `price`, `pickup_location`, `dropoff_location`, `description`, `image1`, `image2`, `image3`, `image4`, `inclusion1`, `inclusion2`, `inclusion3`, `inclusion4`, `exclusion1`, `exclusion2`, `exclusion3`, `exclusion4`, `posted_by_type`, `created_at`, `status`, `closed_dates`, `lunch_location`) VALUES
(28, 'Package 2', 2999.00, 'Zamboanga City', 'Zamboanga City', 'Adventure is calling—are you ready to answer? Explore the wild with us!', '1759987776_1200px-Piduan_Falls,_Mt._Malindang,_Don_Victoriano,_Misamis_Occidental.jpg', '1759987776_Screenshot 2025-09-24 174908.png', '1759987776_Screenshot 2025-09-24 150519.png', '1759987776_1688083933656.webp', 'Transportation (fully aircon)', 'Tour guide', 'Entrance fee', '', 'Hotel or Accomodation', 'Meals', 'Snacks', '', 'agency', '2025-10-09 07:29:36', 'approved', '[\"2025-10-29\",\"2025-10-30\"]', NULL),
(30, 'Package 1', 3589.00, 'Municipality of Plaridel', 'Municipality of Plaridel', 'Are you looking for soul soothing beauty and cultural treasures of MisOcc and one unforgettable journey ?\r\nBook now! and let nature heal you. Let heritage inspire you. Let Misamis Occidental move you.', '1759992101_05.jpg', '1759989346_Interior_of_Jimenez_Church,_Misamis_Occidental.jpg', '1759989346_mfmnf4664.webp', '1759992403_maxresdefault.jpg', 'Transportation (fully aircon)', 'Tour guide', 'Fuel', '', 'Hotel or Accomodation', 'Meals', 'Snacks', 'Entrance Fee', 'agency', '2025-10-09 07:55:46', 'approved', '[\"2025-10-22\",\"2025-10-30\"]', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `package_destinations`
--

CREATE TABLE `package_destinations` (
  `id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `tourist_spot_id` int(10) UNSIGNED NOT NULL,
  `stop_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_destinations`
--

INSERT INTO `package_destinations` (`id`, `package_id`, `tourist_spot_id`, `stop_order`, `created_at`) VALUES
(413, 28, 1, 1, '2025-10-18 02:43:39'),
(414, 28, 83, 2, '2025-10-18 02:43:39'),
(415, 28, 2, 3, '2025-10-18 02:43:39'),
(416, 28, 79, 4, '2025-10-18 02:43:39'),
(473, 30, 83, 1, '2025-10-22 09:01:04'),
(474, 30, 128, 2, '2025-10-22 09:01:04'),
(475, 30, 80, 3, '2025-10-22 09:01:04'),
(476, 30, 82, 4, '2025-10-22 09:01:04');

-- --------------------------------------------------------

--
-- Table structure for table `package_unavailable_dates`
--

CREATE TABLE `package_unavailable_dates` (
  `id` int(11) NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_unavailable_dates`
--

INSERT INTO `package_unavailable_dates` (`id`, `package_id`, `date`) VALUES
(4, 30, '2025-10-31');

-- --------------------------------------------------------

--
-- Table structure for table `pay_via_qr`
--

CREATE TABLE `pay_via_qr` (
  `id` int(11) NOT NULL,
  `tourist_id` int(11) NOT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `booking_date` date DEFAULT NULL,
  `pax` int(11) DEFAULT 1,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `mode_of_payment` varchar(50) NOT NULL DEFAULT 'GCash QR',
  `reschedule_date` date DEFAULT NULL,
  `reschedule_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `tourist_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `tourist_id`, `package_id`, `rating`, `created_at`) VALUES
(5, 4, 28, 2, '2025-10-12 12:06:51'),
(6, 4, 30, 5, '2025-10-19 07:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `spot_owners`
--

CREATE TABLE `spot_owners` (
  `id` int(10) UNSIGNED NOT NULL,
  `name_of_tourist_spot` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spot_owners`
--

INSERT INTO `spot_owners` (`id`, `name_of_tourist_spot`, `fullname`, `email`, `password`, `profile_image`, `phone_number`, `created_at`) VALUES
(1, 'Asenso Global Gardens (AGG)', 'Elisa Fiel', 'elisa@gmail.com', '$2y$10$DKm4zHqLvN8IyaO2dHHZhuz19pfQ9atiCkpFUBr8jT3MLyogPpdUq', 'profile_1759823056.jpg', '09876543562', '2025-09-14 14:42:16'),
(6, 'Piduan Falls', 'Tourism Staff', 'staff01@gmail.com', '', 'profile_1759111986.jpg', '09102912159', '2025-09-24 18:13:16'),
(7, 'Bawbawon Island', 'Agency', 'agency01@gmail.com', '', 'profile_1760064398.jpg', '09102912150', '2025-09-24 20:28:08'),
(9, 'Birhen sa Cotta Shrine', 'Demetrio Ramos', 'dem@gmail.com', '$2y$10$HLCbAN.pf2NF8whpj2YNa.1UXyB.70iD0OrXJLBsJPr47WqdlibeG', 'profile_1760756737.jpg', '09093182860', '2025-09-25 18:59:46'),
(10, 'Green Tops', 'Sheenalen Carriaga', 'shen@gmail.com', '$2y$10$FzdEa5Z8VHH5m4Qzd.TxtuadBW61sWL7zW/G7GsW3IRNF.kVHJO0.', 'profile_1760064422.jpg', '09093182960', '2025-09-25 19:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `tourism_officers`
--

CREATE TABLE `tourism_officers` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourism_officers`
--

INSERT INTO `tourism_officers` (`id`, `fullname`, `phone`, `email`, `password`, `profile_image`, `created_at`) VALUES
(1, 'Tourism Staff', '09102912150', 'staff01@gmail.com', '$2y$10$RG2GRlq2MutBjEuRDKBgX.Vu9SnKgKrd3NBPb69v/zr12s64o.us.', 'profile_1.jpg', '2025-09-14 14:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `tourists`
--

CREATE TABLE `tourists` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourists`
--

INSERT INTO `tourists` (`id`, `fullname`, `address`, `email`, `password`, `profile_image`, `phone_number`, `status`, `created_at`) VALUES
(4, 'Jessel Ve', 'Catagan, Tangub City, Misamis Occ.', 'customer@gmail.com', '$2y$10$Sqh6MyBwyzZ5wxq6mplOpuaHVItR5eujj7DZdhChYwzrSJ6S0zs5a', 'uploads/user_4_1761129407.jpg', '09102912159', 'active', '2025-10-03 10:46:06');

-- --------------------------------------------------------

--
-- Table structure for table `tourist_spots`
--

CREATE TABLE `tourist_spots` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `entrance_fee` decimal(10,2) DEFAULT NULL,
  `activity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `posted_by_type` enum('agency','spot_owner','tourism_officers') NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `owner_name` varchar(255) NOT NULL,
  `name_of_tourist_spot` varchar(255) NOT NULL,
  `spot_created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourist_spots`
--

INSERT INTO `tourist_spots` (`id`, `owner_id`, `description`, `location`, `created_at`, `image1`, `image2`, `image3`, `entrance_fee`, `activity`, `latitude`, `longitude`, `posted_by_type`, `status`, `owner_name`, `name_of_tourist_spot`, `spot_created_at`) VALUES
(1, 1, 'TANGUB CITY HIGHLAND ESCAPE\r\nAsenso Global Gardens (AGG)\r\n\r\nLocated almost 1000 feet above sea level, within the Mount Malindang Ranges Asenso Global Gardens is fast becoming the prime tourist destination in the province of Misamis Occidental. The vast garden complex covering 191 hectares of land is likewise envisioned to be equipped with amenities for recreation and adventure. Take the challenge of completing the 490 steps from the Reception Building up to the Café Building.  AGG is going to have a variety of flowers and geometric gardens as part of its development plan. The AGG was built under the administration of Gov. Henry S. Oaminal, as part of his Asenso Turismo Misamis Occidental vision of making tourism as the driver for local economic development.  Aside from the AGG, there are several mountain resorts, private swimming pools and eco-park that can be visited in Tangub City.\r\n\r\n🏞️ Distance from Tangub City proper: Approx. 12 kilometers\r\n🛵 Travel time by single motorcycle: Around 25–30 minutes\r\n🚛 Travel time by truck or larger vehicle: Around 40–45 minutes, depending on road conditions\r\n🚐 Van service available: Fare is ₱200 per person, ideal for group tours or family outings\r\n🎉 Events You Can Host at AGG:\r\nGarden weddings and prenup shoots 🌿💍\r\nCorporate retreats and team-building activities 🧭\r\nCultural showcases and eco-fairs 🎭\r\nEducational tours and botanical workshops 🌱\r\nYoga and wellness sessions in nature 🧘‍♀️', 'Hoyohoy, Tangub City, Misamis Occidental', '2025-09-23 21:08:35', 'spot_1_0.png', 'spot_1758632915_1.jpg', 'spot_1758632915_2.webp', 200.00, '490-steps climb', 8.1611310, 123.6926690, 'spot_owner', 'verified', 'Elisa Fiel', 'Asenso Global Gardens (AGG)', '2025-09-23 21:08:35'),
(2, 6, 'Lake Duminagat is an enchanting crater lake nestled within the lush expanse of Mount Malindang Natural Park Misamis Occidental. Covering approximately 8.04 hectares, with a maximum depth of 20.95 meters and a water volume of 933,000 cubic meters, this small but mystical lake lies in the heart of the forested highlands. Surrounded by scenic valleys and mountains, it is accessible through the remote barangays of Gandawan and Duminagat, within the municipality of Don Victoriano.  To the Subanen tribe, Lake Duminagat is considered sacred, and as such, swimming is strictly prohibited. Deeply revered by the local indigenous community, the lake is believed to possess powerful healing properties.\r\n\r\n🧭 Travel Info Location: Barangays Gandawan and Duminagat, Don Victoriano, Misamis Occidental Travel Time: Approx. 1.5–2 hours by motorcycle or 4x4 from Don Victoriano town proper Trek Duration: 1–2 hours depending on trail conditions; expect forest paths and elevation changes Road Condition: Mixed paved and rough roads; best visited during dry season.\r\n\r\n💸 Entrance & Fees Environmental Fee: ₱50 per person Guide Fee (mandatory): ₱200–₱300 per group Parking Fee: Free for motorcycles and private vehicles Camping Fee (if staying overnight): ₱100 per tent Swimming: Not allowed due to cultural restrictions.\r\n\r\n🌿 Tips for Visitors Respect the sacred nature of the lake—no swimming or loud activities Wear hiking shoes and bring rain gear; weather can shift quickly Pack food and water—no stores nearby Coordinate with local tourism officers or Subanen guides before visiting Best visited early morning for misty views and peaceful ambiance', 'Mt. Malindang, Don Victoriano, Misamis Occidental', '2025-09-25 16:51:25', 'spot_1758790285_0.webp', 'spot_1758790285_1.jpg', 'spot_1758790285_2.png', 50.00, '🥾 Highland Trekking, 📸 Nature & Landscape Photography, 🧘 Spiritual Retreats & Meditation, 🪶 Cultural Immersion with Subanen guides, 🌿 Biodiversity Spotting & Eco-study, ⛺ Camping (designated areas only)', 8.2967560, 123.6109130, 'tourism_officers', 'verified', 'Tourism Staff', 'Lake Duminagat', '2025-09-25 16:51:25'),
(79, 6, 'Piduan Falls is a breathtaking 60-meter wide, 20-meter tall curtain-like cascade nestled at the foot of Mount Malindang, in Sitio Piduan, Barangay Napangan, just 20–30 minutes from Don Victoriano town proper. Surrounded by lush forest and rich biodiversity, this majestic waterfall is revered by the Subanen tribe for its healing waters and spiritual significance.\r\n\r\n🧭 Travel Info\r\nLocation: Sitio Piduan, Barangay Napangan, Don Victoriano\r\nTravel Time: Approx. 20–30 minutes by motorcycle or 4x4 from Don Victoriano centro\r\nTrek Duration: 30–45 minutes from the jump-off point (rocky terrain; wear sturdy shoes)\r\n\r\n💸 Entrance & Fees\r\nEntrance Fee: ₱100 per person\r\nGuide Fee (optional but recommended): ₱100–₱150 per group\r\nParking Fee: Free for motorcycles or private vehicles\r\nSwimming & Picnic Area: Included in entrance fee\r\nResort Access (if visiting nearby pools or cottages): Separate charges may apply\r\n\r\n🌿 Tips for Visitors\r\nWear hiking shoes or sandals with grip\r\nBring water and snacks (no stores nearby)\r\nRespect local custo', 'Barangay Lalud, Don Victoriano, Misamis Occidental.', '2025-09-25 16:57:27', 'spot_1759991669_1.jpg', 'spot_1758790647_1.jpg', 'spot_1758790647_2.jpg', 100.00, '🥾 Trekking & Nature Walks, 🏊 Swimming, 📸 Photo Shoots, 🧘Meditation & Spiritual Retreats, 🪶Cultural Immersion, 🍃Eco-study & Biodiversity Spotting', 8.2509660, 123.5658790, 'tourism_officers', 'verified', 'Tourism Staff', 'Piduan Falls', '2025-09-25 16:57:27'),
(80, 6, 'Formerly known as The Subanen Village, Tudela Highland Resort and Eco Park is a fast-emerging eco-tourism destination located in Sitio Tonggo, Barangay Namut, Tudela, Misamis Occidental. Nestled in the highlands, it features 21 Subanen culture inspired cottages, a function hall and restaurant, a refreshing cold, free-flowing swimming pool, and a soothing kawa-kawa herbal hot bath. Visitors are treated to a scenic landscape of vibrant highland flowers and ornamental plants, making it an ideal retreat for nature lovers and eco tour enthusiasts. \r\n\r\n🧭 Travel Info Location: Sitio Tonggo, Barangay Namut, Tudela, Misamis Occidental Travel Time: Approx. 10–15 minutes from Tudela town proper via motorcycle or private vehicle Road Condition: Mostly paved with uphill sections; accessible year-round.\r\n\r\n💸 Entrance & Fees Entrance Fee: ₱50 per person Cottage Rental: ₱500–₱1,000 depending on size and duration Swimming Pool Access: Included in entrance fee Kawa-Kawa Herbal Bath: ₱150 per session Function Hall/Event Use: Rates available upon inquiry.\r\n\r\n🌿 Tips for Visitors Bring light jackets—weather can be chilly in the late afternoon Pre-book cottages during weekends or holidays Try the kawa-kawa bath for a relaxing herbal soak Respect the cultural elements integrated into the resort’s design Ideal for family outings, barkada trips, and quiet retreats', 'Sitio Tonggo, Barangay Namut, Tudela, Misamis Occidental', '2025-09-25 17:03:54', 'spot_1758791034_0.png', 'spot_1758791034_1.png', 'spot_1758791034_2.webp', 50.00, '🛁 Kawa-Kawa Herbal Bath, 🏊 Swimming in Cold Spring Pool, 📸 Highland Garden Photo Shoots, 🍽️ Dining with a View, 🪶 Subanen Cultural Appreciation, 🌼 Ornamental Plant Viewing', 8.2802170, 123.6979020, 'tourism_officers', 'verified', 'Tourism Staff', 'Highland Resort & Eco Park', '2025-09-25 17:03:54'),
(81, 6, 'Just 10 minutes from Tangub City proper in Brgy. Capalaran, Beck’s Encantadia Flower Farm is a charming escape for nature lovers and photo enthusiasts, featuring vibrant blooms and serene views. Beside it lies a dragon fruit farm where visitors can explore the plantation, learn about cultivation, and even taste the refreshing fruit. Cap off your visit with a relaxing dip in the traditional Kawa Bath Spa, known for its warm, soothing waters and therapeutic benefits. \r\n\r\n🧭 Travel Info Location: Barangay Capalaran, Tangub City, Misamis Occidental Travel Time: Approx. 10 minutes from Tangub City proper via motorcycle or private vehicle Road Condition: Paved and accessible; light uphill sections.\r\n\r\n💸 Entrance & Fees Flower Farm Entrance: ₱50 per person Dragon Fruit Tour & Tasting: ₱100 per person (seasonal availability) Kawa Bath Spa: ₱150 per session Photo Shoot Packages: Available upon request Parking Fee: Free for motorcycles and private vehicles.\r\n\r\n🌿 Tips for Visitors Visit during early morning or late afternoon for best lighting and cooler weather Wear light, comfortable clothing and sun protection Dragon fruit tasting depends on harvest season—check ahead Book photo shoots or spa sessions in advance during weekends Ideal for couples, families, and barkada bonding trips', 'Brgy. Capalaran, Tangub City, Misamis Occidental.', '2025-09-25 17:21:28', 'spot_1758792088_0.webp', 'spot_1758792088_1.jpg', 'spot_1758792088_2.jpg', 50.00, '🌸 Flower Viewing & Garden Walks, 📸 Casual & Themed Photo Shoots, 🌵 Dragon Fruit Farm Tour, 🍽️ Fruit Tasting (seasonal), 🛁 Kawa Bath Spa Experience, 🧘 Relaxation & Wellness Retreats', 8.1190060, 123.7378620, 'tourism_officers', 'verified', 'Tourism Staff', 'Beck’s Encantadia Flower Farm', '2025-09-25 17:21:28'),
(82, 6, 'The Saint John the Baptist Parish Church, more commonly known as Jimenez Church, is a late 19th century Roman Catholic church known for its well-preserved Baroque architecture. Unique for its lack of a pediment, the church features a portico with three arched entrances, a parapet, and pedimented saints\' niches. Its three-tiered bell tower, complete with a working clock \r\nmechanism, adds to its charm. Inside, the church boasts some of the finest preserved interiors in Mindanao, including an 1898 ceiling painting and walls made of tabique pampango, a traditional Filipino construction technique. It has a working pipe organ, acquired in 1894, from Zaragoza, Spain. Beside the Church is the chapel devoted to the Divine Mercy, containing a first-class relic of St. Faustina Kowalska.\r\n\r\n🧭 Travel Info Location: Poblacion, Jimenez, Misamis Occidental Travel Time: Approx. 30–40 minutes from Ozamiz City via private vehicle or public transport Road Condition: Paved and accessible; town center location.\r\n\r\n💸 Entrance & Fees Church Entry: Free (open to the public during visiting hours) Guided Heritage Tour: ₱100–₱150 per group (optional; coordinate with parish office) Divine Mercy Chapel Access: Free Donations: Encouraged for church upkeep and preservation.\r\n\r\n🌿 Tips for Visitors Dress modestly—this is an active place of worship Visit during weekdays for quieter ambiance Ask permission before taking photos inside the church Coordinate with parish staff for guided tours or organ viewing Ideal for heritage enthusiasts, pilgrims, and architecture lovers', 'Barangay Nacional, Jimenez, Misamis Occidental.', '2025-09-25 18:08:24', 'spot_1758794904_0.jpg', 'spot_1758794904_1.jpg', 'spot_1760354550_3.png', 0.00, '🙏 Pilgrimage & Prayer, 📸 Heritage & Architecture Photography, 🎼 Pipe Organ Viewing (by request), 🪶 Cultural & Historical Appreciation, 🧘 Quiet Reflection & Spiritual Retreat, 🕍 Divine Mercy Relic Veneration', 8.3344730, 123.8392940, 'tourism_officers', 'verified', 'Tourism Staff', 'St. John the Baptist Church with Divine  Mercy relic', '2025-09-25 18:08:24'),
(83, 6, 'Asenso Ozamiz Wellness Park is a flagship tourism initiative originally launched by then-Mayor Ando Oaminal and completed under the leadership of Mayor Indy F. Oaminal Jr. Officially \r\nopened to the public on June 30, 2023,the park provides a vibrant and relaxing space for both Ozamiznons and visitors. Designed to support emotional, social, for dining, exercise, leisure, and shopping, including food and souvenir stalls. While further developments are ongoing, the park has already become a popular destination for families and friends, drawing regular crowds from within and beyond the province, especially in the late afternoons and evenings.  \r\n\r\n🧭 Travel Info Location: Ozamiz City, Misamis Occidental Travel Time: Approx. 5–10 minutes from Ozamiz City proper via motorcycle or private vehicle Road Condition: Fully paved and accessible; located within city limits.\r\n\r\n💸 Entrance & Fees\r\nPark Entry: Free Fitness Zone Access: Free (open to public) Food & Souvenir Stalls: Prices vary per vendor Event Space Rental: Available upon inquiry Parking Fee: Free for motorcycles and private vehicles.\r\n\r\n🌿 Tips for Visitors Visit during sunset for cooler weather and scenic views Try local snacks and delicacies from food stalls Perfect for family bonding, barkada hangouts, and solo wellness time Check Ozamiz City’s official page for updates on events and activities Respect shared spaces—keep the park clean and peaceful', 'Don Mariano Marcos Avenue, Ozamiz City, Misamis Occidental.', '2025-09-25 18:19:26', 'spot_1759993636_0.webp', 'spot_1758795566_1.webp', 'spot_1758795566_2.webp', 0.00, '🚶 Leisure Walks & Jogging, 🧘 Wellness & Fitness Sessions, 🍽️ Food Trips & Local Dining, 🛍️ Souvenir Shopping, 🎭 Community Events & Performances, 📸 Garden & Lifestyle Photography', 8.1404840, 123.8460710, 'tourism_officers', 'verified', 'Tourism Staff', 'Asenso Ozamiz Wellness Park  ', '2025-09-25 18:19:26'),
(86, 9, 'Just outside the southern wall of Cotta Fort in Ozamiz City stands the revered bas relief of the Birhen sa Cotta, facing the tranquil waters of Panguil Bay. This miraculous image of the Blessed Virgin Mary is deeply venerated by devotees, who celebrate its feast every December 18th in honor of the Immaculate Conception. Considered one of the most significant religious and pilgrimage sites in Mindanao, the shrine symbolizes protection, healing, and unwavering faith among the faithful.\r\n\r\n🧭 Travel Info Location: Cotta Fort, Ozamiz City, Misamis Occidental, Philippines 7200 Travel Time: Approx. 5 minutes from Ozamiz City proper via tricycle or private vehicle Road Condition: Paved and accessible; located near the Ozamiz Port.\r\n\r\n💸 Entrance & Fees Shrine Access: Free (open to the public daily) Cotta Fort Entry: Free (subject to local tourism guidelines) Donations: Optional; encouraged for shrine maintenance Souvenirs & Candles: Available from nearby vendors.\r\n\r\n🌿 Tips for Visitors Visit during early morning or sunset for peaceful ambiance and scenic bay views Dress modestly and respectfully this is a sacred site Bring candles or flowers if offering prayers Join the December 18th festivities for a vibrant display of faith and culture Ideal for pilgrims, heritage enthusiasts, and spiritual seekers.', 'Cotta Fort, Ozamiz City, Misamis Occidental.', '2025-09-25 19:03:04', 'spot_86_0.jpg', 'spot_1758798184_1.JPG', 'spot_1758798184_2.JPG', 0.00, '🙏 Pilgrimage & Prayer 📸 Heritage & Bayfront Photography, 🕊️ Marian Devotion & Feast Participation, 🪶 Cultural & Historical Appreciation, 🧘 Quiet Reflection by the Bay, 🎭 Religious Processions (during feast days)', 8.1399570, 123.8465250, 'spot_owner', 'verified', 'Demetrio Ramos', 'Birhen sa Cotta Shrine', '2025-09-25 19:03:04'),
(87, 10, 'Perched in the highlands of Barangay Banglay, Tangub City, Misamis Occidental, The Green Tops is a scenic viewpoint and leisure spot offering breathtaking views of the surrounding mountains and valleys. Known for its cool breeze and panoramic vistas, it’s a favorite among locals and travelers seeking a peaceful escape. Whether you\'re here for a quiet moment, a photo session, or a refreshing break from the lowlands, The Green Tops delivers a rejuvenating highland experience.\r\n\r\n🧭 Travel Info Location: Barangay Banglay, Tangub City, Misamis Occidental, Philippines 7214 Travel Time: Approx. 20–30 minutes from Tangub City proper via motorcycle or private vehicle Road Condition: Uphill with mixed paved and rough sections: best accessed during dry weather\r\n\r\n💸 Entrance & Fees Viewpoint Access: ₱50 per person Parking Fee: Free for motorcycles and private vehicles Food & Refreshments: Available from nearby vendors (seasonal) Photo Shoots: Casual photography allowed; professional shoots may require coordination.\r\n\r\n🌿 Tips for Visitors Visit during sunrise or sunset for dramatic views and cooler temperatures Bring light jackets—weather can be chilly Wear comfortable shoes for walking around the area Ideal for solo travelers, couples, and barkada trips.', 'Barangay Banglay, Tangub City, Misamis Occidental.', '2025-09-25 20:09:05', 'spot_1758802145_0.webp', 'spot_1758802145_1.webp', 'spot_1758802145_2.jpg', 50.00, '🌄 Highland Viewing & Relaxation, 📸 Landscape & Lifestyle Photography, 🧘 Meditation & Quiet Retreats, 🍃 Nature Appreciation, 🚶 Light Walks & Picnics', 8.1479570, 123.7047830, 'spot_owner', 'verified', 'Sheenalen Carriaga', 'The Green Tops', '2025-09-25 20:09:05'),
(88, 9, 'Built in 1756 by Spanish Jesuit priest Fr. Jose Ducos, Cotta Fort is one of the most iconic landmarks of Ozamiz City. Strategically located along the shores of Panguil Bay, it served as a Spanish military outpost to defend against Moro pirate attacks during the colonial period. Its lighthouse, rising above the stone walls, is the first structure visible to travelers arriving by sea. Restored in 2002, the fort remains a proud symbol of Ozamiz’s colonial heritage, with its four bulwarks San Fernando, San Jose, Santiago, and San Ignacio standing as enduring reminders of its defensive role and the survival of Christianity in Misamis Occidental. Today, it draws tourists seeking heritage, history, and panoramic bay views.\r\n\r\n🧭 Travel Info Location: Cotta Fort, Don Mariano Marcos Avenue, Ozamiz City, Misamis Occidental, Philippines 7200 Travel Time: Approx. 5 minutes from Ozamiz City proper via tricycle or private vehicle Road Condition: Paved and accessible; located near Ozamiz Port and City Hall.\r\n\r\n💸Entrance & Fees Fort Access: Free open to the public daily Lighthouse Viewing: Free (subject to local guidelines) \r\nDonations: Optional; encouraged for site preservation Souvenirs & Snacks: Available from nearby vendors.', 'Cotta Fort, Don Mariano Marcos Avenue, Ozamiz City, Misamis Occidental', '2025-09-25 20:19:36', 'spot_1758802776_0.jpg', 'spot_1758802776_1.png', 'spot_1758802776_2.jpg', 5.00, '🕍 Heritage & Historical Exploration, 📸 Bayfront & Architectural Photography, 🧘 Quiet Reflection by the Sea, 🪶 Cultural Appreciation, 🎭 Feast Day Visits & Religious Processions (linked to Birhen sa Cotta Shrine)', 8.1401170, 123.8469770, 'spot_owner', 'verified', 'Demetrio Ramos', 'Cotta Fort (El Fuerte de la Concepcion y del Triunfo)', '2025-09-25 20:19:36'),
(128, 7, 'Located off the coast of Plaridel, Misamis Occidental, Bawbawon Islands are a stunning cluster of seven large coral islands surrounded by smaller coral islets and lush mangrove forests. These islands are known for their powdery white sand beaches and crystal-clear green waters, where seagrass beds, giant clams, and sea urchins can be spotted just beneath the boat. Visitors can enjoy a tranquil boat cruise around the Bawbawon Mangrove Sanctuary or take a scenic walk along the Bawbawon Boardwalk in Barangay Panalsalan, which winds through the majestic mangrove forest—offering a peaceful, awe-inspiring nature experience. \r\n\r\n🧭 Travel Info Location: Off the coast of Barangay Panalsalan, Plaridel, Misamis Occidental Access Point: Boat ride from Plaridel Port or Barangay Panalsalan Travel Time: Approx. 15–20 minutes by boat from Plaridel town proper Road Condition: Paved roads to jump-off point; boat access required.\r\n\r\n💸 Entrance & Fees Island Access Fee: ₱100–₱150 per person (varies by operator) Boardwalk Fee: ₱50 per person Boat Rental: ₱500–₱1,000 per group (round trip; depends on boat size) Environmental Fee: ₱30 per person Guide Fee: Optional; ₱100–₱200 per group.\r\n\r\n🌿 Tips for Visitors Bring sun protection—hats, sunscreen, and light clothing Wear sandals or aqua shoes for boardwalk and boat landings Best visited during early morning or late afternoon for cooler weather Coordinate with local tourism office or boat operators for smoother access Respect marine life—avoid touching corals or disturbing wildlife.', 'Barangay Panalsalan, Plaridel, Misamis Occidental', '2025-10-12 08:55:12', 'spot_1760230512_0.jpg', 'spot_1760230512_1.webp', 'spot_1760230512_2.jpg', 50.00, '🚤 Boat Cruising around Mangrove Sanctuary, 🌿 Scenic Walks on Bawbawon Boardwalk, 📸 Nature & Seascape Photography, 🪸 Coral & Marine Life Viewing, 🧘 Meditation & Eco-Retreats, 🪶 Cultural Appreciation (with local guides)', 8.6276400, 123.7055080, 'agency', 'verified', 'Travel Bee', 'Bawbawon Island', '2025-10-12 08:55:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agency`
--
ALTER TABLE `agency`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `completed_booking`
--
ALTER TABLE `completed_booking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `itinerary`
--
ALTER TABLE `itinerary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_destinations`
--
ALTER TABLE `package_destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `tourist_spot_id` (`tourist_spot_id`);

--
-- Indexes for table `package_unavailable_dates`
--
ALTER TABLE `package_unavailable_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_package_id` (`package_id`);

--
-- Indexes for table `pay_via_qr`
--
ALTER TABLE `pay_via_qr`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tourist_id` (`tourist_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `spot_owners`
--
ALTER TABLE `spot_owners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tourism_officers`
--
ALTER TABLE `tourism_officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tourists`
--
ALTER TABLE `tourists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agency`
--
ALTER TABLE `agency`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `completed_booking`
--
ALTER TABLE `completed_booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `itinerary`
--
ALTER TABLE `itinerary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=701;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `package_destinations`
--
ALTER TABLE `package_destinations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=477;

--
-- AUTO_INCREMENT for table `package_unavailable_dates`
--
ALTER TABLE `package_unavailable_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pay_via_qr`
--
ALTER TABLE `pay_via_qr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `spot_owners`
--
ALTER TABLE `spot_owners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tourism_officers`
--
ALTER TABLE `tourism_officers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tourists`
--
ALTER TABLE `tourists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `package_destinations`
--
ALTER TABLE `package_destinations`
  ADD CONSTRAINT `package_destinations_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_destinations_ibfk_2` FOREIGN KEY (`tourist_spot_id`) REFERENCES `tourist_spots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_unavailable_dates`
--
ALTER TABLE `package_unavailable_dates`
  ADD CONSTRAINT `fk_package_id` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`tourist_id`) REFERENCES `tourists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  ADD CONSTRAINT `tourist_spots_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `spot_owners` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
