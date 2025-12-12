<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaciones - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/instructor-fichas.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'instructor-fichas';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <!-- Contenido Principal -->
        <main class="main-content">
            <div class="container">
                <!-- Mensajes de sesión -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>

                <!-- Tabs de gestión -->
                <div class="tabs-container">
                    <div class="tabs">
                        <button class="tab-button active" data-tab="fichas">
                            <i class="fas fa-clipboard-list"></i> Por Ficha
                        </button>
                        <button class="tab-button" data-tab="asignacion-rapida">
                            <i class="fas fa-bolt"></i> Asignación Rápida
                        </button>
                        <button class="tab-button" data-tab="lideres">
                            <i class="fas fa-star"></i> Gestión Instructor Líder
                        </button>
                    </div>
                </div>

                <div class="tab-content active" id="tab-fichas">
                    <div class="section-header">
                        <h2><i class="fas fa-graduation-cap"></i> Gestión por Ficha / Instructor Líder</h2>
                        <div class="search-box">
                            <input type="text" id="buscarFicha" placeholder="Buscar ficha..." 
                                   class="form-control">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <div class="fichas-grid">
                        <?php foreach ($fichasListado as $ficha): ?>
                        <div class="ficha-card">
                            <div class="ficha-header">
                                <h3><?= htmlspecialchars($ficha['numero_ficha']) ?></h3>
                                <span class="badge badge-<?= $ficha['estado'] == 'activa' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($ficha['estado']) ?>
                                </span>
                            </div>
                            <div class="ficha-body">
                                <p><?= htmlspecialchars($ficha['nombre']) ?></p>
                                <div class="ficha-stats">
                                    <i class="fas fa-users"></i>
                                    <span id="instructores-ficha-<?= $ficha['id'] ?>">
                                        Cargando...
                                    </span>
                                </div>
                            </div>
                            <div class="ficha-footer">
                                <button class="btn btn-primary btn-sm" 
                                        onclick="abrirModalAsignacionFicha(<?= $ficha['id'] ?>, '<?= htmlspecialchars($ficha['numero_ficha'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-user-plus"></i> Asignar Instructores
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pagination['totalPages'] > 1): ?>
                    <div class="pagination">
                        <?php for ($page = 1; $page <= $pagination['totalPages']; $page++): ?>
                        <a href="/instructor-fichas?page=<?= $page ?>" 
                           class="pagination-link <?= $page === $pagination['currentPage'] ? 'active' : '' ?>">
                            <?= $page ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Asignación Rápida -->
                <div class="tab-content" id="tab-asignacion-rapida">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt"></i> Asignación Rápida</h2>
                    </div>

                    <div class="quick-assign-container">
                        <div class="quick-assign-form">
                            <div class="form-group">
                                <label for="quickInstructor">
                                    <i class="fas fa-user"></i> Seleccionar Instructor
                                </label>
                                <input 
                                    type="text" 
                                    id="quickInstructorSearch" 
                                    class="form-control" 
                                    placeholder="Buscar instructor por nombre o documento..."
                                    autocomplete="off"
                                    style="margin-bottom: 0.5rem;"
                                >
                                <select id="quickInstructor" class="form-control">
                                    <option value="">-- Seleccione un instructor --</option>
                                    <?php foreach ($instructores as $instructor): ?>
                                    <option value="<?= $instructor['id'] ?>">
                                        <?= htmlspecialchars($instructor['nombre']) ?> 
                                        (<?= $instructor['total_fichas'] ?> fichas actuales)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-graduation-cap"></i> Seleccionar Fichas
                                </label>
                                <div class="form-group">
                                    <label for="quickFichaSearch">
                                        <i class="fas fa-search"></i> Buscar ficha por número
                                    </label>
                                    <input type="text" id="quickFichaSearch" class="form-control" placeholder="Ej: 2995479">
                                </div>
                                <div class="fichas-checkbox-grid" id="quickFichaList">
                                    <?php foreach ($fichasParaAsignacionRapida as $ficha): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" 
                                               id="quick-ficha-<?= $ficha['id'] ?>" 
                                               name="quickFichas[]" 
                                               value="<?= $ficha['id'] ?>"
                                               data-numero="<?= htmlspecialchars($ficha['numero_ficha']) ?>">
                                        <label for="quick-ficha-<?= $ficha['id'] ?>">
                                            <?= htmlspecialchars($ficha['numero_ficha']) ?> - 
                                            <?= htmlspecialchars(substr($ficha['nombre'], 0, 30)) ?>...
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="limpiarAsignacionRapida()">
                                    <i class="fas fa-times"></i> Limpiar
                                </button>
                                <button type="button" class="btn btn-primary" onclick="realizarAsignacionRapida()">
                                    <i class="fas fa-save"></i> Asignar Fichas
                                </button>
                            </div>
                        </div>

                        <div class="quick-assign-info">
                            <h3><i class="fas fa-info-circle"></i> Información</h3>
                            <p>Use esta sección para asignar rápidamente múltiples fichas a un instructor.</p>
                            <ul>
                                <li>Seleccione un instructor del listado</li>
                                <li>Marque las fichas que desea asignar</li>
                                <li>Haga clic en "Asignar Fichas"</li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Nota:</strong> Esta acción agregará las fichas seleccionadas 
                                sin eliminar las asignaciones existentes.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Gestión de Instructores Líder -->
                <div class="tab-content" id="tab-lideres">
                    <div class="section-header">
                        <h2><i class="fas fa-star"></i> Gestión de Instructores Líderes</h2>
                        <div class="page-actions">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="abrirModalImportLideres()">
                                <i class="fas fa-file-import"></i> Importar CSV
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-id-card"></i> Documento</th>
                                    <th><i class="fas fa-user"></i> Nombre</th>
                                    <th><i class="fas fa-envelope"></i> Email</th>
                                    <th><i class="fas fa-star"></i> Fichas como Líder</th>
                                    <th><i class="fas fa-cog"></i> Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($instructoresLideres)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <span class="text-muted">No hay instructores líderes configurados actualmente.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($instructoresLideres as $lider): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($lider['documento']) ?></strong></td>
                                            <td><?= htmlspecialchars($lider['nombre']) ?></td>
                                            <td><?= htmlspecialchars($lider['email']) ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <?= (int) $lider['total_fichas_lider'] ?> ficha<?= ((int)$lider['total_fichas_lider']) === 1 ? '' : 's' ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <button 
                                                    type="button" 
                                                    class="btn-action btn-edit" 
                                                    title="Gestionar fichas donde es líder"
                                                    onclick="abrirModalLiderFichas(<?= (int) $lider['id'] ?>, '<?= htmlspecialchars($lider['nombre'], ENT_QUOTES) ?>')"
                                                >
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modal de Asignación para Ficha (estandarizado como anomalías) -->
        <div id="modalAsignacionFicha" class="modal">
            <div class="modal-content">
                <div class="modal-title">
                    <span id="modalTituloFicha">
                        <i class="fas fa-user-tag"></i>
                        Asignar Instructores a la Ficha
                    </span>
                    <button class="modal-close" type="button" onclick="cerrarModalFicha()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="modalFichaId">

                    <div class="info-box" id="infoFichaSeleccionada" style="margin-bottom: 16px; display: none;">
                        <strong>Ficha:</strong> <span id="infoFichaNumero"></span>
                    </div>

                    <p class="text-muted" style="margin-bottom: 12px;">
                        Seleccione los instructores que estarán vinculados a la ficha y marque
                        con la estrella el <strong>instructor líder</strong>.
                    </p>

                    <div class="instructores-list">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalFicha()">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarInstructoresFicha()">
                        <i class="fas fa-save"></i>
                        Guardar Asignaciones
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de gestión de fichas donde el instructor es líder -->
        <div id="modalLiderFichas" class="modal">
            <div class="modal-content">
                <div class="modal-title">
                    <span id="modalLiderTitulo">
                        <i class="fas fa-star"></i>
                        Fichas donde es Instructor Líder
                    </span>
                    <button class="modal-close" type="button" onclick="cerrarModalLiderFichas()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted" id="modalLiderDescripcion" style="margin-bottom: 1rem;"></p>
                    <div id="listaFichasLider" class="instructores-list">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalLiderFichas()">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de importación de instructores líderes -->
        <div id="modalImportLideres" class="modal">
            <div class="modal-content">
                <div class="modal-title">
                    <span>
                        <i class="fas fa-file-import"></i>
                        Importar Instructores Líderes
                    </span>
                    <button class="modal-close" type="button" onclick="cerrarModalImportLideres()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted" style="margin-bottom: 0.75rem;">
                        Cargue un archivo CSV con las columnas 
                        <code>documento_instructor</code> y <code>numero_ficha</code> para asignar líderes.
                    </p>

                    <div class="form-group">
                        <label><i class="fas fa-file-csv"></i> Archivo CSV</label>
                        <div class="file-upload-area" onclick="document.getElementById('csvLideresFile').click()">
                            <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <div class="file-upload-text">
                                <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                                <small>Formato: documento_instructor, numero_ficha</small>
                            </div>
                            <input 
                                type="file" 
                                id="csvLideresFile" 
                                name="csv_file" 
                                accept=".csv" 
                                style="display: none;"
                            >
                        </div>
                        <div id="csvLideresInfo" style="display: none;" class="file-selected">
                            <div>
                                <div class="file-selected-name" id="csvLideresName"></div>
                                <div class="file-selected-size" id="csvLideresSize"></div>
                            </div>
                            <button type="button" class="file-remove" onclick="clearCsvLideresFile()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>
                            Las asignaciones importadas <strong>reemplazarán</strong> al líder actual de cada ficha incluida.
                        </span>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalImportLideres()">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitImportLideres()">
                        <i class="fas fa-file-import"></i>
                        Importar Líderes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= asset('js/modules/instructor-fichas.js') ?>"></script>
</body>
</html>
