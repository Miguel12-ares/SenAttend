<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Instructor - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/modules/instructor-fichas.css') ?>">
</head>
<body>
    <div class="wrapper">
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <h1>SENAttend</h1>
                        <p class="subtitle">Detalle del Instructor</p>
                    </div>
                    <nav class="nav">
                        <a href="/instructor-fichas" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <div class="detail-header">
                    <h2><i class="fas fa-user-tie"></i> <?= htmlspecialchars($instructor['nombre']) ?></h2>
                    <span class="badge badge-instructor">Instructor</span>
                </div>

                <div class="detail-grid">
                    <div class="detail-card">
                        <h3>Información General</h3>
                        <ul>
                            <li><strong>Documento:</strong> <?= htmlspecialchars($instructor['documento']) ?></li>
                            <li><strong>Email:</strong> <?= htmlspecialchars($instructor['email']) ?></li>
                            <li><strong>Total fichas:</strong> <?= $instructor['total_fichas_asignadas'] ?? count($instructor['fichas_asignadas'] ?? []) ?></li>
                        </ul>
                    </div>
                    <div class="detail-card">
                        <h3>Resumen de Asignaciones</h3>
                        <p><strong>Fichas activas:</strong> <?= $instructor['total_fichas_activas'] ?? 0 ?></p>
                        <p><strong>Última actualización:</strong> <?= date('d/m/Y') ?></p>
                    </div>
                </div>

                <section class="detail-section">
                    <div class="section-header">
                        <h3><i class="fas fa-graduation-cap"></i> Fichas Asignadas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Asignada desde</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($instructor['fichas_asignadas'])): ?>
                                    <?php foreach ($instructor['fichas_asignadas'] as $ficha): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ficha['numero_ficha']) ?></td>
                                        <td><?= htmlspecialchars($ficha['nombre']) ?></td>
                                        <td><?= ucfirst($ficha['estado']) ?></td>
                                        <td><?= htmlspecialchars($ficha['fecha_asignacion'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4">No tiene fichas asignadas actualmente.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="detail-section">
                    <div class="section-header">
                        <h3><i class="fas fa-layer-group"></i> Fichas Disponibles Para Asignar</h3>
                    </div>
                    <?php if (!empty($fichasDisponibles)): ?>
                        <div class="fichas-grid">
                            <?php foreach ($fichasDisponibles as $ficha): ?>
                            <div class="ficha-card">
                                <div class="ficha-header">
                                    <h3><?= htmlspecialchars($ficha['numero_ficha']) ?></h3>
                                    <span class="badge badge-success"><?= ucfirst($ficha['estado']) ?></span>
                                </div>
                                <div class="ficha-body">
                                    <p><?= htmlspecialchars($ficha['nombre']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay fichas disponibles para asignar.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

