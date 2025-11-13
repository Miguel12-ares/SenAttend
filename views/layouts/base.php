<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $title ?? 'SENAttend - Sistema de Asistencia SENA' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <?= $additionalStyles ?? '' ?>
</head>
<body>
    <div class="wrapper">
        <?php if (isset($showHeader) && $showHeader): ?>
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <h1>SENAttend</h1>
                        <p class="subtitle">Sistema de Asistencia SENA</p>
                    </div>
                    <?php if (isset($user)): ?>
                    <nav class="nav">
                        <span class="user-info">Bienvenido, <?= htmlspecialchars($user['nombre']) ?></span>
                        <a href="/auth/logout" class="btn btn-secondary btn-sm">Cerrar Sesión</a>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <?php endif; ?>

        <main class="main-content">
            <?= $content ?? '' ?>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="/js/app.js"></script>
    <?= $additionalScripts ?? '' ?>
</body>
</html>

