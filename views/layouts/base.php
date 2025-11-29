<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $title ?? 'SENAttend - Sistema de Asistencia SENA' ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <?= $additionalStyles ?? '' ?>
</head>
<body>
    <div class="wrapper">
        <?php if (isset($showHeader) && $showHeader): ?>
        <?php require __DIR__ . '/../components/header.php'; ?>
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

    <script src="<?= asset('js/common/app.js') ?>"></script>
    <?= $additionalScripts ?? '' ?>
</body>
</html>

