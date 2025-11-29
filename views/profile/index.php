<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/profile/profile.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'perfil';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content profile-page">
            <div class="container profile-container">
                <!-- Header del Perfil -->
                <div class="profile-header">
                    <div class="profile-title-section">
                        <h1>Mi Perfil</h1>
                        <p class="profile-subtitle">Gestiona tu información personal y configuración de cuenta</p>
                    </div>
                </div>

                <!-- Mensajes de éxito/error -->
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success profile-alert">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-error profile-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Información del Usuario -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h2>Información Personal</h2>
                    </div>
                    <div class="profile-card-body">
                        <div class="profile-info-grid">
                            <div class="profile-info-box">
                                <div class="info-box-content">
                                    <span class="info-label">Documento</span>
                                    <span class="info-value"><?= htmlspecialchars($user['documento'] ?? 'N/A') ?></span>
                                </div>
                            </div>

                            <div class="profile-info-box">
                                <div class="info-box-content">
                                    <span class="info-label">Nombre Completo</span>
                                    <span class="info-value"><?= htmlspecialchars($user['nombre'] ?? 'N/A') ?></span>
                                </div>
                            </div>

                            <div class="profile-info-box">
                                <div class="info-box-content">
                                    <span class="info-label">Correo Electrónico</span>
                                    <span class="info-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                                </div>
                            </div>

                            <div class="profile-info-box">
                                <div class="info-box-content">
                                    <span class="info-label">Rol en el Sistema</span>
                                    <span class="info-value">
                                        <span class="badge badge-<?= $user['rol'] ?? 'secondary' ?>">
                                            <?= ucfirst($user['rol'] ?? 'N/A') ?>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cambio de Contraseña -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h2>Seguridad y Contraseña</h2>
                    </div>
                    <div class="profile-card-body">
                        <form method="POST" action="/perfil/cambiar-password" class="password-form" id="passwordForm">
                            <div class="form-group">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="form-control" 
                                    required
                                    placeholder="Ingrese su contraseña actual"
                                >
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="form-control" 
                                    required
                                    minlength="6"
                                    placeholder="Mínimo 6 caracteres"
                                >
                                <small class="form-text">La contraseña debe tener al menos 6 caracteres</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    required
                                    minlength="6"
                                    placeholder="Confirme su nueva contraseña"
                                >
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-submit">Cambiar Contraseña</button>
                                <button type="reset" class="btn btn-secondary btn-reset">Limpiar Campos</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje</p>
            </div>
        </footer>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        // Validación del formulario de contraseña
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas nuevas no coinciden');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
        });
    </script>

</body>
</html>
