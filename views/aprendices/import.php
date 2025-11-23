<?php
/**
 * Vista: Importación CSV de Aprendices
 * Funcionalidades: input file CSV, FormData POST, mostrar progreso, tabla resumen errores
 */

$title = 'Importar Aprendices CSV - SENAttend';
$showHeader = true;

ob_start();
?>

<link rel="stylesheet" href="/css/components.css">

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
                        <div class="file-upload-icon">📄</div>
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
                        <button type="button" class="file-remove" onclick="clearFile()">×</button>
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
                        🔍 Validar Archivo
                    </button>
                    <button type="button" onclick="iniciarImportacion()" class="btn btn-primary">
                        📂 Importar Aprendices
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
                    <span class="status-value" id="statusValidation">⏳ Pendiente</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Procesando registros:</span>
                    <span class="status-value" id="statusProcessing">⏳ Pendiente</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Guardando en base de datos:</span>
                    <span class="status-value" id="statusSaving">⏳ Pendiente</span>
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
                    🔄 Nueva Importación
                </button>
                <a href="/aprendices" class="btn btn-primary">
                    ✅ Ver Aprendices
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.import-container {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.import-step {
    margin-bottom: 2rem;
}

.import-step h2 {
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--color-primary);
}

.file-upload-area {
    border: 2px dashed var(--color-gray-300);
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--color-gray-50);
}

.file-upload-area:hover {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}

.file-upload-area.dragover {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}

.file-upload-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.file-upload-text {
    color: var(--color-gray-600);
}

.file-upload-text strong {
    color: var(--color-primary);
}

.file-selected {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--color-success-light);
    border: 1px solid var(--color-success);
    border-radius: 4px;
    margin-top: 1rem;
}

.file-selected-name {
    font-weight: bold;
    color: var(--color-success-dark);
}

.file-selected-size {
    font-size: 0.9rem;
    color: var(--color-gray-600);
}

.file-remove {
    background: var(--color-danger);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
}

.progress-container {
    margin: 2rem 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: var(--color-gray-200);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-primary), var(--color-success));
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-weight: bold;
    color: var(--color-primary);
}

.import-status {
    background: var(--color-gray-50);
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-gray-200);
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: bold;
}

.status-value {
    font-family: monospace;
}

.results-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.result-card {
    background: var(--color-gray-50);
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid var(--color-primary);
}

.result-card.success {
    border-left-color: var(--color-success);
}

.result-card.warning {
    border-left-color: var(--color-warning);
}

.result-card.error {
    border-left-color: var(--color-danger);
}

.result-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.result-label {
    color: var(--color-gray-600);
    font-size: 0.9rem;
}

.error-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.error-table th,
.error-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--color-gray-200);
}

.error-table th {
    background: var(--color-gray-100);
    font-weight: bold;
}

.error-table tr:hover {
    background: var(--color-gray-50);
}

/* Colores SENA */
:root {
    --color-sena-green: #39A900;
    --color-sena-orange: #FF8C00;
    --color-primary: var(--color-sena-green);
    --color-primary-light: rgba(57, 169, 0, 0.1);
    --color-success: #28a745;
    --color-success-light: rgba(40, 167, 69, 0.1);
    --color-success-dark: #1e7e34;
    --color-warning: var(--color-sena-orange);
    --color-danger: #dc3545;
    --color-gray-50: #f8f9fa;
    --color-gray-100: #e9ecef;
    --color-gray-200: #dee2e6;
    --color-gray-300: #ced4da;
    --color-gray-600: #6c757d;
}
</style>

<script src="/js/components.js"></script>
<script>
// ==============================================
// MANEJO DE ARCHIVO
// ==============================================

document.getElementById('csv_file')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        document.getElementById('fileInfo').style.display = 'flex';
    }
});

function clearFile() {
    document.getElementById('csv_file').value = '';
    document.getElementById('fileInfo').style.display = 'none';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Drag and drop
const uploadArea = document.querySelector('.file-upload-area');
if (uploadArea) {
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('csv_file').files = files;
            const event = new Event('change');
            document.getElementById('csv_file').dispatchEvent(event);
        }
    });
}

// ==============================================
// VALIDACIÓN DE ARCHIVO
// ==============================================

async function validarArchivo() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);

    if (!formData.get('csv_file')?.name) {
        Notification.error('Seleccione un archivo CSV');
        return;
    }

    if (!formData.get('ficha_id')) {
        Notification.error('Seleccione una ficha');
        return;
    }

    updateStatus('statusValidation', '⏳ Validando...', 'info');
    
    try {
        const result = await API.post('/api/aprendices/validar-csv', formData);
        
        if (result.success && result.data.valid) {
            if (result.data.tiene_errores) {
                updateStatus('statusValidation', '⚠️ Con advertencias', 'warning');
                
                const errores = result.data.errores.slice(0, 10).join('<br>');
                await Confirm.show(
                    'Advertencias de Validación',
                    `<div style="text-align: left; max-height: 300px; overflow-y: auto;">${errores}</div>`,
                    {
                        confirmText: 'Entendido',
                        confirmClass: 'btn-info'
                    }
                );
            } else {
                updateStatus('statusValidation', '✅ Válido', 'success');
                Notification.success(`✓ Archivo válido: ${result.data.aprendices_validos} aprendices listos para importar`);
            }
        } else {
            updateStatus('statusValidation', '❌ Error', 'error');
            const error = result.error || result.data?.errors?.join(', ') || 'Error de validación';
            Notification.error(error);
        }
    } catch (error) {
        updateStatus('statusValidation', '❌ Error', 'error');
        Notification.error('Error al validar el archivo');
    }
}

// ==============================================
// IMPORTACIÓN
// ==============================================

async function iniciarImportacion() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);

    if (!formData.get('csv_file')?.name) {
        Notification.error('Seleccione un archivo CSV');
        return;
    }

    if (!formData.get('ficha_id')) {
        Notification.error('Seleccione una ficha');
        return;
    }

    const confirmado = await Confirm.show(
        'Confirmar Importación',
        '¿Desea proceder con la importación de aprendices?',
        {
            confirmText: 'Importar',
            confirmClass: 'btn-primary'
        }
    );

    if (!confirmado) return;

    // Mostrar paso 2
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';

    // Simular progreso
    updateProgress(10, 'Validando archivo...');
    updateStatus('statusValidation', '⏳ Validando...', 'info');

    try {
        // Paso 1: Validación
        await new Promise(resolve => setTimeout(resolve, 1000));
        updateProgress(30, 'Archivo validado correctamente');
        updateStatus('statusValidation', '✅ Completado', 'success');

        // Paso 2: Procesamiento
        updateStatus('statusProcessing', '⏳ Procesando...', 'info');
        updateProgress(50, 'Procesando registros...');

        const result = await API.post('/api/aprendices/importar', formData);
        
        await new Promise(resolve => setTimeout(resolve, 1000));
        updateProgress(80, 'Registros procesados');
        updateStatus('statusProcessing', '✅ Completado', 'success');

        // Paso 3: Guardado
        updateStatus('statusSaving', '⏳ Guardando...', 'info');
        updateProgress(90, 'Guardando en base de datos...');

        await new Promise(resolve => setTimeout(resolve, 500));
        updateProgress(100, 'Importación completada');
        updateStatus('statusSaving', '✅ Completado', 'success');

        // Mostrar resultados
        setTimeout(() => {
            mostrarResultados(result.data);
        }, 1000);

    } catch (error) {
        updateStatus('statusProcessing', '❌ Error', 'error');
        updateStatus('statusSaving', '❌ Error', 'error');
        Notification.error('Error durante la importación');
    }
}

function updateProgress(percentage, text) {
    document.getElementById('progressFill').style.width = percentage + '%';
    document.getElementById('progressText').textContent = text;
}

function updateStatus(elementId, text, type) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
        element.className = 'status-value ' + type;
    }
}

function mostrarResultados(data) {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'block';

    const summary = document.getElementById('resultsSummary');
    const details = document.getElementById('resultsDetails');

    // Resumen
    summary.innerHTML = `
        <div class="result-card success">
            <div class="result-number">${data.imported || 0}</div>
            <div class="result-label">Aprendices Importados</div>
        </div>
        <div class="result-card warning">
            <div class="result-number">${data.skipped || 0}</div>
            <div class="result-label">Registros Omitidos</div>
        </div>
        <div class="result-card error">
            <div class="result-number">${data.errors?.length || 0}</div>
            <div class="result-label">Errores Encontrados</div>
        </div>
    `;

    // Detalles de errores
    if (data.errors && data.errors.length > 0) {
        let errorsHtml = '<h3>Errores Detallados</h3>';
        errorsHtml += '<table class="error-table">';
        errorsHtml += '<thead><tr><th>Línea</th><th>Error</th></tr></thead><tbody>';
        
        data.errors.forEach((error, index) => {
            errorsHtml += `<tr><td>${index + 1}</td><td>${error}</td></tr>`;
        });
        
        errorsHtml += '</tbody></table>';
        details.innerHTML = errorsHtml;
    }

    Notification.success(data.message || 'Importación completada');
}

function nuevaImportacion() {
    document.getElementById('step3').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    
    // Limpiar formulario
    document.getElementById('importForm').reset();
    clearFile();
    
    // Resetear estados
    updateProgress(0, 'Listo para nueva importación');
    updateStatus('statusValidation', '⏳ Pendiente', '');
    updateStatus('statusProcessing', '⏳ Pendiente', '');
    updateStatus('statusSaving', '⏳ Pendiente', '');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
