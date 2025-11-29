<?php
/**
 * Vista de Gestión de Horarios de Turnos
 * Solo accesible para usuarios con rol Admin
 */

// Prevenir acceso directo
if (!isset($user) || $user['rol'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

$title = 'Gestión de Horarios - SENAttend';
$showHeader = true;
$currentPage = 'configuracion';
$additionalStyles = asset_css('css/configuracion/horarios.css');
$additionalScripts = '';

ob_start();
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" style="margin-bottom: 1.5rem;">
        <ol class="breadcrumb" style="background: #f8f9fa; padding: 0.75rem 1rem; border-radius: 0.375rem;">
            <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Gestión de Horarios</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-clock"></i> Configuración de Horarios de Turnos</h1>
            <p>Configure los horarios y límites de llegada para cada turno</p>
        </div>
    </div>

    <!-- Alertas de éxito/error -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <!-- Card de Configuración -->
    <div class="card" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px; margin-bottom: 2rem;">
        <div class="card-header" style="background: var(--color-primary); color: white; padding: 1rem 1.5rem; border-radius: 8px 8px 0 0;">
            <h5 style="margin: 0;">
                <i class="fas fa-cog"></i>
                Configuración de Turnos
            </h5>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <!-- Información importante -->
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i>
                <strong>Importante:</strong> Los cambios en los horarios se aplicarán inmediatamente a todos los registros de asistencia futuros. 
                Los registros históricos no se verán afectados.
            </div>

            <!-- Formulario de configuración -->
            <form method="POST" action="/configuracion/horarios/actualizar" id="formHorarios" class="needs-validation" novalidate>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Turno</th>
                                <th style="width: 25%;">Hora Inicio</th>
                                <th style="width: 25%;">Hora Fin</th>
                                <th style="width: 25%;">Hora Límite Llegada</th>
                                <th style="width: 5%;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $iconos = [
                                'Mañana' => 'fa-sun',
                                'Tarde' => 'fa-cloud-sun',
                                'Noche' => 'fa-moon'
                            ];
                            $colores = [
                                'Mañana' => 'warning',
                                'Tarde' => 'info',
                                'Noche' => 'dark'
                            ];
                            
                            foreach ($turnos as $turno): 
                                $nombreLower = strtolower($turno['nombre_turno']);
                                $icono = $iconos[$turno['nombre_turno']] ?? 'fa-clock';
                                $color = $colores[$turno['nombre_turno']] ?? 'secondary';
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?= $color ?>" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                                            <i class="fas <?= $icono ?>"></i>
                                            <?= htmlspecialchars($turno['nombre_turno']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <input 
                                            type="time" 
                                            class="form-control" 
                                            name="<?= $nombreLower ?>_inicio" 
                                            id="<?= $nombreLower ?>_inicio"
                                            value="<?= htmlspecialchars(substr($turno['hora_inicio'], 0, 5)) ?>"
                                            step="60"
                                            required
                                        >
                                        <div class="invalid-feedback">
                                            Ingrese una hora de inicio válida
                                        </div>
                                    </td>
                                    <td>
                                        <input 
                                            type="time" 
                                            class="form-control" 
                                            name="<?= $nombreLower ?>_fin" 
                                            id="<?= $nombreLower ?>_fin"
                                            value="<?= htmlspecialchars(substr($turno['hora_fin'], 0, 5)) ?>"
                                            step="60"
                                            required
                                        >
                                        <div class="invalid-feedback">
                                            Ingrese una hora de fin válida
                                        </div>
                                    </td>
                                    <td>
                                        <input 
                                            type="time" 
                                            class="form-control" 
                                            name="<?= $nombreLower ?>_limite" 
                                            id="<?= $nombreLower ?>_limite"
                                            value="<?= htmlspecialchars(substr($turno['hora_limite_llegada'], 0, 5)) ?>"
                                            step="60"
                                            required
                                        >
                                        <div class="invalid-feedback">
                                            Ingrese una hora límite válida
                                        </div>
                                        <small class="text-muted">
                                            Después de esta hora se marca tardanza
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($turno['activo']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Botones de acción -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                    <a href="/dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver al Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save"></i>
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card de ayuda -->
    <div class="card" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 8px;">
        <div class="card-header" style="background: #f8f9fa; padding: 1rem 1.5rem; border-radius: 8px 8px 0 0;">
            <h6 style="margin: 0;">
                <i class="fas fa-question-circle"></i>
                Ayuda
            </h6>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <h6 style="color: var(--color-primary);">
                        <i class="fas fa-clock"></i>
                        Hora de Inicio
                    </h6>
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Hora en la que comienza el turno. Los registros antes de esta hora no se asignarán a este turno.
                    </p>
                </div>
                <div>
                    <h6 style="color: var(--color-primary);">
                        <i class="fas fa-clock"></i>
                        Hora de Fin
                    </h6>
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Hora en la que termina el turno. Debe ser posterior a la hora de inicio.
                    </p>
                </div>
                <div>
                    <h6 style="color: var(--color-primary);">
                        <i class="fas fa-exclamation-triangle"></i>
                        Hora Límite de Llegada
                    </h6>
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Hora máxima para llegar puntual. Después de esta hora, la asistencia se marcará como <strong>tardanza</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
(function() {
    'use strict';
    
    const form = document.getElementById('formHorarios');
    
    form.addEventListener('submit', function(event) {
        // Validar que hora_inicio < hora_fin
        let valido = true;
        const turnos = ['mañana', 'tarde', 'noche'];
        
        turnos.forEach(turno => {
            const inicio = document.getElementById(turno + '_inicio').value;
            const fin = document.getElementById(turno + '_fin').value;
            const limite = document.getElementById(turno + '_limite').value;
            
            if (inicio >= fin) {
                alert(`Error en turno ${turno.charAt(0).toUpperCase() + turno.slice(1)}: La hora de inicio debe ser menor que la hora de fin`);
                valido = false;
            }
            
            if (limite < inicio || limite > fin) {
                alert(`Error en turno ${turno.charAt(0).toUpperCase() + turno.slice(1)}: La hora límite debe estar entre la hora de inicio y fin`);
                valido = false;
            }
        });
        
        if (!form.checkValidity() || !valido) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
})();

// Confirmación antes de guardar
document.getElementById('btnGuardar').addEventListener('click', function(e) {
    if (!confirm('¿Está seguro de que desea actualizar la configuración de horarios? Los cambios se aplicarán inmediatamente.')) {
        e.preventDefault();
    }
});
</script>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
