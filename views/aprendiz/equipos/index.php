<?php
/** @var array $user */
/** @var array $equipos */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Equipos - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-equipos';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <!-- Header -->
                <div class="dashboard-header">
                    <div>
                        <h2>
                            <i class="fas fa-laptop"></i>
                            Mis Equipos
                        </h2>
                        <p class="subtitle">
                            Gestiona todos tus equipos registrados y accede a sus códigos QR.
                        </p>
                    </div>
                    <div>
                        <?php 
                        $url = '/aprendiz/panel';
                        require __DIR__ . '/../../components/back-button.php'; 
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

                

                <!-- Lista de Equipos -->
                <?php if (!empty($equipos)): ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-list"></i> Equipos Registrados (<?= count($equipos) ?>)</h2>
                    </div>
                    <div class="aprendiz-equipos-list">
                        <div class="equipos-grid">
                            <?php foreach ($equipos as $equipo): ?>
                                <div class="equipo-card">
                                    <div class="equipo-imagen">
                                        <?php if (!empty($equipo['imagen'])): ?>
                                            <img src="<?= asset($equipo['imagen']) ?>" alt="<?= htmlspecialchars($equipo['marca']) ?>">
                                        <?php else: ?>
                                            <div class="equipo-imagen-placeholder">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-info">
                                        <h3><?= htmlspecialchars($equipo['marca']) ?></h3>
                                        <div class="equipo-details">
                                            <p><strong>Serial:</strong> <code><?= htmlspecialchars($equipo['numero_serial']) ?></code></p>
                                            <p><strong>Estado:</strong> 
                                                <span class="badge-<?= $equipo['estado'] === 'activo' ? 'activo' : 'inactivo' ?>">
                                                    <?= htmlspecialchars(ucfirst($equipo['estado'])) ?>
                                                </span>
                                            </p>
                                            <?php if (!empty($equipo['fecha_asignacion'])): ?>
                                                <p><strong>Registrado:</strong> <?= date('d/m/Y', strtotime($equipo['fecha_asignacion'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="equipo-actions">
                                            <a href="/aprendiz/equipos/<?= (int)$equipo['equipo_id'] ?>/qr" class="btn btn-primary">
                                                <i class="fas fa-qrcode"></i> Ver QR
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php else: ?>
                <section class="aprendiz-equipos-card">
                    <div class="aprendiz-equipos-header">
                        <h2><i class="fas fa-laptop"></i> Mis Equipos</h2>
                    </div>
                    <div class="empty-state" style="text-align: center; padding: 3rem 1rem;">
                        <i class="fas fa-laptop" style="font-size: 4rem; color: #999; margin-bottom: 1rem;"></i>
                        <p style="color: #666; font-size: 1.1rem; margin-bottom: 1rem;">No tienes equipos registrados aún.</p>
                        <a href="/aprendiz/equipos/crear" class="btn btn-primary">
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
</body>
</html>

