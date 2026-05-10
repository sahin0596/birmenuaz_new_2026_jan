<?php
// Set UTF-8 encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

session_start();
require_once 'config.php';

// If already logged in, redirect to appropriate panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/admin.php');
    exit;
}
if (isset($_SESSION['restaurant_logged_in']) && $_SESSION['restaurant_logged_in'] === true) {
    header('Location: admin/restaurant_admin.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'İstifadəçi adı və şifrə tələb olunur!';
    } else {
        // Admin girişi: bu məlumatlarla admin panelə yönləndir
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: admin/admin.php');
            exit;
        }
        
        // Restoran girişi
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, name, slug, login_password FROM restaurants WHERE login_username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $restaurant = $result->fetch_assoc();
            
            if (password_verify($password, $restaurant['login_password'])) {
                $_SESSION['restaurant_logged_in'] = true;
                $_SESSION['restaurant_id'] = $restaurant['id'];
                $_SESSION['restaurant_name'] = $restaurant['name'];
                $_SESSION['restaurant_slug'] = $restaurant['slug'];
                $_SESSION['user_role'] = 'restaurant_admin';
                
                header('Location: admin/restaurant_admin.php');
                exit;
            }
        }
        
        $stmt->close();
        $conn->close();
        $error = 'İstifadəçi adı və ya şifrə yanlışdır!';
    }
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daxil ol - 1menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: transparent;
            color: var(--text-primary);
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .login-bg-iframe {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            z-index: 0;
            display: block;
        }

        .login-bg-blur {
            position: fixed;
            inset: 0;
            z-index: 1;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.12);
            pointer-events: none;
        }

        [data-theme="dark"] .login-bg-blur {
            background: rgba(0, 0, 0, 0.15);
        }

        .login-overlay {
            position: fixed;
            inset: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-container {
            width: 100%;
            max-width: 380px;
            position: relative;
            z-index: 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            position: relative;
        }

        [data-theme="dark"] .login-card {
            background: rgba(30, 30, 30, 0.88);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-header h1 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: -0.02em;
        }

        .login-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            background: var(--primary-color);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 6px;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-family: inherit;
            color: var(--text-primary);
        }

        [data-theme="dark"] .form-group input {
            background: rgba(50, 50, 50, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        [data-theme="dark"] .form-group input:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .error-message {
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.25);
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login {
            width: 100%;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s ease, transform 0.1s ease;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            opacity: 0.92;
        }

        .btn-login:active {
            transform: scale(0.99);
        }

        .back-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--dark-border);
        }

        .back-link a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: var(--primary-color);
        }

        .theme-toggle-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .theme-toggle {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 12px;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        [data-theme="dark"] .theme-toggle {
            background: rgba(30, 30, 30, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
                border-radius: 14px;
            }

            .login-header h1 {
                font-size: 1.2rem;
            }

            .login-icon {
                width: 48px;
                height: 48px;
                font-size: 22px;
                border-radius: 12px;
            }

            .theme-toggle-container {
                top: 12px;
                right: 12px;
            }

            .theme-toggle {
                width: 36px;
                height: 36px;
                font-size: 16px;
                border-radius: 10px;
            }
        }
    </style>
    <script>
        // Apply theme immediately on page load
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }

        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
            }
        }

        // Initialize theme icon
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            updateThemeIcon(currentTheme);
        });
    </script>
</head>
<body>
    <iframe class="login-bg-iframe" src="./" title="Ana səhifə"></iframe>
    <div class="login-bg-blur" aria-hidden="true"></div>
    <div class="theme-toggle-container">
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Rəng rejimini dəyişdir" title="Rəng rejimi">
            <i class="bi bi-moon-fill" id="themeIcon"></i>
        </button>
    </div>
    <div class="login-overlay">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h1>Restoran Girişi</h1>
                </div>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">İstifadəçi adı</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="İstifadəçi adınızı daxil edin" 
                            required 
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Şifrə</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Şifrənizi daxil edin" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-login">
                        Daxil ol
                    </button>
                </form>

                <div class="back-link">
                    <a href="./">Ana səhifə</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>



