<?php
// Vista: Dashboard de Analítica y Reportes para Administrativos
// - Selector de período (semanal/mensual)
// - Selector de fichas
// - Generación de reportes vía AJAX
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analítica y Reportes - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/analytics.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'analytics';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container analytics-container">
                <!-- Header -->
                <div class="analytics-header">
                    <h2><i class="fas fa-chart-line"></i> Analítica y Reportes</h2>
                    <p class="subtitle-page">
                        Genera reportes estadísticos detallados de asistencia por ficha, con análisis semanal o mensual.
                    </p>
                </div>

                <!-- Selector de Reportes -->
                <section class="report-selector">
                    <h3>1. Configurar Reporte</h3>
                    
                    <?php if (empty($fichas)): ?>
                        <div class="empty-state">
                            <h4><i class="fas fa-info-circle"></i> No hay fichas disponibles</h4>
                            <p>No se encontraron fichas en el sistema.</p>
                        </div>
                    <?php else: ?>
                    
                    <form id="formGenerarReporte">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <!-- Selector de Tipo de Reporte -->
                        <div class="form-group">
                            <label for="tipo_reporte">Tipo de Reporte</label>
                            <div class="report-type-selector">
                                <div class="report-type-option">
                                    <input type="radio" id="tipo_semanal" name="tipo_reporte" value="semanal" checked>
                                    <label for="tipo_semanal">
                                        <i class="fas fa-calendar-week"></i>
                                        <span>Reporte Semanal</span>
                                        <small>Últimos 7 días</small>
                                    </label>
                                </div>
                                <div class="report-type-option">
                                    <input type="radio" id="tipo_mensual" name="tipo_reporte" value="mensual">
                                    <label for="tipo_mensual">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Reporte Mensual</span>
                                        <small>Mes completo</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Selector de Ficha -->
                        <div class="form-group">
                            <label for="buscar_ficha">Buscar Ficha</label>
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
                            <select id="ficha_select" class="form-control" name="ficha_id" required>
                                <option value="">-- Selecciona una ficha --</option>
                                <?php foreach ($fichas as $ficha): ?>
                                    <option value="<?= (int)$ficha['id'] ?>"
                                            data-numero="<?= htmlspecialchars($ficha['numero_ficha']) ?>">
                                        <?= htmlspecialchars($ficha['numero_ficha'] . ' - ' . $ficha['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Opciones de Período Semanal -->
                        <div id="opciones_semanal" class="periodo-options">
                            <div class="form-group">
                                <label for="fecha_inicio_semanal">Fecha de Inicio (Opcional)</label>
                                <input type="date"
                                       id="fecha_inicio_semanal"
                                       name="fecha_inicio"
                                       class="form-control"
                                       max="<?= date('Y-m-d') ?>">
                                <small class="form-text">Si no se especifica, se usará la última semana</small>
                            </div>
                        </div>

                        <!-- Opciones de Período Mensual -->
                        <div id="opciones_mensual" class="periodo-options" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="mes_select">Mes</label>
                                    <select id="mes_select" name="mes" class="form-control">
                                        <option value="">-- Mes Actual --</option>
                                        <option value="1">Enero</option>
                                        <option value="2">Febrero</option>
                                        <option value="3">Marzo</option>
                                        <option value="4">Abril</option>
                                        <option value="5">Mayo</option>
                                        <option value="6">Junio</option>
                                        <option value="7">Julio</option>
                                        <option value="8">Agosto</option>
                                        <option value="9">Septiembre</option>
                                        <option value="10">Octubre</option>
                                        <option value="11">Noviembre</option>
                                        <option value="12">Diciembre</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="año_select">Año</label>
                                    <select id="año_select" name="año" class="form-control">
                                        <option value="">-- Año Actual --</option>
                                        <?php 
                                        $añoActual = (int)date('Y');
                                        for ($i = $añoActual; $i >= $añoActual - 3; $i--): 
                                        ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de Generación -->
                        <div class="form-actions">
                            <button type="submit" id="btnGenerarReporte" class="btn btn-primary">
                                <span class="btn-text">
                                    <i class="fas fa-file-excel"></i> Generar Reporte
                                </span>
                                <span class="btn-loader" style="display: none;">
                                    <div class="spinner-small"></div> Generando...
                                </span>
                            </button>
                        </div>
                    </form>

                    <?php endif; ?>

                    <!-- Contenedor de Alertas -->
                    <div id="alertContainer" class="alert-container" style="display: none;"></div>
                </section>

                <!-- Información Adicional -->
                <section class="info-section">
                    <h3>2. Información del Reporte</h3>
                    <div class="info-cards">
                        <div class="info-card">
                            <i class="fas fa-chart-bar"></i>
                            <h4>Estadísticas Incluidas</h4>
                            <ul>
                                <li>Porcentaje de asistencia por ficha</li>
                                <li>Estadísticas detalladas por aprendiz</li>
                                <li>Media de hora de ingreso</li>
                                <li>Patrones de tardanzas por día</li>
                                <li>Aprendices con problemas de asistencia</li>
                                <li>Tardanzas justificadas</li>
                            </ul>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-file-excel"></i>
                            <h4>Formato del Archivo</h4>
                            <ul>
                                <li>Formato: Excel (.xlsx)</li>
                                <li>Múltiples hojas organizadas</li>
                                <li>Resumen general</li>
                                <li>Detalle por aprendiz</li>
                                <li>Análisis de patrones</li>
                                <li>Listo para imprimir o compartir</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend</strong></p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/modules/analytics.js') ?>"></script>
</body>
</html>
