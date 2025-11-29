<?php
/**
 * Vista: Crear Ficha
 */

$title = 'Crear Ficha - SENAttend';
$showHeader = true;
$currentPage = 'fichas';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css');
$additionalScripts = asset_js('js/common/components.js');

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-plus-circle"></i> Nueva Ficha</h1>
            <p>Registrar una nueva ficha de formación</p>
        </div>
        <div class="page-actions">
            <a href="/fichas" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <span class="btn-text">Volver</span></a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Información de la Ficha</h2>
        </div>
        <div class="card-body">
            <form id="createForm" method="POST" action="/fichas">
                <div class="form-group">
                    <label for="numero_ficha"><i class="fas fa-hashtag"></i> Número de Ficha *</label>
                    <input 
                        type="text" 
                        id="numero_ficha" 
                        name="numero_ficha" 
                        class="form-control"
                        required
                        pattern="[A-Za-z0-9]{4,20}"
                        title="El número de ficha debe tener entre 4 y 20 caracteres alfanuméricos"
                        placeholder="Ej: 2025001">
                    <small class="form-text">4-20 caracteres alfanuméricos</small>
                </div>

                <div class="form-group">
                    <label for="nombre"><i class="fas fa-book"></i> Nombre del Programa *</label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="nombre" 
                        class="form-control"
                        required
                        minlength="10"
                        maxlength="255"
                        placeholder="Ej: Técnico en Programación de Software">
                    <small class="form-text">Mínimo 10 caracteres</small>
                </div>

                <div class="form-group">
                    <label for="estado"><i class="fas fa-info-circle"></i> Estado *</label>
                    <select id="estado" name="estado" class="form-control" required>
                        <option value="activa" selected>Activa</option>
                        <option value="finalizada">Finalizada</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span class="btn-text">Crear Ficha</span></button>
                    <a href="/fichas" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('createForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        Loading.show('Creando ficha...');
        
        try {
            const result = await API.post('/api/fichas', data);
            
            if (result.success) {
                Notification.success('Ficha creada correctamente');
                setTimeout(() => window.location.href = '/fichas', 1000);
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
    document.getElementById('numero_ficha').addEventListener('input', function(e) {
        const value = e.target.value;
        const regex = /^[A-Za-z0-9]{4,20}$/;
        
        if (value && !regex.test(value)) {
            e.target.setCustomValidity('4-20 caracteres alfanuméricos');
        } else {
            e.target.setCustomValidity('');
        }
    });

    document.getElementById('nombre').addEventListener('input', function(e) {
        const value = e.target.value;
        
        if (value.length > 0 && value.length < 10) {
            e.target.setCustomValidity('Mínimo 10 caracteres');
        } else {
            e.target.setCustomValidity('');
        }
    });
</script>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
