<?php
/** @var array $aprendiz */
/** @var array $qrInfo */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR del Equipo - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $user = null;
        $currentPage = 'aprendiz-equipo-qr';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="aprendiz-dashboard">
                    <section class="aprendiz-dashboard-header">
                        <div>
                            <h1>Código QR de tu equipo</h1>
                            <p>Puedes descargar o capturar este código para presentarlo en el CTA.</p>
                        </div>
                        <div class="aprendiz-actions">
                            <a href="/aprendiz/panel" class="btn btn-outline btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver al panel
                            </a>
                        </div>
                    </section>

                    <section class="aprendiz-equipos-card" style="text-align:center;">
                        <h2>QR del equipo</h2>
                        <p style="margin-bottom:1rem;">
                            Token: <code><?= htmlspecialchars($qrInfo['token']) ?></code>
                        </p>
                        <div style="display:flex;justify-content:center;margin-bottom:1.5rem;">
                            <img src="<?= $qrInfo['image_base64'] ?>" alt="QR del equipo" style="max-width:300px;">
                        </div>
                        <p style="font-size:0.9rem;color:#666;">
                            Generado: <?= htmlspecialchars($qrInfo['fecha_generacion']) ?><br>
                            Expira: <?= htmlspecialchars($qrInfo['fecha_expiracion'] ?? 'Sin expiración definida') ?>
                        </p>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>


