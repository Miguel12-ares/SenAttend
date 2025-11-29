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
    // Variables globales
    let aprendicesData = [];
    let fichaSeleccionada = null;
    let fechaSeleccionada = null;

    // Inicialización cuando el DOM está listo
    document.addEventListener('DOMContentLoaded', function() {
        inicializarEventos();
        
        // Si hay datos precargados, mostrarlos
        <?php if ($ficha && !empty($aprendices) && $validacionFecha['valido']): ?>
        fichaSeleccionada = <?= $fichaSeleccionada ?? 'null' ?>;
        fechaSeleccionada = '<?= $fechaSeleccionada ?>';
        aprendicesData = <?= json_encode($aprendices) ?>;
        renderizarTablaAprendices(aprendicesData);
        document.getElementById('contenedorAprendices').style.display = 'block';
        <?php endif; ?>
    });

    function inicializarEventos() {
        // Evento para cargar aprendices dinámicamente
        document.getElementById('btnCargarAprendices').addEventListener('click', cargarAprendicesDinamico);
        
        // Eventos de controles masivos
        document.getElementById('btnMarcarTodosPresente').addEventListener('click', () => marcarTodos('presente'));
        document.getElementById('btnMarcarTodosAusente').addEventListener('click', () => marcarTodos('ausente'));
        document.getElementById('btnLimpiarSeleccion').addEventListener('click', limpiarSeleccion);
        
        // Evento de envío del formulario
        document.getElementById('formAsistencia').addEventListener('submit', guardarAsistencia);
        
        // Atajos de teclado
        document.addEventListener('keydown', manejarAtajosTeclado);
    }

    async function cargarAprendicesDinamico() {
        const fichaId = document.getElementById('ficha').value;
        const fecha = document.getElementById('fecha').value;
        
        if (!fichaId || !fecha) {
            mostrarAlerta('Por favor seleccione una ficha y fecha', 'warning');
            return;
        }

        const btnCargar = document.getElementById('btnCargarAprendices');
        const btnText = btnCargar.querySelector('.btn-text');
        const btnLoader = btnCargar.querySelector('.btn-loader');
        
        // Mostrar loader
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-flex';
        btnCargar.disabled = true;

        try {
            const response = await fetch(`/api/asistencia/aprendices/${fichaId}?fecha=${fecha}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                aprendicesData = data.aprendices;
                fichaSeleccionada = fichaId;
                fechaSeleccionada = fecha;
                
                renderizarTablaAprendices(aprendicesData);
                document.getElementById('contenedorAprendices').style.display = 'block';
                
                // Actualizar campos hidden
                document.getElementById('fichaIdHidden').value = fichaId;
                document.getElementById('fechaHidden').value = fecha;
                
                mostrarAlerta(`Se cargaron ${aprendicesData.length} aprendices exitosamente`, 'success');
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando aprendices:', error);
            mostrarAlerta('Error al cargar aprendices: ' + error.message, 'error');
        } finally {
            // Ocultar loader
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
            btnCargar.disabled = false;
        }
    }

    function renderizarTablaAprendices(aprendices) {
        const tbody = document.getElementById('tablaAprendicesBody');
        tbody.innerHTML = '';

        aprendices.forEach((aprendiz, index) => {
            const row = document.createElement('tr');
            row.className = aprendiz.asistencia_id ? 'ya-registrado' : 'sin-registro';
            
            row.innerHTML = `
                <td class="col-numero">${index + 1}</td>
                <td class="col-foto">
                    <div class="avatar-placeholder">
                        ${aprendiz.nombre.charAt(0)}${aprendiz.apellido.charAt(0)}
                    </div>
                </td>
                <td class="col-documento">${aprendiz.documento}</td>
                <td class="col-nombre">
                    <strong>${aprendiz.apellido}, ${aprendiz.nombre}</strong>
                </td>
                <td class="col-email">${aprendiz.email || 'N/A'}</td>
                <td class="col-estado">
                    ${renderizarEstadoAsistencia(aprendiz)}
                </td>
                <td class="col-hora">
                    ${aprendiz.asistencia_hora ? formatearHora(aprendiz.asistencia_hora) : '--'}
                </td>
                <td class="col-observaciones">
                    ${renderizarObservaciones(aprendiz)}
                </td>
            `;
            
            tbody.appendChild(row);
        });

        // Agregar eventos a los controles
        agregarEventosTabla();
        actualizarContadores();
    }

    function renderizarEstadoAsistencia(aprendiz) {
        if (aprendiz.asistencia_id) {
            // Ya tiene registro
            const badgeClass = `badge-estado badge-${aprendiz.asistencia_estado}`;
            return `<span class="${badgeClass}">${capitalizar(aprendiz.asistencia_estado)}</span>`;
        } else {
            // Registrar nuevo
            return `
                <div class="estado-radio-moderno">
                    <label class="radio-label radio-presente">
                        <input type="radio" name="asistencias[${aprendiz.id_aprendiz}]" value="presente" required>
                        <span class="radio-custom"></span>
                        <span class="radio-text">Presente</span>
                    </label>
                    <label class="radio-label radio-ausente">
                        <input type="radio" name="asistencias[${aprendiz.id_aprendiz}]" value="ausente">
                        <span class="radio-custom"></span>
                        <span class="radio-text">Ausente</span>
                    </label>
                    <label class="radio-label radio-tardanza">
                        <input type="radio" name="asistencias[${aprendiz.id_aprendiz}]" value="tardanza">
                        <span class="radio-custom"></span>
                        <span class="radio-text">Tardanza</span>
                    </label>
                </div>
            `;
        }
    }

    function renderizarObservaciones(aprendiz) {
        if (aprendiz.asistencia_id) {
            return aprendiz.observaciones || '--';
        } else {
            return `<textarea name="observaciones[${aprendiz.id_aprendiz}]" class="observaciones-input" placeholder="Observaciones opcionales..." maxlength="255"></textarea>`;
        }
    }

    function agregarEventosTabla() {
        // Eventos para radio buttons
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', actualizarContadores);
        });
    }

    function actualizarContadores() {
        const presentes = document.querySelectorAll('input[value="presente"]:checked').length;
        const ausentes = document.querySelectorAll('input[value="ausente"]:checked').length;
        const tardanzas = document.querySelectorAll('input[value="tardanza"]:checked').length;
        const total = presentes + ausentes + tardanzas;

        document.getElementById('conteoPresentes').textContent = presentes;
        document.getElementById('conteoAusentes').textContent = ausentes;
        document.getElementById('conteoTardanzas').textContent = tardanzas;
        document.getElementById('conteoSeleccionados').textContent = total;

        // Habilitar/deshabilitar botón de guardar
        document.getElementById('btnGuardar').disabled = total === 0;
    }

    function marcarTodos(estado) {
        document.querySelectorAll(`input[value="${estado}"]`).forEach(radio => {
            if (!radio.closest('tr').classList.contains('ya-registrado')) {
                radio.checked = true;
            }
        });
        actualizarContadores();
    }

    function limpiarSeleccion() {
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            if (!radio.closest('tr').classList.contains('ya-registrado')) {
                radio.checked = false;
            }
        });
        actualizarContadores();
    }

    async function guardarAsistencia(event) {
        event.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardar');
        const btnText = btnGuardar.querySelector('.btn-text');
        const btnLoader = btnGuardar.querySelector('.btn-loader');
        
        // Mostrar loader
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-flex';
        btnGuardar.disabled = true;

        try {
            // Recopilar datos del formulario
            const formData = new FormData(event.target);
            
            // Validar que hay selecciones
            const radiosChecked = document.querySelectorAll('input[type="radio"]:checked');
            if (radiosChecked.length === 0) {
                throw new Error('Debe marcar al menos un aprendiz');
            }

            // Enviar formulario tradicional (por compatibilidad)
            event.target.submit();
            
        } catch (error) {
            mostrarAlerta('Error: ' + error.message, 'error');
            
            // Restaurar botón
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
            btnGuardar.disabled = false;
        }
    }

    function manejarAtajosTeclado(event) {
        // Ctrl + P: Marcar todos presente
        if (event.ctrlKey && event.key === 'p') {
            event.preventDefault();
            marcarTodos('presente');
        }
        
        // Ctrl + A: Marcar todos ausente
        if (event.ctrlKey && event.key === 'a') {
            event.preventDefault();
            marcarTodos('ausente');
        }
        
        // Ctrl + T: Marcar todos tardanza
        if (event.ctrlKey && event.key === 't') {
            event.preventDefault();
            marcarTodos('tardanza');
        }
        
        // Ctrl + L: Limpiar selección
        if (event.ctrlKey && event.key === 'l') {
            event.preventDefault();
            limpiarSeleccion();
        }
    }

    // Funciones utilitarias
    function mostrarAlerta(mensaje, tipo) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-temporal`;
        alertDiv.textContent = mensaje;
        
        const container = document.querySelector('.asistencia-form');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    function capitalizar(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatearHora(hora) {
        const [h, m] = hora.split(':');
        const hour = parseInt(h);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${m} ${ampm}`;
    }
    </script>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

