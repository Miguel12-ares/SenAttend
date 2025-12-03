<?php
/** @var array $user */
/** @var array $asistencias */
// Debug: Verificar que las variables estén definidas
if (!isset($asistencias)) {
    $asistencias = [];
    error_log("Vista asistencias: Variable \$asistencias no está definida");
}
if (!isset($user)) {
    error_log("Vista asistencias: Variable \$user no está definida");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Asistencias - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard-admin/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
    <style>
        .asistencias-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .asistencias-table th,
        .asistencias-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .asistencias-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        .badge-presente {
            background-color: #d4edda;
            color: #155724;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-ausente {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-tardanza {
            background-color: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #999;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-asistencias';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <!-- Header del Dashboard -->
                <div class="dashboard-header">
                    <div>
                        <h2>
                            <i class="fas fa-calendar-check"></i>
                            Mis Asistencias al CTA
                        </h2>
                        <p class="subtitle">
                            Revisa el historial de tus asistencias al Centro de Tecnología Agropecuaria.
                        </p>
                    </div>
                    <div>
                        <?php 
                        $url = '/aprendiz/panel';
                        require __DIR__ . '/../components/back-button.php'; 
                        ?>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Debug temporal -->
                <?php if (defined('APP_ENV') && APP_ENV === 'local'): ?>
                    <div style="background: #f0f0f0; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; font-size: 0.85rem;">
                        <strong>Debug Info:</strong><br>
                        Aprendiz ID: <?= htmlspecialchars($user['id'] ?? 'N/A') ?><br>
                        Total asistencias: <?= count($asistencias ?? []) ?><br>
                        <?php if (!empty($asistencias)): ?>
                            Primera asistencia ID: <?= htmlspecialchars($asistencias[0]['id'] ?? 'N/A') ?><br>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Tabla de Asistencias -->
                <?php if (!empty($asistencias)): ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-list"></i> Historial de Asistencias</h2>
                    </div>
                    <div class="aprendiz-equipos-list">
                        <table class="asistencias-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                    <th>Ficha</th>
                                    <th>Instructor</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($asistencias as $asistencia): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            try {
                                                echo date('d/m/Y', strtotime($asistencia['fecha']));
                                            } catch (Exception $e) {
                                                echo htmlspecialchars($asistencia['fecha'] ?? 'N/A');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            try {
                                                echo date('H:i', strtotime($asistencia['hora']));
                                            } catch (Exception $e) {
                                                echo htmlspecialchars($asistencia['hora'] ?? 'N/A');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $estado = $asistencia['estado'];
                                            $badgeClass = 'badge-' . $estado;
                                            $estadoTexto = ucfirst($estado);
                                            ?>
                                            <span class="<?= $badgeClass ?>">
                                                <?= htmlspecialchars($estadoTexto) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($asistencia['numero_ficha']) ?></strong>
                                            <?php if (!empty($asistencia['ficha_nombre'])): ?>
                                                <br>
                                                <small style="color: #666;">
                                                    <?= htmlspecialchars($asistencia['ficha_nombre']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($asistencia['instructor_nombre_completo'])): ?>
                                                <?= htmlspecialchars($asistencia['instructor_nombre_completo']) ?>
                                            <?php else: ?>
                                                <span style="color: #999;">No disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($asistencia['observaciones'])): ?>
                                                <span style="color: #666; font-size: 0.9rem;">
                                                    <?= htmlspecialchars($asistencia['observaciones']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #999;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php else: ?>
                <section class="aprendiz-equipos-card">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No tienes asistencias registradas aún.</p>
                        <p style="color: #999;">Las asistencias aparecerán aquí una vez que sean registradas por tu instructor.</p>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

