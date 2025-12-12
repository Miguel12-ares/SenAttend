<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaciones - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/instructor-fichas.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'instructor-fichas';
        // Determinar pestaña activa desde querystring (por defecto 'instructores')
        $activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['instructores','fichas','asignacion-rapida']) ? $_GET['tab'] : 'instructores';
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

                <!-- Tabs de navegación -->
                <div class="tabs-container">
                    <div class="tabs">
                        <button class="tab-button <?= $activeTab === 'instructores' ? 'active' : '' ?>" data-tab="instructores">
                            <i class="fas fa-chalkboard-teacher"></i> Por Instructor
                        </button>
                        <button class="tab-button <?= $activeTab === 'fichas' ? 'active' : '' ?>" data-tab="fichas">
                            <i class="fas fa-clipboard-list"></i> Por Ficha
                        </button>
                        <button class="tab-button <?= $activeTab === 'asignacion-rapida' ? 'active' : '' ?>" data-tab="asignacion-rapida">
                            <i class="fas fa-bolt"></i> Asignación Rápida
                        </button>
                    </div>
                </div>

                <!-- Tab: Gestión por Instructor -->
                <div class="tab-content <?= $activeTab === 'instructores' ? 'active' : '' ?>" id="tab-instructores">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> Gestión por Instructor</h2>
                        <div class="search-box">
                            <input type="text" id="buscarInstructor" placeholder="Buscar instructor..." 
                                   class="form-control">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table" id="tablaInstructores">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Fichas Asignadas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instructores as $instructor): ?>
                                <tr>
                                    <td data-label="Documento"><?= htmlspecialchars($instructor['documento']) ?></td>
                                    <td data-label="Nombre">
                                        <strong><?= htmlspecialchars($instructor['nombre']) ?></strong>
                                    </td>
                                    <td data-label="Email"><?= htmlspecialchars($instructor['email']) ?></td>
                                    <td data-label="Fichas Asignadas">
                                        <span class="badge badge-info">
                                            <?= $instructor['total_fichas'] ?> fichas
                                        </span>
                                        <?php if ($instructor['total_fichas'] > 0): ?>
                                        <div class="fichas-preview">
                                            <?php 
                                            $maxFichas = 3;
                                            $fichasMostradas = array_slice($instructor['fichas'], 0, $maxFichas);
                                            foreach ($fichasMostradas as $ficha): 
                                            ?>
                                            <span class="ficha-tag">
                                                <?= htmlspecialchars($ficha['numero_ficha']) ?>
                                            </span>
                                            <?php endforeach; ?>
                                            <?php if ($instructor['total_fichas'] > $maxFichas): ?>
                                            <span class="ficha-tag">+<?= $instructor['total_fichas'] - $maxFichas ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="abrirModalAsignacion(<?= $instructor['id'] ?>, '<?= htmlspecialchars($instructor['nombre'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit"></i> Gestionar
                                        </button>
                                        <a href="/instructor-fichas/instructor/<?= $instructor['id'] ?>" 
                                           class="btn btn-secondary btn-sm">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Gestión por Ficha -->
                <div class="tab-content <?= $activeTab === 'fichas' ? 'active' : '' ?>" id="tab-fichas">
                    <div class="section-header">
                        <h2><i class="fas fa-graduation-cap"></i> Gestión por Ficha</h2>
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
                        <?php
                            // Mantener la pestaña activa en la paginación
                            $query = http_build_query(array_merge($_GET, ['page' => $page, 'tab' => 'fichas']));
                        ?>
                        <a href="/instructor-fichas?<?= $query ?>" 
                           class="pagination-link <?= $page === $pagination['currentPage'] ? 'active' : '' ?>">
                            <?= $page ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Asignación Rápida -->
                <div class="tab-content <?= $activeTab === 'asignacion-rapida' ? 'active' : '' ?>" id="tab-asignacion-rapida">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt"></i> Asignación Rápida</h2>
                    </div>

                    <div class="quick-assign-container">
                        <div class="quick-assign-form">
                            <div class="form-group">
                                <label for="quickInstructor">
                                    <i class="fas fa-user"></i> Seleccionar Instructor
                                </label>
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
            </div>
        </main>

        <!-- Modal de Asignación para Instructor -->
        <div id="modalAsignacion" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitulo">Gestionar Fichas</h2>
                    <button class="modal-close" onclick="cerrarModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalInstructorId">
                    
                    <div class="form-group">
                        <label>Fichas Disponibles</label>
                        <select id="modalFichasDisponibles" multiple size="8" class="form-control">
                            <!-- Se llena dinámicamente -->
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button class="btn btn-primary" onclick="agregarFichas()">
                            <i class="fas fa-plus"></i> Agregar →
                        </button>
                        <button class="btn btn-danger" onclick="quitarFichas()">
                            ← <i class="fas fa-minus"></i> Quitar
                        </button>
                    </div>

                    <div class="form-group">
                        <label>Fichas Asignadas</label>
                        <select id="modalFichasAsignadas" multiple size="8" class="form-control">
                            <!-- Se llena dinámicamente -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                    <button class="btn btn-primary" onclick="guardarAsignaciones()">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Asignación para Ficha -->
        <div id="modalAsignacionFicha" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTituloFicha">Asignar Instructores</h2>
                    <button class="modal-close" onclick="cerrarModalFicha()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalFichaId">
                    
                    <div class="instructores-list">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="cerrarModalFicha()">Cancelar</button>
                    <button class="btn btn-primary" onclick="guardarInstructoresFicha()">
                        <i class="fas fa-save"></i> Guardar Asignaciones
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= asset('js/common/app.js') ?>"></script>
    <script src="<?= asset('js/modules/instructor-fichas.js') ?>"></script>
</body>
</html>
