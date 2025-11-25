<?php
/**
 * Vista: Lista de Aprendices - Fase 2
 * Incluye búsqueda dinámica, filtros avanzados, importación CSV y modales
 */

$title = 'Gestión de Aprendices - SENAttend';
$showHeader = true;
$currentPage = 'aprendices';
$additionalStyles = '<link rel="stylesheet" href="/css/components.css"><link rel="stylesheet" href="/css/fichas.css"><link rel="stylesheet" href="/css/aprendices.css">';
$additionalScripts = '<script src="/js/aprendices.js"></script><script src="/js/aprendices-import.js"></script><script src="/js/search-simple.js"></script>';

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-users"></i> Gestión de Aprendices</h1>
            <p>Administra los aprendices del SENA</p>
        </div>
        <div class="page-actions">
            <button onclick="abrirModalImportar()" class="btn btn-secondary"><i class="fas fa-file-import"></i> <span class="btn-text">Importar CSV</span></button>
            <a href="/aprendices/crear" class="btn btn-primary"><i class="fas fa-user-plus"></i> <span class="btn-text">Nuevo Aprendiz</span></a>
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
            <h3 class="filter-panel-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
        </div>
        <form method="GET" action="/aprendices" id="filterForm">
            <div class="filter-panel-body">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Buscar</label>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="search"
                            name="search" 
                            class="form-control" 
                            placeholder="Documento, nombre, apellido..."
                            value="<?= htmlspecialchars($search ?? '') ?>"
                        >
                        <span class="search-box-icon"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="estado"><i class="fas fa-info-circle"></i> Estado</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?= ($estado ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
                        <option value="retirado" <?= ($estado ?? '') === 'retirado' ? 'selected' : '' ?>>Retirados</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ficha"><i class="fas fa-clipboard-list"></i> Ficha</label>
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
                <a href="/aprendices" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Limpiar</span></a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> <span class="btn-text">Aplicar Filtros</span></button>
            </div>
        </form>
    </div>

    <!-- Tabla de aprendices -->
    <div class="table-container">
        <?php if (empty($aprendices)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No se encontraron aprendices</p>
                <a href="/aprendices/crear" class="btn btn-primary"><i class="fas fa-user-plus"></i> Crear primer aprendiz</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> Documento</th>
                            <th><i class="fas fa-user"></i> Nombre Completo</th>
                            <th><i class="fas fa-envelope"></i> Correo Electrónico</th>
                            <th><i class="fas fa-info-circle"></i> Estado</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
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
                                    <i class="fas fa-<?= $aprendiz['estado'] === 'activo' ? 'check-circle' : 'archive' ?>"></i>
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
                            <i class="fas fa-chevron-left"></i> <span class="btn-text">Anterior</span>
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        <i class="fas fa-file-alt"></i> Página <?= $page ?> de <?= $totalPages ?> (<?= $total ?> registros)
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>&ficha=<?= urlencode($fichaId ?? '') ?>" class="btn btn-secondary">
                            <span class="btn-text">Siguiente</span> <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Importación CSV -->
<div id="importModal" class="modal">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2 class="modal-title"><i class="fas fa-file-import"></i> Importar Aprendices desde CSV</h2>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import_ficha_id"><i class="fas fa-clipboard-list"></i> Seleccionar Ficha *</label>
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
                    <label><i class="fas fa-file-csv"></i> Archivo CSV</label>
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
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
                    <strong><i class="fas fa-info-circle"></i> Formato del CSV:</strong><br>
                    • Primera línea: encabezados (documento, nombre, apellido, email)<br>
                    • Documento: 6-20 dígitos numéricos<br>
                    • Los aprendices duplicados serán omitidos
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="cerrarModalImportar()" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></button>
            <button type="button" onclick="validarArchivo()" class="btn btn-info"><i class="fas fa-check-circle"></i> <span class="btn-text">Validar</span></button>
            <button type="button" onclick="importarCSV()" class="btn btn-primary"><i class="fas fa-file-import"></i> <span class="btn-text">Importar</span></button>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="modal">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
        <div class="modal-body">
            <p>¿Está seguro de eliminar al aprendiz <strong id="aprendizName"></strong>?</p>
            <p class="warning-text"><i class="fas fa-info-circle"></i> Esta acción no se puede deshacer.</p>
        </div>
        <form id="deleteForm" method="POST">
            <div class="modal-actions">
                <button type="button" onclick="cerrarModalEliminar()" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <span class="btn-text">Eliminar</span></button>
            </div>
        </form>
    </div>
</div>

<style>
.btn-text {
    display: inline;
}

@media (max-width: 768px) {
    .btn-text {
        display: none;
    }
    
    .btn i {
        margin: 0;
    }
}

.btn-danger {
    background-color: var(--color-danger);
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid var(--color-info);
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

