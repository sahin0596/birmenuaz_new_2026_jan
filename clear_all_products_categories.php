<?php
/**
 * Bütün məhsulları və kateqoriyaları silir.
 * İstifadə: php clear_all_products_categories.php
 */
require_once __DIR__ . '/config.php';

$root = realpath(__DIR__);
function toFullPath($rel, $root) {
    $rel = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($rel, '/\\'));
    return $root . DIRECTORY_SEPARATOR . $rel;
}

$conn = getDBConnection();

// 1. Məhsul şəkillərini silməzdən əvvəl yadda saxla
$result = $conn->query("SELECT image_path FROM products WHERE image_path IS NOT NULL AND image_path != ''");
$imagePaths = [];
while ($row = $result->fetch_assoc()) {
    $path = trim($row['image_path'] ?? '');
    if (!$path) continue;
    $fullPath = toFullPath(preg_replace('#^\.\./#', '', $path), $root);
    if (file_exists($fullPath) && is_file($fullPath)) {
        $imagePaths[] = $fullPath;
    }
}

// 2. Bütün məhsulları sil
$conn->query("DELETE FROM products");
$productsDeleted = $conn->affected_rows;

// 3. Şəkil fayllarını sil
foreach ($imagePaths as $p) {
    if (file_exists($p)) @unlink($p);
}

// 4. Bütün kateqoriyaları sil
$conn->query("DELETE FROM categories");
$categoriesDeleted = $conn->affected_rows;

$conn->close();

echo "TAMAMLANDI.\n";
echo "- Silinən məhsullar: $productsDeleted\n";
echo "- Silinən kateqoriyalar: $categoriesDeleted\n";
