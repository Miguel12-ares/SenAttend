<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error del servidor</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/errors.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-content">
            <h1 class="error-code">500</h1>
            <h2 class="error-title">Error interno del servidor</h2>
            <p class="error-message">
                Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.
            </p>
            <?php if (defined('APP_ENV') && APP_ENV === 'local' && isset($message)): ?>
            <div class="error-details">
                <h3>Detalles del error (solo visible en desarrollo):</h3>
                <pre><?= htmlspecialchars($message) ?></pre>
            </div>
            <?php endif; ?>
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Ir al Inicio</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Volver</a>
            </div>
        </div>
    </div>
</body>
</html>

