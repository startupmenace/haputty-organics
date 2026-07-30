<?php
echo "HAPUTTY ORGANICS E-Commerce Setup\n";
echo "========================\n\n";

// Database config
$host = 'localhost';
$dbname = 'toothsavior';
$user = 'root';
$pass = '';

try {
    // Connect without database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Database '$dbname' created.\n";

    // Switch to database
    $pdo->exec("USE `$dbname`");

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
          id INT AUTO_INCREMENT PRIMARY KEY,
          first_name VARCHAR(100) NOT NULL,
          last_name VARCHAR(100) NOT NULL,
          email VARCHAR(255) NOT NULL UNIQUE,
          phone VARCHAR(50) NOT NULL,
          password VARCHAR(255) NOT NULL,
          address TEXT,
          is_admin TINYINT(1) DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[OK] Table 'users' created.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(255) NOT NULL,
          slug VARCHAR(255) NOT NULL UNIQUE,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[OK] Table 'categories' created.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
          id INT AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(500) NOT NULL,
          category_id INT NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          original_price DECIMAL(10,2) DEFAULT NULL,
          rating DECIMAL(2,1) DEFAULT 0,
          reviews_count INT DEFAULT 0,
          description TEXT,
          features TEXT,
          details TEXT,
          colors TEXT,
          images TEXT,
          in_stock TINYINT(1) DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");
    echo "[OK] Table 'products' created.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT DEFAULT NULL,
          order_ref VARCHAR(50) NOT NULL UNIQUE,
          total DECIMAL(10,2) NOT NULL,
          status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
          payment_method VARCHAR(50) DEFAULT 'mpesa',
          shipping_address TEXT,
          phone VARCHAR(50),
          notes TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    echo "[OK] Table 'orders' created.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
          id INT AUTO_INCREMENT PRIMARY KEY,
          order_id INT NOT NULL,
          product_id INT NOT NULL,
          product_title VARCHAR(500) NOT NULL,
          quantity INT NOT NULL,
          color VARCHAR(100) DEFAULT NULL,
          price DECIMAL(10,2) NOT NULL,
          image_url VARCHAR(500) DEFAULT NULL,
          FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
          FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    echo "[OK] Table 'order_items' created.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wishlists (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT DEFAULT NULL,
          product_id INT NOT NULL,
          session_id VARCHAR(255) DEFAULT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id),
          FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    echo "[OK] Table 'wishlists' created.\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT DEFAULT NULL,
          session_id VARCHAR(255) DEFAULT NULL,
          product_id INT NOT NULL,
          quantity INT DEFAULT 1,
          color VARCHAR(100) DEFAULT NULL,
          FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ");
    echo "[OK] Table 'cart' created.\n";

    // Insert categories
    $pdo->exec("
        INSERT IGNORE INTO categories (name, slug) VALUES
        ('ALL PRODUCTS', 'all-products'),
        ('TEETH WHITENING KITS', 'teeth-whitening-kits'),
        ('SONIC TOOTHBRUSHES', 'sonic-toothbrushes'),
        ('WATER FLOSSERS', 'water-flossers'),
        ('WHITENING GELS & PENS', 'whitening-gels-pens'),
        ('WHITENING STRIPS', 'whitening-strips'),
        ('CHARCOAL CARE', 'charcoal-care')
    ");
    echo "[OK] Categories seeded.\n";

    // Insert products
    $pdo->exec("
        INSERT IGNORE INTO products (id, title, category_id, price, original_price, rating, reviews_count, description, features, details, colors, images, in_stock) VALUES
        (1, 'HAPUTTY ORGANICS Professional 32-LED Cold Light Teeth Whitening Kit - Complete Home Edition', 2, 7500, 9800, 5.0, 64,
         'Achieve dentist-grade teeth whitening from home in just 16 minutes per day.',
         '[\"32 Dual LED Light Emitters\",\"Includes 4x Carbamide Peroxide Gel Pens (35%)\",\"Wireless magnetic fast-charging base\",\"Up to 8 shades whiter\",\"Enamel-safe formula\"]',
         '[{\"key\":\"Light Technology\",\"val\":\"32 Cold-Light LEDs\"},{\"key\":\"Gel Formula\",\"val\":\"35% Carbamide Peroxide\"},{\"key\":\"Treatment Time\",\"val\":\"16 Minutes\"},{\"key\":\"Battery Life\",\"val\":\"20 Treatments\"},{\"key\":\"Warranty\",\"val\":\"1 Year\"}]',
         '[{\"name\":\"Arctic White\",\"hex\":\"#FFFFFF\"},{\"name\":\"Onyx Black\",\"hex\":\"#18181B\"}]',
         '[\"https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&q=80\",\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (2, 'HAPUTTY ORGANICS SonicPro X9 40,000 VPM Electric Toothbrush Set with UV Sanitizer', 3, 6800, 8500, 4.9, 52,
         'Removes up to 10x more surface stains than manual brushing.',
         '[\"40,000 VPM sonic motor\",\"5 Cleaning Modes\",\"UV Sanitizing Travel Case\",\"6 DuPont brush heads\",\"30-day battery\"]',
         '[{\"key\":\"Vibration\",\"val\":\"40,000 VPM\"},{\"key\":\"Waterproof\",\"val\":\"IPX7\"},{\"key\":\"Timer\",\"val\":\"2-Minute Smart Pacer\"}]',
         '[{\"name\":\"Matte Black\",\"hex\":\"#18181B\"},{\"name\":\"Pearl White\",\"hex\":\"#F8FAFC\"},{\"name\":\"Rose Gold\",\"hex\":\"#FB7185\"}]',
         '[\"https://images.unsplash.com/photo-1559591937-e58af10079d3?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (3, 'HAPUTTY ORGANICS V34 Color Corrector Teeth Whitening Serum (30ml)', 2, 2800, 3800, 4.8, 39,
         'Purple color-correcting technology for instant brightness.',
         '[\"Water-soluble purple dye\",\"Instant bright effect\",\"Safe on enamel\"]',
         '[{\"key\":\"Volume\",\"val\":\"30ml\"},{\"key\":\"Usage\",\"val\":\"Daily\"}]',
         '[{\"name\":\"Deep Violet\",\"hex\":\"#581C87\"}]',
         '[\"https://images.unsplash.com/photo-1608248597261-83325803d450?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (4, 'HAPUTTY ORGANICS Enamel-Safe 35% Whitening Gel Refill Pens (4-Pack Bundle)', 5, 3200, 4200, 4.9, 41,
         'Precision twist-pen applicators for targeted stain removal.',
         '[\"4 Precision Pens\",\"15-minute dry\",\"Peppermint essence\"]',
         '[{\"key\":\"Volume\",\"val\":\"4 x 2ml\"},{\"key\":\"Sessions\",\"val\":\"Up to 80\"}]',
         '[{\"name\":\"Clear Mint\",\"hex\":\"#E2E8F0\"}]',
         '[\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (5, 'HAPUTTY ORGANICS Express Dissolving Whitening Strips (28 Strips / 14 Days)', 6, 2600, 3500, 4.7, 28,
         'No-slip dissolving strips for deep stain removal.',
         '[\"30-Minute action\",\"No-slip grip\",\"Zero sensitivity\"]',
         '[{\"key\":\"Quantity\",\"val\":\"28 Strips\"}]',
         '[]',
         '[\"https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (6, 'HAPUTTY ORGANICS Cordless Professional Water Flosser Hydro Irrigator (300ml)', 4, 4900, 6500, 4.9, 57,
         'Flushes plaque from hard-to-reach areas.',
         '[\"1400-1800 pulses/min\",\"4 pressure modes\",\"300ml tank\",\"4 jet nozzles\"]',
         '[{\"key\":\"Tank\",\"val\":\"300ml\"},{\"key\":\"Pressure\",\"val\":\"30-120 PSI\"}]',
         '[{\"name\":\"Classic White\",\"hex\":\"#FFFFFF\"},{\"name\":\"Midnight Black\",\"hex\":\"#18181B\"}]',
         '[\"https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (7, 'HAPUTTY ORGANICS Organic Activated Charcoal Powder & Bamboo Toothbrush Set', 7, 1950, 2600, 4.8, 33,
         'Natural coconut shell charcoal for gentle whitening.',
         '[\"100% Organic\",\"Includes bamboo brush\",\"Chemical-free\"]',
         '[{\"key\":\"Weight\",\"val\":\"50g\"}]',
         '[{\"name\":\"Natural Charcoal\",\"hex\":\"#27272A\"}]',
         '[\"https://images.unsplash.com/photo-1608248597261-83325803d450?auto=format&fit=crop&w=800&q=80\"]',
         1),
        (8, 'HAPUTTY ORGANICS Nano-Hydroxyapatite Remineralizing Whitening Toothpaste (100g)', 2, 1600, 2200, 4.9, 22,
         'Repairs enamel micro-fissures while whitening.',
         '[\"Rebuilds enamel\",\"Relieves sensitivity\",\"SLS/Paraben free\"]',
         '[{\"key\":\"Weight\",\"val\":\"100g\"}]',
         '[]',
         '[\"https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=800&q=80\"]',
         1)
    ");
    echo "[OK] Products seeded.\n";

    // Create admin user (password: admin123)
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO users (first_name, last_name, email, phone, password, is_admin) VALUES
        ('Admin', 'HAPUTTY ORGANICS', 'admin@haputty.co.ke', '254700000000', '$hash', 1)
    ");
    echo "[OK] Admin user created (admin@haputty.co.ke / admin123).\n";

    echo "\n====================================\n";
    echo "Setup Complete!\n";
    echo "====================================\n";
    echo "Admin Login: admin@haputty.co.ke\n";
    echo "Admin Password: admin123\n";
    echo "Admin Panel: /admin/\n";

} catch (PDOException $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    exit(1);
}
