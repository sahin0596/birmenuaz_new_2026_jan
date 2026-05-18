<?php
// Set UTF-8 encoding for all output (mbstring extension required)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
    mb_regex_encoding('UTF-8');
}

// Define admin credentials FIRST (before any database operations)
define('ADMIN_USERNAME', 'suleymansuleymanli2005');
define('ADMIN_PASSWORD', '20056216');

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

$envDbHost = getenv('DB_HOST');
$envDbUser = getenv('DB_USER');
$envDbPass = getenv('DB_PASS');
$envDbName = getenv('DB_NAME');
$envDbPort = getenv('DB_PORT');
$envAppEnv = getenv('APP_ENV') ?: getenv('ENVIRONMENT');

if ($envDbHost && $envDbUser && $envDbName) {
    define('DB_HOST', $envDbHost);
    define('DB_USER', $envDbUser);
    define('DB_PASS', $envDbPass ?: '');
    define('DB_NAME', $envDbName);
    define('DB_PORT', $envDbPort ?: 5432);
    define('ENVIRONMENT', $envAppEnv ?: 'PRODUCTION');
} elseif ($isLocal) {
    // Local PostgreSQL Database configuration
    define('DB_HOST', 'dpg-d85051jbc2fs73ck80l0-a.frankfurt-postgres.render.com');
    define('DB_USER', 'birmenu');
    define('DB_PASS', 'Ilnt94gnfi7gwZ09QstWfy1uar0lv1UZ');
    define('DB_NAME', 'birmenu');
    define('DB_PORT', 5432);
    define('ENVIRONMENT', 'LOCAL');
} else {
    // Production PostgreSQL Database configuration
    define('DB_HOST', 'dpg-d85051jbc2fs73ck80l0-a');
    define('DB_USER', 'birmenu');
    define('DB_PASS', 'Ilnt94gnfi7gwZ09QstWfy1uar0lv1UZ');
    define('DB_NAME', 'birmenu');
    define('DB_PORT', 5432);
    define('ENVIRONMENT', 'PRODUCTION');
}

// PostgreSQL compatibility wrapper for existing MySQLi-style usage
class PgSqlCompatResult {
    private array $rows;
    private int $index = 0;
    public int $num_rows;

    public function __construct(PDOStatement $stmt) {
        $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc() {
        if ($this->index >= $this->num_rows) {
            return null;
        }
        return $this->rows[$this->index++];
    }

    public function fetch_array() {
        return $this->fetch_assoc();
    }
}

class PgSqlCompatStatement {
    private PDO $pdo;
    private string $sql;
    private array $params = [];
    private ?PDOStatement $stmt = null;
    public string $error = '';
    private PgSqlCompat $conn;

    public function __construct(PDO $pdo, string $sql, PgSqlCompat $conn) {
        $this->pdo = $pdo;
        $this->sql = $sql;
        $this->conn = $conn;
    }

    public function bind_param($types) {
        $args = func_get_args();
        array_shift($args);
        $this->params = $args;
        return true;
    }

    public function execute() {
        try {
            $this->stmt = $this->pdo->prepare($this->sql);
            if ($this->stmt === false) {
                $this->error = 'Statement preparation failed';
                return false;
            }
            $success = $this->stmt->execute($this->params);
            if (!$success) {
                $errorInfo = $this->stmt->errorInfo();
                $this->error = $errorInfo[2] ?? 'Statement execution failed';
                return false;
            }
            $this->conn->affected_rows = $this->stmt->rowCount();
            if ($this->conn->isInsertQuery($this->sql)) {
                $this->conn->insert_id = $this->conn->fetchLastInsertId($this->stmt, $this->sql);
            }
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function get_result() {
        if ($this->stmt === null) {
            if (!$this->execute()) {
                return null;
            }
        }
        return new PgSqlCompatResult($this->stmt);
    }

    public function close() {
        $this->stmt = null;
    }
}

class PgSqlCompat {
    private ?PDO $pdo;
    public string $error = '';
    public string $connect_error = '';
    public int $affected_rows = 0;
    public ?int $insert_id = null;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function query(string $sql) {
        $sql = $this->translateSql($sql);
        if ($sql === null) {
            return true;
        }

        try {
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                $errorInfo = $this->pdo->errorInfo();
                $this->error = $errorInfo[2] ?? 'Query execution failed';
                return false;
            }

            if ($this->isSelectQuery($sql)) {
                return new PgSqlCompatResult($stmt);
            }

            $this->affected_rows = $stmt->rowCount();
            if ($this->isInsertQuery($sql)) {
                $this->insert_id = $this->fetchLastInsertId($stmt, $sql);
            }
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function prepare(string $sql) {
        return new PgSqlCompatStatement($this->pdo, $this->translateSql($sql), $this);
    }

    public function select_db(string $databaseName) {
        return true;
    }

    public function set_charset(string $charset) {
        return true;
    }

    public function close() {
        $this->pdo = null;
    }

    public function real_escape_string(string $value) {
        return substr($this->pdo->quote($value), 1, -1);
    }

    public function isSelectQuery(string $sql): bool {
        return preg_match('/^\s*(SELECT|SHOW|WITH|EXPLAIN)/i', $sql) === 1;
    }

    public function isInsertQuery(string $sql): bool {
        return preg_match('/^\s*INSERT\s+/i', $sql) === 1;
    }

    public function fetchLastInsertId(PDOStatement $stmt, string $sql): ?int {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false && isset($row['id'])) {
            return (int)$row['id'];
        }

        if (preg_match('/INSERT\s+INTO\s+"?([A-Za-z0-9_]+)"?/i', $sql, $matches)) {
            $sequenceName = $matches[1] . '_id_seq';
            try {
                return (int)$this->pdo->lastInsertId($sequenceName);
            } catch (PDOException $e) {
                return null;
            }
        }

        return null;
    }

    private function translateSql(string $sql): ?string {
        $trimmed = trim($sql);

        if (preg_match('/^SHOW\s+TABLES\s+LIKE\s+\'([^\']+)\'/i', $trimmed, $matches)) {
            $tableName = $matches[1];
            return "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '" . str_replace("'", "''", $tableName) . "' LIMIT 1";
        }

        if (preg_match('/^SHOW\s+COLUMNS\s+FROM\s+[`\"]?([A-Za-z0-9_]+)[`\"]?\s+LIKE\s+\'([^\']+)\'/i', $trimmed, $matches)) {
            $tableName = $matches[1];
            $columnName = $matches[2];
            return "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '" . str_replace("'", "''", $tableName) . "' AND column_name LIKE '" . str_replace("'", "''", $columnName) . "'";
        }

        if (stripos($trimmed, 'CREATE DATABASE IF NOT EXISTS') === 0) {
            return null;
        }

        if (stripos($trimmed, 'TABLE_SCHEMA = DATABASE()') !== false) {
            return preg_replace('/TABLE_SCHEMA\s*=\s*DATABASE\(\)/i', "table_catalog = current_database() AND table_schema = 'public'", $sql);
        }

        if ($this->isInsertQuery($trimmed) && stripos($trimmed, 'RETURNING') === false) {
            return rtrim($sql, ';') . ' RETURNING id';
        }

        return $sql;
    }
}

// Create database connection
function getDBConnection() {
    try {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $errorMessage = $e->getMessage();

        if (strpos($errorMessage, 'database "' . DB_NAME . '" does not exist') !== false || strpos($errorMessage, 'does not exist') !== false) {
            try {
                $dsn = sprintf('pgsql:host=%s;port=%s;dbname=postgres', DB_HOST, DB_PORT);
                $adminPdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $adminPdo->exec(sprintf("CREATE DATABASE \"%s\" ENCODING = 'UTF8'", DB_NAME));
                $adminPdo = null;
                $pdo = new PDO(sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME), DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $inner) {
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                header('Content-Type: application/json');
                die(json_encode(['success' => false, 'message' => 'Verilənlər bazasına qoşulma alınmadı: ' . $inner->getMessage()], JSON_UNESCAPED_UNICODE));
            }
        } else {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json');
            die(json_encode(['success' => false, 'message' => 'Verilənlər bazasına qoşulma alınmadı: ' . $errorMessage], JSON_UNESCAPED_UNICODE));
        }
    }

    $pdo->exec("SET NAMES 'UTF8'");
    return new PgSqlCompat($pdo);
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
                id SERIAL PRIMARY KEY,
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
                is_active SMALLINT DEFAULT 1,
                view_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$conn->query($sql)) {
                error_log("Error creating restaurants table: " . $conn->error);
            }
        } else {
            // Table exists, add missing columns
            // Add wifi_name column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'wifi_name'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN wifi_name VARCHAR(100)");
            }
            
            // Add wifi_password column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'wifi_password'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN wifi_password VARCHAR(100)");
            }
            
            // Add login_username column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'login_username'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN login_username VARCHAR(100) DEFAULT NULL");
            }
            
            // Add login_password column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'login_password'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN login_password VARCHAR(255) DEFAULT NULL");
            }
            
            // Add view_count column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'view_count'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN view_count INT DEFAULT 0");
            }
            
            // Add social media columns if they don't exist
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'instagram_url'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN instagram_url VARCHAR(500) DEFAULT NULL");
            }
            
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'facebook_url'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN facebook_url VARCHAR(500) DEFAULT NULL");
            }
            
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'whatsapp_url'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN whatsapp_url VARCHAR(500) DEFAULT NULL");
            }
            
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'tiktok_url'");
            if ($result && $result->num_rows == 0) {
                $ok = $conn->query("ALTER TABLE restaurants ADD COLUMN tiktok_url VARCHAR(500) DEFAULT NULL");
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
                $conn->query("ALTER TABLE restaurants ADD COLUMN phone2 VARCHAR(50) DEFAULT NULL");
            }
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'phone3'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN phone3 VARCHAR(50) DEFAULT NULL");
            }
            $result = $conn->query("SHOW COLUMNS FROM restaurants LIKE 'phone4'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE restaurants ADD COLUMN phone4 VARCHAR(50) DEFAULT NULL");
            }
        }
        
        // Categories table (must be created before products)
        $categoriesTableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
        if ($categoriesTableCheck->num_rows == 0) {
            $sql = "CREATE TABLE categories (
                id SERIAL PRIMARY KEY,
                restaurant_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                icon VARCHAR(100),
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($sql)) {
                error_log("Error creating categories table: " . $conn->error);
            }
        } else {
            // Add translation columns for categories if they don't exist
            $result = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
            if ($result && $result->num_rows == 0) {
                // Add name translation columns
                $conn->query("ALTER TABLE categories ADD COLUMN name_az VARCHAR(255) DEFAULT NULL");
                $conn->query("ALTER TABLE categories ADD COLUMN name_en VARCHAR(255) DEFAULT NULL");
                $conn->query("ALTER TABLE categories ADD COLUMN name_ru VARCHAR(255) DEFAULT NULL");
                
                // Copy existing name values to name_az
                $conn->query("UPDATE categories SET name_az = name WHERE name_az IS NULL OR name_az = ''");
            }
        }
        
        // Products table - Check if exists (must be created after restaurants and categories)
        $productsTableCheck = $conn->query("SHOW TABLES LIKE 'products'");
        
        if ($productsTableCheck->num_rows == 0) {
            // Table doesn't exist, create it
            $sql = "CREATE TABLE products (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                discount_price DECIMAL(10, 2) DEFAULT NULL,
                category_id INT DEFAULT NULL,
                image_path VARCHAR(500),
                popular SMALLINT DEFAULT 0,
                restaurant_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($sql)) {
                error_log("Error creating products table: " . $conn->error);
            }
        } else {
            // Table exists, add missing columns and migrate category
            // Add discount_price column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_price'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE products ADD COLUMN discount_price DECIMAL(10, 2) DEFAULT NULL");
            }
            
            // Add restaurant_id column if it doesn't exist
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'restaurant_id'");
            if ($result && $result->num_rows == 0) {
                $conn->query("ALTER TABLE products ADD COLUMN restaurant_id INT DEFAULT NULL");
            }
            
            // Migrate from category ENUM to category_id INT
            $result = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
            if ($result && $result->num_rows == 0) {
                // Add category_id column
                $conn->query("ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL");
                
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
                $conn->query("ALTER TABLE products ADD COLUMN name_az VARCHAR(255) DEFAULT NULL");
                $conn->query("ALTER TABLE products ADD COLUMN name_en VARCHAR(255) DEFAULT NULL");
                $conn->query("ALTER TABLE products ADD COLUMN name_ru VARCHAR(255) DEFAULT NULL");
                
                // Copy existing name values to name_az
                $conn->query("UPDATE products SET name_az = name WHERE name_az IS NULL OR name_az = ''");
                
                // Add description translation columns
                $result = $conn->query("SHOW COLUMNS FROM products LIKE 'description_az'");
                if ($result && $result->num_rows == 0) {
                    $conn->query("ALTER TABLE products ADD COLUMN description_az TEXT DEFAULT NULL");
                    $conn->query("ALTER TABLE products ADD COLUMN description_en TEXT DEFAULT NULL");
                    $conn->query("ALTER TABLE products ADD COLUMN description_ru TEXT DEFAULT NULL");
                    
                    // Copy existing description values to description_az
                    $conn->query("UPDATE products SET description_az = description WHERE description_az IS NULL AND description IS NOT NULL");
                }
            }
        }
        
        // Sets table (combo/package)
        $setsTableCheck = $conn->query("SHOW TABLES LIKE 'product_sets'");
        if ($setsTableCheck->num_rows == 0) {
            $sql = "CREATE TABLE product_sets (
                id SERIAL PRIMARY KEY,
                restaurant_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                image_path VARCHAR(500),
                is_active SMALLINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($sql)) {
                error_log("Error creating product_sets table: " . $conn->error);
            }
        }

        // Mərkəzi menyu bazası – bütün restoranların məhsulları burada toplanır
        $catTplCheck = $conn->query("SHOW TABLES LIKE 'category_templates'");
        if ($catTplCheck->num_rows == 0) {
            $sql = "CREATE TABLE category_templates (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if (!$conn->query($sql)) error_log("Error creating category_templates: " . $conn->error);
        }
        $prodTplCheck = $conn->query("SHOW TABLES LIKE 'product_templates'");
        if ($prodTplCheck->num_rows == 0) {
            $sql = "CREATE TABLE product_templates (
                id SERIAL PRIMARY KEY,
                category_template_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                discount_price DECIMAL(10, 2) DEFAULT NULL,
                image_path VARCHAR(500) DEFAULT NULL,
                popular SMALLINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_template_id) REFERENCES category_templates(id) ON DELETE SET NULL
            )";
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
    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    // Ensure UTF-8 encoding
    $pdo->exec("SET NAMES 'UTF8'");
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
