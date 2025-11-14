<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Ficha - SenAttend</title>
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
                    <h1>Detalle de Ficha</h1>
                    <p>Información completa de la ficha</p>
                </div>
                <div class="page-actions">
                    <a href="/fichas" class="btn btn-secondary">← Volver</a>
                    <a href="/fichas/<?= $ficha['id'] ?>/editar" class="btn btn-primary">Editar</a>
                </div>
            </div>

            <!-- Información de la Ficha -->
            <div class="card">
                <div class="card-header">
                    <h2>Información General</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Número de Ficha:</span>
                            <span class="info-value"><strong><?= htmlspecialchars($ficha['numero_ficha']) ?></strong></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estado:</span>
                            <span class="badge badge-<?= $ficha['estado'] === 'activa' ? 'success' : 'secondary' ?>">
                                <?= htmlspecialchars($ficha['estado']) ?>
                            </span>
                        </div>
                        <div class="info-item full-width">
                            <span class="info-label">Nombre del Programa:</span>
                            <span class="info-value"><?= htmlspecialchars($ficha['nombre']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha de Creación:</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($ficha['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total de Aprendices:</span>
                            <span class="info-value"><strong><?= $totalAprendices ?? 0 ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Aprendices -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h2>Aprendices Vinculados (<?= count($aprendices ?? []) ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($aprendices)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre Completo</th>
                                    <th>Código Carnet</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aprendices as $aprendiz): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($aprendiz['documento']) ?></td>
                                        <td><?= htmlspecialchars($aprendiz['nombre'] . ' ' . $aprendiz['apellido']) ?></td>
                                        <td><?= htmlspecialchars($aprendiz['codigo_carnet'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $aprendiz['estado'] === 'activo' ? 'success' : 'warning' ?>">
                                                <?= htmlspecialchars($aprendiz['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>No hay aprendices vinculados a esta ficha.</p>
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
                        <?php if ($ficha['estado'] === 'activa'): ?>
                            <button onclick="cambiarEstado('finalizada')" class="btn btn-warning">
                                Finalizar Ficha
                            </button>
                        <?php else: ?>
                            <button onclick="cambiarEstado('activa')" class="btn btn-success">
                                Activar Ficha
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($totalAprendices == 0): ?>
                            <button onclick="eliminarFicha()" class="btn btn-danger">
                                Eliminar Ficha
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled title="No se puede eliminar una ficha con aprendices">
                                Eliminar Ficha
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/components.js"></script>
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
        
        .info-item.full-width {
            grid-column: 1 / -1;
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

