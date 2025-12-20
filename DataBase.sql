-- Création de la base de données
CREATE DATABASE IF NOT EXISTS techshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE techshop;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des produits
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    stock INT DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_price (price),
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- Table du panier
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, item_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Table des détails de commande
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- Insertion des catégories
INSERT INTO categories (name, description) VALUES
('Smartphones', 'Téléphones mobiles et accessoires'),
('Ordinateurs', 'PC portables et de bureau'),
('Audio', 'Casques, écouteurs et enceintes'),
('Gaming', 'Consoles et accessoires de jeu'),
('Montres', 'Montres connectées et smartwatches');

-- Insertion d'un administrateur par défaut
-- Mot de passe: admin123 (hashé avec password_hash)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@techshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('user', 'user@techshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');
('heesh', 'heesh@techshop.com', '$2y$10$EFsI4UB7bzKyN4Vx6dR0/ONzR2Nb64o50iMeBm0LGyzc8rhk8ybOK', 'admin');

-- Insertion de produits d'exemple
INSERT INTO items (name, description, price, category_id, stock, image_url) VALUES
('iPhone 15 Pro', 'Smartphone Apple avec puce A17 Pro, caméra 48MP et écran Super Retina XDR', 1199.99, 1, 50, 'https://images.unsplash.com/photo-1696446702183-cbd50c781e28?w=500'),
('Samsung Galaxy S24', 'Smartphone Samsung avec écran AMOLED 6.2", processeur Snapdragon 8 Gen 3', 999.99, 1, 45, 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=500'),
('MacBook Pro M3', 'Ordinateur portable Apple avec puce M3, 16GB RAM, écran Liquid Retina XDR', 2499.99, 2, 30, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500'),
('Dell XPS 15', 'PC portable ultra-performant avec Intel i9, 32GB RAM, écran 4K OLED', 1899.99, 2, 25, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500'),
('Sony WH-1000XM5', 'Casque sans fil à réduction de bruit active, autonomie 30h', 399.99, 3, 100, 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=500'),
('AirPods Pro 2', 'Écouteurs sans fil Apple avec réduction de bruit adaptative', 279.99, 3, 150, 'https://images.unsplash.com/photo-1606841837239-c5a1a4a07af7?w=500'),
('PlayStation 5', 'Console de jeu Sony avec SSD ultra-rapide et ray tracing', 499.99, 4, 40, 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=500'),
('Xbox Series X', 'Console Microsoft 4K avec 1TB de stockage et Quick Resume', 499.99, 4, 35, 'https://images.unsplash.com/photo-1621259182978-fbf93132d53d?w=500'),
('Apple Watch Ultra 2', 'Montre connectée robuste avec GPS précis et autonomie 36h', 849.99, 5, 60, 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=500'),
('Samsung Galaxy Watch 6', 'Smartwatch Android avec suivi santé avancé et écran AMOLED', 349.99, 5, 70, 'https://images.unsplash.com/photo-1617625802912-cdf4eaa937e2?w=500'),
('iPad Pro 12.9"', 'Tablette Apple avec puce M2, écran Liquid Retina XDR', 1099.99, 2, 55, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=500'),
('Bose QuietComfort', 'Casque confortable avec réduction de bruit premium', 349.99, 3, 80, 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=500');