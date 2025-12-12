<?php
/**
 * Vista para Escanear Código QR - Instructores
 * Permite a los instructores escanear códigos QR y registrar asistencia
 */

// Variables para el layout
$title = 'Escanear Código QR - SENAttend';
$additionalStyles = asset_css('css/modules/qr.css');
$showHeader = true;
$currentPage = 'qr-escanear';

// Configuración de turnos (mapa jornada -> datos de turno) pasada desde el controlador
$turnosConfig = $turnosConfig ?? [];

// Obtener usuario de sesión (pasado desde el controlador)
$user = $user ?? null;

ob_start();
?>

<div class="qr-container">
    <div class="qr-header">
        <h1>
            <i class="fas fa-camera"></i>
            Escanear Código QR
        </h1>
        <p class="subtitle">Escanea el código QR del aprendiz para registrar su asistencia</p>
    </div>

    <!-- Selector de ficha -->
    <div class="qr-card config-card">
        <h2>1. Selecciona la Ficha</h2>
        <!-- Buscador en vivo por número de ficha -->
        <div class="form-group">
            <label for="fichaSearch">Buscar número de ficha</label>
            <input
                type="text"
                id="fichaSearch"
                placeholder="Escribe el número de ficha para filtrar..."
                autocomplete="off"
            >
        </div>

        <div class="form-group">
            <label for="fichaSelect">Ficha Activa</label>
            <select id="fichaSelect" name="ficha_id" required>
                <option value="">Selecciona una ficha...</option>
                <?php foreach ($fichas as $ficha): ?>
                    <?php
                        $jornadaFicha = $ficha['jornada'] ?? null;
                        $horaLimite = null;
                        if ($jornadaFicha && !empty($turnosConfig[$jornadaFicha]['hora_limite_llegada'])) {
                            $horaLimite = $turnosConfig[$jornadaFicha]['hora_limite_llegada'];
                        }
                    ?>
                    <option
                        value="<?= htmlspecialchars($ficha['id']) ?>"
                        data-jornada="<?= htmlspecialchars($jornadaFicha ?? '') ?>"
                        data-hora-limite="<?= htmlspecialchars($horaLimite ?? '') ?>"
                    >
                        <?= htmlspecialchars($ficha['numero_ficha']) ?> - <?= htmlspecialchars($ficha['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="info-banner">
            <i class="fas fa-circle-info"></i>
            <span>
                Fecha de registro: <strong><?= date('d/m/Y') ?></strong>
                | Hora límite tardanza: <strong id="horaLimiteTardanzaTexto">--</strong>
            </span>
        </div>
    </div>

    <!-- Escáner -->
    <div id="scannerCard" class="qr-card scanner-card" style="display: none;">
        <h2>2. Escanear Código QR</h2>
        <div class="scanner-container">
            <div id="reader"></div>
            <div class="scanner-controls">
                <button id="btnIniciarScanner" class="btn btn-primary">
                    <i class="fas fa-play"></i>
                    Iniciar Escáner
                </button>
                <button id="btnDetenerScanner" class="btn btn-danger" style="display: none;">
                    <i class="fas fa-stop"></i>
                    Detener
                </button>
            </div>
        </div>

        <div id="scanResult" class="scan-result"></div>
    </div>

    <!-- Historial de escaneos -->
    <div id="historialCard" class="qr-card history-card" style="display: none;">
        <h2>3. Registro de Asistencias</h2>
        <div id="historialContainer" class="historial-container">
            <!-- Se llenará con JavaScript -->
        </div>
        
        <div class="estadisticas" id="estadisticas">
            <!-- Se llenará con JavaScript -->
        </div>
    </div>
</div>

<!-- Librería html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// Inicializar fecha desde PHP
window.fechaHoy = '<?= date('Y-m-d') ?>';
</script>
<script src="<?= asset('js/modules/qr-escanear.js') ?>"></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

