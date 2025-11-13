<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SENAttend</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <h1>SENAttend</h1>
                        <p class="subtitle">Sistema de Asistencia SENA</p>
                    </div>
                    <nav class="nav">
                        <span class="user-info">
                            Bienvenido, <strong><?= htmlspecialchars($user['nombre']) ?></strong>
                            <span class="badge badge-<?= $user['rol'] ?>"><?= ucfirst($user['rol']) ?></span>
                        </span>
                        <a href="/auth/logout" class="btn btn-secondary btn-sm">Cerrar Sesión</a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <div class="dashboard-header">
                    <h2>Panel de Control</h2>
                    <p class="text-muted">Gestión de asistencia y aprendices SENA</p>
                </div>

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-fichas">📚</div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_fichas']) ?></h3>
                            <p>Fichas Registradas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-aprendices">👥</div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_aprendices']) ?></h3>
                            <p>Aprendices Activos</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-usuarios">👤</div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_usuarios']) ?></h3>
                            <p>Usuarios del Sistema</p>
                        </div>
                    </div>
                </div>

                <!-- Menú de acciones -->
                <div class="actions-section">
                    <h3>Acciones Rápidas</h3>
                    <div class="actions-grid">
                        <a href="/asistencia/registrar" class="action-card">
                            <span class="action-icon">✓</span>
                            <h4>Registrar Asistencia</h4>
                            <p>Marcar asistencia de aprendices</p>
                        </a>

                        <a href="/fichas" class="action-card">
                            <span class="action-icon">📋</span>
                            <h4>Ver Fichas</h4>
                            <p>Consultar fichas registradas</p>
                        </a>

                        <a href="/aprendices" class="action-card">
                            <span class="action-icon">👥</span>
                            <h4>Gestionar Aprendices</h4>
                            <p>Administrar aprendices</p>
                        </a>

                        <a href="#" class="action-card" onclick="alert('Próximamente: Reportes y estadísticas'); return false;">
                            <span class="action-icon">📊</span>
                            <h4>Reportes</h4>
                            <p>Generar reportes de asistencia</p>
                        </a>
                    </div>
                </div>

                <!-- Lista de fichas activas -->
                <?php if (!empty($fichasActivas)): ?>
                <div class="fichas-section">
                    <h3>Fichas Activas Recientes</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número de Ficha</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fichasActivas as $ficha): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ficha['numero_ficha']) ?></strong></td>
                                    <td><?= htmlspecialchars($ficha['nombre']) ?></td>
                                    <td>
                                        <span class="badge badge-success"><?= ucfirst($ficha['estado']) ?></span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary" onclick="alert('Funcionalidad en desarrollo'); return false;">
                                            Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Información del MVP -->
                <div class="info-box">
                    <h4>✅ Estado del Sistema - Fase 0 MVP</h4>
                    <ul class="checklist">
                        <li>✓ Arquitectura MVC con PSR-4 configurada</li>
                        <li>✓ Conexión PDO persistente operativa</li>
                        <li>✓ Sistema de autenticación funcional</li>
                        <li>✓ Middleware de protección de rutas</li>
                        <li>✓ Base de datos con <?= $stats['total_fichas'] ?> fichas y <?= $stats['total_aprendices'] ?> aprendices</li>
                    </ul>
                </div>
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

