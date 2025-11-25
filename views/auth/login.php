<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SENAttend</title>
    <link rel="stylesheet" href="/assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/components/header-public.css">
    <link rel="stylesheet" href="/css/login.css">
</head>
<body class="login-page">
    <?php include __DIR__ . '/../components/header-public.php'; ?>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>SENAttend</h1>
                <p>Sistema de Asistencia SENA</p>
            </div>

            <?php if (isset($error) && $error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (isset($message) && $message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form action="/auth/login" method="POST" id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="ejemplo@sena.edu.co"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Ingresar
                </button>
            </form>
        </div>
    </div>

    <script src="/js/app.js"></script>
    <script>
        // Validación simple del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Por favor complete todos los campos');
                return false;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                alert('Por favor ingrese un email válido');
                return false;
            }
        });
    </script>
</body>
</html>

