<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard-admin/dashboard.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'dashboard';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <!-- Header del Dashboard -->
                <div class="dashboard-header">
                    <h2>
                        <i class="fas fa-cogs"></i>
                        Panel Administrativo
                    </h2>
                    <p class="subtitle">
                        Accede a las funciones administrativas principales del sistema.
                    </p>
                </div>

                <!-- Acciones Rápidas -->
                <div class="actions-section">
                    <h3>Acciones Rápidas</h3>
                    
                    <div class="actions-grid-sena">
                        
                        <!-- Acciones para Instructor y Coordinador -->
                        <?php if (in_array($user['rol'], ['instructor', 'coordinador'])): ?>
                        
                        <!-- Registrar Asistencia -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h4>Registrar Asistencia</h4>
                            <p>Administrar, crear y deshabilitar registros de asistencia.</p>
                            <div class="action-buttons">
                                <a href="/asistencia/registrar" class="btn-sena">
                                    <i class="fas fa-plus"></i>
                                    Registrar Asistencia
                                </a>
                            </div>
                        </div>

                        <!-- Escanear QR -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <h4>Escanear QR</h4>
                            <p>Registrar asistencia mediante código QR de aprendices.</p>
                            <div class="action-buttons">
                                <a href="/qr/escanear" class="btn-sena">
                                    <i class="fas fa-camera"></i>
                                    Escanear QR
                                </a>
                            </div>
                        </div>

                        <?php endif; ?>



                        <!-- Acciones para Admin y Administrativo -->
                        <?php if (in_array($user['rol'], ['admin', 'administrativo'])): ?>
                        
                        <!-- Gestión de Fichas -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <h4>Gestión de Fichas</h4>
                            <p>Administrar, crear y deshabilitar fichas.</p>
                            <div class="action-buttons">
                                <a href="/fichas/crear" class="btn-sena">
                                    <i class="fas fa-plus"></i>
                                    Crear Ficha
                                </a>
                                <a href="/fichas" class="btn-sena">
                                    <i class="fas fa-list"></i>
                                    Administrar Fichas
                                </a>
                            </div>
                        </div>

                        <!-- Gestionar Aprendices -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h4>Gestionar Aprendices</h4>
                            <p>Centraliza la administración de aprendices.</p>
                            <div class="action-buttons">
                                <a href="/aprendices" class="btn-sena">
                                    <i class="fas fa-users-cog"></i>
                                    Administrar Aprendices
                                </a>
                            </div>
                        </div>

                        <!-- Gestión de Instructores -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h4>Gestión de Instructores</h4>
                            <p>Administrar instructores del sistema.</p>
                            <div class="action-buttons">
                                <a href="/gestion-instructores/crear" class="btn-sena">
                                    <i class="fas fa-plus"></i>
                                    Crear Instructor
                                </a>
                                <a href="/gestion-instructores" class="btn-sena">
                                    <i class="fas fa-list"></i>
                                    Administrar Instructores
                                </a>
                            </div>
                        </div>

                        <!-- Asignación de Fichas -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h4>Asignación de Fichas</h4>
                            <p>Asignar instructores a fichas de formación.</p>
                            <div class="action-buttons">
                                <a href="/instructor-fichas" class="btn-sena">
                                    <i class="fas fa-link"></i>
                                    Gestionar Asignaciones
                                </a>
                            </div>
                        </div>

                        <?php endif; ?>

                        <!-- Acciones solo para Admin -->
                        <?php if ($user['rol'] === 'admin'): ?>

                        <!-- Configurar Horarios -->
                        <div class="action-card-sena">
                            <div class="action-icon-sena">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Configurar Horarios</h4>
                            <p>Gestionar turnos y límites de llegada.</p>
                            <div class="action-buttons">
                                <a href="/configuracion/horarios" class="btn-sena">
                                    <i class="fas fa-cog"></i>
                                    Configurar Turnos
                                </a>
                            </div>
                        </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend v1.0 MVP</strong></p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/dashboard-admin/dashboard.js') ?>"></script>
</body>
</html>
