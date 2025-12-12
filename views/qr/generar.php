<?php
/**
 * Vista para Generar Código QR - Aprendices
 * Permite a los aprendices generar su código QR personal
 */

// Variables para el layout
$title = 'Generar Código QR - SENAttend';
$additionalStyles = asset_css('css/modules/qr.css');
$showHeader = true;
$currentPage = 'qr-generar';

// Obtener usuario de sesión (pasado desde el controlador)
$user = $user ?? null;

ob_start();
?>

<div class="qr-container">
    <div class="qr-header">
        <h1>
            <i class="fas fa-qrcode"></i>
            Generar Código QR
        </h1>
        <p class="subtitle">Genera tu código QR personal para registrar asistencia</p>
    </div>

    <!-- Formulario de búsqueda -->
    <div class="qr-card search-card">
        <h2>1. Buscar Aprendiz</h2>
        <div class="search-form">
            <div class="form-group">
                <label for="documento">Número de Documento</label>
                <input 
                    type="text" 
                    id="documento" 
                    name="documento"
                    placeholder="Ingresa tu número de documento"
                    autocomplete="off"
                    maxlength="20"
                >
            </div>
            <button id="btnBuscar" class="btn btn-primary">
                <i class="fas fa-magnifying-glass"></i>
                Buscar
            </button>
        </div>

        <div id="resultadoBusqueda" class="resultado-busqueda" style="display: none;">
            <!-- Se llenará con JavaScript -->
        </div>
    </div>

    <!-- Información del aprendiz -->
    <div id="infoAprendiz" class="qr-card info-card" style="display: none;">
        <h2>2. Información del Aprendiz</h2>
        <div class="info-content">
            <!-- Se llenará con JavaScript -->
        </div>
    </div>

    <!-- Generador de QR -->
    <div id="generadorQR" class="qr-card generator-card" style="display: none;">
        <h2>3. Tu Código QR</h2>
        <div class="qr-content">
            <div id="qrCodeContainer" class="qr-display">
                <!-- El QR se generará aquí -->
            </div>
            <div class="qr-actions">
                <button id="btnDescargar" class="btn btn-success">
                    <i class="fas fa-download"></i>
                    Descargar QR
                </button>
                <button id="btnNuevo" class="btn btn-secondary">
                    <i class="fas fa-rotate"></i>
                    Generar Nuevo
                </button>
            </div>
        </div>

        <div class="qr-instructions">
            <h3>¿Cómo usar tu código QR?</h3>
            <ol>
                <li>Descarga o guarda una captura de pantalla de tu código QR</li>
                <li>Muestra tu código QR al instructor cuando ingreses a clase</li>
                <li>El instructor escaneará tu código y se registrará tu asistencia automáticamente</li>
            </ol>
            <p class="note"><i class="fas fa-lightbulb"></i> <strong>Importante:</strong> Este código QR es personal e intransferible. No lo compartas con otros aprendices.</p>
        </div>
    </div>
</div>

<!-- Modal de Error -->
<div id="modalError" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-error">
        <div class="modal-header">
            <i class="fas fa-xmark-circle modal-icon"></i>
            <h3 id="modalErrorTitulo">Error</h3>
        </div>
        <div class="modal-body">
            <p id="modalErrorMensaje">Ha ocurrido un error</p>
        </div>
        <div class="modal-footer">
            <button id="btnCerrarModalError" class="btn btn-primary">
                <i class="fas fa-times"></i>
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div id="modalExito" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-success">
        <div class="modal-header">
            <i class="fas fa-check-circle modal-icon"></i>
            <h3>¡Descarga Exitosa!</h3>
        </div>
        <div class="modal-body">
            <p>Tu código QR ha sido descargado correctamente. Puedes encontrarlo en tu carpeta de descargas.</p>
            <p class="modal-timer">Se cerrará automáticamente en <strong id="countdown">5</strong> segundos...</p>
        </div>
        <div class="modal-footer">
            <button id="btnCerrarModalExito" class="btn btn-success">
                <i class="fas fa-check"></i>
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Librerías -->
<script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.9.2/lib/qr-code-styling.js"></script>
<script src="<?= asset('js/modules/qr-generar.js') ?>"></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

