<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENAttend - Sistema de Asistencia SENA</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .home-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #39A900 0%, #2d8600 100%);
            display: flex;
            flex-direction: column;
        }

        .home-header {
            background: rgba(0, 0, 0, 0.2);
            padding: 1.5rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .home-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .home-logo h1 {
            color: white;
            font-size: 2rem;
            margin: 0;
        }

        .home-logo p {
            color: rgba(255,255,255,0.9);
            margin: 0;
            font-size: 0.9rem;
        }

        .home-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 3rem 0;
        }

        .home-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .home-info {
            color: white;
        }

        .home-info h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .home-info p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            opacity: 0.95;
        }

        .home-features {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }

        .home-features li {
            padding: 0.5rem 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .home-features li::before {
            content: "✓";
            display: inline-block;
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
        }

        .qr-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .qr-card h3 {
            color: #39A900;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .qr-card p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #39A900;
        }

        .btn-generate {
            width: 100%;
            padding: 1rem;
            background: #39A900;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-generate:hover {
            background: #2d8600;
        }

        .btn-generate:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .qr-result {
            margin-top: 2rem;
            text-align: center;
            display: none;
        }

        .qr-result.active {
            display: block;
        }

        .qr-code-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .qr-code-container canvas {
            max-width: 100%;
            height: auto;
        }

        .aprendiz-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .aprendiz-info p {
            margin: 0.5rem 0;
            color: #333;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .btn-login {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #39A900;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: rgba(255,255,255,0.9);
            transform: translateY(-2px);
        }

        .home-footer {
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            padding: 1.5rem 0;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .home-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .home-info h2 {
                font-size: 2rem;
            }

            .qr-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="home-wrapper">
        <header class="home-header">
            <div class="container">
                <div class="home-logo">
                    <h1>SENAttend</h1>
                    <p>Sistema de Asistencia SENA</p>
                </div>
                <a href="/login" class="btn-login">Iniciar Sesión</a>
            </div>
        </header>

        <main class="home-content">
            <div class="container">
                <div class="home-grid">
                    <div class="home-info">
                        <h2>Bienvenido al Sistema de Asistencia del SENA</h2>
                        <p>
                            SENAttend es la plataforma oficial para el registro y control de asistencia 
                            de aprendices en el Servicio Nacional de Aprendizaje (SENA).
                        </p>
                        <ul class="home-features">
                            <li>Registro rápido mediante código QR</li>
                            <li>Control de asistencia en tiempo real</li>
                            <li>Gestión eficiente de fichas y aprendices</li>
                            <li>Reportes y estadísticas detalladas</li>
                            <li>Acceso seguro y protegido</li>
                        </ul>
                        <p style="margin-top: 2rem; font-size: 0.95rem; opacity: 0.9;">
                            <strong>¿Eres aprendiz?</strong> Genera tu código QR personal ingresando tu número 
                            de documento en el formulario. Este código te permitirá registrar tu asistencia 
                            de forma rápida y segura.
                        </p>
                    </div>

                    <div class="qr-card">
                        <h3>Generar Código QR de Aprendiz</h3>
                        <p>Ingresa tu número de documento para generar tu código QR personal</p>

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
            </div>
        </footer>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        const form = document.getElementById('qrForm');
        const btnGenerar = document.getElementById('btnGenerar');
        const qrResult = document.getElementById('qrResult');
        const alertContainer = document.getElementById('alertContainer');
        const aprendizInfo = document.getElementById('aprendizInfo');
        const qrCodeContainer = document.getElementById('qrCodeContainer');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const documento = document.getElementById('documento').value.trim();

            // Validar formato
            if (!/^\d{6,20}$/.test(documento)) {
                showAlert('El documento debe contener solo números (6-20 dígitos)', 'error');
                return;
            }

            // Deshabilitar botón
            btnGenerar.disabled = true;
            btnGenerar.textContent = 'Generando...';

            try {
                const response = await fetch('/api/public/aprendiz/validar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ documento })
                });

                const result = await response.json();

                if (result.success) {
                    // Mostrar información del aprendiz
                    aprendizInfo.innerHTML = `
                        <p><strong>Nombre:</strong> ${result.data.aprendiz.nombre_completo}</p>
                        <p><strong>Documento:</strong> ${result.data.aprendiz.documento}</p>
                        <p><strong>Código de Carnet:</strong> ${result.data.aprendiz.codigo_carnet || 'N/A'}</p>
                    `;

                    // Limpiar contenedor de QR
                    qrCodeContainer.innerHTML = '';

                    // Generar código QR con datos simplificados (ID|FECHA)
                    // Esto hace el QR mucho más pequeño y fácil de escanear
                    new QRCode(qrCodeContainer, {
                        text: result.data.qr_data,  // Ya viene en formato simple: "ID|FECHA"
                        width: 256,
                        height: 256,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    // Mostrar resultado
                    qrResult.classList.add('active');
                    form.style.display = 'none';
                    showAlert('Código QR generado exitosamente', 'success');

                } else {
                    showAlert(result.message || 'Error al generar el código QR', 'error');
                }

            } catch (error) {
                console.error('Error:', error);
                showAlert('Error de conexión. Por favor intenta nuevamente.', 'error');
            } finally {
                btnGenerar.disabled = false;
                btnGenerar.textContent = 'Generar Código QR';
            }
        });

        function showAlert(message, type) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;

            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
