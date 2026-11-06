-- ============================================================
-- NAIJAHAVEN - Nigerian Housing Marketplace
-- Import in phpMyAdmin (or: mysql -u root < database.sql)
-- ============================================================
CREATE DATABASE IF NOT EXISTS naijahaven CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE naijahaven;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT NULL,
  whatsapp VARCHAR(30) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('seeker','agent','admin') NOT NULL DEFAULT 'seeker',
  agency_name VARCHAR(150) DEFAULT NULL,
  agent_status ENUM('pending','verified','suspended') DEFAULT NULL,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin -> admin@haven.test / admin123 | Demo agent -> agent@haven.test / agent123
-- Both demo accounts are pre-verified and flagged to force a password change on first login.
INSERT INTO users (name, email, phone, whatsapp, password, role, agency_name, agent_status, email_verified, force_password_change) VALUES
('Site Admin', 'admin@haven.test', NULL, NULL, '$2b$10$fJ5qR67SwlW.oXjDSvylDubTkSanN7hbaReeKi73PVa.DyKiymkT2', 'admin', NULL, NULL, 1, 1),
('Chinedu Okafor', 'agent@haven.test', '+2348031112233', '+2348031112233', '$2b$10$rz889E8GEYBykn6ApVh2.uZYL/BTdSHubySkLbXzUJLQ9loBeNxPe', 'agent', 'Okafor Prime Properties', 'verified', 1, 1);

CREATE TABLE listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agent_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  listing_type ENUM('rent','shortlet','land') NOT NULL,
  city VARCHAR(60) NOT NULL,
  area VARCHAR(80) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  bedrooms TINYINT DEFAULT NULL,
  bathrooms TINYINT DEFAULT NULL,
  toilets TINYINT DEFAULT NULL,
  size_sqm INT DEFAULT NULL,
  furnished TINYINT(1) DEFAULT 0,
  serviced TINYINT(1) DEFAULT 0,
  title_doc VARCHAR(80) DEFAULT NULL,       -- e.g. C of O, Governor's Consent, Registered Survey
  price_year DECIMAL(14,2) DEFAULT NULL,    -- annual rent (₦)
  price_night DECIMAL(12,2) DEFAULT NULL,   -- shortlet nightly (₦)
  price_total DECIMAL(15,2) DEFAULT NULL,   -- land outright (₦)
  description TEXT,
  image_url VARCHAR(500) DEFAULT NULL,
  featured TINYINT(1) DEFAULT 0,
  status ENUM('pending','approved','rejected','taken') DEFAULT 'pending',
  rating_avg DECIMAL(2,1) DEFAULT NULL,
  rating_count INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_browse (status, listing_type, city)
) ENGINE=InnoDB;

INSERT INTO listings (agent_id, title, listing_type, city, area, address, bedrooms, bathrooms, toilets, size_sqm, furnished, serviced, title_doc, price_year, price_night, price_total, description, image_url, featured, status) VALUES
(2,'Luxury 4-Bed Terrace Duplex, Lekki Phase 1','rent','Lagos','Lekki Phase 1','Admiralty Way axis',4,4,5,NULL,0,1,NULL,8500000,NULL,NULL,'Brand-new terrace duplex in a gated estate: all rooms en-suite, fitted kitchen, 24/7 power rotation, swimming pool and gym. Service charge inclusive.','https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=900',1,'approved'),
(2,'Executive 3-Bed Flat, Gwarinpa Abuja','rent','Abuja','Gwarinpa','6th Avenue',3,3,4,NULL,0,0,NULL,3200000,NULL,NULL,'Spacious upstairs flat with POP finishing, ample parking, borehole water and prepaid meter. Family-friendly close.','https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=900',1,'approved'),
(2,'Smart Mini Flat, Yaba','rent','Lagos','Yaba','Herbert Macaulay Way axis',1,1,2,NULL,0,0,NULL,1200000,NULL,NULL,'Clean mini flat perfect for young professionals — 10 mins to Yaba tech cluster, prepaid meter, secure compound.','https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=900',0,'approved'),
(2,'2-Bed Apartment, GRA Port Harcourt','rent','Port Harcourt','GRA Phase 2','Woji Road axis',2,2,3,NULL,0,1,NULL,2500000,NULL,NULL,'Serviced apartment in quiet GRA close with standby generator, water treatment and uniformed security.','https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=900',0,'approved'),
(2,'Premium Shortlet Studio, Victoria Island','shortlet','Lagos','Victoria Island','Akin Adesola axis',1,1,1,NULL,1,1,NULL,NULL,85000,NULL,'Designer studio with king bed, 100Mbps WiFi, Netflix, 24/7 power and concierge. Perfect for business stays.','https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=900',1,'approved'),
(2,'Chic 2-Bed Shortlet, Lekki','shortlet','Lagos','Lekki Phase 1','Off Freedom Way',2,2,3,NULL,1,1,NULL,NULL,120000,NULL,'Instagram-worthy apartment with pool access, PS5, smart TV in every room and full-time house-keeping.','https://images.unsplash.com/photo-1567767292278-a4f21aa2d36e?w=900',1,'approved'),
(2,'Cosy 1-Bed Shortlet, Wuse 2 Abuja','shortlet','Abuja','Wuse 2','Aminu Kano Crescent axis',1,1,1,NULL,1,1,NULL,NULL,65000,NULL,'Warm, fully-serviced apartment minutes from restaurants and nightlife. Airport pickup available.','https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=900',0,'approved'),
(2,'600sqm Corner-Piece Land, Ajah','land','Lagos','Ajah','Abraham Adesanya axis',NULL,NULL,NULL,600,0,0,'Registered Survey + Deed',NULL,NULL,45000000,'Dry, table-flat corner-piece in a developed estate with good access road. Instant allocation.','https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=900',1,'approved'),
(2,'Half Plot (450sqm), Ibeju-Lekki','land','Lagos','Ibeju-Lekki','Eleko axis',NULL,NULL,NULL,450,0,0,'C of O',NULL,NULL,28000000,'C of O land inside serviced estate near the Free Trade Zone corridor. High-appreciation corridor.','https://images.unsplash.com/photo-1628744448840-55bdb2497bd4?w=900',0,'approved'),
(2,'1000sqm Residential Plot, Guzape Abuja','land','Abuja','Guzape','Guzape Hills',NULL,NULL,NULL,1000,0,0,'R of O + Approved Layout',NULL,NULL,120000000,'Elevated plot with panoramic city views in fast-developing Guzape. Ideal for a smart-home build.','https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=900',0,'approved'),
(2,'5-Bed Detached Duplex + BQ, Maitama','rent','Abuja','Maitama','Off Aguiyi Ironsi Way',5,5,6,NULL,0,1,NULL,25000000,NULL,NULL,'Diplomatic-zone detached house with lush compound, boys quarters, ample parking and top-grade security.','https://images.unsplash.com/photo-1613977257363-707ba9348227?w=900',1,'approved'),
(2,'Luxury Penthouse Shortlet, Ikoyi','shortlet','Lagos','Ikoyi','Banana Island Road axis',3,3,4,NULL,1,1,NULL,NULL,250000,NULL,'Skyline penthouse with private terrace, chef-on-request and cinema room. The ultimate Lagos flex.','https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=900',1,'approved');

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  listing_id INT NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  nights INT NOT NULL,
  total DECIMAL(14,2) NOT NULL,
  status ENUM('pending','paid','confirmed','completed','cancelled') DEFAULT 'pending',
  payment_ref VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT DEFAULT NULL,
  agent_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','closed') DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL,
  FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Brute-force login protection: recent failed attempts per email+IP
CREATE TABLE login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_identifier_time (identifier, created_at)
) ENGINE=InnoDB;

-- One-time tokens for email verification and password reset
CREATE TABLE user_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  purpose ENUM('reset','verify') NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message VARCHAR(255) NOT NULL,
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  listing_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_fav (user_id, listing_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  user_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review (listing_id, user_id),
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE listing_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(40) NOT NULL,
  target_id INT DEFAULT NULL,
  details VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
