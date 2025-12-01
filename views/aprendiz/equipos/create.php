<?php
/** @var array $aprendiz */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Equipo - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $user = null;
        $currentPage = 'aprendiz-equipo-create';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="aprendiz-dashboard">
                    <section class="aprendiz-dashboard-header">
                        <div>
                            <h1>Registrar nuevo equipo</h1>
                            <p>Vincula tu equipo portátil para gestionar sus ingresos y salidas del CTA.</p>
                        </div>
                        <div class="aprendiz-actions">
                            <a href="/aprendiz/panel" class="btn btn-outline btn-sm">
                                <i class="fas fa-arrow-left"></i> Volver al panel
                            </a>
                        </div>
                    </section>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <section class="aprendiz-equipos-card">
                        <form action="/aprendiz/equipos" method="POST" class="form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="numero_serial">Número de serie del equipo</label>
                                <input
                                    type="text"
                                    id="numero_serial"
                                    name="numero_serial"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars($old['numero_serial'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="marca">Marca del equipo</label>
                                <input
                                    type="text"
                                    id="marca"
                                    name="marca"
                                    class="form-control"
                                    required
                                    value="<?= htmlspecialchars($old['marca'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="imagen">Imagen del equipo (opcional)</label>
                                <input
                                    type="file"
                                    id="imagen"
                                    name="imagen"
                                    class="form-control"
                                    accept="image/*"
                                >
                                <small style="color:#666;font-size:0.85rem;">
                                    Formatos permitidos: JPG, PNG. Tamaño máximo recomendado: 2MB.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar equipo
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>


