<?php
/**
 * Vista: Lista de Aprendices - Fase 2
 * Incluye búsqueda dinámica, filtros avanzados, importación CSV y modales
 */

$title = 'Gestión de Aprendices - SENAttend';
$showHeader = true;

ob_start();
?>

<link rel="stylesheet" href="/css/components.css">

<div class="container">
    <div class="page-header">
        <h1>Gestión de Aprendices</h1>
        <div class="page-actions">
            <button onclick="abrirModalImportar()" class="btn btn-secondary">📂 Importar CSV</button>
            <a href="/aprendices/crear" class="btn btn-primary">+ Nuevo Aprendiz</a>
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

    <!-- Panel de filtros -->
    <div class="filter-panel">
        <div class="filter-panel-header">
            <h3 class="filter-panel-title">Filtros de Búsqueda</h3>
        </div>
        <form method="GET" action="/aprendices" id="filterForm">
            <div class="filter-panel-body">
                <div class="form-group">
                    <label for="search">Buscar</label>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="search"
                            name="search" 
                            class="form-control" 
                            placeholder="Documento, nombre, apellido..."
                            value="<?= htmlspecialchars($search ?? '') ?>"
                        >
                        <span class="search-box-icon">🔍</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?= ($estado ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
                        <option value="retirado" <?= ($estado ?? '') === 'retirado' ? 'selected' : '' ?>>Retirados</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ficha">Ficha</label>
                    <select name="ficha" id="ficha" class="form-control">
                        <option value="">Todas las fichas</option>
                        <?php foreach ($fichas as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= ($fichaId ?? 0) == $f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['numero_ficha']) ?> - <?= htmlspecialchars($f['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <a href="/aprendices" class="btn btn-secondary">Limpiar</a>
                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
            </div>
        </form>
    </div>

    <!-- Tabla de aprendices -->
    <div class="table-container">
        <?php if (empty($aprendices)): ?>
            <div class="empty-state">
                <p>No se encontraron aprendices</p>
                <a href="/aprendices/crear" class="btn btn-primary">Crear primer aprendiz</a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Nombre Completo</th>
                        <th>Código Carnet</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aprendices as $aprendiz): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($aprendiz['documento']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($aprendiz['apellido'] . ', ' . $aprendiz['nombre']) ?>
                            </td>
                            <td><?= htmlspecialchars($aprendiz['codigo_carnet'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge badge-<?= $aprendiz['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($aprendiz['estado']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn-action btn-view" title="Ver detalles">
                                    👁️
                                </a>
                                <a href="/aprendices/<?= $aprendiz['id'] ?>/editar" class="btn-action btn-edit" title="Editar">
                                    ✏️
                                </a>
                                <button 
                                    onclick="confirmarEliminarAprendiz(<?= $aprendiz['id'] ?>, '<?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido'], ENT_QUOTES) ?>')" 
                                    class="btn-action btn-delete" 
                                    title="Eliminar"
                                >
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>&ficha=<?= urlencode($fichaId ?? '') ?>" class="btn btn-secondary">
                            « Anterior
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        Página <?= $page ?> de <?= $totalPages ?> (<?= $total ?> registros)
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>&ficha=<?= urlencode($fichaId ?? '') ?>" class="btn btn-secondary">
                            Siguiente »
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Importación CSV -->
<div id="importModal" class="modal" style="display: none;">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2 class="modal-title">Importar Aprendices desde CSV</h2>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import_ficha_id">Seleccionar Ficha *</label>
                    <select name="ficha_id" id="import_ficha_id" class="form-control" required>
                        <option value="">-- Seleccione una ficha --</option>
                        <?php foreach ($fichas as $f): ?>
                            <option value="<?= $f['id'] ?>">
                                <?= htmlspecialchars($f['numero_ficha']) ?> - <?= htmlspecialchars($f['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Archivo CSV</label>
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="file-upload-icon">📄</div>
                        <div class="file-upload-text">
                            <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                            <small>Formato: documento, nombre, apellido, codigo_carnet</small>
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
                    • Primera línea: encabezados (documento, nombre, apellido, codigo_carnet)<br>
                    • Documento: 6-20 dígitos numéricos<br>
                    • Los aprendices duplicados serán omitidos
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="cerrarModalImportar()" class="btn btn-secondary">Cancelar</button>
            <button type="button" onclick="validarArchivo()" class="btn btn-info">Validar</button>
            <button type="button" onclick="importarCSV()" class="btn btn-primary">Importar</button>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2>Confirmar Eliminación</h2>
        <p>¿Está seguro de eliminar al aprendiz <strong id="aprendizName"></strong>?</p>
        <p class="warning-text">Esta acción no se puede deshacer.</p>
        <form id="deleteForm" method="POST">
            <div class="modal-actions">
                <button type="button" onclick="cerrarModalEliminar()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script src="/js/components.js"></script>
<script>
// ==============================================
// BÚSQUEDA DINÁMICA
// ==============================================

new SearchBox('search', (value) => {
    if (value.length >= 3 || value.length === 0) {
        document.getElementById('filterForm').submit();
    }
});

// ==============================================
// IMPORTACIÓN CSV
// ==============================================

function abrirModalImportar() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalImportar() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        const form = document.getElementById('importForm');
        if (form) form.reset();
        const fileInfo = document.getElementById('fileInfo');
        if (fileInfo) fileInfo.style.display = 'none';
    }
}

// Cerrar modal al hacer clic fuera
document.getElementById('importModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalImportar();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('importModal');
        if (modal && modal.style.display === 'flex') {
            cerrarModalImportar();
        }
    }
});

// Manejo de archivo seleccionado
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

async function validarArchivo() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);

    if (!formData.get('csv_file')?.name) {
        Notification.error('Seleccione un archivo CSV');
        return;
    }

    Loading.show('Validando archivo...');

    const result = await API.post('/api/aprendices/validar-csv', formData);
    
    Loading.hide();

    if (result.success && result.data.valid) {
        if (result.data.tiene_errores) {
            const errores = result.data.errores.slice(0, 10).join('<br>');
            Notification.warning(`Archivo válido pero con ${result.data.errores.length} advertencias`);
            
            // Mostrar modal con errores
            await Confirm.show(
                'Advertencias de Validación',
                `<div style="text-align: left; max-height: 300px; overflow-y: auto;">${errores}</div>`,
                {
                    confirmText: 'Entendido',
                    confirmClass: 'btn-info'
                }
            );
        } else {
            Notification.success(`✓ Archivo válido: ${result.data.aprendices_validos} aprendices listos para importar`);
        }
    } else {
        const error = result.error || result.data?.errors?.join(', ') || 'Error de validación';
        Notification.error(error);
    }
}

async function importarCSV() {
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
        '¿Desea importar los aprendices desde el archivo CSV?',
        {
            confirmText: 'Importar',
            confirmClass: 'btn-primary'
        }
    );

    if (!confirmado) return;

    Loading.show('Importando aprendices...');

    const result = await API.post('/api/aprendices/importar', formData);
    
    Loading.hide();

    if (result.success && result.data.success) {
        Notification.success(result.data.message);
        cerrarModalImportar();
        
        // Recargar después de 1.5 segundos
        setTimeout(() => window.location.reload(), 1500);
    } else {
        const error = result.error || result.data?.errors?.join(', ') || 'Error al importar';
        Notification.error(error);
    }
}

// ==============================================
// ELIMINACIÓN
// ==============================================

function confirmarEliminarAprendiz(id, nombre) {
    document.getElementById('aprendizName').textContent = nombre;
    document.getElementById('deleteForm').action = '/aprendices/' + id + '/eliminar';
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalEliminar() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Cerrar modal de eliminación al hacer clic fuera
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalEliminar();
    }
});

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal && deleteModal.style.display === 'flex') {
            cerrarModalEliminar();
        }
    }
});

// ==============================================
// CAMBIOS AUTOMÁTICOS EN FILTROS
// ==============================================

document.getElementById('estado')?.addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('ficha')?.addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});
</script>

<style>
/* Estilos adicionales específicos */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background-color: var(--color-primary);
    color: white;
    padding: 1rem;
    text-align: left;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid var(--color-gray-200);
}

.table tbody tr:hover {
    background-color: var(--color-gray-100);
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: background 0.2s;
    text-decoration: none;
}

.btn-action:hover {
    background-color: var(--color-gray-200);
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-gray-200);
}

.pagination-info {
    color: var(--color-gray-600);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--color-gray-600);
}

.btn-danger {
    background-color: var(--color-danger);
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.warning-text {
    color: var(--color-danger);
    font-size: 0.9rem;
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

