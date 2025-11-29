<?php
// Vista: Dashboard de Reportes de Asistencia para Instructores
// - Selector de fichas con cards
// - Historial de exportaciones
// - Generación de reporte vía AJAX con loader y notificaciones
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Reportes de Asistencia - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/gestion-reportes.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'gestion-reportes';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container gestion-reportes">
                <h2><i class="fas fa-file-excel"></i> Exportar Reportes</h2>
                <p class="subtitle-page">
                    Selecciona una ficha y una fecha para generar el reporte de asistencia en Excel.
                </p>

                <section class="selector-reportes">
                    <h3>1. Seleccionar Ficha y Fecha</h3>
                    <?php if (empty($fichas)): ?>
                        <div class="empty-state">
                            <h4><i class="fas fa-info-circle"></i> No tienes fichas disponibles</h4>
                            <p>No hay fichas asignadas a tu perfil ni fichas donde hayas registrado asistencia.</p>
                        </div>
                    <?php else: ?>
                    <form id="formGenerarReporte">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="selector-ficha-simple">
                            <div class="form-group">
                                <label for="buscar_ficha">Buscar por número de ficha</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-search"></i>
                                    <input type="text"
                                           id="buscar_ficha"
                                           class="form-control"
                                           placeholder="Escribe el número de ficha para filtrar">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="ficha_select">Ficha</label>
                                <select id="ficha_select" class="form-control" name="ficha_id">
                                    <option value="">-- Selecciona una ficha --</option>
                                    <?php foreach ($fichas as $ficha): ?>
                                        <option value="<?= (int) $ficha['id'] ?>"
                                                data-numero="<?= htmlspecialchars($ficha['numero_ficha']) ?>">
                                            <?= htmlspecialchars($ficha['numero_ficha'] . ' - ' . $ficha['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="selector-fecha">
                            <div class="form-group">
                                <label for="fecha_reporte">Fecha del reporte</label>
                                <input type="date"
                                       id="fecha_reporte"
                                       name="fecha"
                                       class="form-control"
                                       value="<?= date('Y-m-d') ?>"
                                       max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="acciones-reporte">
                            <button type="button" id="btnGenerarReporte" class="btn btn-primary" disabled>
                                <span class="btn-text"><i class="fas fa-file-export"></i> Generar Reporte</span>
                                <span class="btn-loader" style="display: none;">
                                    <div class="spinner-small"></div> Generando...
                                </span>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div id="alertContainer" class="alert-container" style="display: none;"></div>
                </section>

                <section class="historial-exportaciones">
                    <h3>2. Historial de Exportaciones</h3>
                    <?php if (empty($historial)): ?>
                        <p class="text-muted">Aún no has generado ningún reporte.</p>
                    <?php else: ?>
                        <div class="tabla-historial-wrapper">
                            <table class="tabla-historial">
                                <thead>
                                    <tr>
                                        <th>Fecha Reporte</th>
                                        <th>Ficha</th>
                                        <th>Archivo</th>
                                        <th>Presentes</th>
                                        <th>Ausentes</th>
                                        <th>Tardanzas</th>
                                        <th>Descargar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['fecha_reporte']) ?></td>
                                            <td><?= htmlspecialchars($item['numero_ficha'] . ' - ' . $item['nombre_ficha']) ?></td>
                                            <td><?= htmlspecialchars($item['nombre_archivo']) ?></td>
                                            <td><?= (int) $item['presentes'] ?></td>
                                            <td><?= (int) $item['ausentes'] ?></td>
                                            <td><?= (int) $item['tardanzas'] ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-secondary" href="<?= '/exports/' . rawurlencode($item['nombre_archivo']) ?>" target="_blank">
                                                    <i class="fas fa-download"></i> Descargar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend</strong></p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/modules/gestion-reportes.js') ?>"></script>
</body>
</html>


