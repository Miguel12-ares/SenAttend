<?php
/**
 * Vista: Importación CSV de Aprendices
 * Funcionalidades: input file CSV, FormData POST, mostrar progreso, tabla resumen errores
 */

$title = 'Importar Aprendices CSV - SENAttend';
$showHeader = true;
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/aprendices-import.css');

ob_start();
?>

<div class="container">
    <div class="page-header">
        <h1>Importar Aprendices desde CSV</h1>
        <div class="page-actions">
            <a href="/aprendices" class="btn btn-secondary">← Volver a Aprendices</a>
        </div>
    </div>

    <!-- Mensajes de feedback -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors'])): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <!-- Formulario de importación -->
    <div class="import-container">
        <div class="import-step" id="step1">
            <h2>Paso 1: Seleccionar Archivo y Ficha</h2>
            
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="ficha_id">Seleccionar Ficha *</label>
                    <select name="ficha_id" id="ficha_id" class="form-control" required>
                        <option value="">-- Seleccione una ficha --</option>
                        <?php foreach ($fichas as $ficha): ?>
                            <option value="<?= $ficha['id'] ?>">
                                <?= htmlspecialchars($ficha['numero_ficha']) ?> - <?= htmlspecialchars($ficha['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Archivo CSV *</label>
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="file-upload-icon"><i class="fas fa-file"></i></div>
                        <div class="file-upload-text">
                            <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                            <small>Formato: documento, nombres, apellidos, email, numero_ficha</small>
                        </div>
                        <input 
                            type="file" 
                            id="csv_file" 
                            name="csv_file" 
                            accept=".csv" 
                            required
                        >
                    </div>
                    <div id="fileInfo" style="display: none;" class="file-selected">
                        <div>
                            <div class="file-selected-name" id="fileName"></div>
                            <div class="file-selected-size" id="fileSize"></div>
                        </div>
                        <button type="button" class="file-remove" onclick="clearFile()"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Formato del CSV:</strong><br>
                    • Primera línea: encabezados (documento, nombres, apellidos, email, numero_ficha)<br>
                    • Documento: 6-20 dígitos numéricos únicos<br>
                    • Email: formato válido y único (opcional)<br>
                    • Código carnet: alfanumérico (opcional)<br>
                    • Los aprendices duplicados serán omitidos
                </div>

                <div class="form-actions">
                    <button type="button" onclick="validarArchivo()" class="btn btn-info">
                        <i class="fas fa-magnifying-glass"></i> Validar Archivo
                    </button>
                    <button type="button" onclick="iniciarImportacion()" class="btn btn-primary">
                        <i class="fas fa-folder-open"></i> Importar Aprendices
                    </button>
                </div>
            </form>
        </div>

        <!-- Paso 2: Progreso de importación -->
        <div class="import-step" id="step2" style="display: none;">
            <h2>Paso 2: Procesando Importación</h2>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Iniciando...</div>
            </div>

            <div class="import-status" id="importStatus">
                <div class="status-item">
                    <span class="status-label">Validando archivo:</span>
                    <span class="status-value" id="statusValidation"><i class="fas fa-hourglass"></i> Pendiente</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Procesando registros:</span>
                    <span class="status-value" id="statusProcessing"><i class="fas fa-hourglass"></i> Pendiente</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Guardando en base de datos:</span>
                    <span class="status-value" id="statusSaving"><i class="fas fa-hourglass"></i> Pendiente</span>
                </div>
            </div>
        </div>

        <!-- Paso 3: Resultados -->
        <div class="import-step" id="step3" style="display: none;">
            <h2>Paso 3: Resultados de la Importación</h2>
            
            <div class="results-summary" id="resultsSummary">
                <!-- Se llena dinámicamente -->
            </div>

            <div class="results-details" id="resultsDetails">
                <!-- Tabla de errores si los hay -->
            </div>

            <div class="form-actions">
                <button type="button" onclick="nuevaImportacion()" class="btn btn-secondary">
                    <i class="fas fa-rotate"></i> Nueva Importación
                </button>
                <a href="/aprendices" class="btn btn-primary">
                    <i class="fas fa-check"></i> Ver Aprendices
                </a>
            </div>
        </div>
    </div>
</div>


<script src="<?= asset('js/components.js') ?>"></script>
<script src="<?= asset('js/modules/aprendices-import.js') ?>"></script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
