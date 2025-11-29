<?php
/**
 * Vista: Importar Instructores CSV
 * Importación masiva de instructores desde archivo CSV
 */

$title = 'Importar Instructores - SENAttend';
$showHeader = true;
$currentPage = 'gestion-instructores';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css');
$additionalScripts = '';

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-file-import"></i> Importar Instructores desde CSV</h1>
            <p>Cargue un archivo CSV para importar múltiples instructores</p>
        </div>
        <div class="page-actions">
            <a href="/gestion-instructores" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <!-- Mensajes de feedback -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
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

    <?php if (isset($_SESSION['warnings'])): ?>
        <div class="alert alert-warning">
            <ul>
                <?php foreach ($_SESSION['warnings'] as $warning): ?>
                    <li><?= htmlspecialchars($warning) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['warnings']); ?>
    <?php endif; ?>

    <!-- Detalles de importación -->
    <?php if (isset($_SESSION['import_details'])): ?>
        <div class="import-results">
            <h2><i class="fas fa-check-circle"></i> Resultados de la Importación</h2>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Contraseña Temporal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['import_details'] as $detail): ?>
                            <tr>
                                <td><?= htmlspecialchars($detail['documento']) ?></td>
                                <td><?= htmlspecialchars($detail['nombre']) ?></td>
                                <td>
                                    <?php if ($detail['status'] === 'success'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Exitoso</span>
                                    <?php else: ?>
                                        <span class="badge badge-error"><i class="fas fa-times"></i> Error</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($detail['default_password'])): ?>
                                        <code><?= htmlspecialchars($detail['default_password']) ?></code>
                                    <?php elseif (isset($detail['error'])): ?>
                                        <small class="text-error"><?= htmlspecialchars($detail['error']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php unset($_SESSION['import_details']); ?>
    <?php endif; ?>

    <div class="form-container">
        <div class="form-card">
            <h2><i class="fas fa-download"></i> Plantilla CSV</h2>
            <p>Descargue la plantilla para conocer el formato correcto del archivo:</p>
            <a href="data:text/csv;charset=utf-8,documento,nombre,email%0A12345678,Juan Pérez,juan.perez@sena.edu.co%0A87654321,María González,maria.gonzalez@sena.edu.co" 
               download="plantilla_instructores.csv" 
               class="btn btn-info">
                <i class="fas fa-download"></i> Descargar Plantilla CSV
            </a>
        </div>

        <div class="form-card">
            <h2><i class="fas fa-upload"></i> Cargar Archivo CSV</h2>
            
            <form method="POST" action="/gestion-instructores/importar-csv" enctype="multipart/form-data" id="importForm">
                <div class="form-group">
                    <label><i class="fas fa-file-csv"></i> Archivo CSV</label>
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="file-upload-text">
                            <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                            <small>Formato: documento, nombre, email</small>
                        </div>
                        <input 
                            type="file" 
                            id="csv_file" 
                            name="csv_file" 
                            accept=".csv" 
                            required
                            style="display: none;"
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
                    <strong><i class="fas fa-info-circle"></i> Formato del CSV:</strong><br>
                    • Primera línea: encabezados (documento, nombre, email)<br>
                    • Documento: 7-15 dígitos numéricos<br>
                    • Nombre: Nombre completo del instructor<br>
                    • Email: Correo electrónico válido<br>
                    • La contraseña será generada automáticamente (primeros 6 dígitos del documento)<br>
                    • Los instructores duplicados serán omitidos
                </div>

                <div class="form-actions">
                    <a href="/gestion-instructores" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Importar Instructores</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-container {
    max-width: 900px;
    margin: 0 auto;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.form-card h2 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
    color: var(--color-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.file-upload-area {
    border: 2px dashed #ccc;
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.file-upload-area:hover {
    border-color: var(--color-primary);
    background: #f0f8f0;
}

.file-upload-icon {
    font-size: 3rem;
    color: var(--color-primary);
    margin-bottom: 1rem;
}

.file-upload-text strong {
    color: var(--color-primary);
    font-size: 1.1rem;
}

.file-selected {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #e8f5e9;
    border-radius: 8px;
    margin-top: 1rem;
}

.file-selected-name {
    font-weight: 600;
    color: #333;
}

.file-selected-size {
    font-size: 0.875rem;
    color: #666;
}

.file-remove {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-remove:hover {
    background: #c82333;
}

.import-results {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.import-results h2 {
    color: var(--color-success);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

code {
    background: #f4f4f4;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    color: #d63384;
}

.text-error {
    color: var(--color-danger);
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid var(--color-info);
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .form-card {
        padding: 1rem;
    }
    
    .file-upload-area {
        padding: 2rem 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.getElementById('csv_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
        document.getElementById('fileInfo').style.display = 'flex';
        document.querySelector('.file-upload-area').style.display = 'none';
    }
});

function clearFile() {
    document.getElementById('csv_file').value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.querySelector('.file-upload-area').style.display = 'block';
}

document.getElementById('importForm').addEventListener('submit', function(e) {
    const file = document.getElementById('csv_file').files[0];
    if (!file) {
        e.preventDefault();
        alert('Por favor seleccione un archivo CSV');
        return false;
    }
    
    if (!file.name.endsWith('.csv')) {
        e.preventDefault();
        alert('El archivo debe ser un CSV');
        return false;
    }
    
    return true;
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
