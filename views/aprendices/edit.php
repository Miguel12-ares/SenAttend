<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aprendiz - SenAttend</title>
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
                    <h1>Editar Aprendiz</h1>
                    <p>Modificar información del aprendiz</p>
                </div>
                <div class="page-actions">
                    <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn btn-secondary">← Volver</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="editForm" method="POST" action="/aprendices/<?= $aprendiz['id'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="documento">Documento *</label>
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
                                <label for="codigo_carnet">Código Carnet</label>
                                <input 
                                    type="text" 
                                    id="codigo_carnet" 
                                    name="codigo_carnet" 
                                    class="form-control"
                                    value="<?= htmlspecialchars($aprendiz['codigo_carnet'] ?? '') ?>"
                                    maxlength="50"
                                    placeholder="ABC123">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre *</label>
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
                                <label for="apellido">Apellido *</label>
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
                            <label for="estado">Estado *</label>
                            <select id="estado" name="estado" class="form-control" required>
                                <option value="activo" <?= $aprendiz['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="retirado" <?= $aprendiz['estado'] === 'retirado' ? 'selected' : '' ?>>Retirado</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            <a href="/aprendices/<?= $aprendiz['id'] ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/components.js"></script>
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
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>

