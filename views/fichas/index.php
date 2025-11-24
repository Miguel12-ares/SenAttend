<?php
/**
 * Vista: Lista de Fichas
 * Incluye búsqueda, filtros, paginación y acciones CRUD
 */

$title = 'Gestión de Fichas - SENAttend';
$showHeader = true;
$currentPage = 'fichas';

ob_start();
?>

<link rel="stylesheet" href="/css/components.css">

<div class="container">
    <div class="page-header">
        <h1>Gestión de Fichas</h1>
        <div class="page-actions">
            <button onclick="abrirModalImportar()" class="btn btn-secondary"><i class="fas fa-folder-open"></i> Importar CSV</button>
            <a href="/fichas/crear" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Ficha</a>
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
            <div class="form-row">
                <div class="form-col">
                    <input 
                        type="text" 
                        name="search" 
                        id="searchInput"
                        class="form-control" 
                        placeholder="Buscar por número o nombre..."
                        value="<?= htmlspecialchars($search ?? '') ?>"
                    >
                </div>
                <div class="form-col">
                    <select name="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="activa" <?= ($estado ?? '') === 'activa' ? 'selected' : '' ?>>Activas</option>
                        <option value="finalizada" <?= ($estado ?? '') === 'finalizada' ? 'selected' : '' ?>>Finalizadas</option>
                    </select>
                </div>
                <div class="form-col">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="/fichas" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de fichas -->
    <div class="table-container">
        <?php if (empty($fichas)): ?>
            <div class="empty-state">
                <p>No se encontraron fichas</p>
                <a href="/fichas/crear" class="btn btn-primary">Crear primera ficha</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número Ficha</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Aprendices</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fichas as $ficha): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($ficha['numero_ficha']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($ficha['nombre']) ?></td>
                            <td>
                                <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($ficha['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $totalAprendices = $this->fichaRepository->countAprendices($ficha['id']); 
                                echo $totalAprendices;
                                ?>
                            </td>
                            <td class="actions">
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
                            « Anterior
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        Página <?= $page ?> de <?= $totalPages ?> (<?= $total ?> registros)
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&estado=<?= urlencode($estado ?? '') ?>" class="btn btn-secondary">
                            Siguiente »
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
        <h2>Confirmar Eliminación</h2>
        <p>¿Está seguro de eliminar la ficha <strong id="fichaName"></strong>?</p>
        <p class="warning-text">Esta acción no se puede deshacer.</p>
        <form id="deleteForm" method="POST">
            <div class="modal-actions">
                <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    margin: 0;
}

.search-filter-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-row {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
}

.form-col {
    flex: 1;
}

.form-col:last-child {
    flex: 0 0 auto;
    display: flex;
    gap: 0.5rem;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Los estilos de tablas, .actions y .btn-action están centralizados en style.css y components.css */

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

.badge-secondary {
    background-color: var(--color-gray-600);
    color: white;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
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

<!-- Modal de Importación CSV -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <h2 class="modal-title">Importar Fichas desde CSV</h2>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Archivo CSV</label>
                    <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                        <div class="file-upload-icon"><i class="fas fa-file"></i></div>
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
                    <strong>Formato del CSV:</strong><br>
                    • Primera línea: encabezados (numero_ficha, nombre, estado)<br>
                    • Número de ficha: 4-20 caracteres alfanuméricos<br>
                    • Estado: activa o finalizada (opcional, por defecto: activa)<br>
                    • Las fichas duplicadas serán omitidas
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="cerrarModalImportar()" class="btn btn-secondary">Cancelar</button>
            <button type="button" onclick="validarArchivoFichas()" class="btn btn-info"><i class="fas fa-magnifying-glass"></i> Validar</button>
            <button type="button" onclick="importarCSV()" class="btn btn-primary"><i class="fas fa-folder-open"></i> Importar</button>
        </div>
    </div>
</div>

<script src="/js/components.js"></script>
<script src="/js/fichas-import.js"></script>
<script src="/js/search-simple.js"></script>

<script>
// ==============================================
// ELIMINACIÓN
// ==============================================

function confirmarEliminar(id, nombre) {
    document.getElementById('fichaName').textContent = nombre;
    document.getElementById('deleteForm').action = '/fichas/' + id + '/eliminar';
    document.getElementById('deleteModal').classList.add('active');
}

function cerrarModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// Cerrar modal al hacer clic fuera
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

