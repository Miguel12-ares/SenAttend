<?php
/**
 * Vista: Detalle de Ficha
 */

$title = 'Detalle de Ficha - SENAttend';
$showHeader = true;
$currentPage = 'fichas';
$additionalStyles = asset_css('css/common/components.css') . asset_css('css/modules/fichas.css');
$additionalScripts = asset_js('js/common/components.js');

ob_start();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-clipboard-list"></i> Detalle de Ficha</h1>
            <p>Información completa de la ficha</p>
        </div>
        <div class="page-actions">
            <a href="/fichas" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <span class="btn-text">Volver</span></a>
            <a href="/fichas/<?= $ficha['id'] ?>/editar" class="btn btn-primary"><i class="fas fa-edit"></i> <span class="btn-text">Editar</span></a>
        </div>
    </div>

    <!-- Información de la Ficha -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-info-circle"></i> Información General</h2>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-hashtag"></i> Número de Ficha</span>
                    <span class="info-value"><strong><?= htmlspecialchars($ficha['numero_ficha']) ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-info-circle"></i> Estado</span>
                    <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                        <i class="fas fa-<?= $ficha['estado'] === 'activa' ? 'check-circle' : 'archive' ?>"></i>
                        <?= ucfirst($ficha['estado']) ?>
                    </span>
                </div>
                <div class="info-item full-width">
                    <span class="info-label"><i class="fas fa-book"></i> Nombre del Programa</span>
                    <span class="info-value"><?= htmlspecialchars($ficha['nombre']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-calendar"></i> Fecha de Creación</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($ficha['created_at'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-users"></i> Total de Aprendices</span>
                    <span class="info-value"><strong><?= $totalAprendices ?? 0 ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Aprendices -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Aprendices Vinculados (<?= count($aprendices ?? []) ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($aprendices)): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-card"></i> Documento</th>
                                <th><i class="fas fa-user"></i> Nombre Completo</th>
                                <th><i class="fas fa-envelope"></i> Correo Electrónico</th>
                                <th><i class="fas fa-info-circle"></i> Estado</th>
                                <th><i class="fas fa-cog"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aprendices as $aprendiz): ?>
                                <tr>
                                    <td><?= htmlspecialchars($aprendiz['documento']) ?></td>
                                    <td><?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido']) ?></td>
                                    <td><?= htmlspecialchars($aprendiz['email'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $aprendiz['estado'] === 'activo' ? 'success' : 'warning' ?>">
                                            <i class="fas fa-<?= $aprendiz['estado'] === 'activo' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                                            <?= ucfirst($aprendiz['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn-action btn-view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay aprendices vinculados a esta ficha.
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
                <?php if ($ficha['estado'] === 'activa'): ?>
                    <button onclick="cambiarEstado('finalizada')" class="btn btn-warning">
                        <i class="fas fa-archive"></i> <span class="btn-text">Finalizar Ficha</span>
                    </button>
                <?php else: ?>
                    <button onclick="cambiarEstado('activa')" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> <span class="btn-text">Activar Ficha</span>
                    </button>
                <?php endif; ?>
                
                <?php if ($totalAprendices == 0): ?>
                    <button onclick="eliminarFicha()" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <span class="btn-text">Eliminar Ficha</span>
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled title="No se puede eliminar una ficha con aprendices">
                        <i class="fas fa-trash"></i> <span class="btn-text">Eliminar Ficha</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    async function cambiarEstado(nuevoEstado) {
        const confirmar = await Confirm.show(
            'Cambiar Estado',
            `¿Está seguro de cambiar el estado a "${nuevoEstado}"?`
        );
        
        if (!confirmar) return;
        
        Loading.show('Cambiando estado...');
        
        try {
            const result = await API.post(`/api/fichas/<?= $ficha['id'] ?>/estado`, {
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
    
    async function eliminarFicha() {
        const confirmar = await Confirm.show(
            'Eliminar Ficha',
            '¿Está seguro de eliminar esta ficha? Esta acción no se puede deshacer.'
        );
        
        if (!confirmar) return;
        
        Loading.show('Eliminando ficha...');
        
        try {
            const result = await API.delete(`/api/fichas/<?= $ficha['id'] ?>`);
            
            if (result.success) {
                Notification.success('Ficha eliminada correctamente');
                setTimeout(() => window.location.href = '/fichas', 1000);
            } else {
                Notification.error(result.errors?.[0] || 'Error al eliminar ficha');
            }
        } catch (error) {
            Notification.error('Error de conexión');
        } finally {
            Loading.hide();
        }
    }
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

.btn-warning {
    background-color: var(--color-warning);
    color: var(--color-dark);
}

.btn-warning:hover {
    background-color: #e0a800;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid var(--color-info);
    padding: 1rem;
    border-radius: 8px;
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

