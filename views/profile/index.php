<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/components.css') ?>">
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

    <style>
        /* Estilos para la página de perfil - Colores SENA */
        .profile-page {
            background: var(--color-gray-100);
            min-height: calc(100vh - 200px);
            padding: 1.5rem 0;
        }

        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header del Perfil */
        .profile-header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            border-radius: var(--border-radius);
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            color: white;
            box-shadow: var(--box-shadow);
        }

        .profile-title-section h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-subtitle {
            margin: 0;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        /* Cards de Perfil */
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .profile-card-header {
            background: var(--color-primary);
            padding: 1rem 1.5rem;
            color: white;
            border-bottom: 2px solid var(--color-primary-dark);
        }

        .profile-card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .profile-card-body {
            padding: 1.5rem;
        }

        /* Grid de Información */
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .profile-info-box {
            background: var(--color-gray-100);
            border-radius: var(--border-radius);
            padding: 1rem;
            border-left: 3px solid var(--color-primary);
            transition: var(--transition);
        }

        .profile-info-box:hover {
            border-left-color: var(--color-primary-dark);
            background: var(--color-gray-200);
        }

        .info-box-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-gray-900);
        }

        /* Formulario de Contraseña */
        .password-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--color-gray-900);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--color-gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(57, 169, 0, 0.1);
        }

        .form-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--color-gray-600);
        }

        /* Botones del Formulario */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn-submit,
        .btn-reset {
            flex: 1;
            min-width: 150px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-submit {
            background-color: var(--color-primary);
            color: white;
        }

        .btn-submit:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(57, 169, 0, 0.3);
        }

        .btn-reset {
            background: var(--color-gray-200);
            color: var(--color-gray-900);
            border: 2px solid var(--color-gray-300);
        }

        .btn-reset:hover {
            background: var(--color-gray-300);
            border-color: var(--color-gray-600);
        }

        /* Alertas */
        .profile-alert {
            padding: 1rem 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            box-shadow: var(--box-shadow);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--color-success);
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--color-danger);
        }

        .profile-alert i {
            font-size: 1.25rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.25rem 1.5rem;
            }

            .profile-title-section h1 {
                font-size: 1.5rem;
            }

            .profile-info-grid {
                grid-template-columns: 1fr;
            }

            .profile-card-body {
                padding: 1.25rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-reset {
                width: 100%;
                min-width: auto;
            }

            .password-form {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 0 0.5rem;
            }

            .profile-header {
                padding: 1rem 1.25rem;
            }

            .profile-title-section h1 {
                font-size: 1.35rem;
            }

            .profile-subtitle {
                font-size: 0.9rem;
            }

            .profile-card-header {
                padding: 0.875rem 1.25rem;
            }

            .profile-card-header h2 {
                font-size: 1.15rem;
            }

            .profile-card-body {
                padding: 1rem;
            }
        }
    </style>
</body>
</html>
