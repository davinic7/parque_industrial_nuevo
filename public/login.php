<?php
/**
 * Login - Parque Industrial de Catamarca
 */
require_once __DIR__ . '/../config/config.php';

if ($auth->isLoggedIn()) {
    redirect($_SESSION['user_rol'] === 'empresa' ? EMPRESA_URL . '/dashboard.php' : MINISTERIO_URL . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Token de seguridad inválido. Intente nuevamente.';
    }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$error && (empty($email) || empty($password))) {
        $error = 'Complete todos los campos';
    } elseif (!$error) {
        $result = $auth->login($email, $password);
        if ($result['success']) {
            redirect($result['user']['rol'] === 'empresa' ? EMPRESA_URL . '/dashboard.php' : MINISTERIO_URL . '/dashboard.php');
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar - Parque Industrial de Catamarca</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            background: #0f2438;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .login-box { width: 100%; max-width: 400px; margin: auto; padding: 20px; }
        .login-card {
            background: #fff;
            border-radius: 14px;
            padding: 40px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.35);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand-name {
            font-family: 'Montserrat', system-ui, sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1b3a5c;
            letter-spacing: -0.01em;
            margin: 0;
        }
        .login-brand-sub {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #6b6460;
            margin-top: 4px;
        }
        .form-label { font-weight: 600; font-size: 0.88rem; color: #2e2a27; margin-bottom: 5px; }
        .form-control {
            padding: 11px 14px;
            border-radius: 6px;
            border: 1px solid #d9d3ca;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #1b3a5c;
            box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.12);
        }
        .btn-login {
            background: #c4601a;
            border: none;
            padding: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 6px;
            width: 100%;
            color: #fff;
            letter-spacing: 0.01em;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-login:hover {
            background: #e8813a;
            color: #fff;
            transform: translateY(-1px);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.55);
            font-size: 0.88rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: rgba(255,255,255,0.85); }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-card">
        <div class="login-brand">
            <i class="bi bi-building" style="font-size:1.75rem; color:#1b3a5c; opacity:0.85;"></i>
            <p class="login-brand-name">Parque Industrial</p>
            <p class="login-brand-sub">Catamarca</p>
        </div>

        <?php show_flash(); ?>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:6px; font-size:0.9rem;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" autocomplete="email" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-login">Ingresar</button>
        </form>

        <div class="text-center mt-4">
            <a href="recuperar.php" class="text-muted small">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
    <a href="<?= PUBLIC_URL ?>/" class="back-link"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a>
</div>
</body>
</html>
