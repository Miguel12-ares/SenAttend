<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Aprendiz - SenAttend</title>
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
                    <h1>Nuevo Aprendiz</h1>
                    <p>Registrar un nuevo aprendiz</p>
                </div>
                <div class="page-actions">
                    <a href="/aprendices" class="btn btn-secondary">← Volver</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="createForm" method="POST" action="/aprendices">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="documento">Documento *</label>
                                <input 
                                    type="text" 
                                    id="documento" 
                                    name="documento" 
                                    class="form-control"
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
                                    maxlength="50"
                                    placeholder="ABC123">
                                <small class="form-text">Opcional</small>
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
                                    required
                                    minlength="2"
                                    maxlength="100"
                                    placeholder="Pérez">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="estado">Estado *</label>
                                <select id="estado" name="estado" class="form-control" required>
                                    <option value="activo" selected>Activo</option>
                                    <option value="retirado">Retirado</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="ficha_id">Vincular a Ficha (Opcional)</label>
                                <select id="ficha_id" name="ficha_id" class="form-control">
                                    <option value="">-- Sin vincular --</option>
                                    <?php if (!empty($fichas)): ?>
                                        <?php foreach ($fichas as $ficha): ?>
                                            <option value="<?= $ficha['id'] ?>">
                                                <?= htmlspecialchars($ficha['numero_ficha']) ?> - <?= htmlspecialchars($ficha['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Crear Aprendiz</button>
                            <a href="/aprendices" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/components.js"></script>
    <script>
        document.getElementById('createForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Convertir ficha_id a número si existe
            if (data.ficha_id) {
                data.ficha_id = parseInt(data.ficha_id);
            } else {
                delete data.ficha_id;
            }
            
            Loading.show('Creando aprendiz...');
            
            try {
                const result = await API.post('/api/aprendices', data);
                
                if (result.success) {
                    Notification.success('Aprendiz creado correctamente');
                    setTimeout(() => window.location.href = '/aprendices', 1000);
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

