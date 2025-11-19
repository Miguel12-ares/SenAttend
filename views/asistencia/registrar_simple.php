<?php
// Vista simplificada de Registro de Asistencia - FUNCIONAL
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Asistencia - SENAttend</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/asistencia-registrar.css">
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
                        <a href="/" class="btn btn-secondary btn-sm">Volver al Dashboard</a>
                        <span class="user-info">
                            <strong><?= htmlspecialchars($user['nombre'] ?? 'Usuario') ?></strong>
                            <span class="badge badge-<?= $user['rol'] ?? 'instructor' ?>"><?= ucfirst($user['rol'] ?? 'Instructor') ?></span>
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

                <!-- Selector de Ficha y Fecha -->
                <div class="selector-section">
                    <form method="GET" action="/asistencia/registrar">
                        <div class="form-row">
                            <div class="form-group-inline">
                                <label for="ficha">Seleccionar Ficha *</label>
                                <select name="ficha" id="ficha" class="form-control" required onchange="this.form.submit()">
                                    <option value="">-- Seleccione una ficha --</option>
                                    <?php if (isset($fichas) && is_array($fichas)): ?>
                                        <?php foreach ($fichas as $f): ?>
                                            <option value="<?= $f['id'] ?>" <?= ($fichaSeleccionada == $f['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($f['numero_ficha']) ?> - <?= htmlspecialchars($f['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group-inline">
                                <label for="fecha">Fecha de Registro *</label>
                                <input type="date" name="fecha" id="fecha" class="form-control" 
                                       value="<?= htmlspecialchars($fechaSeleccionada ?? date('Y-m-d')) ?>" 
                                       max="<?= date('Y-m-d') ?>"
                                       onchange="this.form.submit()" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Cargar</button>
                        </div>
                    </form>

                    <?php if (isset($validacionFecha) && !$validacionFecha['valido']): ?>
                    <div class="alert alert-error" style="margin-top: 1rem;">
                        ⚠️ <?= htmlspecialchars($validacionFecha['mensaje']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Estadísticas -->
                <?php if (isset($estadisticas) && $estadisticas && $estadisticas['total'] > 0): ?>
                <div class="stats-grid">
                    <div class="stat-box">
                        <h4>Total Registrados</h4>
                        <div class="number"><?= $estadisticas['total'] ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Presentes</h4>
                        <div class="number" style="color: #28a745;"><?= $estadisticas['presentes'] ?> (<?= $estadisticas['porcentaje_presentes'] ?? 0 ?>%)</div>
                    </div>
                    <div class="stat-box">
                        <h4>Ausentes</h4>
                        <div class="number" style="color: #dc3545;"><?= $estadisticas['ausentes'] ?> (<?= $estadisticas['porcentaje_ausentes'] ?? 0 ?>%)</div>
                    </div>
                    <div class="stat-box">
                        <h4>Tardanzas</h4>
                        <div class="number" style="color: #ffc107;"><?= $estadisticas['tardanzas'] ?> (<?= $estadisticas['porcentaje_tardanzas'] ?? 0 ?>%)</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabla de Aprendices -->
                <?php if (isset($ficha) && $ficha && !empty($aprendices) && isset($validacionFecha) && $validacionFecha['valido']): ?>
                <form method="POST" action="/asistencia/guardar" id="formAsistencia">
                    <input type="hidden" name="ficha_id" value="<?= $fichaSeleccionada ?>">
                    <input type="hidden" name="fecha" value="<?= htmlspecialchars($fechaSeleccionada) ?>">

                    <div class="tabla-asistencia">
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Documento</th>
                                    <th>Apellidos y Nombres</th>
                                    <th>Código Carnet</th>
                                    <th>Estado de Asistencia</th>
                                    <th>Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $contador = 1; ?>
                                <?php foreach ($aprendices as $aprendiz): ?>
                                <tr>
                                    <td><?= $contador++ ?></td>
                                    <td><?= htmlspecialchars($aprendiz['documento']) ?></td>
                                    <td><strong><?= htmlspecialchars($aprendiz['apellido'] . ' ' . $aprendiz['nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($aprendiz['codigo_carnet'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (isset($aprendiz['asistencia_id']) && $aprendiz['asistencia_id']): ?>
                                            <!-- Ya tiene registro -->
                                            <span class="badge-estado badge-<?= $aprendiz['asistencia_estado'] ?>">
                                                <?= ucfirst($aprendiz['asistencia_estado']) ?>
                                            </span>
                                        <?php else: ?>
                                            <!-- Registrar nuevo -->
                                            <div class="estado-radio">
                                                <label>
                                                    <input type="radio" name="asistencias[<?= $aprendiz['id_aprendiz'] ?>]" value="presente" required>
                                                    Presente
                                                </label>
                                                <label>
                                                    <input type="radio" name="asistencias[<?= $aprendiz['id_aprendiz'] ?>]" value="ausente">
                                                    Ausente
                                                </label>
                                                <label>
                                                    <input type="radio" name="asistencias[<?= $aprendiz['id_aprendiz'] ?>]" value="tardanza">
                                                    Tardanza
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= isset($aprendiz['asistencia_hora']) && $aprendiz['asistencia_hora'] ? date('h:i A', strtotime($aprendiz['asistencia_hora'])) : '--' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 2rem; text-align: center;">
                        <button type="submit" class="btn-guardar" id="btnGuardar">
                            💾 Guardar Asistencia
                        </button>
                        <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                            Se guardarán <span id="conteoSeleccionados">0</span> registros nuevos
                        </p>
                    </div>
                </form>

                <script>
                // Contar selecciones
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('formAsistencia');
                    if (form) {
                        form.addEventListener('change', function() {
                            const radios = document.querySelectorAll('input[type="radio"]:checked');
                            const contador = document.getElementById('conteoSeleccionados');
                            const btnGuardar = document.getElementById('btnGuardar');
                            
                            if (contador) contador.textContent = radios.length;
                            if (btnGuardar) btnGuardar.disabled = radios.length === 0;
                        });
                    }
                });
                </script>

                <?php elseif (isset($ficha) && $ficha && empty($aprendices)): ?>
                <div class="empty-state">
                    <h3>📋 No hay aprendices en esta ficha</h3>
                    <p>La ficha seleccionada no tiene aprendices asignados.</p>
                    <a href="/aprendices" class="btn btn-primary">Gestionar Aprendices</a>
                </div>

                <?php elseif (!isset($ficha) || !$ficha): ?>
                <div class="empty-state">
                    <h3>👆 Seleccione una ficha para comenzar</h3>
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

    <script src="/js/app.js"></script>
</body>
</html>
