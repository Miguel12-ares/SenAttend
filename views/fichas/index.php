<?php
/**
 * Vista: Lista de Fichas
 * Incluye búsqueda, filtros, paginación y acciones CRUD
 */

$title = 'Gestión de Fichas - SENAttend';
$showHeader = true;
$currentPage = 'fichas';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css');
$additionalScripts = asset_js('js/modules/fichas.js') . asset_js('js/modules/fichas-import.js') . asset_js('js/common/search-simple.js');

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-clipboard-list"></i> Gestión de Fichas</h1>
            <p>Administra las fichas de formación del SENA</p>
        </div>
        <div class="page-actions">
            <button onclick="abrirModalImportar()" class="btn btn-secondary"><i class="fas fa-file-import"></i> <span class="btn-text">Importar CSV</span></button>
            <a href="/fichas/crear" class="btn btn-primary"><i class="fas fa-plus-circle"></i> <span class="btn-text">Nueva Ficha</span></a>
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

    <!-- Barra de búsqueda y filtros -->
    <div class="search-filter-bar">
        <form method="GET" action="/fichas" class="search-form">
            <div class="form-row-filter">
                <div class="form-group">
                    <label for="searchInput"><i class="fas fa-search"></i> Buscar</label>
                    <div class="search-box">
                        <input 
                            type="text" 
                            name="search" 
                            id="searchInput"
                            class="form-control" 
                            placeholder="Buscar por número de ficha..."
                            value="<?= htmlspecialchars($search ?? '') ?>"
                        >
                        <span class="search-box-icon"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="estado"><i class="fas fa-filter"></i> Estado</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="activa" <?= ($estado ?? '') === 'activa' ? 'selected' : '' ?>>Activas</option>
                        <option value="finalizada" <?= ($estado ?? '') === 'finalizada' ? 'selected' : '' ?>>Finalizadas</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-text">Buscar</span></button>
                    <a href="/fichas" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Limpiar</span></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de fichas -->
    <div class="table-container">
        <?php if (empty($fichas)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>No se encontraron fichas</p>
                <a href="/fichas/crear" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Crear primera ficha</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Número Ficha</th>
                            <th><i class="fas fa-book"></i> Nombre</th>
                            <th><i class="fas fa-info-circle"></i> Estado</th>
                            <th><i class="fas fa-users"></i> Aprendices</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fichas as $ficha): ?>
                        <tr>
                            <td data-label="Número Ficha">
                                <strong><?= htmlspecialchars($ficha['numero_ficha']) ?></strong>
                            </td>
                            <td data-label="Nombre"><?= htmlspecialchars($ficha['nombre']) ?></td>
                            <td data-label="Estado">
                                <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                    <i class="fas fa-<?= $ficha['estado'] === 'activa' ? 'check-circle' : 'archive' ?>"></i>
                                    <?= ucfirst($ficha['estado']) ?>
                                </span>
                            </td>
                            <td data-label="Aprendices">
                                <span style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-users" style="color: var(--color-primary);"></i>
                                    <strong><?php 
                                    $totalAprendices = $this->fichaRepository->countAprendices($ficha['id']); 
                                    echo $totalAprendices;
                                    ?></strong>
                                </span>
                            </td>
                            <td data-label="Acciones" class="actions">
                                <a href="/fichas/<?= $ficha['id'] ?>" class="btn-action btn-view" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/fichas/<?= $ficha['id'] ?>/editar" class="btn-action btn-edit" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <button 
                                    onclick="confirmarEliminar(<?= $ficha['id'] ?>, '<?= htmlspecialchars($ficha['numero_ficha'], ENT_QUOTES) ?>')" 
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
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> <span class="btn-text">Anterior</span>
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        <i class="fas fa-file-alt"></i> Página <?= $page ?> de <?= $totalPages ?> (<?= $total ?> registros)
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>" class="btn btn-secondary">
                            <span class="btn-text">Siguiente</span> <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h2 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
        <div class="modal-body">
            <p>¿Está seguro de eliminar la ficha <strong id="fichaName"></strong>?</p>
            <p class="warning-text"><i class="fas fa-info-circle"></i> Esta acción no se puede deshacer.</p>
        </div>
        <form id="deleteForm" method="POST">
            <div class="modal-actions">
                <button type="button" onclick="cerrarModal()" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <span class="btn-text">Eliminar</span></button>
            </div>
        </form>
    </div>
</div>


<!-- Modal de Importación CSV -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <h2 class="modal-title"><i class="fas fa-file-import"></i> Importar Fichas desde CSV</h2>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label><i class="fas fa-file-csv"></i> Archivo CSV</label>
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="file-upload-text">
                            <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                            <small>Formato: numero_ficha, nombre, estado</small>
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
                    • Primera línea: encabezados (numero_ficha, nombre, estado)<br>
                    • Número de ficha: 4-20 caracteres alfanuméricos<br>
                    • Estado: activa o finalizada (opcional, por defecto: activa)<br>
                    • Las fichas duplicadas serán omitidas
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="cerrarModalImportar()" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></button>
            <button type="button" onclick="validarArchivoFichas()" class="btn btn-info"><i class="fas fa-check-circle"></i> <span class="btn-text">Validar</span></button>
            <button type="button" onclick="importarCSV()" class="btn btn-primary"><i class="fas fa-file-import"></i> <span class="btn-text">Importar</span></button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

