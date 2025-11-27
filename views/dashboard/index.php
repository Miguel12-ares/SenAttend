<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'dashboard';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="dashboard-header">
                    <h2>Panel de Control</h2>
                    <p class="text-muted">
                        <?php if ($user['rol'] === 'admin'): ?>
                            Gestión de asistencia y aprendices SENA
                        <?php else: ?>
                            Sistema de Registro de Asistencia
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Estadísticas - Solo para Admin -->
                <?php if ($user['rol'] === 'admin'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-fichas"><i class="fas fa-book"></i></div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_fichas']) ?></h3>
                            <p>Fichas Registradas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-aprendices"><i class="fas fa-users"></i></div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_aprendices']) ?></h3>
                            <p>Aprendices Activos</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-usuarios"><i class="fas fa-user"></i></div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_usuarios']) ?></h3>
                            <p>Usuarios del Sistema</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menú de acciones -->
                <div class="actions-section">
                    <h3>Acciones Rápidas</h3>
                    <div class="actions-grid">
                        
                        <!-- Acciones para Instructor y Coordinador -->
                        <?php if (in_array($user['rol'], ['instructor', 'coordinador'])): ?>
                        
                        <a href="/asistencia/registrar" class="action-card">
                            <span class="action-icon"><i class="fas fa-check"></i></span>
                            <h4>Registrar Asistencia</h4>
                            <p>Marcar asistencia de aprendices</p>
                        </a>

                        <a href="/qr/escanear" class="action-card action-card-qr">
                            <span class="action-icon"><i class="fas fa-camera"></i></span>
                            <h4>Escanear QR</h4>
                            <p>Registrar asistencia con código QR</p>
                        </a>

                        <?php endif; ?>

                        <!-- Acciones para Admin -->
                        <?php if ($user['rol'] === 'admin'): ?>
                        
                        <a href="/fichas" class="action-card">
                            <span class="action-icon"><i class="fas fa-clipboard-list"></i></span>
                            <h4>Ver Fichas</h4>
                            <p>Consultar fichas registradas</p>
                        </a>

                        <a href="/aprendices" class="action-card">
                            <span class="action-icon"><i class="fas fa-users"></i></span>
                            <h4>Gestionar Aprendices</h4>
                            <p>Administrar aprendices</p>
                        </a>

                        <a href="#" class="action-card" onclick="alert('Próximamente: Reportes y estadísticas'); return false;">
                            <span class="action-icon"><i class="fas fa-chart-bar"></i></span>
                            <h4>Reportes</h4>
                            <p>Generar reportes de asistencia</p>
                        </a>

                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lista de fichas activas - Solo para Admin -->
                <?php if ($user['rol'] === 'admin' && !empty($fichasActivas)): ?>
                <div class="fichas-section">
                    <h3>Fichas Activas Recientes</h3>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número de Ficha</th>
                                    <th>Nombre del Programa</th>
                                    <th>Estado</th>
                                    <th>Aprendices</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fichasActivas as $ficha): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ficha['numero_ficha']) ?></strong></td>
                                    <td>
                                        <div class="ficha-info">
                                            <div class="ficha-nombre"><?= htmlspecialchars($ficha['nombre']) ?></div>
                                            <small class="text-muted">Creada: <?= date('d/m/Y', strtotime($ficha['created_at'] ?? 'now')) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($ficha['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="aprendices-info">
                                            <strong><?= $ficha['total_aprendices'] ?? 0 ?></strong>
                                            <small class="text-muted">registrados</small>
                                            <?php if (($ficha['aprendices_activos'] ?? 0) != ($ficha['total_aprendices'] ?? 0)): ?>
                                                <small class="text-success"><?= $ficha['aprendices_activos'] ?? 0 ?> activos</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="btn-group">
                                            <a href="/fichas/<?= $ficha['id'] ?>" class="btn btn-sm btn-primary" title="Ver detalles de la ficha">
                                                <i class="fas fa-eye"></i> Detalles
                                            </a>
                                            <a href="/asistencia/registrar?ficha=<?= $ficha['id'] ?>" class="btn btn-sm btn-success" title="Registrar asistencia">
                                                <i class="fas fa-check"></i> Asistencia
                                            </a>
                                            <a href="/fichas/<?= $ficha['id'] ?>/editar" class="btn btn-sm btn-secondary" title="Editar ficha">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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

    <script src="<?= asset('js/app.js') ?>"></script>
    
    <style>
    /* Estilos adicionales para la tabla mejorada */
    .ficha-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .ficha-nombre {
        font-weight: 500;
        color: #333;
    }
    
    .aprendices-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .aprendices-info strong {
        font-size: 1.2em;
        color: var(--color-primary, #39A900);
    }
    
    .actions-cell {
        min-width: 200px;
    }
    
    .btn-group {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .btn-group .btn {
        padding: 0.5rem 0.875rem;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        white-space: nowrap;
        margin: 0;
    }
    
    .btn-group .btn i,
    .btn-group .btn .fas,
    .btn-group .btn .far {
        font-size: 0.875rem;
        margin: 0;
        line-height: 1;
    }
    
    /* Los estilos de tablas están centralizados en style.css y dashboard.css */
    
    .action-card-qr {
        border: 2px solid #39A900;
        background: linear-gradient(135deg, #f8fff5 0%, #ffffff 100%);
    }
    
    .action-card-qr:hover {
        border-color: #2d8400;
        background: linear-gradient(135deg, #e8f5e0 0%, #f8fff5 100%);
    }
    
    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: 500;
    }
    
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    
    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    .text-muted {
        color: #6c757d !important;
        font-size: 0.9em;
    }
    
    /* Responsividad */
    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
        }
        
        .btn-group .btn {
            margin-bottom: 2px;
        }
        
        .actions-cell {
            min-width: 120px;
        }
    }
    </style>
</body>
</html>

