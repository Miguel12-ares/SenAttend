<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Instructores Líderes - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/components.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'instructor-fichas';
        require __DIR__ . '/../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="page-header">
                    <div>
                        <h1><i class="fas fa-file-import"></i> Importar Instructores Líderes</h1>
                        <p>Importe asignaciones de instructor líder a ficha desde un archivo CSV.</p>
                    </div>
                    <div class="page-actions">
                        <a href="/instructor-fichas" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Asignaciones
                        </a>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <div class="form-card">
                        <h2><i class="fas fa-info-circle"></i> Formato del CSV</h2>
                        <p>El archivo debe contener las columnas:</p>
                        <ul>
                            <li><code>documento_instructor</code>: Documento del instructor (debe existir como usuario con rol <strong>instructor</strong>).</li>
                            <li><code>numero_ficha</code>: Número de ficha tal como está registrada en el sistema.</li>
                        </ul>
                        <p>Ejemplo:</p>
                        <pre style="background:#f8f9fa;border-radius:6px;padding:0.75rem;">documento_instructor,numero_ficha
123456789,2995479
987654321,2995480</pre>
                    </div>

                    <div class="form-card">
                        <h2><i class="fas fa-upload"></i> Cargar Archivo CSV</h2>
                        <form method="POST" action="/instructor-fichas/lideres/importar" enctype="multipart/form-data" id="importLideresForm">
                            <div class="form-group">
                                <label><i class="fas fa-file-csv"></i> Archivo CSV</label>
                                <div class="file-upload-area" onclick="document.getElementById('csv_file').click()">
                                    <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="file-upload-text">
                                        <strong>Click para seleccionar archivo</strong> o arrastra aquí<br>
                                        <small>Formato: documento_instructor, numero_ficha</small>
                                    </div>
                                    <input 
                                        type="file" 
                                        id="csv_file" 
                                        name="csv_file" 
                                        accept=".csv" 
                                        required
                                        style="display: none;"
                                    >
                                </div>
                                <div id="fileInfo" style="display: none;" class="file-selected">
                                    <div>
                                        <div class="file-selected-name" id="fileName"></div>
                                        <div class="file-selected-size" id="fileSize"></div>
                                    </div>
                                    <button type="button" class="file-remove" onclick="clearFile()"><i class="fas fa-times"></i></button>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>
                                    Las asignaciones importadas <strong>reemplazarán</strong> al líder actual de cada ficha incluida en el archivo.
                                </span>
                            </div>

                            <div class="form-actions">
                                <a href="/instructor-fichas" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-import"></i> Importar Líderes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('csv_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
            document.getElementById('fileInfo').style.display = 'flex';
            document.querySelector('.file-upload-area').style.display = 'none';
        }
    });

    function clearFile() {
        document.getElementById('csv_file').value = '';
        document.getElementById('fileInfo').style.display = 'none';
        document.querySelector('.file-upload-area').style.display = 'block';
    }

    document.getElementById('importLideresForm').addEventListener('submit', function(e) {
        const file = document.getElementById('csv_file').files[0];
        if (!file) {
            e.preventDefault();
            alert('Por favor seleccione un archivo CSV');
            return false;
        }
        if (!file.name.toLowerCase().endsWith('.csv')) {
            e.preventDefault();
            alert('El archivo debe ser un CSV');
            return false;
        }
        return true;
    });
    </script>
</body>
</html>


