<?php
// Set UTF-8 encoding for all output (mbstring extension required)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
    mb_regex_encoding('UTF-8');
}

// Start output buffering to catch any errors
ob_start();

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Error handling - log errors but don't display them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Fatal error və ya gözlənilməz çıxışda JSON qaytar
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'PHP xətası: ' . ($err['message'] ?? 'Bilinməyən xəta')], JSON_UNESCAPED_UNICODE);
    }
});

require_once '../config.php';

// Safe output buffer clear - prevents 500 when buffer is empty
function api_ob_clean() {
    if (ob_get_length() !== false) {
        ob_clean();
    }
}

// Verilənlər bazası və cədvəlin mövcudluğunu config.php artıq yoxlayır.

// ------------------------------------------------------------
// Filesystem helpers: write to project root (../), not /api/
// ------------------------------------------------------------
function getProjectRootPath() {
    static $root = null;
    if ($root === null) {
        $root = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    }
    return $root ?: (__DIR__ . DIRECTORY_SEPARATOR . '..');
}

function toFsPath($relativePath) {
    $root = getProjectRootPath();
    $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
    return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
}

function toWebPath($path) {
    $path = str_replace('\\', '/', $path);
    return ltrim($path, '/');
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get':
        case 'get_products':
            getProducts();
            break;
        case 'add':
            addProduct();
            break;
        case 'update':
            updateProduct();
            break;
        case 'delete':
            deleteProduct();
            break;
        case 'delete_all':
            deleteAllProducts();
            break;
        // Restaurant operations
        case 'get_restaurants':
            getRestaurants();
            break;
        case 'get_restaurant':
            getRestaurant();
            break;
        case 'add_restaurant':
            addRestaurant();
            break;
        case 'update_restaurant':
            updateRestaurant();
            break;
        case 'delete_restaurant':
            deleteRestaurant();
            break;
        case 'get_statistics':
            getStatistics();
            break;
        case 'get_restaurant_stats':
            getRestaurantStats();
            break;
        // Menu management
        case 'get_categories':
            getCategories();
            break;
        case 'add_category':
            addCategory();
            break;
        case 'update_category':
            updateCategory();
            break;
        case 'delete_category':
            deleteCategory();
            break;
        case 'get_sets':
            getSets();
            break;
        case 'add_set':
            addSet();
            break;
        case 'update_set':
            updateSet();
            break;
        case 'delete_set':
            deleteSet();
            break;
        case 'export_restaurant':
            exportRestaurant();
            break;
        case 'import_restaurant':
            importRestaurant();
            break;
        case 'get_product_templates':
            getProductTemplates();
            break;
        case 'add_product_from_template':
            addProductFromTemplate();
            break;
        case 'get_category_templates':
            getCategoryTemplates();
            break;
        case 'add_category_from_template':
            addCategoryFromTemplate();
            break;
        case 'add_categories_from_template':
            addCategoriesFromTemplate();
            break;
        case 'sync_all_to_pool':
            syncAllProductsToPool();
            break;
        case 'clear_product_templates':
            clearProductTemplates();
            break;
        default:
            api_ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid action'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    // Clear any output before sending JSON
    api_ob_clean();
    echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

// End output buffering and send response
ob_end_flush();

function getProducts() {
    try {
        $conn = getDBConnection();
        $category_id = $_GET['category_id'] ?? '';
        $restaurant_id = $_GET['restaurant_id'] ?? '';
        $id = $_GET['id'] ?? '';
        $lang = $_GET['lang'] ?? 'az';
        
        // Check if translation columns exist for products
        $checkColumns = $conn->query("SHOW COLUMNS FROM products LIKE 'name_az'");
        $hasProductTranslationColumns = $checkColumns && $checkColumns->num_rows > 0;
        
        // If translation columns don't exist, try to add them (one-time migration)
        if (!$hasProductTranslationColumns) {
            // This will be handled by config.php on next page load, but we can also try here
            try {
                $conn->query("ALTER TABLE products ADD COLUMN name_az VARCHAR(255) DEFAULT NULL AFTER name");
                $conn->query("ALTER TABLE products ADD COLUMN name_en VARCHAR(255) DEFAULT NULL AFTER name_az");
                $conn->query("ALTER TABLE products ADD COLUMN name_ru VARCHAR(255) DEFAULT NULL AFTER name_en");
                $conn->query("UPDATE products SET name_az = name WHERE name_az IS NULL OR name_az = ''");
                
                $conn->query("ALTER TABLE products ADD COLUMN description_az TEXT DEFAULT NULL AFTER description");
                $conn->query("ALTER TABLE products ADD COLUMN description_en TEXT DEFAULT NULL AFTER description_az");
                $conn->query("ALTER TABLE products ADD COLUMN description_ru TEXT DEFAULT NULL AFTER description_en");
                $conn->query("UPDATE products SET description_az = description WHERE description_az IS NULL AND description IS NOT NULL");
                
                $hasProductTranslationColumns = true;
            } catch (Exception $e) {
                // Columns might already exist or other error, ignore
                error_log("Translation columns migration: " . $e->getMessage());
            }
        }
        
        // Check if translation columns exist for categories
        $checkCatColumns = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
        $hasCategoryTranslationColumns = $checkCatColumns && $checkCatColumns->num_rows > 0;
        
        // If translation columns don't exist for categories, try to add them
        if (!$hasCategoryTranslationColumns) {
            try {
                $conn->query("ALTER TABLE categories ADD COLUMN name_az VARCHAR(255) DEFAULT NULL AFTER name");
                $conn->query("ALTER TABLE categories ADD COLUMN name_en VARCHAR(255) DEFAULT NULL AFTER name_az");
                $conn->query("ALTER TABLE categories ADD COLUMN name_ru VARCHAR(255) DEFAULT NULL AFTER name_en");
                $conn->query("UPDATE categories SET name_az = name WHERE name_az IS NULL OR name_az = ''");
                
                $hasCategoryTranslationColumns = true;
            } catch (Exception $e) {
                error_log("Category translation columns migration: " . $e->getMessage());
            }
        }
        
        // Always select all columns including translation columns for post-processing
        // This ensures we have all the data we need
        $nameColumn = "p.name";
        $descColumn = "p.description";
        
        // Category name translation
        if ($hasCategoryTranslationColumns) {
            if ($lang == 'az') {
                $categoryNameColumn = "COALESCE(NULLIF(TRIM(c.name_az), ''), c.name) as category_name";
            } else if ($lang == 'en') {
                $categoryNameColumn = "COALESCE(NULLIF(TRIM(c.name_en), ''), NULLIF(TRIM(c.name_az), ''), c.name) as category_name";
            } else if ($lang == 'ru') {
                $categoryNameColumn = "COALESCE(NULLIF(TRIM(c.name_ru), ''), NULLIF(TRIM(c.name_az), ''), c.name) as category_name";
            } else {
                $categoryNameColumn = "COALESCE(NULLIF(TRIM(c.name_az), ''), c.name) as category_name";
            }
        } else {
            $categoryNameColumn = "c.name as category_name";
        }
        
        // Build SQL query - always select all columns including translation columns if they exist
        if ($hasProductTranslationColumns) {
            $sql = "SELECT p.id, p.price, p.discount_price, p.category_id, p.image_path, p.popular, p.restaurant_id, p.created_at, 
                    p.name, p.name_az, p.name_en, p.name_ru,
                    p.description, p.description_az, p.description_en, p.description_ru,
                    $categoryNameColumn, c.icon as category_icon 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE 1=1";
        } else {
            $sql = "SELECT p.*, $categoryNameColumn, c.icon as category_icon 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE 1=1";
        }
        $params = [];
        $types = '';
        
        if ($id) {
            $sql .= " AND p.id = ?";
            $params[] = $id;
            $types .= 'i';
        }
        
        if ($restaurant_id) {
            $sql .= " AND p.restaurant_id = ?";
            $params[] = $restaurant_id;
            $types .= 'i';
        }
        
        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('SQL hazırlama xətası: ' . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('SQL icra xətası: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Post-process to apply translations based on language
            if ($hasProductTranslationColumns) {
                $originalName = $row['name'] ?? '';
                $originalDesc = $row['description'] ?? '';
                
                // Apply translation based on language with proper fallback
                if ($lang == 'en') {
                    // Try name_en first, then name_az, then original name
                    if (!empty($row['name_en']) && trim($row['name_en']) !== '') {
                        $row['name'] = trim($row['name_en']);
                    } else if (!empty($row['name_az']) && trim($row['name_az']) !== '') {
                        $row['name'] = trim($row['name_az']);
                    } else {
                        $row['name'] = $originalName;
                    }
                    
                    if (!empty($row['description_en']) && trim($row['description_en']) !== '') {
                        $row['description'] = trim($row['description_en']);
                    } else if (!empty($row['description_az']) && trim($row['description_az']) !== '') {
                        $row['description'] = trim($row['description_az']);
                    } else {
                        $row['description'] = $originalDesc;
                    }
                } else if ($lang == 'ru') {
                    // Try name_ru first, then name_az, then original name
                    if (!empty($row['name_ru']) && trim($row['name_ru']) !== '') {
                        $row['name'] = trim($row['name_ru']);
                    } else if (!empty($row['name_az']) && trim($row['name_az']) !== '') {
                        $row['name'] = trim($row['name_az']);
                    } else {
                        $row['name'] = $originalName;
                    }
                    
                    if (!empty($row['description_ru']) && trim($row['description_ru']) !== '') {
                        $row['description'] = trim($row['description_ru']);
                    } else if (!empty($row['description_az']) && trim($row['description_az']) !== '') {
                        $row['description'] = trim($row['description_az']);
                    } else {
                        $row['description'] = $originalDesc;
                    }
                } else {
                    // az or default - use name_az if available, otherwise original name
                    if (!empty($row['name_az']) && trim($row['name_az']) !== '') {
                        $row['name'] = trim($row['name_az']);
                    } else {
                        $row['name'] = $originalName;
                    }
                    
                    if (!empty($row['description_az']) && trim($row['description_az']) !== '') {
                        $row['description'] = trim($row['description_az']);
                    } else {
                        $row['description'] = $originalDesc;
                    }
                }
                
                // Remove translation columns from output (keep only final name and description)
                unset($row['name_az']);
                unset($row['name_en']);
                unset($row['name_ru']);
                unset($row['description_az']);
                unset($row['description_en']);
                unset($row['description_ru']);
            }
            $products[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        // Debug: Log first product to verify translation
        if (count($products) > 0 && isset($products[0])) {
            $firstProduct = $products[0];
            error_log("API getProducts: lang=$lang, hasTranslationColumns=" . ($hasProductTranslationColumns ? 'yes' : 'no') . ", first product name: " . ($firstProduct['name'] ?? 'N/A'));
            error_log("First product ID: " . ($firstProduct['id'] ?? 'N/A'));
        }
        
        echo json_encode(['success' => true, 'products' => $products], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->close();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Translation function using MyMemory Translation API (free)
function translateText($text, $targetLang) {
    if (empty($text) || trim($text) === '') {
        return $text;
    }
    
    // Language codes mapping for MyMemory API
    // Note: MyMemory uses 'az' for Azerbaijani, but if it doesn't work, we can try alternatives
    $langMap = [
        'az' => 'az|en',  // Azerbaijani to English (if direct az not supported, use en as intermediate)
        'en' => 'en',
        'ru' => 'ru'
    ];
    
    $sourceLang = 'az'; // Default source language (Azerbaijani)
    $targetLangCode = $targetLang;
    
    if ($targetLangCode === 'az') {
        return $text; // No need to translate to same language
    }
    
    // Build language pair for API
    // Try direct translation first: az->en or az->ru
    $langPair = $sourceLang . '|' . $targetLangCode;
    
    // MyMemory Translation API (free, with limits: 10000 words/day)
    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=" . $langPair;
    
    // Use cURL if available, otherwise file_get_contents
    // Optimized timeout for better performance during import
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout for translation
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BirMenu Translation Client');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || !empty($curlError)) {
            // Return original text on error (don't log to avoid spam during import)
            return $text;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 seconds timeout
                'ignore_errors' => true,
                'user_agent' => 'BirMenu Translation Client'
            ]
        ]);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            // Return original text on error
            return $text;
        }
    }
    
    $data = json_decode($response, true);
    
    // Check if translation was successful
    if (isset($data['responseData']['translatedText']) && !empty($data['responseData']['translatedText'])) {
        $translated = trim($data['responseData']['translatedText']);
        // Sometimes API returns the same text if translation fails, check if it's different
        if ($translated !== $text && $translated !== '') {
            return $translated;
        }
    }
    
    // Fallback: return original text if translation fails
    error_log("Translation failed for text: " . substr($text, 0, 50) . "... Target: $targetLangCode");
    return $text;
}

function addProduct() {
    $conn = getDBConnection();
    
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $discount_price = $_POST['discount_price'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $popular = isset($_POST['popular']) ? 1 : 0;
    $restaurant_id = $_POST['restaurant_id'] ?? null;
    
    // Validate inputs
    if (empty($name) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'Ad və qiymət tələb olunur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Get restaurant slug for image path
    $restaurantSlug = null;
    if ($restaurant_id) {
        $stmt = $conn->prepare("SELECT slug FROM restaurants WHERE id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $restaurantSlug = $result['slug'] ?? null;
        $stmt->close();
    }
    
    // Check if translation columns exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM products LIKE 'name_az'");
    $hasTranslationColumns = $checkColumns && $checkColumns->num_rows > 0;
    
    // Auto-translate product name and description
    $name_az = $name; // Original name is assumed to be in Azerbaijani
    $name_en = '';
    $name_ru = '';
    $description_az = $description;
    $description_en = '';
    $description_ru = '';
    
    if ($hasTranslationColumns) {
        // Translate name to English and Russian
        $name_en = translateText($name, 'en');
        $name_ru = translateText($name, 'ru');
        
        // Translate description if provided
        if (!empty($description)) {
            $description_en = translateText($description, 'en');
            $description_ru = translateText($description, 'ru');
        }
    }
    
    // Handle image upload - save to restaurant-specific folder
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($_FILES['image'], 'products/', $restaurantSlug);
    }
    
    // Insert with or without translation columns
    if ($hasTranslationColumns) {
        $stmt = $conn->prepare("INSERT INTO products (name, name_az, name_en, name_ru, description, description_az, description_en, description_ru, price, discount_price, category_id, image_path, popular, restaurant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssddissi", $name, $name_az, $name_en, $name_ru, $description, $description_az, $description_en, $description_ru, $price, $discount_price, $category_id, $imagePath, $popular, $restaurant_id);
    } else {
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount_price, category_id, image_path, popular, restaurant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddissi", $name, $description, $price, $discount_price, $category_id, $imagePath, $popular, $restaurant_id);
    }
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $categoryName = '';
        if ($category_id) {
            $cs = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $cs->bind_param("i", $category_id);
            $cs->execute();
            $cr = $cs->get_result()->fetch_assoc();
            if ($cr) $categoryName = $cr['name'] ?? '';
            $cs->close();
        }
        syncProductToPool($conn, $categoryName, $name, $description, $price, $discount_price, $imagePath, $popular);
        echo json_encode(['success' => true, 'message' => 'Məhsul uğurla əlavə edildi', 'id' => $newId], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
}

function updateProduct() {
    $conn = getDBConnection();
    
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $discount_price = $_POST['discount_price'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $popular = isset($_POST['popular']) ? 1 : 0;
    $restaurant_id = $_POST['restaurant_id'] ?? null;
    
    if (!$id || empty($name) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'ID, ad və qiymət tələb olunur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Get restaurant slug for image path
    $restaurantSlug = null;
    if ($restaurant_id) {
        $stmt = $conn->prepare("SELECT slug FROM restaurants WHERE id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $restaurantSlug = $result['slug'] ?? null;
        $stmt->close();
    }
    
    // Check if translation columns exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM products LIKE 'name_az'");
    $hasTranslationColumns = $checkColumns && $checkColumns->num_rows > 0;
    
    // Auto-translate product name and description
    $name_az = $name; // Original name is assumed to be in Azerbaijani
    $name_en = '';
    $name_ru = '';
    $description_az = $description;
    $description_en = '';
    $description_ru = '';
    
    if ($hasTranslationColumns) {
        // Translate name to English and Russian
        $name_en = translateText($name, 'en');
        $name_ru = translateText($name, 'ru');
        
        // Translate description if provided
        if (!empty($description)) {
            $description_en = translateText($description, 'en');
            $description_ru = translateText($description, 'ru');
        }
    }
    
    // Get current image
    $stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $imagePath = $current['image_path'] ?? null;
    
    // Handle image upload - save to restaurant-specific folder
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image
        if ($imagePath && file_exists($imagePath)) {
            unlink($imagePath);
        }
        $imagePath = uploadImage($_FILES['image'], 'products/', $restaurantSlug);
    }
    
    // Update with or without translation columns
    if ($hasTranslationColumns) {
        $stmt = $conn->prepare("UPDATE products SET name=?, name_az=?, name_en=?, name_ru=?, description=?, description_az=?, description_en=?, description_ru=?, price=?, discount_price=?, category_id=?, image_path=?, popular=?, restaurant_id=? WHERE id=?");
        $stmt->bind_param("ssssssssddissii", $name, $name_az, $name_en, $name_ru, $description, $description_az, $description_en, $description_ru, $price, $discount_price, $category_id, $imagePath, $popular, $restaurant_id, $id);
    } else {
    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, discount_price=?, category_id=?, image_path=?, popular=?, restaurant_id=? WHERE id=?");
    $stmt->bind_param("ssddissii", $name, $description, $price, $discount_price, $category_id, $imagePath, $popular, $restaurant_id, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Məhsul yeniləndi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
}

function deleteProduct() {
    $conn = getDBConnection();
    $id = $_GET['id'] ?? $_POST['id'] ?? 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Yanlış ID'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete image file if exists
        if ($row && $row['image_path'] && file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }
        echo json_encode(['success' => true, 'message' => 'Məhsul silindi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $conn->close();
}

function deleteAllProducts() {
    $conn = getDBConnection();
    
    // Get all image paths before deleting
    $result = $conn->query("SELECT image_path FROM products WHERE image_path IS NOT NULL");
    $imagePaths = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['image_path'] && file_exists($row['image_path'])) {
            $imagePaths[] = $row['image_path'];
        }
    }
    
    // Delete all products
    $sql = "DELETE FROM products";
    if ($conn->query($sql) === TRUE) {
        // Delete image files
        foreach ($imagePaths as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Bütün məhsullar silindi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $conn->close();
}

// =====================
// Restaurant Functions
// =====================

function getRestaurants() {
    try {
        // Clear any previous output
        api_ob_clean();
        
        $conn = getDBConnection();
        
        // Optimized query with LEFT JOIN to get product count in one query (no N+1 problem)
        // Only select needed fields for main page display
        $sql = "SELECT r.id, r.name, r.slug, r.logo_path, r.cover_path, r.is_active, r.created_at, r.view_count,
                COALESCE(COUNT(p.id), 0) as product_count
                FROM restaurants r
                LEFT JOIN products p ON r.id = p.restaurant_id
                GROUP BY r.id, r.name, r.slug, r.logo_path, r.cover_path, r.is_active, r.created_at, r.view_count
                ORDER BY r.created_at DESC";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception('SQL query failed: ' . $conn->error);
        }
        
        $restaurants = [];
        while ($row = $result->fetch_assoc()) {
            // Format image paths
            $row['logo_path'] = !empty($row['logo_path']) ? $row['logo_path'] : null;
            $row['cover_path'] = !empty($row['cover_path']) ? $row['cover_path'] : null;
            
            // Ensure all required fields have default values
            $row['name'] = $row['name'] ?? '';
            $row['slug'] = $row['slug'] ?? '';
            $row['is_active'] = isset($row['is_active']) ? (int)$row['is_active'] : 1;
            $row['product_count'] = (int)($row['product_count'] ?? 0);
            $row['view_count'] = isset($row['view_count']) ? (int)$row['view_count'] : 0;
            $row['created_at'] = $row['created_at'] ?? date('Y-m-d H:i:s');
            
            $restaurants[] = $row;
        }
        
        $conn->close();
        api_ob_clean();
        echo json_encode(['success' => true, 'restaurants' => $restaurants], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->close();
        }
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function getRestaurant() {
    $conn = getDBConnection();
    $id = $_GET['id'] ?? 0;
    $slug = $_GET['slug'] ?? '';
    $trackView = isset($_GET['track_view']) && $_GET['track_view'] === 'true';
    
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($slug) {
        $stmt = $conn->prepare("SELECT * FROM restaurants WHERE slug = ?");
        $stmt->bind_param("s", $slug);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID və ya slug lazımdır'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $restaurant = $result->fetch_assoc();
    
    $stmt->close();
    
    if ($restaurant && $trackView) {
        // Increment view count
        $updateStmt = $conn->prepare("UPDATE restaurants SET view_count = view_count + 1 WHERE id = ?");
        $updateStmt->bind_param("i", $restaurant['id']);
        $updateStmt->execute();
        $updateStmt->close();
        $restaurant['view_count'] = (int)$restaurant['view_count'] + 1;
    }
    
    $conn->close();
    
    if ($restaurant) {
        echo json_encode(['success' => true, 'restaurant' => $restaurant], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Restoran tapılmadı'], JSON_UNESCAPED_UNICODE);
    }
}

function addRestaurant() {
    $conn = getDBConnection();
    
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $description = $_POST['description'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $phone2 = $_POST['phone2'] ?? '';
    $phone3 = $_POST['phone3'] ?? '';
    $phone4 = $_POST['phone4'] ?? '';
    $wifi_name = $_POST['wifi_name'] ?? '';
    $wifi_password = $_POST['wifi_password'] ?? '';
    $login_username = trim($_POST['login_username'] ?? '');
    $login_password = $_POST['login_password'] ?? '';
    $instagram_url = $_POST['instagram_url'] ?? '';
    $facebook_url = $_POST['facebook_url'] ?? '';
    $whatsapp_url = $_POST['whatsapp_url'] ?? '';
    $tiktok_url = $_POST['tiktok_url'] ?? '';
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
    
    // Validate
    if (empty($name) || empty($slug)) {
        echo json_encode(['success' => false, 'message' => 'Ad və slug tələb olunur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Sanitize slug
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
    
    // Auto-generate restoran admin girişi (login panel üçün) - boşsa avtomatik yarad
    $generatedPassword = null;
    if (empty($login_username)) {
        $login_username = $slug;
    }
    if (empty($login_password)) {
        $login_password = bin2hex(random_bytes(5));
        $generatedPassword = $login_password;
    }
    
    // Check if slug exists
    $stmt = $conn->prepare("SELECT id FROM restaurants WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu slug artıq istifadə olunur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Handle logo upload
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoPath = uploadImage($_FILES['logo'], 'logos/', $slug);
    }
    
    // Handle cover upload - upload to restaurant folder
    $coverPath = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $coverPath = uploadImage($_FILES['cover'], 'covers/', $slug);
    }
    
    // Hash password if provided
    $hashedPassword = !empty($login_password) ? password_hash($login_password, PASSWORD_DEFAULT) : null;
    
    $stmt = $conn->prepare("INSERT INTO restaurants (name, slug, description, address, phone, phone2, phone3, phone4, logo_path, cover_path, wifi_name, wifi_password, login_username, login_password, instagram_url, facebook_url, whatsapp_url, tiktok_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssssssi", $name, $slug, $description, $address, $phone, $phone2, $phone3, $phone4, $logoPath, $coverPath, $wifi_name, $wifi_password, $login_username, $hashedPassword, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $is_active);
    
    if ($stmt->execute()) {
        $restaurantId = $conn->insert_id;
        
        // Create restaurant directory and HTML file
        createRestaurantDirectory($restaurantId, $slug, $name, $description, $address, $phone, $wifi_password, $logoPath, $coverPath, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $phone2, $phone3, $phone4);
        
        $response = ['success' => true, 'message' => 'Restoran əlavə edildi', 'id' => $restaurantId];
        if ($generatedPassword !== null) {
            $response['generated_credentials'] = [
                'login_username' => $login_username,
                'login_password' => $generatedPassword
            ];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $conn->close();
}

function updateRestaurant() {
    $conn = getDBConnection();
    
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $description = $_POST['description'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $phone2 = $_POST['phone2'] ?? '';
    $phone3 = $_POST['phone3'] ?? '';
    $phone4 = $_POST['phone4'] ?? '';
    $wifi_name = $_POST['wifi_name'] ?? '';
    $wifi_password = $_POST['wifi_password'] ?? '';
    $login_username = $_POST['login_username'] ?? '';
    $login_password = $_POST['login_password'] ?? '';
    $instagram_url = $_POST['instagram_url'] ?? '';
    $facebook_url = $_POST['facebook_url'] ?? '';
    $whatsapp_url = $_POST['whatsapp_url'] ?? '';
    $tiktok_url = $_POST['tiktok_url'] ?? '';
    $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
    
    if (!$id || empty($name) || empty($slug)) {
        echo json_encode(['success' => false, 'message' => 'ID, ad və slug tələb olunur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Sanitize slug
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
    
    // Check if slug exists for other restaurant
    $stmt = $conn->prepare("SELECT id FROM restaurants WHERE slug = ? AND id != ?");
    $stmt->bind_param("si", $slug, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bu slug artıq istifadə olunur']);
        return;
    }
    
    // Get current data
    $stmt = $conn->prepare("SELECT slug, logo_path, cover_path FROM restaurants WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    
    $oldSlug = $current['slug'];
    $logoPath = $current['logo_path'];
    $coverPath = $current['cover_path'];
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Delete old logo
        if ($logoPath && file_exists(toFsPath(toWebPath($logoPath)))) {
            unlink(toFsPath(toWebPath($logoPath)));
        }
        $logoPath = uploadImage($_FILES['logo'], 'logos/', $slug);
    }
    
    // Handle cover upload - upload to restaurant folder
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        // Delete old cover - check both old and new path formats
        if ($coverPath) {
            // Try new path format first (restaurant folder)
            if (file_exists(toFsPath(toWebPath($coverPath)))) {
                unlink(toFsPath(toWebPath($coverPath)));
            } else {
                // Try old path format (uploads/restaurants/covers/)
                $oldPath = 'uploads/restaurants/covers/' . basename($coverPath);
                if (file_exists(toFsPath($oldPath))) {
                    unlink(toFsPath($oldPath));
                }
            }
        }
        $coverPath = uploadImage($_FILES['cover'], 'covers/', $slug);
    }
    
    // Handle password update - only hash if new password is provided
    $passwordUpdate = '';
    $passwordValue = null;
    if (!empty($login_password)) {
        $passwordUpdate = ', login_password=?';
        $passwordValue = password_hash($login_password, PASSWORD_DEFAULT);
    }
    
    $sql = "UPDATE restaurants SET name=?, slug=?, description=?, address=?, phone=?, phone2=?, phone3=?, phone4=?, logo_path=?, cover_path=?, wifi_name=?, wifi_password=?, login_username=?" . $passwordUpdate . ", instagram_url=?, facebook_url=?, whatsapp_url=?, tiktok_url=?, is_active=? WHERE id=?";
    
    if (!empty($login_password)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssssssssii", $name, $slug, $description, $address, $phone, $phone2, $phone3, $phone4, $logoPath, $coverPath, $wifi_name, $wifi_password, $login_username, $passwordValue, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $is_active, $id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssssssssii", $name, $slug, $description, $address, $phone, $phone2, $phone3, $phone4, $logoPath, $coverPath, $wifi_name, $wifi_password, $login_username, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $is_active, $id);
    }
    
    if ($stmt->execute()) {
        // Always update the HTML file to ensure cover image is updated
        // If slug changed, delete old directory and create new one
        if ($oldSlug !== $slug) {
            // Delete old directory
            if ($oldSlug) {
                deleteDirectory($oldSlug);
            }
            
            // Create new directory with new slug
            createRestaurantDirectory($id, $slug, $name, $description, $address, $phone, $wifi_password, $logoPath, $coverPath, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $phone2, $phone3, $phone4);
        } else {
            // Just update the HTML file - always update to ensure cover image is refreshed
            createRestaurantDirectory($id, $slug, $name, $description, $address, $phone, $wifi_password, $logoPath, $coverPath, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $phone2, $phone3, $phone4);
        }
        
        echo json_encode(['success' => true, 'message' => 'Restoran yeniləndi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $conn->close();
}

function deleteRestaurant() {
    $conn = getDBConnection();
    $id = $_GET['id'] ?? $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID tələb olunur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Get restaurant data to delete
    $stmt = $conn->prepare("SELECT slug, logo_path, cover_path FROM restaurants WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $restaurant = $stmt->get_result()->fetch_assoc();
    
    if (!$restaurant) {
        echo json_encode(['success' => false, 'message' => 'Restoran tapılmadı']);
        return;
    }
    
    // Delete restaurant from database
    $stmt = $conn->prepare("DELETE FROM restaurants WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete images
        if ($restaurant['logo_path'] && file_exists(toFsPath(toWebPath($restaurant['logo_path'])))) {
            unlink(toFsPath(toWebPath($restaurant['logo_path'])));
        }
        if ($restaurant['cover_path'] && file_exists(toFsPath(toWebPath($restaurant['cover_path'])))) {
            unlink(toFsPath(toWebPath($restaurant['cover_path'])));
        }
        
        // Delete restaurant directory
        $restaurantDir = $restaurant['slug'];
        if ($restaurantDir) {
            deleteDirectory($restaurantDir);
        }

        // Mərkəzi bazadan (product_templates və category_templates) sil — yenidən qalan restoranlardan doldur
        $tables = $conn->query("SHOW TABLES LIKE 'product_templates'");
        if ($tables && $tables->num_rows > 0) {
            $conn->query("DELETE FROM product_templates");
            $catTpl = $conn->query("SHOW TABLES LIKE 'category_templates'");
            if ($catTpl && $catTpl->num_rows > 0) {
                $conn->query("DELETE FROM category_templates");
            }
            $res = $conn->query("
                SELECT p.id, p.name, p.description, p.price, p.discount_price, p.image_path, p.popular, c.name AS category_name, c.icon AS category_icon, c.display_order AS category_order
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
            ");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $catName = trim($row['category_name'] ?? '');
                    ensureCategoryTemplate($conn, $catName, $row['category_icon'] ?? '', (int)($row['category_order'] ?? 0));
                    syncProductToPool($conn, $catName, $row['name'], $row['description'] ?? '', $row['price'], $row['discount_price'], $row['image_path'] ?? '', (int)($row['popular'] ?? 0));
                }
            }
            $catRes = $conn->query("SELECT DISTINCT c.name, c.icon, c.display_order FROM categories c LEFT JOIN products p ON p.category_id = c.id WHERE p.id IS NULL");
            if ($catRes) {
                while ($catRow = $catRes->fetch_assoc()) {
                    $n = trim($catRow['name'] ?? '');
                    if ($n) ensureCategoryTemplate($conn, $n, $catRow['icon'] ?? '', (int)($catRow['display_order'] ?? 0));
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Restoran silindi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $conn->close();
}

function uploadImage($file, $subdir, $restaurantSlug = null) {
    // Always store under project root, but return web-relative path
    $subdir = toWebPath($subdir);
    if ($subdir !== '' && substr($subdir, -1) !== '/') {
        $subdir .= '/';
    }

    $relativeDir = $restaurantSlug
        ? toWebPath($restaurantSlug) . '/uploads/' . $subdir
        : 'uploads/' . $subdir;

    $fsDir = toFsPath($relativeDir);
    if (!file_exists($fsDir)) {
        mkdir($fsDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return null;
    }
    
    $fileName = uniqid() . '.' . $fileExtension;
    $targetFsPath = rtrim($fsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
    $targetWebPath = toWebPath($relativeDir . $fileName);
    
    if (move_uploaded_file($file['tmp_name'], $targetFsPath)) {
        return $targetWebPath;
    }
    
    return null;
}

function deleteDirectory($dir) {
    // Accept either a slug (relative) or an absolute path (used in recursion)
    $fsDir = $dir;
    if (!preg_match('/^[A-Za-z]:\\\\/', $dir) && strpos($dir, '/') !== 0 && strpos($dir, '\\\\') !== 0) {
        $fsDir = toFsPath($dir);
    }

    if (!is_dir($fsDir)) {
        return false;
    }
    
    $files = array_diff(scandir($fsDir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $fsDir . DIRECTORY_SEPARATOR . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($fsDir);
}

function createRestaurantDirectory($id, $slug, $name, $description, $address, $phone, $wifi, $logoPath, $coverPath, $instagram_url = '', $facebook_url = '', $whatsapp_url = '', $tiktok_url = '', $phone2 = '', $phone3 = '', $phone4 = '') {
    $slug = toWebPath($slug);

    // If a legacy folder was mistakenly created under /api/<slug>, move it to project root.
    $legacyDir = __DIR__ . DIRECTORY_SEPARATOR . $slug;
    $targetDir = toFsPath($slug);
    if (is_dir($legacyDir) && !is_dir($targetDir)) {
        @rename($legacyDir, $targetDir);
    }

    // Create directory in project root
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Read template from project root
    $template = file_get_contents(toFsPath('templates/restaurant_template.html'));
    
    // Logo HTML - logo is now in restaurant folder: restaurant-slug/uploads/logos/filename.png
    $logoHtml = '';
    if ($logoPath) {
        // Calculate base path
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api.php';
        // api.php lives in /api/, but restaurant pages live one level above
        $basePath = dirname(dirname($scriptName));
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '/';
        } else {
            $basePath = rtrim($basePath, '/') . '/';
        }
        
        $filename = basename($logoPath);
        // Absolute path: /birmenu/restaurant-slug/uploads/logos/filename.png
        $fullLogoPath = $basePath . $slug . '/uploads/logos/' . $filename;
        $logoHtml = '<img src="' . $fullLogoPath . '" alt="' . htmlspecialchars($name) . '">';
    } else {
        $logoHtml = '<img src="../assets/images/logo.png" alt="' . htmlspecialchars($name) . '">';
    }
    
    // Favicon HTML - logo is now in restaurant folder
    $faviconHtml = '';
    if ($logoPath) {
        // Calculate base path
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api.php';
        // api.php lives in /api/, but restaurant pages live one level above
        $basePath = dirname(dirname($scriptName));
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '/';
        } else {
            $basePath = rtrim($basePath, '/') . '/';
        }
        
        $filename = basename($logoPath);
        // Absolute path: /birmenu/restaurant-slug/uploads/logos/filename.png
        $fullLogoPath = $basePath . $slug . '/uploads/logos/' . $filename;
        $faviconHtml = '<link rel="icon" type="image/png" href="' . $fullLogoPath . '">';
    } else {
        $faviconHtml = '<link rel="icon" type="image/png" href="../assets/images/logo.png">';
    }
    
    // Restaurant info - with clickable links
    $addressEncoded = $address ? urlencode($address) : '';
    $phoneCleaned = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';
    $googleMapsUrl = $address ? 'https://www.google.com/maps/search/?api=1&query=' . $addressEncoded : '#';
    
    // Helper to build one phone clickable div
    $phoneToHtml = function($num) {
        if (empty($num)) return '';
        $cleaned = preg_replace('/[^0-9+]/', '', $num);
        return '<div class="clickable-info" onclick="window.location.href=\'tel:' . htmlspecialchars($cleaned) . '\'" style="cursor: pointer;" title="Zəng et"><i class="bi bi-telephone"></i> <span>' . htmlspecialchars($num) . '</span></div>';
    };
    $phoneToContactCard = function($num, $delay) {
        if (empty($num)) return '';
        $cleaned = preg_replace('/[^0-9+]/', '', $num);
        return '
        <div class="contact-card fade-in clickable-contact-card" style="--delay: ' . $delay . 's; cursor: pointer;" onclick="window.location.href=\'tel:' . htmlspecialchars($cleaned) . '\'" title="Zəng et">
            <div class="contact-icon">
                <i class="bi bi-telephone-fill"></i>
            </div>
            <h4>Telefon</h4>
            <p>' . htmlspecialchars($num) . '</p>
        </div>';
    };
    
    // Restaurant info - with icon and text visible (main phone + optional phone2, phone3, phone4)
    $addressHtml = $address ? '<div class="clickable-info" onclick="window.open(\'' . $googleMapsUrl . '\', \'_blank\')" style="cursor: pointer;" title="Google Maps-də aç"><i class="bi bi-geo-alt"></i> <span>' . htmlspecialchars($address) . '</span></div>' : '';
    $phoneHtml = $phoneToHtml($phone) . $phoneToHtml($phone2) . $phoneToHtml($phone3) . $phoneToHtml($phone4);
    $wifiHtml = $wifi ? '<div title="WiFi Şifrəsi"><i class="bi bi-wifi"></i> <span>WiFi: ' . htmlspecialchars($wifi) . '</span></div>' : '';
    
    // Contact cards - with clickable links (main phone + optional phone2, phone3, phone4)
    $contactAddress = $address ? '
        <div class="contact-card fade-in clickable-contact-card" style="--delay: 0.2s; cursor: pointer;" onclick="window.open(\'' . $googleMapsUrl . '\', \'_blank\')" title="Google Maps-də aç">
            <div class="contact-icon">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
            <h4>Ünvan</h4>
            <p>' . htmlspecialchars($address) . '</p>
        </div>' : '';
    
    $contactPhone = $phoneToContactCard($phone, 0.3) . $phoneToContactCard($phone2, 0.35) . $phoneToContactCard($phone3, 0.4) . $phoneToContactCard($phone4, 0.45);
    
    $contactWifi = $wifi ? '
        <div class="contact-card fade-in" style="--delay: 0.4s;">
            <div class="contact-icon">
                <i class="bi bi-wifi"></i>
            </div>
            <h4>WiFi Şifrəsi</h4>
            <p>' . htmlspecialchars($wifi) . '</p>
        </div>' : '';
    
    // Social media links HTML - Always create div, even if empty
    $socialMediaHtml = '<div class="restaurant-social-links">';
    if ($instagram_url) {
        $socialMediaHtml .= '<a href="' . htmlspecialchars($instagram_url) . '" target="_blank" rel="noopener noreferrer" class="social-link" title="Instagram"><i class="bi bi-instagram"></i></a>';
    }
    if ($facebook_url) {
        $socialMediaHtml .= '<a href="' . htmlspecialchars($facebook_url) . '" target="_blank" rel="noopener noreferrer" class="social-link" title="Facebook"><i class="bi bi-facebook"></i></a>';
    }
    if ($whatsapp_url) {
        $socialMediaHtml .= '<a href="' . htmlspecialchars($whatsapp_url) . '" target="_blank" rel="noopener noreferrer" class="social-link" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>';
    }
    if ($tiktok_url) {
        $socialMediaHtml .= '<a href="' . htmlspecialchars($tiktok_url) . '" target="_blank" rel="noopener noreferrer" class="social-link" title="TikTok"><i class="bi bi-tiktok"></i></a>';
    }
    $socialMediaHtml .= '</div>';
    
    // Cover background style - using CSS variable for proper blur effect
    $coverBackground = '';
    if ($coverPath && trim($coverPath) !== '') {
        // Cover image is now in restaurant folder: restaurant-slug/uploads/covers/filename.jpg
        // For HTML, we need relative path from restaurant subdirectory: uploads/covers/filename.jpg
        $filename = basename($coverPath);
        
        // Verify file exists (filesystem path)
        $coverPathWeb = toWebPath($coverPath);
        if (!file_exists(toFsPath($coverPathWeb))) {
            // Try alternative path (for backward compatibility)
            $altPath = $slug . '/uploads/covers/' . $filename;
            if (file_exists(toFsPath($altPath))) {
                $coverFile = $altPath;
            } else {
                $coverFile = null;
                error_log("Cover image not found: $coverPathWeb (tried: $altPath)");
            }
        } else {
            $coverFile = $coverPathWeb;
        }
        
        if ($coverFile) {
            // Calculate base path from script location
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/api.php';
            // api.php lives in /api/, but restaurant pages live one level above
            $basePath = dirname(dirname($scriptName));
            // Normalize base path
            if ($basePath === '/' || $basePath === '.') {
                $basePath = '/';
            } else {
                $basePath = rtrim($basePath, '/') . '/';
            }
            
            // Normalize cover file path - remove any leading slashes or base path
            $normalizedCoverPath = ltrim(str_replace('\\', '/', $coverFile), '/');
            
            // Check if path already contains restaurant slug
            $slugPattern = '/' . preg_quote($slug, '/') . '\//';
            $hasSlug = preg_match($slugPattern, $normalizedCoverPath);
            
            // Check if path already contains base path
            $basePathClean = trim($basePath, '/');
            $hasBasePath = !empty($basePathClean) && strpos($normalizedCoverPath, $basePathClean) === 0;
            
            // Build absolute path without duplication
            if ($hasBasePath) {
                // Path already has base path, check for duplicate slug
                $pathAfterBase = substr($normalizedCoverPath, strlen($basePathClean));
                $pathAfterBase = ltrim($pathAfterBase, '/');
                
                // Check if slug is duplicated
                if (preg_match('/^' . preg_quote($slug, '/') . '\/' . preg_quote($slug, '/') . '\//', $pathAfterBase)) {
                    // Remove duplicate slug
                    $pathAfterBase = preg_replace('/^' . preg_quote($slug, '/') . '\//', '', $pathAfterBase);
                }
                
                // Ensure slug is present once
                if (!preg_match('/^' . preg_quote($slug, '/') . '\//', $pathAfterBase)) {
                    $absolutePath = $basePath . $slug . '/' . $pathAfterBase;
                } else {
                    $absolutePath = $basePath . $pathAfterBase;
                }
            } else if ($hasSlug) {
                // Path has slug but no base path
                // Check for duplicate slug
                if (preg_match('/^' . preg_quote($slug, '/') . '\/' . preg_quote($slug, '/') . '\//', $normalizedCoverPath)) {
                    // Remove duplicate slug
                    $normalizedCoverPath = preg_replace('/^' . preg_quote($slug, '/') . '\//', '', $normalizedCoverPath);
                }
                $absolutePath = $basePath . $normalizedCoverPath;
            } else {
                // Path doesn't have slug, add it
                // Remove 'uploads/covers/' if it's at the start
                if (strpos($normalizedCoverPath, 'uploads/covers/') === 0) {
                    $absolutePath = $basePath . $slug . '/' . $normalizedCoverPath;
                } else {
                    // Just filename or relative path
                    $absolutePath = $basePath . $slug . '/uploads/covers/' . $filename;
                }
            }
            
            // Normalize path separators and remove any double slashes
            $absolutePath = preg_replace('#/+#', '/', $absolutePath);
            $absolutePath = str_replace('//', '/', $absolutePath);
            
            // Add cache busting parameter to ensure fresh image loads
            $filemtime = file_exists(toFsPath($coverFile)) ? filemtime(toFsPath($coverFile)) : time();
            $absolutePathWithCache = $absolutePath . '?v=' . $filemtime;
            
            $coverBackground = '--bg-image: url(\'' . $absolutePathWithCache . '\');';
            error_log("Cover image set in HTML: $absolutePathWithCache (base: $basePath, from: $coverPathWeb, normalized: $normalizedCoverPath, file exists: " . (file_exists(toFsPath($coverFile)) ? 'yes' : 'no') . ")");
        }
    } else {
        error_log("No cover path provided for restaurant: $slug");
    }
    
    // Replace placeholders
    $html = str_replace([
        '{{RESTAURANT_ID}}',
        '{{RESTAURANT_SLUG}}',
        '{{RESTAURANT_NAME}}',
        '{{RESTAURANT_DESCRIPTION}}',
        '{{RESTAURANT_ADDRESS}}',
        '{{RESTAURANT_PHONE}}',
        '{{RESTAURANT_WIFI}}',
        '{{LOGO}}',
        '{{CONTACT_ADDRESS}}',
        '{{CONTACT_PHONE}}',
        '{{CONTACT_WIFI}}',
        '{{COVER_BACKGROUND}}',
        '{{SOCIAL_MEDIA_LINKS}}',
        '{{FAVICON}}'
    ], [
        $id,
        $slug,
        htmlspecialchars($name),
        htmlspecialchars($description ?: 'Xoş gəlmisiniz'),
        $addressHtml,
        $phoneHtml,
        $wifiHtml,
        $logoHtml,
        $contactAddress,
        $contactPhone,
        $contactWifi,
        $coverBackground,
        $socialMediaHtml,
        $faviconHtml
    ], $template);
    
    // Write HTML file to project root/<slug>/index.html
    file_put_contents(toFsPath($slug . '/index.html'), $html);
    
    return true;
}

// Get Statistics
function getRestaurantStats() {
    try {
        $conn = getDBConnection();
        $restaurant_id = $_GET['restaurant_id'] ?? 0;
        
        if (!$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'Restaurant ID tələb olunur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Total categories
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM categories WHERE restaurant_id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_categories = (int)($result->fetch_assoc()['total'] ?? 0);
        
        // Total products
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE restaurant_id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_products = (int)($result->fetch_assoc()['total'] ?? 0);
        
        // Active products - check if is_active column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'is_active'");
        $active_products = 0;
        if ($checkColumn && $checkColumn->num_rows > 0) {
            // Column exists, use it
            $stmt = $conn->prepare("SELECT COUNT(*) as active FROM products WHERE restaurant_id = ? AND is_active = 1");
            $stmt->bind_param("i", $restaurant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $active_products = (int)($result->fetch_assoc()['active'] ?? 0);
        } else {
            // Column doesn't exist, count all products as active
            $stmt = $conn->prepare("SELECT COUNT(*) as active FROM products WHERE restaurant_id = ?");
            $stmt->bind_param("i", $restaurant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $active_products = (int)($result->fetch_assoc()['active'] ?? 0);
        }
        
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_categories' => $total_categories,
                'total_products' => $total_products,
                'active_products' => $active_products
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStatistics() {
    try {
        $conn = getDBConnection();
        
        // Total restaurants
        $result = $conn->query("SELECT COUNT(*) as total FROM restaurants");
        if (!$result) {
            throw new Exception('SQL query failed: ' . $conn->error);
        }
        $row = $result->fetch_assoc();
        $total = (int)($row['total'] ?? 0);
        
        // Active restaurants
        $result = $conn->query("SELECT COUNT(*) as active FROM restaurants WHERE is_active = 1");
        if (!$result) {
            throw new Exception('SQL query failed: ' . $conn->error);
        }
        $row = $result->fetch_assoc();
        $active = (int)($row['active'] ?? 0);
        
        // Inactive restaurants
        $inactive = $total - $active;
        
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'statistics' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->close();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Xəta: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Categories Functions
function getCategories() {
    global $pdo;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Verilənlər bazası bağlantısı yoxdur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    try {
        $restaurant_id = $_GET['restaurant_id'] ?? 0;
        $lang = $_GET['lang'] ?? 'az';
        
        if (!$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'Restaurant ID lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Check if translation columns exist
        try {
            $checkColumns = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'categories' AND column_name LIKE 'name_az'");
            $hasTranslationColumns = $checkColumns && $checkColumns->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            $hasTranslationColumns = false;
        }
        
        if ($hasTranslationColumns) {
            // Use translation columns if they exist
            // First try the requested language, then fallback to az, then to original name
            $nameColumn = "COALESCE(NULLIF(c.name_$lang, ''), NULLIF(c.name_az, ''), c.name) as name";
        } else {
            // Use regular column
            $nameColumn = "c.name";
        }
        
        $stmt = $pdo->prepare("
            SELECT c.*, $nameColumn, 
                   (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
            FROM categories c 
            WHERE c.restaurant_id = ? 
            ORDER BY c.display_order ASC, c.name ASC
        ");
        $stmt->execute([$restaurant_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'categories' => $categories], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function addCategory() {
    $conn = getDBConnection();
    
        $restaurant_id = $_POST['restaurant_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $icon = $_POST['icon'] ?? '';
        $display_order = $_POST['display_order'] ?? 0;
        
        if (!$restaurant_id || !$name) {
            echo json_encode(['success' => false, 'message' => 'Restaurant ID və ad lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
    // Check if translation columns exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
    $hasTranslationColumns = $checkColumns && $checkColumns->num_rows > 0;
    
    // Auto-translate category name
    $name_az = $name; // Original name is assumed to be in Azerbaijani
    $name_en = '';
    $name_ru = '';
    
    if ($hasTranslationColumns) {
        // Translate name to English and Russian
        $name_en = translateText($name, 'en');
        $name_ru = translateText($name, 'ru');
    }
    
    // Insert with or without translation columns
    if ($hasTranslationColumns) {
        $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, name_az, name_en, name_ru, icon, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $restaurant_id, $name, $name_az, $name_en, $name_ru, $icon, $display_order);
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, icon, display_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $restaurant_id, $name, $icon, $display_order);
    }
    
    if ($stmt->execute()) {
        ensureCategoryTemplate($conn, $name, $icon, $display_order);
        echo json_encode(['success' => true, 'message' => 'Kateqoriya əlavə edildi', 'id' => $conn->insert_id], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
}

function updateCategory() {
    $conn = getDBConnection();
    
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $icon = $_POST['icon'] ?? '';
        $display_order = $_POST['display_order'] ?? 0;
        
        if (!$id || !$name) {
            echo json_encode(['success' => false, 'message' => 'ID və ad lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
    // Check if translation columns exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
    $hasTranslationColumns = $checkColumns && $checkColumns->num_rows > 0;
    
    // Auto-translate category name
    $name_az = $name; // Original name is assumed to be in Azerbaijani
    $name_en = '';
    $name_ru = '';
    
    if ($hasTranslationColumns) {
        // Translate name to English and Russian
        $name_en = translateText($name, 'en');
        $name_ru = translateText($name, 'ru');
    }
    
    // Update with or without translation columns
    if ($hasTranslationColumns) {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, name_az = ?, name_en = ?, name_ru = ?, icon = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $name, $name_az, $name_en, $name_ru, $icon, $display_order, $id);
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $icon, $display_order, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Kateqoriya yeniləndi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
}

function deleteCategory() {
    $conn = getDBConnection();
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
        
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Kateqoriya silindi'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
}

// Sets Functions
function getSets() {
    global $pdo;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Verilənlər bazası bağlantısı yoxdur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    try {
        $restaurant_id = $_GET['restaurant_id'] ?? 0;
        if (!$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'Restaurant ID lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM product_sets WHERE restaurant_id = ? ORDER BY created_at DESC");
        $stmt->execute([$restaurant_id]);
        $sets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'sets' => $sets], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function addSet() {
    global $pdo;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Verilənlər bazası bağlantısı yoxdur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    try {
        $restaurant_id = $_POST['restaurant_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $image_path = '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!$restaurant_id || !$name || !$price) {
            echo json_encode(['success' => false, 'message' => 'Restaurant ID, ad və qiymət lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/sets/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'set_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_path = $filepath;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO product_sets (restaurant_id, name, description, price, image_path, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$restaurant_id, $name, $description, $price, $image_path, $is_active]);
        
        echo json_encode(['success' => true, 'message' => 'Set əlavə edildi', 'id' => $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function updateSet() {
    global $pdo;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Verilənlər bazası bağlantısı yoxdur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    try {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!$id || !$name || !$price) {
            echo json_encode(['success' => false, 'message' => 'ID, ad və qiymət lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/sets/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'set_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_path = $filepath;
            }
        }
        
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE product_sets SET name = ?, description = ?, price = ?, image_path = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $image_path, $is_active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE product_sets SET name = ?, description = ?, price = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $is_active, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Set yeniləndi'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

function deleteSet() {
    global $pdo;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Verilənlər bazası bağlantısı yoxdur'], JSON_UNESCAPED_UNICODE);
        return;
    }
    try {
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM product_sets WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Set silindi'], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Mərkəzi menyu bazasına kateqoriya əlavə et / tap — eyni ad varsa duplikat yaratmır
function ensureCategoryTemplate($conn, $name, $icon = '', $display_order = 0) {
    $name = trim($name ?? '');
    if ($name === '') return null;
    $stmt = $conn->prepare("SELECT id FROM category_templates WHERE TRIM(name) = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) return (int)$r['id'];
    $stmt = $conn->prepare("INSERT INTO category_templates (name, icon, display_order) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $icon, $display_order);
    $stmt->execute();
    return (int)$conn->insert_id;
}

// Məhsulu mərkəzi bazaya əlavə et (əl ilə və ya idxal zamanı) — eyni məhsul təkrar əlavə olunmur
function syncProductToPool($conn, $categoryName, $name, $description, $price, $discount_price, $image_path, $popular = 0) {
    $tables = $conn->query("SHOW TABLES LIKE 'product_templates'");
    if (!$tables || $tables->num_rows === 0) return;
    $categoryName = trim($categoryName ?? '');
    $catId = ensureCategoryTemplate($conn, $categoryName);
    $name = trim($name ?? '');
    if ($name === '') return;
    // Eyni kateqoriya + ad varsa duplikat yaratma (NULL-safe)
    $chk = $conn->prepare("SELECT id FROM product_templates WHERE (category_template_id <=> ?) AND TRIM(name) = ? LIMIT 1");
    $chk->bind_param("is", $catId, $name);
    $chk->execute();
    if ($chk->get_result()->fetch_assoc()) return;
    $image_path = $image_path ?? '';
    if ($discount_price === null || $discount_price === '') {
        $stmt = $conn->prepare("INSERT INTO product_templates (category_template_id, name, description, price, discount_price, image_path, popular) VALUES (?, ?, ?, ?, NULL, ?, ?)");
        $stmt->bind_param("issdsi", $catId, $name, $description, $price, $image_path, $popular);
    } else {
        $stmt = $conn->prepare("INSERT INTO product_templates (category_template_id, name, description, price, discount_price, image_path, popular) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddsi", $catId, $name, $description, $price, $discount_price, $image_path, $popular);
    }
    $stmt->execute();
}

// Bütün mərkəzi məhsul şablonlarını qaytar (dublikatsız — hər kateqoriya+ad üçün bir məhsul)
function getProductTemplates() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT pt.id, pt.name, pt.description, pt.price, pt.discount_price, pt.image_path, pt.popular,
                   ct.name AS category_name
            FROM product_templates pt
            LEFT JOIN category_templates ct ON ct.id = pt.category_template_id
            ORDER BY ct.display_order, ct.name, pt.name
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $seen = [];
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $key = ($row['category_name'] ?? '') . '|' . trim($row['name'] ?? '');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $list[] = $row;
        }
        $conn->close();
        echo json_encode(['success' => true, 'templates' => $list], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Şəkil faylını restoranın uploads/products qovluğuna kopyala, yeni web path qaytar
function copyImageToRestaurant($sourceWebPath, $restaurantSlug) {
    if (empty($sourceWebPath)) return null;
    $srcFs = toFsPath($sourceWebPath);
    if (!file_exists($srcFs) || !is_file($srcFs)) return null;
    $ext = strtolower(pathinfo($srcFs, PATHINFO_EXTENSION)) ?: 'jpg';
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) return null;
    $relativeDir = $restaurantSlug . '/uploads/products/';
    $fsDir = toFsPath($relativeDir);
    if (!is_dir($fsDir)) mkdir($fsDir, 0777, true);
    $fileName = uniqid() . '.' . $ext;
    $targetFs = $fsDir . DIRECTORY_SEPARATOR . $fileName;
    if (!copy($srcFs, $targetFs)) return null;
    return toWebPath($relativeDir . $fileName);
}

// Restoranda kateqoriyanı tap və ya əlavə et (adla)
function ensureRestaurantCategory($conn, $restaurant_id, $category_name, $icon = '', $display_order = 0) {
    $category_name = trim($category_name);
    if ($category_name === '') return null;
    $checkCols = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
    $hasTranslation = $checkCols && $checkCols->num_rows > 0;
    if ($hasTranslation) {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE restaurant_id = ? AND (TRIM(name) = ? OR TRIM(COALESCE(NULLIF(name_az, ''), name)) = ?) LIMIT 1");
        $stmt->bind_param("iss", $restaurant_id, $category_name, $category_name);
    } else {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE restaurant_id = ? AND TRIM(name) = ? LIMIT 1");
        $stmt->bind_param("is", $restaurant_id, $category_name);
    }
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) return (int)$r['id'];
    if ($hasTranslation) {
        $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, name_az, name_en, name_ru, icon, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $restaurant_id, $category_name, $category_name, $category_name, $category_name, $icon, $display_order);
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, icon, display_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $restaurant_id, $category_name, $icon, $display_order);
    }
    $stmt->execute();
    return (int)$conn->insert_id;
}

// Mərkəzi kateqoriya şablonlarını qaytar
function getCategoryTemplates() {
    try {
        $conn = getDBConnection();
        $tables = $conn->query("SHOW TABLES LIKE 'category_templates'");
        if (!$tables || $tables->num_rows === 0) {
            $conn->close();
            echo json_encode(['success' => true, 'templates' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        $result = $conn->query("SELECT id, name, icon, display_order FROM category_templates ORDER BY display_order ASC, name ASC");
        $list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        $conn->close();
        echo json_encode(['success' => true, 'templates' => $list], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Şablon kateqoriyasını restorana əlavə et
function addCategoryFromTemplate() {
    try {
        $template_id = (int)($_POST['template_id'] ?? $_GET['template_id'] ?? 0);
        $restaurant_id = (int)($_POST['restaurant_id'] ?? $_GET['restaurant_id'] ?? 0);
        if (!$template_id || !$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'template_id və restaurant_id tələb olunur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, name, icon, display_order FROM category_templates WHERE id = ?");
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        $template = $stmt->get_result()->fetch_assoc();
        if (!$template) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Kateqoriya şablonu tapılmadı'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $category_id = ensureRestaurantCategory($conn, $restaurant_id, trim($template['name']), $template['icon'] ?? '', (int)($template['display_order'] ?? 0));
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Kateqoriya əlavə edildi', 'id' => $category_id], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Birdən çox şablon kateqoriyasını restorana əlavə et
function addCategoriesFromTemplate() {
    try {
        $template_ids = $_POST['template_ids'] ?? [];
        if (is_string($template_ids)) $template_ids = json_decode($template_ids, true) ?: [];
        $restaurant_id = (int)($_POST['restaurant_id'] ?? $_GET['restaurant_id'] ?? 0);
        if (empty($template_ids) || !$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'template_ids və restaurant_id tələb olunur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $conn = getDBConnection();
        $added = 0;
        foreach ($template_ids as $tid) {
            $template_id = (int)$tid;
            if ($template_id <= 0) continue;
            $stmt = $conn->prepare("SELECT id, name, icon, display_order FROM category_templates WHERE id = ?");
            $stmt->bind_param("i", $template_id);
            $stmt->execute();
            $template = $stmt->get_result()->fetch_assoc();
            if ($template) {
                ensureRestaurantCategory($conn, $restaurant_id, trim($template['name']), $template['icon'] ?? '', (int)($template['display_order'] ?? 0));
                $added++;
            }
        }
        $conn->close();
        echo json_encode(['success' => true, 'message' => $added . ' kateqoriya əlavə edildi', 'added' => $added], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Şablon məhsulu restorana əlavə et (şəkil ilə bir yerdə) — kateqoriya məhsulla bir yerdə gəlir
function addProductFromTemplate() {
    try {
        $template_id = (int)($_POST['template_id'] ?? $_GET['template_id'] ?? 0);
        $restaurant_id = (int)($_POST['restaurant_id'] ?? $_GET['restaurant_id'] ?? 0);
        if (!$template_id || !$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'template_id və restaurant_id tələb olunur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $conn = getDBConnection();
        $stmt = $conn->prepare("
            SELECT pt.id, pt.name, pt.description, pt.price, pt.discount_price, pt.image_path, pt.popular,
                   ct.name AS category_name, ct.icon AS category_icon, ct.display_order AS category_order
            FROM product_templates pt
            LEFT JOIN category_templates ct ON ct.id = pt.category_template_id
            WHERE pt.id = ?
        ");
        $stmt->bind_param("i", $template_id);
        $stmt->execute();
        $template = $stmt->get_result()->fetch_assoc();
        if (!$template) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Şablon tapılmadı'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $override_category_id = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);
        if ($override_category_id > 0) {
            $chk = $conn->prepare("SELECT id FROM categories WHERE id = ? AND restaurant_id = ?");
            $chk->bind_param("ii", $override_category_id, $restaurant_id);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()) {
                $category_id = $override_category_id;
            }
        }
        if (!isset($category_id)) {
            $category_name = trim($template['category_name'] ?? '');
            $category_icon = $template['category_icon'] ?? '';
            $category_order = (int)($template['category_order'] ?? 0);
            $category_id = ensureRestaurantCategory($conn, $restaurant_id, $category_name, $category_icon, $category_order);
        }
        $stmt = $conn->prepare("SELECT slug FROM restaurants WHERE id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $rest = $stmt->get_result()->fetch_assoc();
        if (!$rest) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Restoran tapılmadı'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $slug = $rest['slug'];
        $name = trim($template['name'] ?? '');
        // Eyni məhsul (ad + kateqoriya) artıq bu restoranda varsa əlavə etmə
        $dupCheck = $conn->prepare("SELECT id FROM products WHERE restaurant_id = ? AND category_id = ? AND TRIM(name) = ? LIMIT 1");
        $dupCheck->bind_param("iis", $restaurant_id, $category_id, $name);
        $dupCheck->execute();
        if ($dupCheck->get_result()->fetch_assoc()) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'Bu məhsul artıq bu kateqoriyada mövcuddur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $imagePath = copyImageToRestaurant($template['image_path'], $slug);
        if ($template['image_path'] && !$imagePath) $imagePath = $template['image_path'];
        $description = $template['description'] ?? '';
        $price = (float)$template['price'];
        $discount_price = $template['discount_price'] !== null ? (float)$template['discount_price'] : null;
        $popular = (int)($template['popular'] ?? 0);
        $checkCols = $conn->query("SHOW COLUMNS FROM products LIKE 'name_az'");
        $hasTranslation = $checkCols && $checkCols->num_rows > 0;
        if ($hasTranslation) {
            $stmt = $conn->prepare("INSERT INTO products (name, name_az, name_en, name_ru, description, description_az, description_en, description_ru, price, discount_price, category_id, image_path, popular, restaurant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssddissi", $name, $name, $name, $name, $description, $description, $description, $description, $price, $discount_price, $category_id, $imagePath, $popular, $restaurant_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount_price, category_id, image_path, popular, restaurant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddissi", $name, $description, $price, $discount_price, $category_id, $imagePath, $popular, $restaurant_id);
        }
        $stmt->execute();
        $newId = $conn->insert_id;
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Məhsul əlavə edildi', 'id' => $newId], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Mərkəzi bazadakı bütün məhsul şablonlarını sil (product_templates)
function clearProductTemplates() {
    try {
        $conn = getDBConnection();
        $tables = $conn->query("SHOW TABLES LIKE 'product_templates'");
        if (!$tables || $tables->num_rows === 0) {
            $conn->close();
            echo json_encode(['success' => true, 'message' => 'Baza artıq boşdur', 'deleted' => 0], JSON_UNESCAPED_UNICODE);
            return;
        }
        $conn->query("DELETE FROM product_templates");
        $deleted = $conn->affected_rows;
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Bazadakı bütün məhsullar silindi', 'deleted' => $deleted], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Restoran məhsullarından mərkəzi bazanı doldur (dublikatsız)
function syncAllProductsToPool() {
    try {
        set_time_limit(120);
        $conn = getDBConnection();
        $tables = $conn->query("SHOW TABLES LIKE 'product_templates'");
        if (!$tables || $tables->num_rows === 0) {
            $conn->close();
            echo json_encode(['success' => false, 'message' => 'product_templates cədvəli yoxdur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $res = $conn->query("
            SELECT p.id, p.name, p.description, p.price, p.discount_price, p.image_path, p.popular, c.name AS category_name, c.icon AS category_icon, c.display_order AS category_order
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
        ");
        $count = 0;
        while ($row = $res->fetch_assoc()) {
            $catName = trim($row['category_name'] ?? '');
            ensureCategoryTemplate($conn, $catName, $row['category_icon'] ?? '', (int)($row['category_order'] ?? 0));
            syncProductToPool($conn, $catName, $row['name'], $row['description'] ?? '', $row['price'], $row['discount_price'], $row['image_path'] ?? '', (int)($row['popular'] ?? 0));
            $count++;
        }
        $catRes = $conn->query("SELECT DISTINCT c.name, c.icon, c.display_order FROM categories c LEFT JOIN products p ON p.category_id = c.id WHERE p.id IS NULL");
        if ($catRes) {
            while ($catRow = $catRes->fetch_assoc()) {
                $n = trim($catRow['name'] ?? '');
                if ($n) ensureCategoryTemplate($conn, $n, $catRow['icon'] ?? '', (int)($catRow['display_order'] ?? 0));
            }
        }
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Mərkəzi baza yeniləndi', 'synced' => $count], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($conn)) $conn->close();
        api_ob_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Export Restaurant as ZIP (data.json + images)
function exportRestaurant() {
    $tempDir = null;
    try {
        if (!class_exists('ZipArchive')) {
            echo json_encode(['success' => false, 'message' => 'ZIP dəstəyi yoxdur (ZipArchive)'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $conn = getDBConnection();
        $restaurant_id = $_GET['restaurant_id'] ?? $_POST['restaurant_id'] ?? 0;
        
        if (!$restaurant_id) {
            echo json_encode(['success' => false, 'message' => 'Restaurant ID lazımdır'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $restaurant = $result->fetch_assoc();
        
        if (!$restaurant) {
            echo json_encode(['success' => false, 'message' => 'Restoran tapılmadı'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        unset($restaurant['login_password']);
        
        $tempDir = getProjectRootPath() . DIRECTORY_SEPARATOR . 'temp_export_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $uploadsDir = $tempDir . DIRECTORY_SEPARATOR . 'uploads';
        $logosDir = $uploadsDir . DIRECTORY_SEPARATOR . 'logos';
        $coversDir = $uploadsDir . DIRECTORY_SEPARATOR . 'covers';
        $productsDir = $uploadsDir . DIRECTORY_SEPARATOR . 'products';
        foreach ([$logosDir, $coversDir, $productsDir] as $d) {
            if (!is_dir($d)) mkdir($d, 0777, true);
        }
        
        $logoExportPath = null;
        if (!empty($restaurant['logo_path'])) {
            $src = toFsPath($restaurant['logo_path']);
            if (file_exists($src)) {
                $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'png';
                $logoExportPath = 'uploads/logos/logo.' . $ext;
                copy($src, $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoExportPath));
            }
        }
        if ($logoExportPath) {
            $restaurant['logo_export_path'] = $logoExportPath;
        }
        unset($restaurant['logo_base64'], $restaurant['logo_filename']);
        
        $coverExportPath = null;
        if (!empty($restaurant['cover_path'])) {
            $src = toFsPath($restaurant['cover_path']);
            if (file_exists($src)) {
                $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'jpg';
                $coverExportPath = 'uploads/covers/cover.' . $ext;
                copy($src, $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $coverExportPath));
            }
        }
        if ($coverExportPath) {
            $restaurant['cover_export_path'] = $coverExportPath;
        }
        unset($restaurant['cover_base64'], $restaurant['cover_filename']);
        
        $stmt = $conn->prepare("SELECT * FROM categories WHERE restaurant_id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $categoriesResult = $stmt->get_result();
        $categories = [];
        while ($row = $categoriesResult->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $stmt = $conn->prepare("SELECT * FROM products WHERE restaurant_id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $productsResult = $stmt->get_result();
        $products = [];
        $productIndex = 0;
        while ($row = $productsResult->fetch_assoc()) {
            unset($row['image_base64'], $row['image_filename']);
            if (!empty($row['image_path'])) {
                $src = toFsPath($row['image_path']);
                if (file_exists($src)) {
                    $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'jpg';
                    $productIndex++;
                    $relPath = 'uploads/products/product_' . $productIndex . '.' . $ext;
                    copy($src, $tempDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath));
                    $row['image_export_path'] = $relPath;
                }
            }
            $products[] = $row;
        }
        
        $exportData = [
            'restaurant' => $restaurant,
            'categories' => $categories,
            'products' => $products,
            'export_date' => date('Y-m-d H:i:s'),
            'version' => '2.0'
        ];
        $jsonPath = $tempDir . DIRECTORY_SEPARATOR . 'data.json';
        file_put_contents($jsonPath, json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        $conn->close();
        
        $zipPath = getProjectRootPath() . DIRECTORY_SEPARATOR . 'temp_export_' . $restaurant['slug'] . '_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('ZIP yaradıla bilmədi');
        }
        $zip->addFile($jsonPath, 'data.json');
        $baseTemp = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($baseTemp));
                $relativePath = str_replace('\\', '/', $relativePath);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        
        if (!headers_sent()) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="restaurant_' . preg_replace('/[^a-z0-9_-]/i', '_', $restaurant['slug']) . '_' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
        }
        @unlink($zipPath);
        if ($tempDir && is_dir($tempDir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $f) {
                if ($f->isDir()) rmdir($f->getRealPath()); else unlink($f->getRealPath());
            }
            rmdir($tempDir);
        }
        exit;
    } catch (Exception $e) {
        if ($tempDir && is_dir($tempDir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $f) {
                if ($f->isDir()) @rmdir($f->getRealPath()); else @unlink($f->getRealPath());
            }
            @rmdir($tempDir);
        }
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// Import Restaurant (ZIP or JSON with images)
function importRestaurant() {
    $extractDir = null;
    try {
        set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '512M');
        api_ob_clean();
        
        $conn = getDBConnection();
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['import_file']['tmp_name'] ?? '')) {
            $err = $_FILES['import_file']['error'] ?? -1;
            $msg = 'Fayl yüklənmədi.';
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) $msg = 'Fayl çox böyükdür (upload_max_filesize/post_max_size limiti).';
            elseif ($err !== UPLOAD_ERR_OK && $err !== -1) $msg = 'Yükləmə xətası (kod: ' . $err . ').';
            echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $tmpPath = $_FILES['import_file']['tmp_name'];
        $fileName = $_FILES['import_file']['name'] ?? '';
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $zipHeader = @file_get_contents($tmpPath, false, null, 0, 2);
        $isZip = ($ext === 'zip') || ($zipHeader !== false && bin2hex($zipHeader) === '504b');
        
        if ($isZip && class_exists('ZipArchive')) {
            $extractDir = getProjectRootPath() . DIRECTORY_SEPARATOR . 'temp_import_' . uniqid();
            if (!is_dir($extractDir)) mkdir($extractDir, 0777, true);
            $zip = new ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                echo json_encode(['success' => false, 'message' => 'ZIP açıla bilmədi'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $zip->extractTo($extractDir);
            $zip->close();
            $jsonPath = $extractDir . DIRECTORY_SEPARATOR . 'data.json';
            if (!file_exists($jsonPath)) {
                if ($extractDir && is_dir($extractDir)) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($files as $f) { if ($f->isDir()) @rmdir($f->getRealPath()); else @unlink($f->getRealPath()); }
                    @rmdir($extractDir);
                }
                echo json_encode(['success' => false, 'message' => 'ZIP içində data.json tapılmadı'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $fileContent = file_get_contents($jsonPath);
        } else {
            $fileContent = file_get_contents($tmpPath);
        }
        
        $importData = json_decode($fileContent, true);
        
        if (!$importData || !isset($importData['restaurant'])) {
            echo json_encode(['success' => false, 'message' => 'Yanlış fayl formatı'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $restaurantData = $importData['restaurant'];
        $categories = $importData['categories'] ?? [];
        $products = $importData['products'] ?? [];
        
        $slug = $restaurantData['slug'] ?? '';
        if (empty($slug)) {
            echo json_encode(['success' => false, 'message' => 'Slug tələb olunur'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $stmt = $conn->prepare("SELECT id FROM restaurants WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $slug = $slug . '_' . time();
        }
        
        $name = $restaurantData['name'] ?? '';
        $description = $restaurantData['description'] ?? '';
        $address = $restaurantData['address'] ?? '';
        $phone = $restaurantData['phone'] ?? '';
        $phone2 = $restaurantData['phone2'] ?? '';
        $phone3 = $restaurantData['phone3'] ?? '';
        $phone4 = $restaurantData['phone4'] ?? '';
        $wifi_name = $restaurantData['wifi_name'] ?? '';
        $wifi_password = $restaurantData['wifi_password'] ?? '';
        $login_username = $restaurantData['login_username'] ?? '';
        $instagram_url = $restaurantData['instagram_url'] ?? '';
        $facebook_url = $restaurantData['facebook_url'] ?? '';
        $whatsapp_url = $restaurantData['whatsapp_url'] ?? '';
        $tiktok_url = $restaurantData['tiktok_url'] ?? '';
        $login_password = !empty($restaurantData['login_password']) ? password_hash($restaurantData['login_password'], PASSWORD_DEFAULT) : password_hash('restaurant123', PASSWORD_DEFAULT);
        $is_active = isset($restaurantData['is_active']) ? intval($restaurantData['is_active']) : 1;
        
        $logoPath = null;
        $coverPath = null;
        
        // Logo: from ZIP extract path or base64
        if ($extractDir && !empty($restaurantData['logo_export_path'])) {
            $src = $extractDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $restaurantData['logo_export_path']);
            if (file_exists($src)) {
                $logoDir = $slug . '/uploads/logos/';
                if (!file_exists(toFsPath($logoDir))) mkdir(toFsPath($logoDir), 0777, true);
                $logoFileName = uniqid() . '.' . (pathinfo($src, PATHINFO_EXTENSION) ?: 'png');
                $logoPath = $logoDir . $logoFileName;
                copy($src, toFsPath($logoPath));
            }
        } elseif (!empty($restaurantData['logo_base64']) && !empty($restaurantData['logo_filename'])) {
            $logoDir = $slug . '/uploads/logos/';
            if (!file_exists(toFsPath($logoDir))) mkdir(toFsPath($logoDir), 0777, true);
            $logoExtension = pathinfo($restaurantData['logo_filename'], PATHINFO_EXTENSION);
            $logoFileName = uniqid() . '.' . $logoExtension;
            $logoPath = $logoDir . $logoFileName;
            file_put_contents(toFsPath($logoPath), base64_decode($restaurantData['logo_base64']));
        }
        
        // Cover: from ZIP or base64
        if ($extractDir && !empty($restaurantData['cover_export_path'])) {
            $src = $extractDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $restaurantData['cover_export_path']);
            if (file_exists($src)) {
                $coverDir = $slug . '/uploads/covers/';
                if (!file_exists(toFsPath($coverDir))) mkdir(toFsPath($coverDir), 0777, true);
                $coverFileName = uniqid() . '.' . (pathinfo($src, PATHINFO_EXTENSION) ?: 'jpg');
                $coverPath = $coverDir . $coverFileName;
                copy($src, toFsPath($coverPath));
            }
        } elseif (!empty($restaurantData['cover_base64']) && !empty($restaurantData['cover_filename'])) {
            $coverDir = $slug . '/uploads/covers/';
            if (!file_exists(toFsPath($coverDir))) mkdir(toFsPath($coverDir), 0777, true);
            $coverExtension = pathinfo($restaurantData['cover_filename'], PATHINFO_EXTENSION);
            $coverFileName = uniqid() . '.' . $coverExtension;
            $coverPath = $coverDir . $coverFileName;
            file_put_contents(toFsPath($coverPath), base64_decode($restaurantData['cover_base64']));
        }
        
        $stmt = $conn->prepare("INSERT INTO restaurants (name, slug, description, address, phone, phone2, phone3, phone4, logo_path, cover_path, wifi_name, wifi_password, login_username, login_password, instagram_url, facebook_url, whatsapp_url, tiktok_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssi", $name, $slug, $description, $address, $phone, $phone2, $phone3, $phone4, $logoPath, $coverPath, $wifi_name, $wifi_password, $login_username, $login_password, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $is_active);
        
        if (!$stmt->execute()) {
            throw new Exception('Restoran əlavə edilə bilmədi: ' . $conn->error);
        }
        
        $newRestaurantId = $conn->insert_id;
        
        // Check if translation columns exist for categories
        $checkCatColumns = $conn->query("SHOW COLUMNS FROM categories LIKE 'name_az'");
        $hasCategoryTranslationColumns = $checkCatColumns && $checkCatColumns->num_rows > 0;
        
        // Create category mapping (old_id => new_id) və adlar (new_id => name)
        $categoryMap = [];
        $categoryNamesByNewId = [];
        
        // Insert categories
        foreach ($categories as $category) {
            $categoryName = $category['name'] ?? '';
            $icon = $category['icon'] ?? '';
            $display_order = $category['display_order'] ?? 0;
            $oldCategoryId = $category['id'];
            
            // Auto-translate category name
            // OPTIMIZATION: Use existing translations if available
            $name_az = $categoryName; // Original name is assumed to be in Azerbaijani
            $name_en = $category['name_en'] ?? ''; // Use existing translation if available
            $name_ru = $category['name_ru'] ?? ''; // Use existing translation if available
            
            // Skip auto-translation during import for speed
            if ($hasCategoryTranslationColumns && empty($name_en) && empty($name_ru)) {
                // User can translate later via admin panel
                $name_en = '';
                $name_ru = '';
            }
            
            // Insert with or without translation columns
            if ($hasCategoryTranslationColumns) {
                $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, name_az, name_en, name_ru, icon, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssi", $newRestaurantId, $categoryName, $name_az, $name_en, $name_ru, $icon, $display_order);
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, icon, display_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $newRestaurantId, $categoryName, $icon, $display_order);
            }
            
            if ($stmt->execute()) {
                $newCategoryId = $conn->insert_id;
                $categoryMap[$oldCategoryId] = $newCategoryId;
                $categoryNamesByNewId[$newCategoryId] = $categoryName;
                ensureCategoryTemplate($conn, $categoryName, $icon, $display_order);
            }
        }
        
        // Check if translation columns exist for products
        $checkProductColumns = $conn->query("SHOW COLUMNS FROM products LIKE 'name_az'");
        $hasProductTranslationColumns = $checkProductColumns && $checkProductColumns->num_rows > 0;
        
        // Check if is_active column exists in products table
        $checkIsActiveColumn = $conn->query("SHOW COLUMNS FROM products LIKE 'is_active'");
        $hasIsActiveColumn = $checkIsActiveColumn && $checkIsActiveColumn->num_rows > 0;
        
        // Insert products (BATCH PROCESSING for better performance)
        $productCount = count($products);
        $batchSize = 50; // Process 50 products at a time
        $processedCount = 0;
        
        foreach ($products as $index => $product) {
            // Reset execution time every 50 products to avoid timeout
            if ($processedCount % $batchSize === 0) {
                set_time_limit(120); // Reset timer
            }
            
            $productName = $product['name'] ?? '';
            $productDescription = $product['description'] ?? '';
            $price = $product['price'] ?? 0;
            $discount_price = $product['discount_price'] ?? null;
            $oldCategoryId = $product['category_id'] ?? null;
            $newCategoryId = isset($categoryMap[$oldCategoryId]) ? $categoryMap[$oldCategoryId] : null;
            $popular = isset($product['popular']) ? intval($product['popular']) : 0;
            
            // Auto-translate product name and description
            // OPTIMIZATION: Skip translation during import to save time
            // Translation can be done later via batch update
            $name_az = $productName; // Original name is assumed to be in Azerbaijani
            $name_en = $product['name_en'] ?? ''; // Use existing translation if available
            $name_ru = $product['name_ru'] ?? ''; // Use existing translation if available
            $description_az = $productDescription;
            $description_en = $product['description_en'] ?? ''; // Use existing translation if available
            $description_ru = $product['description_ru'] ?? ''; // Use existing translation if available
            
            // Only translate if translations are missing (to avoid API timeout)
            if ($hasProductTranslationColumns && empty($name_en) && empty($name_ru)) {
                // Skip auto-translation during import for speed
                // User can translate later via admin panel
                $name_en = ''; 
                $name_ru = '';
                $description_en = '';
                $description_ru = '';
            }
            
            // Product image: from ZIP extract path or base64
            $imagePath = null;
            if (!empty($extractDir) && !empty($product['image_export_path'])) {
                $src = $extractDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $product['image_export_path']);
                if (file_exists($src)) {
                    $productDir = $slug . '/uploads/products/';
                    if (!file_exists(toFsPath($productDir))) mkdir(toFsPath($productDir), 0777, true);
                    $imageFileName = uniqid() . '.' . (pathinfo($src, PATHINFO_EXTENSION) ?: 'jpg');
                    $imagePath = $productDir . $imageFileName;
                    copy($src, toFsPath($imagePath));
                }
            } elseif (!empty($product['image_base64']) && !empty($product['image_filename'])) {
                $productDir = $slug . '/uploads/products/';
                if (!file_exists(toFsPath($productDir))) mkdir(toFsPath($productDir), 0777, true);
                $imageExtension = pathinfo($product['image_filename'], PATHINFO_EXTENSION);
                $imageFileName = uniqid() . '.' . $imageExtension;
                $imagePath = $productDir . $imageFileName;
                file_put_contents(toFsPath($imagePath), base64_decode($product['image_base64']));
            }
            
            // Insert with or without translation columns and is_active
            if ($hasProductTranslationColumns && $hasIsActiveColumn) {
                $stmt = $conn->prepare("INSERT INTO products (name, name_az, name_en, name_ru, description, description_az, description_en, description_ru, price, discount_price, category_id, image_path, popular, restaurant_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $isActive = isset($product['is_active']) ? intval($product['is_active']) : 1;
                $stmt->bind_param("ssssssssddissii", $productName, $name_az, $name_en, $name_ru, $productDescription, $description_az, $description_en, $description_ru, $price, $discount_price, $newCategoryId, $imagePath, $popular, $newRestaurantId, $isActive);
            } elseif ($hasProductTranslationColumns) {
                $stmt = $conn->prepare("INSERT INTO products (name, name_az, name_en, name_ru, description, description_az, description_en, description_ru, price, discount_price, category_id, image_path, popular, restaurant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssddissi", $productName, $name_az, $name_en, $name_ru, $productDescription, $description_az, $description_en, $description_ru, $price, $discount_price, $newCategoryId, $imagePath, $popular, $newRestaurantId);
            } elseif ($hasIsActiveColumn) {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount_price, category_id, image_path, popular, restaurant_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $isActive = isset($product['is_active']) ? intval($product['is_active']) : 1;
                $stmt->bind_param("ssddissii", $productName, $productDescription, $price, $discount_price, $newCategoryId, $imagePath, $popular, $newRestaurantId, $isActive);
            } else {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount_price, category_id, image_path, popular, restaurant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssddissi", $productName, $productDescription, $price, $discount_price, $newCategoryId, $imagePath, $popular, $newRestaurantId);
            }
            $stmt->execute();
            $catName = $categoryNamesByNewId[$newCategoryId] ?? '';
            syncProductToPool($conn, $catName, $productName, $productDescription, $price, $discount_price, $imagePath, $popular);
            
            $processedCount++;
            
            // Log progress every 50 products
            if ($processedCount % $batchSize === 0) {
                error_log("Import progress: $processedCount / $productCount products imported");
            }
        }
        
        error_log("Import completed: $processedCount products imported successfully");
        
        // Create restaurant directory and HTML file
        createRestaurantDirectory($newRestaurantId, $slug, $name, $description, $address, $phone, $wifi_password, $logoPath, $coverPath, $instagram_url, $facebook_url, $whatsapp_url, $tiktok_url, $phone2, $phone3, $phone4);
        
        $conn->close();
        
        if ($extractDir && is_dir($extractDir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $f) {
                if ($f->isDir()) @rmdir($f->getRealPath()); else @unlink($f->getRealPath());
            }
            @rmdir($extractDir);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Restoran uğurla idxal edildi',
            'restaurant_id' => $newRestaurantId,
            'slug' => $slug
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if (isset($extractDir) && $extractDir && is_dir($extractDir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $f) {
                if ($f->isDir()) @rmdir($f->getRealPath()); else @unlink($f->getRealPath());
            }
            @rmdir($extractDir);
        }
        if (isset($conn)) {
            $conn->close();
        }
        echo json_encode(['success' => false, 'message' => 'Xəta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}


