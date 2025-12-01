<?php
/** @var array $aprendiz */
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
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        // Para el panel de aprendiz reutilizamos el header principal,
        // sin usuario del sistema interno (solo branding y menú responsive).
        $user = null;
        $currentPage = 'aprendiz-panel';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="aprendiz-dashboard">
                    <section class="aprendiz-dashboard-header">
                        <div>
                            <h1>Bienvenido, <?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido']) ?></h1>
                            <p>Aquí podrás gestionar tus equipos y sus accesos al CTA.</p>
                        </div>
                        <div class="aprendiz-actions">
                            <a href="/aprendiz/logout" class="btn btn-outline btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                            </a>
                            <a href="/aprendiz/equipos/crear" class="btn btn-primary btn-sm">
                                <i class="fas fa-laptop"></i> Registrar equipo
                            </a>
                        </div>
                    </section>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <section class="aprendiz-equipos-card">
                        <div class="aprendiz-equipos-header">
                            <h2>Mis equipos registrados</h2>
                        </div>
                        <div class="aprendiz-equipos-list">
                            <?php if (!empty($equipos)): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Equipo</th>
                                            <th>Serial</th>
                                            <th>Marca</th>
                                            <th>Estado relación</th>
                                            <th>Activo</th>
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
                                                        <span style="font-size:0.85rem;color:#999;">Sin imagen</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($equipo['numero_serial']) ?></td>
                                                <td><?= htmlspecialchars($equipo['marca']) ?></td>
                                                <td>
                                                    <span class="badge-<?= $equipo['estado'] === 'activo' ? 'activo' : 'inactivo' ?>">
                                                        <?= htmlspecialchars(ucfirst($equipo['estado'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge-<?= $equipo['activo'] ? 'activo' : 'inactivo' ?>">
                                                        <?= $equipo['activo'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="/aprendiz/equipos/<?= (int)$equipo['equipo_id'] ?>/qr" class="btn btn-outline btn-sm">
                                                        <i class="fas fa-qrcode"></i> Ver QR
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No tienes equipos registrados aún.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/aprendiz/panel.js') ?>"></script>
</body>
</html>


