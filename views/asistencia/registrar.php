<?php
// Vista de Registro de Asistencia - FUNCIONALIDAD CRÍTICA MVP
// Sprint 4 - Registro Manual
// Dev 3: Interfaz Registro Manual Optimizada
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Asistencia - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/asistencia-registrar.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/asistencia-registrar-optimizado.css') ?>">
</head>
<body>
    <div class="wrapper">
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <h1>SENAttend</h1>
                        <p class="subtitle">Registro de Asistencia</p>
                    </div>
                    <nav class="nav">
                        <a href="/dashboard" class="btn btn-secondary btn-sm">Volver al Dashboard</a>
                        <span class="user-info">
                            <strong><?= htmlspecialchars($user['nombre']) ?></strong>
                            <span class="badge badge-<?= $user['rol'] ?>"><?= ucfirst($user['rol']) ?></span>
                        </span>
                        <a href="/auth/logout" class="btn btn-secondary btn-sm">Cerrar Sesión</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container asistencia-form">
                <h2>Registro de Asistencia</h2>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['errors'])): ?>
                <div class="alert alert-error">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['errors']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['warnings'])): ?>
                <div class="alert" style="background: #fff3cd; border-left-color: #ffc107;">
                    <?php foreach ($_SESSION['warnings'] as $warning): ?>
                        <p><?= htmlspecialchars($warning) ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['warnings']); ?>
                </div>
                <?php endif; ?>

                <!-- Selector de Ficha y Fecha - Dev 3: Carga dinámica optimizada -->
                <div class="selector-section">
                    <form method="GET" action="/asistencia/registrar" id="formSelector">
                        <div class="form-row">
                            <div class="form-group-inline">
                                <label for="ficha">Seleccionar Ficha *</label>
                                <div class="select-wrapper">
                                    <select name="ficha" id="ficha" class="form-control" required>
                                        <option value="">-- Seleccione una ficha --</option>
                                        <?php foreach ($fichas as $f): ?>
                                            <option value="<?= $f['id'] ?>" <?= $fichaSeleccionada == $f['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($f['numero_ficha']) ?> - <?= htmlspecialchars($f['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="loader" id="fichaLoader" style="display: none;">
                                        <div class="spinner"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group-inline">
                                <label for="fecha">Fecha de Registro *</label>
                                <input type="date" name="fecha" id="fecha" class="form-control" 
                                       value="<?= htmlspecialchars($fechaSeleccionada) ?>" 
                                       max="<?= date('Y-m-d') ?>"
                                       min="<?= date('Y-m-d', strtotime('-7 days')) ?>" required>
                            </div>

                            <div class="form-group-inline">
                                <button type="button" class="btn btn-primary" id="btnCargarAprendices">
                                    <span class="btn-text"><i class="fas fa-clipboard-list"></i> Cargar Aprendices</span>
                                    <span class="btn-loader" style="display: none;">
                                        <div class="spinner-small"></div> Cargando...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php if ($ficha && !$validacionFecha['valido']): ?>
                    <div class="alert alert-error" style="margin-top: 1rem;">
                        <i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($validacionFecha['mensaje']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas -->
                <?php if ($estadisticas && $estadisticas['total'] > 0): ?>
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4>Total Registrados</h4>
                        <div class="number"><?= $estadisticas['total'] ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Presentes</h4>
                        <div class="number" style="color: #28a745;"><?= $estadisticas['presentes'] ?> (<?= $estadisticas['porcentaje_presentes'] ?>%)</div>
                    </div>
                    <div class="stat-box">
                        <h4>Ausentes</h4>
                        <div class="number" style="color: #dc3545;"><?= $estadisticas['ausentes'] ?> (<?= $estadisticas['porcentaje_ausentes'] ?>%)</div>
                    </div>
                    <div class="stat-box">
                        <h4>Tardanzas</h4>
                        <div class="number" style="color: #ffc107;"><?= $estadisticas['tardanzas'] ?> (<?= $estadisticas['porcentaje_tardanzas'] ?>%)</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabla de Aprendices - Dev 3: Tabla responsive con funcionalidades avanzadas -->
                <?php if ($ficha && !empty($aprendices) && $validacionFecha['valido']): ?>
                <div id="contenedorAprendices" style="display: none;">
                    <!-- Controles de tabla -->
                    <div class="tabla-controles">
                        <div class="controles-izquierda">
                            <button type="button" class="btn btn-success btn-sm" id="btnMarcarTodosPresente">
                                <i class="fas fa-check"></i> Marcar Todos Presente
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" id="btnMarcarTodosAusente">
                                <i class="fas fa-xmark"></i> Marcar Todos Ausente
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" id="btnLimpiarSeleccion">
                                <i class="fas fa-rotate"></i> Limpiar Selección
                            </button>
                        </div>
                        <div class="controles-derecha">
                            <div class="contador-tiempo">
                                <span id="contadorAsistencia">
                                    <strong>Presentes:</strong> <span id="conteoPresentes">0</span> |
                                    <strong>Ausentes:</strong> <span id="conteoAusentes">0</span> |
                                    <strong>Tardanzas:</strong> <span id="conteoTardanzas">0</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="/asistencia/guardar" id="formAsistencia">
                        <input type="hidden" name="ficha_id" id="fichaIdHidden">
                        <input type="hidden" name="fecha" id="fechaHidden">

                        <div class="tabla-asistencia-responsive">
                            <table id="tablaAprendices" class="tabla-moderna">
                                <thead>
                                    <tr>
                                        <th class="col-numero">N°</th>
                                        <th class="col-foto">Foto</th>
                                        <th class="col-documento">Documento</th>
                                        <th class="col-nombre">Apellidos y Nombres</th>
                                        <th class="col-email">Correo Electrónico</th>
                                        <th class="col-estado">Estado de Asistencia</th>
                                        <th class="col-hora">Hora</th>
                                        <th class="col-observaciones">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaAprendicesBody">
                                    <!-- Contenido cargado dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <div class="botones-accion">
                            <button type="submit" class="btn-guardar" id="btnGuardar" disabled>
                                <span class="btn-text"><i class="fas fa-floppy-disk"></i> Guardar Asistencia</span>
                                <span class="btn-loader" style="display: none;">
                                    <div class="spinner-small"></div> Guardando...
                                </span>
                            </button>
                            <p class="info-guardado">
                                Se guardarán <span id="conteoSeleccionados">0</span> registros nuevos
                            </p>
                        </div>
                    </form>
                </div>

                <?php elseif ($ficha && empty($aprendices)): ?>
                <div class="empty-state">
                    <h3><i class="fas fa-clipboard-list"></i> No hay aprendices en esta ficha</h3>
                    <p>La ficha seleccionada no tiene aprendices asignados.</p>
                    <a href="/aprendices" class="btn btn-primary">Gestionar Aprendices</a>
                </div>

                <?php elseif (!$ficha): ?>
                <div class="empty-state">
                    <h3><i class="fas fa-hand-point-up"></i> Seleccione una ficha para comenzar</h3>
                    <p>Elija una ficha y fecha para registrar la asistencia.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend v1.0 MVP</strong></p>
            </div>
        </footer>
    </div>

    <!-- Dev 3: JavaScript optimizado para registro de asistencia -->
    <script>
    // Pasar datos precargados desde PHP a JavaScript
    <?php if ($ficha && !empty($aprendices) && $validacionFecha['valido']): ?>
    window.fichaSeleccionada = <?= $fichaSeleccionada ?? 'null' ?>;
    window.fechaSeleccionada = '<?= $fechaSeleccionada ?>';
    window.aprendicesPrecargados = <?= json_encode($aprendices) ?>;
    <?php else: ?>
    window.fichaSeleccionada = null;
    window.fechaSeleccionada = null;
    window.aprendicesPrecargados = [];
    <?php endif; ?>
    </script>
    <script src="<?= asset('js/modules/asistencia-registrar.js') ?>"></script>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

