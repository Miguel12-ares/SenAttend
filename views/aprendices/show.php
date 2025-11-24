<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Aprendiz - SenAttend</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/components.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../partials/header.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h1>Detalle de Aprendiz</h1>
                    <p>Información completa del aprendiz</p>
                </div>
                <div class="page-actions">
                    <a href="/aprendices" class="btn btn-secondary">← Volver</a>
                    <a href="/aprendices/<?= $aprendiz['id'] ?>/editar" class="btn btn-primary">Editar</a>
                </div>
            </div>

            <!-- Información del Aprendiz -->
            <div class="card">
                <div class="card-header">
                    <h2>Información Personal</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Documento:</span>
                            <span class="info-value"><strong><?= htmlspecialchars($aprendiz['documento']) ?></strong></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Correo Electrónico:</span>
                            <span class="info-value"><?= htmlspecialchars($aprendiz['email'] ?? 'No asignado') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value"><?= htmlspecialchars($aprendiz['nombre']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Apellido:</span>
                            <span class="info-value"><?= htmlspecialchars($aprendiz['apellido']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span class="badge badge-<?= $aprendiz['estado'] === 'activo' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($aprendiz['estado']) ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha de Registro:</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($aprendiz['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fichas Vinculadas -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2>Fichas Vinculadas (<?= count($fichas ?? []) ?>)</h2>
                    <button onclick="mostrarModalVincular()" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Vincular a Ficha</button>
                </div>
                <div class="card-body">
                    <?php if (!empty($fichas)): ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Programa</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fichas as $ficha): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ficha['numero_ficha']) ?></td>
                                            <td><?= htmlspecialchars($ficha['nombre']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars($ficha['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/fichas/<?= $ficha['id'] ?>" class="btn btn-sm btn-info">Ver Ficha</a>
                                                <button onclick="desvincular(<?= $ficha['id'] ?>)" class="btn btn-sm btn-danger">Desvincular</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>No hay fichas vinculadas a este aprendiz.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones Adicionales -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2>Acciones</h2>
                </div>
                <div class="card-body">
                    <div class="action-buttons">
                        <?php if ($aprendiz['estado'] === 'activo'): ?>
                            <button onclick="cambiarEstado('retirado')" class="btn btn-warning">
                                Retirar Aprendiz
                            </button>
                        <?php else: ?>
                            <button onclick="cambiarEstado('activo')" class="btn btn-success">
                                Activar Aprendiz
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="eliminarAprendiz()" class="btn btn-danger">
                            Eliminar Aprendiz
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Vincular a Ficha -->
    <div id="vincularModal" class="modal" style="display: none;">
        <div class="modal-content" onclick="event.stopPropagation();">
            <h2 class="modal-title">Vincular a Ficha</h2>
            <div class="modal-body">
                <form id="vincularForm">
                    <div class="form-group">
                        <label for="ficha_id">Seleccionar Ficha *</label>
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
                <button type="button" onclick="cerrarModalVincular()" class="btn btn-secondary">Cancelar</button>
                <button type="button" onclick="vincular()" class="btn btn-primary">Vincular</button>
            </div>
        </div>
    </div>

    <script src="/js/components.js"></script>
    <script>
        function mostrarModalVincular() {
            const modal = document.getElementById('vincularModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalVincular() {
            const modal = document.getElementById('vincularModal');
            modal.style.display = 'none';
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

    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
        }
        
        .info-value {
            font-size: 1.1em;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>

