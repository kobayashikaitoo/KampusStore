-- ============================================================
-- KampusStore — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS kampusstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kampusstore;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- ── TABLE: users ─────────────────────────────────────────
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    campus VARCHAR(100),
    faculty VARCHAR(100),
    profile_photo VARCHAR(255),
    bio TEXT,
    phone VARCHAR(20),
    whatsapp_number VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    is_verified TINYINT DEFAULT 0,
    is_trusted TINYINT DEFAULT 0,
    is_banned TINYINT DEFAULT 0,
    ban_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_username (username),
    KEY idx_role (role),
    KEY idx_is_banned (is_banned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: categories ────────────────────────────────────
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: products ─────────────────────────────────────
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    is_nego TINYINT DEFAULT 1,
    image VARCHAR(255),
    location VARCHAR(100),
    `condition` ENUM('like_new', 'good', 'fair', 'used') DEFAULT 'good',
    status ENUM('active', 'sold', 'inactive') DEFAULT 'active',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    KEY idx_seller_id (seller_id),
    KEY idx_category_id (category_id),
    KEY idx_status (status),
    KEY idx_created_at (created_at),
    FULLTEXT INDEX ft_title_desc (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: wishlists ────────────────────────────────────
CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: admin_logs ───────────────────────────────────
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target VARCHAR(50),
    target_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_admin_id (admin_id),
    KEY idx_created_at (created_at),
    KEY idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE: reports ──────────────────────────────────────
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    product_id INT,
    user_id INT,
    reason VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'investigating', 'resolved', 'dismissed') DEFAULT 'open',
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_status (status),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── INITIAL DATA: Categories ────────────────────────────
INSERT INTO categories (name, slug, description, order_index) VALUES
('Laptop & Komputer', 'laptop-komputer', NULL, 1),
('Smartphone & Tablet', 'smartphone-tablet', NULL, 2),
('Buku & Catatan', 'buku-catatan', NULL, 3),
('Fashion & Aksesoris', 'fashion-aksesoris', NULL, 4),
('Kebutuhan Kos', 'kebutuhan-kos', NULL, 5),
('Olahraga & Gaming', 'olahraga-gaming', NULL, 6),
('Elektronik', 'elektronik', NULL, 7),
('Lainnya', 'lainnya', NULL, 8);
