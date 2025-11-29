<?php
/**
 * Vista: Detalle de Aprendiz
 */

$title = 'Detalle de Aprendiz - SENAttend';
$showHeader = true;
$currentPage = 'aprendices';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css') . asset_css('css/modules/aprendices.css');
$additionalScripts = asset_js('js/common/components.js');

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-user"></i> Detalle de Aprendiz</h1>
            <p>Información completa del aprendiz</p>
        </div>
        <div class="page-actions">
            <a href="/aprendices" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <span class="btn-text">Volver</span></a>
            <a href="/aprendices/<?= $aprendiz['id'] ?>/editar" class="btn btn-primary"><i class="fas fa-edit"></i> <span class="btn-text">Editar</span></a>
        </div>
    </div>

    <!-- Información del Aprendiz -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-circle"></i> Información Personal</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-id-card"></i> Documento</span>
                    <span class="info-value"><strong><?= htmlspecialchars($aprendiz['documento']) ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope"></i> Correo Electrónico</span>
                    <span class="info-value"><?= htmlspecialchars($aprendiz['email'] ?? 'No asignado') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-signature"></i> Nombre</span>
                    <span class="info-value"><?= htmlspecialchars($aprendiz['nombre']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-signature"></i> Apellido</span>
                    <span class="info-value"><?= htmlspecialchars($aprendiz['apellido']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-info-circle"></i> Estado</span>
                    <span class="badge badge-<?= $aprendiz['estado'] === 'activo' ? 'success' : 'warning' ?>">
                        <i class="fas fa-<?= $aprendiz['estado'] === 'activo' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                        <?= ucfirst($aprendiz['estado']) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-calendar"></i> Fecha de Registro</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($aprendiz['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Fichas Vinculadas -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-clipboard-list"></i> Fichas Vinculadas (<?= count($fichas ?? []) ?>)</h2>
            <button onclick="mostrarModalVincular()" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <span class="btn-text">Vincular a Ficha</span></button>
        </div>
        <div class="card-body">
            <?php if (!empty($fichas)): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Número</th>
                                <th><i class="fas fa-book"></i> Programa</th>
                                <th><i class="fas fa-info-circle"></i> Estado</th>
                                <th><i class="fas fa-cog"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fichas as $ficha): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ficha['numero_ficha']) ?></td>
                                    <td><?= htmlspecialchars($ficha['nombre']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                            <i class="fas fa-<?= $ficha['estado'] === 'activa' ? 'check-circle' : 'archive' ?>"></i>
                                            <?= ucfirst($ficha['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/fichas/<?= $ficha['id'] ?>" class="btn-action btn-view" title="Ver ficha">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="desvincular(<?= $ficha['id'] ?>)" class="btn-action btn-delete" title="Desvincular">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay fichas vinculadas a este aprendiz.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Acciones Adicionales -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-cog"></i> Acciones</h2>
        </div>
        <div class="card-body">
            <div class="action-buttons">
                <?php if ($aprendiz['estado'] === 'activo'): ?>
                    <button onclick="cambiarEstado('retirado')" class="btn btn-warning">
                        <i class="fas fa-user-slash"></i> <span class="btn-text">Retirar Aprendiz</span>
                    </button>
                <?php else: ?>
                    <button onclick="cambiarEstado('activo')" class="btn btn-success">
                        <i class="fas fa-user-check"></i> <span class="btn-text">Activar Aprendiz</span>
                    </button>
                <?php endif; ?>
                
                <button onclick="eliminarAprendiz()" class="btn btn-danger">
                    <i class="fas fa-trash"></i> <span class="btn-text">Eliminar Aprendiz</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Vincular a Ficha -->
<div id="vincularModal" class="modal">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2 class="modal-title"><i class="fas fa-link"></i> Vincular a Ficha</h2>
        <div class="modal-body">
            <form id="vincularForm">
                <div class="form-group">
                    <label for="ficha_id"><i class="fas fa-clipboard-list"></i> Seleccionar Ficha *</label>
                    <select id="ficha_id" name="ficha_id" class="form-control" required>
                        <option value="">-- Seleccione una ficha --</option>
                        <?php if (!empty($fichasDisponibles)): ?>
                            <?php foreach ($fichasDisponibles as $f): ?>
                                <option value="<?= $f['id'] ?>">
                                    <?= htmlspecialchars($f['numero_ficha']) ?> - <?= htmlspecialchars($f['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="cerrarModalVincular()" class="btn btn-secondary"><i class="fas fa-times"></i> <span class="btn-text">Cancelar</span></button>
            <button type="button" onclick="vincular()" class="btn btn-primary"><i class="fas fa-link"></i> <span class="btn-text">Vincular</span></button>
        </div>
    </div>
</div>

<script>
    function mostrarModalVincular() {
        const modal = document.getElementById('vincularModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalVincular() {
        const modal = document.getElementById('vincularModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('vincularForm').reset();
    }

    async function vincular() {
        const fichaId = document.getElementById('ficha_id').value;
        
        if (!fichaId) {
            Notification.error('Debe seleccionar una ficha');
            return;
        }
        
        Loading.show('Vinculando...');
        
        try {
            const result = await API.post(`/api/aprendices/<?= $aprendiz['id'] ?>/vincular`, {
                ficha_id: parseInt(fichaId)
            });
            
            if (result.success) {
                Notification.success('Aprendiz vinculado correctamente');
                setTimeout(() => location.reload(), 1000);
            } else {
                Notification.error(result.errors?.[0] || 'Error al vincular');
            }
        } catch (error) {
            Notification.error('Error de conexión');
        } finally {
            Loading.hide();
            cerrarModalVincular();
        }
    }

    async function desvincular(fichaId) {
        const confirmar = await Confirm.show(
            'Desvincular',
            '¿Está seguro de desvincular este aprendiz de la ficha?'
        );
        
        if (!confirmar) return;
        
        Loading.show('Desvinculando...');
        
        try {
            const result = await API.post(`/api/aprendices/<?= $aprendiz['id'] ?>/desvincular`, {
                ficha_id: fichaId
            });
            
            if (result.success) {
                Notification.success('Aprendiz desvinculado correctamente');
                setTimeout(() => location.reload(), 1000);
            } else {
                Notification.error(result.errors?.[0] || 'Error al desvincular');
            }
        } catch (error) {
            Notification.error('Error de conexión');
        } finally {
            Loading.hide();
        }
    }

    async function cambiarEstado(nuevoEstado) {
        const confirmar = await Confirm.show(
            'Cambiar Estado',
            `¿Está seguro de cambiar el estado a "${nuevoEstado}"?`
        );
        
        if (!confirmar) return;
        
        Loading.show('Cambiando estado...');
        
        try {
            const result = await API.post(`/api/aprendices/<?= $aprendiz['id'] ?>/estado`, {
                estado: nuevoEstado
            });
            
            if (result.success) {
                Notification.success('Estado actualizado correctamente');
                setTimeout(() => location.reload(), 1000);
            } else {
                Notification.error(result.errors?.[0] || 'Error al cambiar estado');
            }
        } catch (error) {
            Notification.error('Error de conexión');
        } finally {
            Loading.hide();
        }
    }

    async function eliminarAprendiz() {
        const confirmar = await Confirm.show(
            'Eliminar Aprendiz',
            '¿Está seguro de eliminar este aprendiz? Esta acción no se puede deshacer.'
        );
        
        if (!confirmar) return;
        
        Loading.show('Eliminando aprendiz...');
        
        try {
            const result = await API.delete(`/api/aprendices/<?= $aprendiz['id'] ?>`);
            
            if (result.success) {
                Notification.success('Aprendiz eliminado correctamente');
                setTimeout(() => window.location.href = '/aprendices', 1000);
            } else {
                Notification.error(result.errors?.[0] || 'Error al eliminar aprendiz');
            }
        } catch (error) {
            Notification.error('Error de conexión');
        } finally {
            Loading.hide();
        }
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('vincularModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalVincular();
        }
    });
</script>


<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>
