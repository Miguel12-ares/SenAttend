<?php
/** @var array $user */
/** @var array|null $ultimoProcesamiento */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR de Equipo - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/portero/escanear.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'portero-escanear';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="portero-escanear">
                    <section class="portero-escanear-header">
                        <div>
                            <h1><i class="fas fa-qrcode"></i> Escanear QR de Equipo</h1>
                            <p>Escanea el código QR del equipo para registrar ingreso o salida</p>
                        </div>
                        <div class="portero-actions">
                            <a href="/portero/panel" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Volver al panel
                            </a>
                        </div>
                    </section>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($ultimoProcesamiento): ?>
                        <div class="ultimo-procesamiento">
                            <h3>Última operación registrada:</h3>
                            <div class="procesamiento-info">
                                <p><strong>Equipo:</strong> <?= htmlspecialchars($ultimoProcesamiento['equipo']['marca'] ?? '') ?> - <?= htmlspecialchars($ultimoProcesamiento['equipo']['numero_serial'] ?? '') ?></p>
                                <p><strong>Aprendiz:</strong> <?= htmlspecialchars($ultimoProcesamiento['aprendiz']['nombre'] ?? '') ?> <?= htmlspecialchars($ultimoProcesamiento['aprendiz']['apellido'] ?? '') ?></p>
                                <p><strong>Fecha/Hora:</strong> <?= htmlspecialchars($ultimoProcesamiento['fecha'] ?? '') ?> <?= htmlspecialchars($ultimoProcesamiento['hora'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <section class="portero-escanear-card">
                        <div class="escanear-options">
                            <div class="scanner-container">
                                <div id="reader"></div>
                                <div id="scanResult" class="scan-result"></div>
                                <div class="scanner-controls">
                                    <button type="button" id="btnIniciarScanner" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Iniciar Escáner
                                    </button>
                                    <button type="button" id="btnDetenerScanner" class="btn btn-danger" style="display: none;">
                                        <i class="fas fa-stop"></i> Detener
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Historial de ingresos -->
                    <section class="portero-escanear-card" id="historialCard">
                        <h2><i class="fas fa-history"></i> Historial de Operaciones</h2>
                        <div id="estadisticasContainer" class="estadisticas-container"></div>
                        <div id="historialContainer" class="historial-container"></div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- Librería html5-qrcode -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/portero/escanear.js') ?>"></script>
</body>
</html>

