<?php
// Set UTF-8 encoding for all output (mbstring extension required)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
    mb_regex_encoding('UTF-8');
}

// Smart configuration - automatically detects local or production environment

// Check if we're on local or production
// Use HTTP_HOST for more reliable detection (hosting servers often report localhost in SERVER_NAME)
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';

// If HTTP_HOST is set and contains a real domain (not localhost), it's production
$isLocal = (
    $host === 'localhost' || 
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    $serverAddr === '127.0.0.1' ||
    (strpos($host, '.local') !== false) ||
    (strpos($host, '192.168.') !== false) ||
    // If HTTP_HOST is empty or not set, check SERVER_NAME
    (empty($host) && (
        $_SERVER['SERVER_NAME'] === 'localhost' || 
        strpos($_SERVER['SERVER_NAME'] ?? '', 'localhost') !== false
    ))
);

// Override: If HTTP_HOST contains a real domain (has dots and is not localhost), force production
if (!empty($host) && 
    strpos($host, 'localhost') === false && 
    strpos($host, '127.0.0.1') === false &&
    strpos($host, '.') !== false) {
    $isLocal = false;
}

if ($isLocal) {
    // Local XAMPP Database configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'birmenu');
    define('DB_PORT', 3307); // XAMPP default. Əgər MySQL 3307-də işləyirsə, dəyişdirin.
    define('ENVIRONMENT', 'LOCAL');
} else {
    // Production Server Database configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 's1094297_birmenu_main');
    define('DB_PASS', 'salamsalam0');
    define('DB_NAME', 's1094297_birmenu_main');
    define('DB_PORT', 3306);
    define('ENVIRONMENT', 'PRODUCTION');
}

// Admin credentials (vahid giriş səhifəsindən admin panelə giriş üçün)
define('ADMIN_USERNAME', 'suleymansuleymanli2005');
define('ADMIN_PASSWORD', '20056216');

// Create database connection
function getDBConnection() {
    // Öncə bazasız qoşuluruq ki, baza yoxdursa yarada bilək
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, "", DB_PORT);
    
    if ($conn->connect_error) {
        // Clear any output before sending JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => "Verilənlər bazasına qoşulma alınmadı: " . $conn->connect_error], JSON_UNESCAPED_UNICODE));
    }

    // Bazanı yaradırıq (əgər yoxdursa)
    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->query($sql);
    
    // Bazanı seçirik
    $conn->select_db(DB_NAME);
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Create tables if they don't exist
function createTables() {
    try {
        $conn = getDBConnection();
        
        // Restaurants table - Check if exists first (must be created before categories and products)
        $restaurantsTableCheck = $conn->query("SHOW TABLES LIKE 'restaurants'");
        
        if ($restaurantsTableCheck->num_rows == 0) {
            // Table doesn't exist, create it with all columns
            $sql = "CREATE TABLE restaurants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                address VARCHAR(500),
                phone VARCHAR(50),
                phone2 VARCHAR(50) DEFAULT NULL,
                phone3 VARCHAR(50) DEFAULT NULL,
                phone4 VARCHAR(50) DEFAULT NULL,
                logo_path VARCHAR(500),
                cover_path VARCHAR(500),
                wifi_name VARCHAR(100),
                wifi_password VARCHAR(100),
                login_username VARCHAR(100) DEFAULT NULL,
                login_password VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                view_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!$conn->query($sql)) {
                error_log("Error creating restaurants table: " . $conn->error);
            }
        } else {
            // Table exists, add missing columns
            // Add wifi_name column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'wifi_name'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN wifi_name VARCHAR(100) AFTER cover_path");
            }
            
            // Add wifi_password column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'wifi_password'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN wifi_password VARCHAR(100) AFTER wifi_name");
            }
            
            // Add login_username column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'login_username'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN login_username VARCHAR(100) DEFAULT NULL AFTER wifi_password");
            }
            
            // Add login_password column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'login_password'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN login_password VARCHAR(255) DEFAULT NULL AFTER login_username");
            }
            
            // Add view_count column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'view_count'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN view_count INT DEFAULT 0 AFTER is_active");
            }
            
            // Add social media columns if they don't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'instagram_url'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN instagram_url VARCHAR(500) DEFAULT NULL AFTER view_count");
            }
            
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'facebook_url'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN facebook_url VARCHAR(500) DEFAULT NULL AFTER instagram_url");
            }
            
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'whatsapp_url'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN whatsapp_url VARCHAR(500) DEFAULT NULL AFTER facebook_url");
            }
            
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'tiktok_url'");
            if ($result && $result->num_rows == 0) {
                $ok = $conn->query("ALTER TABLE restaurants ADD COLUMN tiktok_url VARCHAR(500) DEFAULT NULL AFTER whatsapp_url");
                if (!$ok) {
                    $ok = $conn->query("ALTER TABLE restaurants ADD COLUMN tiktok_url VARCHAR(500) DEFAULT NULL");
                }
                if (!$ok) {
                    error_log("Failed to add tiktok_url column: " . $conn->error);
                }
            }
            // Add optional extra phone columns if they don't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'phone2'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN phone2 VARCHAR(50) DEFAULT NULL AFTER phone");
            }
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'phone3'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN phone3 VARCHAR(50) DEFAULT NULL AFTER phone2");
            }
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'phone4'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN phone4 VARCHAR(50) DEFAULT NULL AFTER phone3");
            }
        }
        
        // Categories table (must be created before products)
        $categoriesTableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($categoriesTableCheck->num_rows == 0) {
            $sql = "CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                icon VARCHAR(100),
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!$conn->query($sql)) {
                error_log("Error creating categories table: " . $conn->error);
            }
        } else {
            // Add translation columns for categories if they don't exist
            $result = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
            if ($result && $result->num_rows == 0) {
                // Add name translation columns
                $conn->query("ALTER TABLE categories ADD COLUMN name_az VARCHAR(255) DEFAULT NULL AFTER name");
                $conn->query("ALTER TABLE categories ADD COLUMN name_en VARCHAR(255) DEFAULT NULL AFTER name_az");
                $conn->query("ALTER TABLE categories ADD COLUMN name_ru VARCHAR(255) DEFAULT NULL AFTER name_en");
                
                // Copy existing name values to name_az
                $conn->query("UPDATE categories SET name_az = name WHERE name_az IS NULL OR name_az = ''");
            }
        }
        
        // Products table - Check if exists (must be created after restaurants and categories)
        $productsTableCheck = $conn->query("SHOW TABLES LIKE 'products'");
        
        if ($productsTableCheck->num_rows == 0) {
            // Table doesn't exist, create it
            $sql = "CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                discount_price DECIMAL(10, 2) DEFAULT NULL,
                category_id INT DEFAULT NULL,
                image_path VARCHAR(500),
                popular TINYINT(1) DEFAULT 0,
                restaurant_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!$conn->query($sql)) {
                error_log("Error creating products table: " . $conn->error);
            }
        } else {
            // Table exists, add missing columns and migrate category
            // Add discount_price column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_price'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE products ADD COLUMN discount_price DECIMAL(10, 2) DEFAULT NULL AFTER price");
            }
            
            // Add restaurant_id column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'restaurant_id'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE products ADD COLUMN restaurant_id INT DEFAULT NULL AFTER popular");
            }
            
            // Migrate from category ENUM to category_id INT
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
            if ($result && $result->num_rows == 0) {
                // Add category_id column
                $conn->query("ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL AFTER discount_price");
                
                // Add foreign key constraint (only if categories table exists)
                $catTableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
                if ($catTableCheck->num_rows > 0) {
                    // Check if constraint already exists
                    $fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND CONSTRAINT_NAME = 'fk_product_category'");
                    if ($fkCheck->num_rows == 0) {
                        $conn->query("ALTER TABLE products ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
                    }
                }
                
                // Drop old category ENUM column if it exists
                $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
                if ($result && $result->num_rows > 0) {
                    $conn->query("ALTER TABLE products DROP COLUMN category");
                }
            }
            
            // Add translation columns for products if they don't exist
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'name_az'");
            if ($result && $result->num_rows == 0) {
                // Add name translation columns
                $conn->query("ALTER TABLE products ADD COLUMN name_az VARCHAR(255) DEFAULT NULL AFTER name");
                $conn->query("ALTER TABLE products ADD COLUMN name_en VARCHAR(255) DEFAULT NULL AFTER name_az");
                $conn->query("ALTER TABLE products ADD COLUMN name_ru VARCHAR(255) DEFAULT NULL AFTER name_en");
                
                // Copy existing name values to name_az
                $conn->query("UPDATE products SET name_az = name WHERE name_az IS NULL OR name_az = ''");
                
                // Add description translation columns
                $result = $conn->query("SHOW COLUMNS FROM products LIKE 'description_az'");
                if ($result && $result->num_rows == 0) {
                    $conn->query("ALTER TABLE products ADD COLUMN description_az TEXT DEFAULT NULL AFTER description");
                    $conn->query("ALTER TABLE products ADD COLUMN description_en TEXT DEFAULT NULL AFTER description_az");
                    $conn->query("ALTER TABLE products ADD COLUMN description_ru TEXT DEFAULT NULL AFTER description_en");
                    
                    // Copy existing description values to description_az
                    $conn->query("UPDATE products SET description_az = description WHERE description_az IS NULL AND description IS NOT NULL");
                }
            }
        }
        
        // Sets table (combo/package)
        $setsTableCheck = $conn->query("SHOW TABLES LIKE 'product_sets'");
        if ($setsTableCheck->num_rows == 0) {
            $sql = "CREATE TABLE product_sets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                image_path VARCHAR(500),
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if (!$conn->query($sql)) {
                error_log("Error creating product_sets table: " . $conn->error);
            }
        }

        // Mərkəzi menyu bazası – bütün restoranların məhsulları burada toplanır
        $catTplCheck = $conn->query("SHOW TABLES LIKE 'category_templates'");
        if ($catTplCheck->num_rows == 0) {
            $sql = "CREATE TABLE category_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            if (!$conn->query($sql)) error_log("Error creating category_templates: " . $conn->error);
        }
        $prodTplCheck = $conn->query("SHOW TABLES LIKE 'product_templates'");
        if ($prodTplCheck->num_rows == 0) {
            $sql = "CREATE TABLE product_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category_template_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                discount_price DECIMAL(10, 2) DEFAULT NULL,
                image_path VARCHAR(500) DEFAULT NULL,
                popular TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_template_id) REFERENCES category_templates(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            if (!$conn->query($sql)) error_log("Error creating product_templates: " . $conn->error);
        }
        
        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("Error in createTables: " . $e->getMessage());
        if (isset($conn)) {
            $conn->close();
        }
        return false;
    }
}

// Initialize database
createTables();

// Create PDO connection for API
try {
    $pdo = @new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    // Ensure UTF-8 encoding
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Log error but don't expose details
    error_log("PDO Connection Error: " . $e->getMessage());
    $pdo = null;
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("Database Connection Error: " . $e->getMessage());
    $pdo = null;
}
?>
