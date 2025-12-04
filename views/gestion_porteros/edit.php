<?php
/**
 * Vista: Editar Portero
 * Formulario para editar un portero existente
 */

$title = 'Editar Portero - SENAttend';
$showHeader = true;
$currentPage = 'gestion-porteros';
// Accesible para admin y administrativo
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css');
$additionalScripts = '';

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-pen-to-square"></i> Editar Portero</h1>
            <p>Modifique la información del portero</p>
        </div>
        <div class="page-actions">
            <a href="/gestion-porteros" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <!-- Mensajes de error -->
    <?php if (isset($_SESSION['errors'])): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="/gestion-porteros/<?= $portero['id'] ?>" id="porteroForm">
            <div class="form-card">
                <h2><i class="fas fa-user-shield"></i> Información del Portero</h2>
                
                <div class="form-group">
                    <label for="documento"><i class="fas fa-id-card"></i> Documento *</label>
                    <input 
                        type="text" 
                        id="documento" 
                        name="documento" 
                        class="form-control" 
                        placeholder="Ej: 12345678"
                        inputmode="numeric"
                        pattern="[0-9]{7,15}"
                        value="<?= htmlspecialchars($_SESSION['old']['documento'] ?? $portero['documento']) ?>"
                        required
                    >
                    <small class="form-text">Documento de identidad (7-15 dígitos numéricos)</small>
                </div>

                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        class="form-control" 
                        placeholder="Ej: Juan Carlos Portero"
                        minlength="2"
                        maxlength="100"
                        value="<?= htmlspecialchars($_SESSION['old']['nombre'] ?? $portero['nombre']) ?>"
                        required
                    >
                    <small class="form-text">Nombre completo del portero</small>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Ej: portero@sena.edu.co"
                        value="<?= htmlspecialchars($_SESSION['old']['email'] ?? $portero['email']) ?>"
                        required
                    >
                    <small class="form-text">Correo institucional del portero</small>
                </div>
            </div>

            <div class="form-card">
                <h2><i class="fas fa-key"></i> Cambiar Contraseña (Opcional)</h2>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Deje estos campos vacíos si no desea cambiar la contraseña
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Nueva Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Mínimo 8 caracteres"
                        minlength="8"
                    >
                    <small class="form-text">Mínimo 8 caracteres, incluir mayúscula, número y carácter especial</small>
                </div>

                <div class="form-group">
                    <label for="password_confirm"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-control" 
                        placeholder="Repita la contraseña"
                        minlength="8"
                    >
                </div>
            </div>

            <div class="form-actions">
                <a href="/gestion-porteros" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.form-card h2 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    color: var(--color-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(57, 169, 0, 0.1);
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid var(--color-info);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .form-card {
        padding: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
// Validación en tiempo real
document.getElementById('documento').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 15) {
        this.value = this.value.slice(0, 15);
    }
});

document.getElementById('porteroForm').addEventListener('submit', function(e) {
    const documento = document.getElementById('documento').value;
    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    if (documento.length < 7 || documento.length > 15) {
        e.preventDefault();
        alert('El documento debe tener entre 7 y 15 dígitos');
        return false;
    }
    
    if (nombre.length < 2 || nombre.length > 100) {
        e.preventDefault();
        alert('El nombre debe tener entre 2 y 100 caracteres');
        return false;
    }
    
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        e.preventDefault();
        alert('El formato del email no es válido');
        return false;
    }
    
    // Validar contraseña solo si se proporcionó
    if (password || passwordConfirm) {
        if (password !== passwordConfirm) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 8 caracteres');
            return false;
        }
        
        if (!/[A-Z]/.test(password)) {
            e.preventDefault();
            alert('La contraseña debe contener al menos una letra mayúscula');
            return false;
        }
        
        if (!/[0-9]/.test(password)) {
            e.preventDefault();
            alert('La contraseña debe contener al menos un número');
            return false;
        }
        
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            e.preventDefault();
            alert('La contraseña debe contener al menos un carácter especial');
            return false;
        }
    }
    
    return true;
});
</script>

<?php
unset($_SESSION['old']);
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
