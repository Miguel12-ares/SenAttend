/**
 * JavaScript para la página de generación de QR (Home)
 */

(function() {
    'use strict';

    const form = document.getElementById('qrForm');
    const btnGenerar = document.getElementById('btnGenerar');
    const qrResult = document.getElementById('qrResult');
    const alertContainer = document.getElementById('alertContainer');
    const aprendizInfo = document.getElementById('aprendizInfo');
    const qrCodeContainer = document.getElementById('qrCodeContainer');

    if (!form || !btnGenerar || !qrResult || !alertContainer || !aprendizInfo || !qrCodeContainer) {
        console.error('Elementos del formulario QR no encontrados');
        return;
    }

    /**
     * Muestra un mensaje de alerta
     */
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

    /**
     * Maneja el envío del formulario
     */
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
                    <p><strong>Correo Electrónico:</strong> ${result.data.aprendiz.email || 'N/A'}</p>
                `;

                // Limpiar contenedor de QR
                qrCodeContainer.innerHTML = '';

                // Verificar que QRCode esté disponible
                if (typeof QRCode === 'undefined') {
                    showAlert('Error: La librería QRCode no está cargada', 'error');
                    return;
                }

                // Generar código QR con datos simplificados (ID|FECHA)
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
})();

