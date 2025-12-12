<?php
/**
 * Vista para Registro de Anomalías
 * Permite registrar anomalías por aprendiz o para la ficha en general
 */

// Variables para el layout
$title = 'Registro de Anomalías - SENAttend';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/anomalias.css');
$showHeader = true;
$currentPage = 'anomalias-registrar';

// Obtener usuario de sesión (pasado desde el controlador)
$user = $user ?? null;
$fichas = $fichas ?? [];
$tiposAnomalias = $tiposAnomalias ?? [];

ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <?= $additionalStyles ?>
</head>
<body>
    <div class="wrapper">
        <?php require __DIR__ . '/../components/header.php'; ?>

        <main class="main-content">
            <div class="anomalias-container">
                <!-- Header -->
                <div class="anomalias-header">
                    <h1>
                        <i class="fas fa-exclamation-triangle"></i>
                        Registro de Anomalías
                    </h1>
                    <p class="subtitle">
                        Registra anomalías de asistencia por aprendiz o para la ficha en general.
                        Puedes registrar anomalías hasta 3 días después del registro de asistencia.
                    </p>
                </div>

                <!-- Selector de Ficha y Fecha -->
                <div class="anomalias-card config-card">
                    <h2>1. Selecciona la Ficha y Fecha</h2>
                    
                    <div class="form-group">
                        <label for="fichaSelect">Ficha</label>
                        <select id="fichaSelect" name="ficha_id" required>
                            <option value="">Selecciona una ficha...</option>
                            <?php foreach ($fichas as $ficha): ?>
                                <option value="<?= htmlspecialchars($ficha['id']) ?>">
                                    <?= htmlspecialchars($ficha['numero_ficha']) ?> - <?= htmlspecialchars($ficha['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fechaSelect">Fecha de Asistencia</label>
                        <input 
                            type="date" 
                            id="fechaSelect" 
                            name="fecha" 
                            max="<?= date('Y-m-d') ?>"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <button id="btnCargarAprendices" class="btn btn-primary" disabled>
                            <i class="fas fa-search"></i>
                            Cargar Aprendices
                        </button>
                    </div>

                    <div id="infoFecha" class="info-banner" style="display: none;">
                        <i class="fas fa-circle-info"></i>
                        <span id="infoFechaTexto"></span>
                    </div>
                </div>

                <!-- Anomalía General de Ficha -->
                <div id="anomaliaFichaCard" class="anomalias-card" style="display: none;">
                    <div class="card-header-with-action">
                        <h2>2. Anomalía General de Ficha</h2>
                        <button id="btnAnomaliaFicha" class="btn btn-warning">
                            <i class="fas fa-plus"></i>
                            Agregar Anomalía de Ficha
                        </button>
                    </div>
                    <div id="anomaliasFichaList" class="anomalias-list"></div>
                </div>

                <!-- Tabla de Aprendices -->
                <div id="aprendicesCard" class="anomalias-card" style="display: none;">
                    <h2>3. Aprendices de la Ficha</h2>
                    
                    <div class="table-responsive">
                        <table class="tabla-anomalias">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre Completo</th>
                                    <th>Estado Asistencia</th>
                                    <th>Anomalías</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="aprendicesTableBody">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mensaje cuando no hay datos -->
                <div id="mensajeVacio" class="anomalias-card empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Selecciona una ficha y una fecha para comenzar</p>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <!-- Modal para Registrar Anomalía -->
    <div id="modalAnomalia" class="modal">
        <div class="modal-content">
            <div class="modal-title">
                <span id="modalTitle">
                    <i class="fas fa-exclamation-triangle"></i>
                    Registrar Anomalía
                </span>
                <button class="modal-close" id="btnCerrarModal" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="formAnomalia">
                    <input type="hidden" id="anomaliaIdAprendiz" name="id_aprendiz">
                    <input type="hidden" id="anomaliaIdFicha" name="id_ficha">
                    <input type="hidden" id="anomaliaFecha" name="fecha_asistencia">
                    <input type="hidden" id="anomaliaTipo" name="tipo_anomalia" value="aprendiz">

                    <div id="infoAprendiz" class="info-box" style="display: none; margin-bottom: 20px;">
                        <strong>Aprendiz:</strong> <span id="infoAprendizNombre"></span>
                    </div>

                    <div class="form-group">
                        <label for="tipoAnomaliaSelect">Tipo de Anomalía <span class="required">*</span></label>
                        <div id="tiposAnomaliasContainer" class="tipos-anomalias-grid">
                            <?php foreach ($tiposAnomalias as $codigo => $tipo): ?>
                                <div class="tipo-anomalia-card" 
                                     data-tipo="<?= htmlspecialchars($codigo) ?>"
                                     style="border-left: 4px solid <?= htmlspecialchars($tipo['color']) ?>">
                                    <input 
                                        type="radio" 
                                        name="tipo_anomalia" 
                                        id="tipo_<?= htmlspecialchars($codigo) ?>" 
                                        value="<?= htmlspecialchars($codigo) ?>"
                                        required
                                    >
                                    <label for="tipo_<?= htmlspecialchars($codigo) ?>" class="tipo-anomalia-label">
                                        <i class="fas fa-<?= htmlspecialchars($tipo['icono'] ?? 'exclamation-triangle') ?>"></i>
                                        <span><?= htmlspecialchars($tipo['nombre']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcionAnomalia">Descripción (Opcional)</label>
                        <textarea 
                            id="descripcionAnomalia" 
                            name="descripcion" 
                            rows="3"
                            placeholder="Agrega detalles adicionales sobre la anomalía..."
                        ></textarea>
                    </div>

                    <div id="validacionFecha" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="mensajeValidacionFecha"></span>
                    </div>
                </form>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btnCancelarAnomalia">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarAnomalia">
                    <i class="fas fa-save"></i>
                    Registrar Anomalía
                </button>
            </div>
        </div>
    </div>

    <!-- Alertas -->
    <div id="alertContainer" class="alert-container"></div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/modules/anomalias.js') ?>"></script>
    <script>
        // Pasar datos PHP a JavaScript
        window.TIPOS_ANOMALIAS = <?= json_encode($tiposAnomalias) ?>;
        window.API_BASE = '<?= asset('') ?>';
    </script>
</body>
</html>

