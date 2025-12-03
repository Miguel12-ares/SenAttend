<?php
/** @var array $user */
/** @var array $equipos */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Aprendiz - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard-admin/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-panel';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <!-- Header del Dashboard -->
                <div class="dashboard-header">
                    <h2>
                        <i class="fas fa-user-graduate"></i>
                        Panel de Aprendiz
                    </h2>
                    <p class="subtitle">
                        Bienvenido, <?= htmlspecialchars($user['nombre'] . ' ' . ($user['apellido'] ?? '')) ?>. Gestiona tus equipos y sus accesos al CTA.
                    </p>
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

                <!-- Acciones Rápidas -->
                <div class="actions-section">
                    <h3>Acciones Rápidas</h3>
                    
                    <div class="actions-grid-sena">
                        <!-- Registrar Equipo -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <h4>Registrar Equipo</h4>
                            <p>Registra un nuevo equipo para poder ingresarlo al CTA.</p>
                            <div class="action-buttons">
                                <a href="/aprendiz/equipos/crear" class="btn-sena">
                                    <i class="fas fa-plus"></i>
                                    Registrar Equipo
                                </a>
                            </div>
                        </div>

                        <!-- Mis Equipos -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-list"></i>
                            </div>
                            <h4>Mis Equipos</h4>
                            <p>Visualiza y gestiona todos tus equipos registrados.</p>
                            <div class="action-buttons">
                                <a href="/aprendiz/equipos" class="btn-sena">
                                    <i class="fas fa-eye"></i>
                                    Ver Mis Equipos
                                </a>
                            </div>
                        </div>

                        <!-- Mis Asistencias -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4>Mis Asistencias</h4>
                            <p>Revisa el historial de tus asistencias al CTA con fechas, estados e instructores.</p>
                            <div class="action-buttons">
                                <a href="/aprendiz/asistencias" class="btn-sena">
                                    <i class="fas fa-eye"></i>
                                    Ver Mis Asistencias
                                </a>
                            </div>
                        </div>

                        <!-- Generar QR de Asistencia -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <h4>Generar QR de Asistencia</h4>
                            <p>Genera tu código QR personal para que el instructor registre tu asistencia al CTA.</p>
                            <div class="action-buttons">
                                <a href="/aprendiz/generar-qr" class="btn-sena">
                                    <i class="fas fa-qrcode"></i>
                                    Generar Mi QR
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Equipos -->
                <?php if (!empty($equipos)): ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-laptop"></i> Mis Equipos Registrados</h2>
                    </div>
                    <div class="aprendiz-equipos-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Equipo</th>
                                    <th>Serial</th>
                                    <th>Marca</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipos as $equipo): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($equipo['imagen'])): ?>
                                                <img src="<?= asset($equipo['imagen']) ?>" alt="Equipo" class="equipo-thumb">
                                            <?php else: ?>
                                                <i class="fas fa-laptop" style="font-size: 2rem; color: #39A900;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= htmlspecialchars($equipo['numero_serial']) ?></code></td>
                                        <td><?= htmlspecialchars($equipo['marca']) ?></td>
                                        <td>
                                            <span class="badge-<?= $equipo['estado'] === 'activo' ? 'activo' : 'inactivo' ?>">
                                                <?= htmlspecialchars(ucfirst($equipo['estado'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/aprendiz/equipos/<?= (int)$equipo['equipo_id'] ?>/qr" class="btn btn-primary btn-sm">
                                                <i class="fas fa-qrcode"></i> Ver QR
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php else: ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-laptop"></i> Mis Equipos Registrados</h2>
                    </div>
                    <div class="empty-state" style="text-align: center; padding: 3rem 1rem;">
                        <i class="fas fa-laptop" style="font-size: 4rem; color: #999; margin-bottom: 1rem;"></i>
                        <p style="color: #666; font-size: 1.1rem;">No tienes equipos registrados aún.</p>
                        <a href="/aprendiz/equipos/crear" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Registrar mi primer equipo
                        </a>
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
    <script src="<?= asset('js/aprendiz/panel.js') ?>"></script>
</body>
</html>


