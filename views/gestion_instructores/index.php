<?php
/**
 * Vista: Lista de Instructores
 * Gestión completa de instructores del sistema
 */

$title = 'Gestión de Instructores - SENAttend';
$showHeader = true;
$currentPage = 'gestion-instructores';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css') . asset_css('css/modules/gestion-instructores.css');
$additionalScripts = asset_js('js/common/components.js');

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-chalkboard-teacher"></i> Gestión de Instructores</h1>
            <p>Administra los instructores del SENA</p>
        </div>
        <div class="page-actions">
            <a href="/gestion-instructores/importar" class="btn btn-secondary"><i class="fas fa-file-import"></i> <span class="btn-text">Importar CSV</span></a>
            <a href="/gestion-instructores/crear" class="btn btn-primary"><i class="fas fa-user-plus"></i> <span class="btn-text">Nuevo Instructor</span></a>
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

    <!-- Panel de filtros -->
    <div class="filter-panel">
        <div class="filter-panel-header">
            <h3 class="filter-panel-title"><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
        </div>
        <form method="GET" action="/gestion-instructores" id="filterForm">
            <div class="filter-panel-body">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Buscar</label>
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="search"
                            name="search" 
                            class="form-control" 
                            placeholder="Buscar por documento..."
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        >
                        <span class="search-box-icon"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre</label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        class="form-control"
                        placeholder="Buscar por nombre..."
                        value="<?= htmlspecialchars($_GET['nombre'] ?? '') ?>"
                    >
                </div>
            </div>
            <div class="filter-actions">
                <a href="/gestion-instructores" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Limpiar</span></a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> <span class="btn-text">Aplicar Filtros</span></button>
            </div>
        </form>
    </div>

    <!-- Tabla de instructores -->
    <div class="table-container">
        <?php if (empty($instructores)): ?>
            <div class="empty-state">
                <i class="fas fa-chalkboard-teacher"></i>
                <p>No se encontraron instructores</p>
                <a href="/gestion-instructores/crear" class="btn btn-primary"><i class="fas fa-user-plus"></i> Crear primer instructor</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> Documento</th>
                            <th><i class="fas fa-user"></i> Nombre</th>
                            <th><i class="fas fa-envelope"></i> Email</th>

                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($instructores as $instructor): ?>
                        <tr>
                            <td data-label="Documento">
                                <strong><?= htmlspecialchars($instructor['documento']) ?></strong>
                            </td>
                            <td data-label="Nombre">
                                <?= htmlspecialchars($instructor['nombre']) ?>
                            </td>
                            <td data-label="Email"><?= htmlspecialchars($instructor['email']) ?></td>

                            <td data-label="Acciones" class="actions">
                                <a href="/gestion-instructores/<?= $instructor['id'] ?>/editar" class="btn-action btn-edit" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <button 
                                    onclick="confirmarEliminar(<?= $instructor['id'] ?>, '<?= htmlspecialchars($instructor['nombre'], ENT_QUOTES) ?>')" 
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
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($_GET['search'] ?? '') ?>&nombre=<?= urlencode($_GET['nombre'] ?? '') ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> <span class="btn-text">Anterior</span>
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        <i class="fas fa-file-alt"></i> Página <?= $page ?> de <?= $totalPages ?> (<?= $total ?> registros)
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($_GET['search'] ?? '') ?>&nombre=<?= urlencode($_GET['nombre'] ?? '') ?>" class="btn btn-secondary">
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
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
        <div class="modal-body">
            <p>¿Está seguro de eliminar al instructor <strong id="instructorName"></strong>?</p>
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

<script src="<?= asset('js/modules/gestion-instructores.js') ?>"></script>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
