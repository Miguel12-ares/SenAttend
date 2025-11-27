<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Ficha - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/instructor-fichas.css') ?>">
</head>
<body>
    <div class="wrapper">
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <h1>SENAttend</h1>
                        <p class="subtitle">Detalle de Ficha</p>
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
                    <h2><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($ficha['numero_ficha']) ?></h2>
                    <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($ficha['estado']) ?>
                    </span>
                </div>

                <div class="detail-grid">
                    <div class="detail-card">
                        <h3>Información de la Ficha</h3>
                        <ul>
                            <li><strong>Nombre:</strong> <?= htmlspecialchars($ficha['nombre']) ?></li>
                            <li><strong>Estado:</strong> <?= ucfirst($ficha['estado']) ?></li>
                            <li><strong>Creada:</strong> <?= htmlspecialchars($ficha['created_at'] ?? '-') ?></li>
                        </ul>
                    </div>
                    <div class="detail-card">
                        <h3>Resumen</h3>
                        <p><strong>Instructores asignados:</strong> <?= count($instructoresAsignados ?? []) ?></p>
                        <p><strong>Asignaciones disponibles:</strong> <?= count($instructoresDisponibles ?? []) ?></p>
                    </div>
                </div>

                <section class="detail-section">
                    <div class="section-header">
                        <h3><i class="fas fa-users"></i> Instructores Asignados</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Asignado desde</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($instructoresAsignados)): ?>
                                    <?php foreach ($instructoresAsignados as $instructor): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($instructor['nombre']) ?></td>
                                        <td><?= htmlspecialchars($instructor['email']) ?></td>
                                        <td><?= ucfirst($instructor['rol']) ?></td>
                                        <td><?= htmlspecialchars($instructor['fecha_asignacion'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4">No hay instructores asignados a esta ficha.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="detail-section">
                    <div class="section-header">
                        <h3><i class="fas fa-user-plus"></i> Instructores Disponibles</h3>
                    </div>
                    <?php if (!empty($instructoresDisponibles)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instructoresDisponibles as $instructor): ?>
                                <tr>
                                    <td><?= htmlspecialchars($instructor['nombre']) ?></td>
                                    <td><?= htmlspecialchars($instructor['email']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p>No hay instructores disponibles para asignar.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

