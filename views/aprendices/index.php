<?php
/**
 * Vista: Lista de Aprendices - Fase 2
 * Incluye búsqueda dinámica, filtros avanzados, importación CSV y modales
 */

$title = 'Gestión de Aprendices - SENAttend';
$showHeader = true;
$currentPage = 'aprendices';

ob_start();
?>

<link rel="stylesheet" href="/css/components.css">

<div class="container">
    <div class="page-header">
        <h1>Gestión de Aprendices</h1>
        <div class="page-actions">
            <button onclick="abrirModalImportar()" class="btn btn-secondary"><i class="fas fa-folder-open"></i> Importar CSV</button>
            <a href="/aprendices/crear" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Aprendiz</a>
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
                        <span class="search-box-icon"><i class="fas fa-magnifying-glass"></i></span>
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
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>Correo Electrónico</th>
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
                            <td><?= htmlspecialchars($aprendiz['email'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge badge-<?= $aprendiz['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($aprendiz['estado']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn-action btn-view" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/aprendices/<?= $aprendiz['id'] ?>/editar" class="btn-action btn-edit" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <button 
                                    onclick="confirmarEliminarAprendiz(<?= $aprendiz['id'] ?>, '<?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido'], ENT_QUOTES) ?>')" 
                                    class="btn-action btn-delete" 
                                    title="Eliminar"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

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
                        <div class="file-upload-icon"><i class="fas fa-file"></i></div>
                        <div class="file-upload-text">
                            <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                            <small>Formato: documento, nombre, apellido, email</small>
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
                    • Primera línea: encabezados (documento, nombre, apellido, email)<br>
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
<script src="/js/aprendices-import.js"></script>
<script src="/js/search-simple.js"></script>

<script>
// Funciones para mantener compatibilidad con HTML existente
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

// Eventos básicos de modales
document.getElementById('importModal')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalImportar();
});

document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEliminar();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const importModal = document.getElementById('importModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (importModal && importModal.style.display === 'flex') {
            cerrarModalImportar();
        }
        if (deleteModal && deleteModal.style.display === 'flex') {
            cerrarModalEliminar();
        }
    }
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

/* Los estilos de .actions y .btn-action ahora están en components.css */

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

