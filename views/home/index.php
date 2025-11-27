<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENAttend - Generar Código QR</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/header-public.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/home/home.css') ?>">
</head>
<body>
    <?php include __DIR__ . '/../components/header-public.php'; ?>
    
    <div class="home-wrapper">
        <main class="home-content">
            <div class="container">
                <div class="home-grid">
                    <div class="home-info">
                        <h2>Genera tu Código QR de Aprendiz</h2>
                        <p>
                            Si eres aprendiz del SENA, puedes generar tu código QR personal ingresando 
                            tu número de documento. Este código te permitirá registrar tu asistencia 
                            de forma rápida y segura en las clases.
                        </p>
                        <ul class="home-features">
                            <li>Código QR único y personalizado</li>
                            <li>Generación instantánea</li>
                            <li>Envío automático por correo electrónico</li>
                            <li>Válido por tiempo limitado</li>
                            <li>Fácil de usar y compartir</li>
                        </ul>
                        <div class="instructions-box">
                            <p style="margin: 0; font-size: 0.95rem; opacity: 0.95;">
                                <i class="fas fa-info-circle"></i>
                                <strong> Instrucciones:</strong> Ingresa tu número de documento en el formulario 
                                de la derecha. Una vez generado, guarda el código QR en tu dispositivo o 
                                toma una captura de pantalla para presentarlo al instructor.
                            </p>
                        </div>
                        <div style="margin-top: 2rem;">
                            <a href="/" class="btn-back-home">
                                <i class="fas fa-arrow-left"></i> Volver al Inicio
                            </a>
                        </div>
                    </div>

                    <div class="qr-card">
                        <h3><i class="fas fa-qrcode"></i> Generar Código QR</h3>
                        <p>Ingresa tu número de documento para generar tu código QR personal de asistencia</p>

                        <div id="alertContainer"></div>

                        <form id="qrForm">
                            <div class="form-group">
                                <label for="documento">Número de Documento</label>
                                <input 
                                    type="text" 
                                    id="documento" 
                                    name="documento" 
                                    placeholder="Ej: 1234567890"
                                    pattern="[0-9]{6,20}"
                                    required
                                    autocomplete="off"
                                >
                                <small style="color: #666; font-size: 0.85rem;">Solo números, entre 6 y 20 dígitos</small>
                            </div>

                            <button type="submit" class="btn-generate" id="btnGenerar">
                                Generar Código QR
                            </button>
                        </form>

                        <div id="qrResult" class="qr-result">
                            <div class="aprendiz-info" id="aprendizInfo"></div>
                            <div class="qr-code-container" id="qrCodeContainer"></div>
                            <p style="color: #666; font-size: 0.9rem; margin-top: 1rem;">
                                Guarda este código QR en tu dispositivo o toma una captura de pantalla. 
                                Preséntalo al instructor para registrar tu asistencia.
                            </p>
                            <button onclick="location.reload()" class="btn-generate" style="margin-top: 1rem; background: #6c757d;">
                                Generar Otro QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="home-footer">
            <div class="container">
                <p>&copy; <?= date('Y') ?> SENA - Servicio Nacional de Aprendizaje | <strong>SENAttend</strong></p>
                <p style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.8;">
                    <a href="/" style="color: white; text-decoration: underline;">Volver al Inicio</a>
                </p>
            </div>
        </footer>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/home/home.js') ?>"></script>
</body>
</html>
