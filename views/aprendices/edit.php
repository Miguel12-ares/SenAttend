<?php
/**
 * Vista: Editar Aprendiz
 */

$title = 'Editar Aprendiz - SENAttend';
$showHeader = true;
$currentPage = 'aprendices';
$additionalStyles = '<link rel="stylesheet" href="/css/components.css"><link rel="stylesheet" href="/css/fichas.css"><link rel="stylesheet" href="/css/aprendices.css">';
$additionalScripts = '<script src="/js/components.js"></script>';

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-edit"></i> Editar Aprendiz</h1>
            <p>Modificar información del aprendiz</p>
        </div>
        <div class="page-actions">
            <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <span class="btn-text">Volver</span></a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Información del Aprendiz</h2>
        </div>
        <div class="card-body">
            <form id="editForm" method="POST" action="/aprendices/<?= $aprendiz['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="documento"><i class="fas fa-id-card"></i> Documento *</label>
                        <input 
                            type="text" 
                            id="documento" 
                            name="documento" 
                            class="form-control"
                            value="<?= htmlspecialchars($aprendiz['documento']) ?>"
                            required
                            pattern="[0-9]{6,20}"
                            title="El documento debe tener entre 6 y 20 dígitos"
                            placeholder="1234567890">
                        <small class="form-text">6-20 dígitos</small>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control"
                            value="<?= htmlspecialchars($aprendiz['email'] ?? '') ?>"
                            maxlength="100"
                            placeholder="aprendiz@ejemplo.com">
                        <small class="form-text">Opcional - Requerido para envío de códigos QR</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre"><i class="fas fa-signature"></i> Nombre *</label>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            class="form-control"
                            value="<?= htmlspecialchars($aprendiz['nombre']) ?>"
                            required
                            minlength="2"
                            maxlength="100"
                            placeholder="Juan">
                    </div>

                    <div class="form-group">
                        <label for="apellido"><i class="fas fa-signature"></i> Apellido *</label>
                        <input 
                            type="text" 
                            id="apellido" 
                            name="apellido" 
                            class="form-control"
                            value="<?= htmlspecialchars($aprendiz['apellido']) ?>"
                            required
                            minlength="2"
                            maxlength="100"
                            placeholder="Pérez">
                    </div>
                </div>

                <div class="form-group">
                    <label for="estado"><i class="fas fa-info-circle"></i> Estado *</label>
                    <select id="estado" name="estado" class="form-control" required>
                        <option value="activo" <?= $aprendiz['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="retirado" <?= $aprendiz['estado'] === 'retirado' ? 'selected' : '' ?>>Retirado</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span class="btn-text">Guardar Cambios</span></button>
                    <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('editForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        Loading.show('Guardando cambios...');
        
        try {
            const result = await API.put(`/api/aprendices/<?= $aprendiz['id'] ?>`, data);
            
            if (result.success) {
                Notification.success('Aprendiz actualizado correctamente');
                setTimeout(() => window.location.href = '/aprendices/<?= $aprendiz['id'] ?>', 1000);
            } else {
                const errores = result.errors || ['Error desconocido'];
                errores.forEach(error => Notification.error(error));
            }
        } catch (error) {
            Notification.error('Error de conexión al servidor');
        } finally {
            Loading.hide();
        }
    });

    // Validación en tiempo real
    document.getElementById('documento').addEventListener('input', function(e) {
        const value = e.target.value;
        const regex = /^[0-9]{6,20}$/;
        
        if (value && !regex.test(value)) {
            e.target.setCustomValidity('6-20 dígitos numéricos');
        } else {
            e.target.setCustomValidity('');
        }
    });
</script>

<style>
.btn-text {
    display: inline;
}

@media (max-width: 768px) {
    .btn-text {
        display: none;
    }
    
    .btn i {
        margin: 0;
    }
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

