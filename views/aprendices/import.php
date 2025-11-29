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

    updateStatus('statusValidation', '<i class="fas fa-hourglass"></i> Validando...', 'info');
    
    try {
        const result = await API.post('/api/aprendices/validar-csv', formData);
        
        // Manejar diferentes formatos de respuesta
        const responseData = result.data || result;
        
        if (result.success && responseData.valid) {
            if (responseData.tiene_errores) {
                updateStatus('statusValidation', '<i class="fas fa-triangle-exclamation"></i> Con advertencias', 'warning');
                
                const errores = (responseData.errores || []);
                const totalErrores = errores.length;
                const aprendicesValidos = responseData.aprendices_validos || 0;
                
                let mensajeAdvertencia = `<strong>Archivo válido con advertencias:</strong><br>`;
                mensajeAdvertencia += `• ${aprendicesValidos} aprendices válidos para importar<br>`;
                mensajeAdvertencia += `• ${totalErrores} advertencias encontradas<br><br>`;
                mensajeAdvertencia += `<strong>Detalles:</strong><br>`;
                mensajeAdvertencia += errores.slice(0, 15).join('<br>');
                if (totalErrores > 15) {
                    mensajeAdvertencia += `<br>... y ${totalErrores - 15} advertencias más`;
                }
                
                await Confirm.show(
                    'Advertencias de Validación',
                    `<div style="text-align: left; max-height: 400px; overflow-y: auto; padding: 10px;">${mensajeAdvertencia}</div>`,
                    {
                        confirmText: 'Continuar de todas formas',
                        confirmClass: 'btn-primary',
                        cancelText: 'Cancelar'
                    }
                );
            } else {
                updateStatus('statusValidation', '<i class="fas fa-check"></i> Válido', 'success');
                Notification.success(`<i class="fas fa-check"></i> Archivo válido: ${responseData.aprendices_validos || 0} aprendices listos para importar`);
            }
        } else {
            updateStatus('statusValidation', '<i class="fas fa-xmark"></i> Error', 'error');
            const errors = responseData.errors || responseData.errores || [];
            const errorMsg = result.error || (Array.isArray(errors) ? errors.join(', ') : (errors || 'Error de validación'));
            Notification.error(errorMsg);
        }
    } catch (error) {
        console.error('Error en validación:', error);
        updateStatus('statusValidation', '<i class="fas fa-xmark"></i> Error', 'error');
        Notification.error('Error al validar el archivo: ' + (error.message || 'Error desconocido'));
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
    updateStatus('statusValidation', '<i class="fas fa-hourglass"></i> Validando...', 'info');

    try {
        // Paso 1: Validación
        await new Promise(resolve => setTimeout(resolve, 1000));
        updateProgress(30, 'Archivo validado correctamente');
        updateStatus('statusValidation', '<i class="fas fa-check"></i> Completado', 'success');

        // Paso 2: Procesamiento
        updateStatus('statusProcessing', '<i class="fas fa-hourglass"></i> Procesando...', 'info');
        updateProgress(50, 'Procesando registros...');

        const result = await API.post('/api/aprendices/importar', formData);
        
        // Manejar diferentes formatos de respuesta
        const responseData = result.data || result;
        
        // Verificar si hubo error en la petición HTTP
        if (!result.success) {
            updateStatus('statusProcessing', '<i class="fas fa-xmark"></i> Error', 'error');
            updateStatus('statusSaving', '<i class="fas fa-xmark"></i> Error', 'error');
            const errors = responseData.errors || [];
            const errorMsg = result.error || (Array.isArray(errors) ? errors.join(', ') : (errors || 'Error durante la importación'));
            Notification.error(errorMsg);
            
            // Mostrar resultados parciales si hay datos
            if (responseData.data || responseData.imported !== undefined) {
                setTimeout(() => {
                    mostrarResultados(responseData.data || responseData);
                }, 1000);
            }
            return;
        }

        // Verificar si la importación fue exitosa en el contenido
        if (!responseData || (responseData.success === false)) {
            updateStatus('statusProcessing', '<i class="fas fa-xmark"></i> Error', 'error');
            updateStatus('statusSaving', '<i class="fas fa-xmark"></i> Error', 'error');
            const errors = responseData?.errors || ['Error desconocido durante la importación'];
            const errorMsg = Array.isArray(errors) ? errors.join(', ') : errors;
            Notification.error(errorMsg);
            
            // Mostrar resultados parciales si hay datos
            if (responseData && (responseData.data || responseData.imported !== undefined)) {
                setTimeout(() => {
                    mostrarResultados(responseData.data || responseData);
                }, 1000);
            }
            return;
        }
        
        await new Promise(resolve => setTimeout(resolve, 1000));
        updateProgress(80, 'Registros procesados');
        updateStatus('statusProcessing', '<i class="fas fa-check"></i> Completado', 'success');

        // Paso 3: Guardado
        updateStatus('statusSaving', '<i class="fas fa-hourglass"></i> Guardando...', 'info');
        updateProgress(90, 'Guardando en base de datos...');

        await new Promise(resolve => setTimeout(resolve, 500));
        updateProgress(100, 'Importación completada');
        updateStatus('statusSaving', '<i class="fas fa-check"></i> Completado', 'success');

        // Mostrar resultados - manejar diferentes formatos
        const finalData = responseData.data || responseData;
        setTimeout(() => {
            mostrarResultados(finalData);
        }, 1000);

    } catch (error) {
        console.error('Error en importación:', error);
        updateStatus('statusProcessing', '<i class="fas fa-xmark"></i> Error', 'error');
        updateStatus('statusSaving', '<i class="fas fa-xmark"></i> Error', 'error');
        Notification.error('Error durante la importación: ' + (error.message || 'Error desconocido'));
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

    // Asegurar que data tenga la estructura correcta
    const imported = data.imported || 0;
    const skipped = data.skipped || 0;
    const errors = data.errors || [];
    const message = data.message || 'Importación completada';

    // Resumen
    summary.innerHTML = `
        <div class="result-card success">
            <div class="result-number">${imported}</div>
            <div class="result-label">Aprendices Importados</div>
        </div>
        <div class="result-card warning">
            <div class="result-number">${skipped}</div>
            <div class="result-label">Registros Omitidos</div>
        </div>
        <div class="result-card error">
            <div class="result-number">${errors.length}</div>
            <div class="result-label">Errores Encontrados</div>
        </div>
    `;

    // Detalles de errores
    if (errors.length > 0) {
        let errorsHtml = '<h3>Errores Detallados</h3>';
        errorsHtml += '<table class="error-table">';
        errorsHtml += '<thead><tr><th>#</th><th>Error</th></tr></thead><tbody>';
        
        errors.forEach((error, index) => {
            const errorText = typeof error === 'string' ? error : JSON.stringify(error);
            errorsHtml += `<tr><td>${index + 1}</td><td>${errorText}</td></tr>`;
        });
        
        errorsHtml += '</tbody></table>';
        details.innerHTML = errorsHtml;
    } else {
        details.innerHTML = '';
    }

    // Mostrar notificación según el resultado
    if (imported > 0) {
        Notification.success(message);
    } else if (errors.length > 0) {
        Notification.warning('No se importaron aprendices. Revise los errores detallados.');
    } else {
        Notification.info(message);
    }
}

function nuevaImportacion() {
    document.getElementById('step3').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    
    // Limpiar formulario
    document.getElementById('importForm').reset();
    clearFile();
    
    // Resetear estados
    updateProgress(0, 'Listo para nueva importación');
    updateStatus('statusValidation', '<i class="fas fa-hourglass"></i> Pendiente', '');
    updateStatus('statusProcessing', '<i class="fas fa-hourglass"></i> Pendiente', '');
    updateStatus('statusSaving', '<i class="fas fa-hourglass"></i> Pendiente', '');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
