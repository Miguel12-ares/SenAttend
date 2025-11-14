<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ficha - SenAttend</title>
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
                    <h1>Editar Ficha</h1>
                    <p>Modificar información de la ficha</p>
                </div>
                <div class="page-actions">
                    <a href="/fichas/<?= $ficha['id'] ?>" class="btn btn-secondary">← Volver</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="editForm" method="POST" action="/fichas/<?= $ficha['id'] ?>">
                        <div class="form-group">
                            <label for="numero_ficha">Número de Ficha *</label>
                            <input 
                                type="text" 
                                id="numero_ficha" 
                                name="numero_ficha" 
                                class="form-control"
                                value="<?= htmlspecialchars($ficha['numero_ficha']) ?>"
                                required
                                pattern="[A-Za-z0-9]{4,20}"
                                title="El número de ficha debe tener entre 4 y 20 caracteres alfanuméricos">
                            <small class="form-text">4-20 caracteres alfanuméricos</small>
                        </div>

                        <div class="form-group">
                            <label for="nombre">Nombre del Programa *</label>
                            <input 
                                type="text" 
                                id="nombre" 
                                name="nombre" 
                                class="form-control"
                                value="<?= htmlspecialchars($ficha['nombre']) ?>"
                                required
                                minlength="10"
                                maxlength="255">
                            <small class="form-text">Mínimo 10 caracteres</small>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado *</label>
                            <select id="estado" name="estado" class="form-control" required>
                                <option value="activa" <?= $ficha['estado'] === 'activa' ? 'selected' : '' ?>>Activa</option>
                                <option value="finalizada" <?= $ficha['estado'] === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            <a href="/fichas/<?= $ficha['id'] ?>" class="btn btn-secondary">Cancelar</a>
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
                const result = await API.put(`/api/fichas/<?= $ficha['id'] ?>`, data);
                
                if (result.success) {
                    Notification.success('Ficha actualizada correctamente');
                    setTimeout(() => window.location.href = '/fichas/<?= $ficha['id'] ?>', 1000);
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
</body>
</html>

