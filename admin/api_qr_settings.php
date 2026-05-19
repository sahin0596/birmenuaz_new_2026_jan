<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz']);
    exit;
}

$dataDir = __DIR__ . '/data';
$settingsFile = $dataDir . '/qr_settings.json';

$defaultSettings = [
    'logo_option' => 'default',
    'logo_path' => '',
    'corner_radius' => 'sharp',
    'qr_color' => '000000',
    'qr_bgcolor' => 'ffffff'
];

function loadSettings($file, $default) {
    if (!file_exists($file)) return $default;
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? array_merge($default, $data) : $default;
}

function saveSettings($file, $data, $dataDir) {
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    return file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get') {
    $settings = loadSettings($settingsFile, $defaultSettings);
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}

if ($action === 'save') {
    $logoOption = $_POST['logo_option'] ?? 'default';
    $cornerRadius = $_POST['corner_radius'] ?? 'sharp';
    $qrColor = $_POST['qr_color'] ?? '';
    $qrBgcolor = $_POST['qr_bgcolor'] ?? '';
    
    $settings = loadSettings($settingsFile, $defaultSettings);
    $settings['qr_color'] = preg_match('/^[a-fA-F0-9]{6}$/', str_replace('#', '', $qrColor)) ? str_replace('#', '', $qrColor) : '000000';
    $settings['qr_bgcolor'] = preg_match('/^[a-fA-F0-9]{6}$/', str_replace('#', '', $qrBgcolor)) ? str_replace('#', '', $qrBgcolor) : 'ffffff';
    $settings['logo_option'] = in_array($logoOption, ['default', 'logo_dark', 'logo.png', 'custom']) ? $logoOption : 'default';
    $settings['corner_radius'] = in_array($cornerRadius, ['sharp', 'small', 'medium', 'large']) ? $cornerRadius : 'sharp';
    
    if ($logoOption === 'custom' && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        finfo_close($finfo);
        
        if (in_array($mime, $allowed)) {
            $uploadDir = realpath(__DIR__ . '/../assets/images');
            $uploadFile = $uploadDir . '/qr_logo.png';
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadFile)) {
                $settings['logo_path'] = 'assets/images/qr_logo.png';
            }
        }
    } elseif ($logoOption === 'logo_dark') {
        $settings['logo_path'] = 'assets/images/logo_dark.png';
    } elseif ($logoOption === 'logo.png') {
        $settings['logo_path'] = 'assets/images/logo.png';
    } elseif ($logoOption === 'default') {
        $settings['logo_path'] = '';
    }
    
    if (saveSettings($settingsFile, $settings, $dataDir)) {
        echo json_encode(['success' => true, 'settings' => $settings]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Yadda saxlanılmadı']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
