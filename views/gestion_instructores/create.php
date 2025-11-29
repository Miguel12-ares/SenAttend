<?php
/**
 * Vista: Crear Instructor
 * Formulario para crear un nuevo instructor
 */

$title = 'Crear Instructor - SENAttend';
$showHeader = true;
$currentPage = 'gestion-instructores';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css') . asset_css('css/modules/gestion-instructores.css');
$additionalScripts = '';

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-user-plus"></i> Crear Nuevo Instructor</h1>
            <p>Complete el formulario para registrar un nuevo instructor</p>
        </div>
        <div class="page-actions">
            <a href="/gestion-instructores" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
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
        <form method="POST" action="/gestion-instructores" id="instructorForm">
            <div class="form-card">
                <h2><i class="fas fa-user"></i> Información del Instructor</h2>
                
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
                        value="<?= htmlspecialchars($_SESSION['old']['documento'] ?? '') ?>"
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
                        placeholder="Ej: Juan Carlos Pérez"
                        minlength="2"
                        maxlength="100"
                        value="<?= htmlspecialchars($_SESSION['old']['nombre'] ?? '') ?>"
                        required
                    >
                    <small class="form-text">Nombre completo del instructor</small>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Ej: instructor@sena.edu.co"
                        value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>"
                        required
                    >
                    <small class="form-text">Correo institucional del instructor</small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Contraseña Temporal:</strong><br>
                    La contraseña será generada automáticamente usando los primeros 6 dígitos del documento.<br>
                    El instructor deberá cambiarla en su primer inicio de sesión.
                </div>
            </div>

            <div class="form-actions">
                <a href="/gestion-instructores" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Instructor</button>
            </div>
        </form>
    </div>
</div>


<script>
// Validación en tiempo real
document.getElementById('documento').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length > 15) {
        this.value = this.value.slice(0, 15);
    }
});

document.getElementById('instructorForm').addEventListener('submit', function(e) {
    const documento = document.getElementById('documento').value;
    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    
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
    
    return true;
});
</script>

<?php
unset($_SESSION['old']);
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
