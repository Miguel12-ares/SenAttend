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

    // Prellenar documento si viene en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const documentoParam = urlParams.get('documento');
    if (documentoParam) {
        const documentoInput = document.getElementById('documento');
        if (documentoInput) {
            documentoInput.value = documentoParam;
            // Auto-generar QR si el documento está prellenado
            setTimeout(() => {
                if (form) {
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                }
            }, 500);
        }
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
     * Enmascara el número de documento mostrando solo el primer y segundo dígito,
     * y el penúltimo y último dígito. El resto se oculta con asteriscos.
     * @param {string} documento - Número de documento completo
     * @returns {string} - Documento enmascarado
     */
    function enmascararDocumento(documento) {
        if (!documento || documento.length < 4) {
            return documento; // Si es muy corto, retornar sin cambios
        }

        const primerDigito = documento[0];
        const segundoDigito = documento[1];
        const penultimoDigito = documento[documento.length - 2];
        const ultimoDigito = documento[documento.length - 1];

        // Calcular cuántos asteriscos necesitamos
        const asteriscos = '*'.repeat(Math.max(0, documento.length - 4));

        return `${primerDigito}${segundoDigito}${asteriscos}${penultimoDigito}${ultimoDigito}`;
    }

    /**
     * Enmascara el correo electrónico mostrando los primeros 3 caracteres
     * y el dominio completo (incluyendo @outlook, @gmail, etc.).
     * El resto se oculta con asteriscos.
     * @param {string} email - Correo electrónico completo
     * @returns {string} - Correo enmascarado
     */
    function enmascararEmail(email) {
        if (!email || email.length < 6) {
            return email; // Si es muy corto, retornar sin cambios
        }

        // Buscar la posición del @
        const arrobaIndex = email.indexOf('@');
        
        if (arrobaIndex === -1) {
            // Si no hay @, mostrar primeros 3 y últimos 3
            if (email.length <= 6) {
                return email;
            }
            const primeros3 = email.substring(0, 3);
            const ultimos3 = email.substring(email.length - 3);
            const asteriscos = '*'.repeat(Math.max(0, email.length - 6));
            return `${primeros3}${asteriscos}${ultimos3}`;
        }

        // Obtener la parte local (antes del @)
        const parteLocal = email.substring(0, arrobaIndex);
        // Obtener el dominio completo (desde el @ en adelante)
        const dominio = email.substring(arrobaIndex);

        // Si la parte local tiene 3 o menos caracteres, mostrar todos
        if (parteLocal.length <= 3) {
            return `${parteLocal}${dominio}`;
        }

        // Mostrar primeros 3 de la parte local
        const primeros3Local = parteLocal.substring(0, 3);
        
        // Mostrar el dominio completo (incluyendo @gmail.com, @outlook.com, etc.)
        // Calcular asteriscos para la parte local
        const asteriscosLocal = '*'.repeat(Math.max(0, parteLocal.length - 3));

        return `${primeros3Local}${asteriscosLocal}${dominio}`;
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
                // Enmascarar credenciales antes de mostrar
                const documentoEnmascarado = enmascararDocumento(result.data.aprendiz.documento);
                const emailEnmascarado = result.data.aprendiz.email 
                    ? enmascararEmail(result.data.aprendiz.email) 
                    : 'N/A';

                // Mostrar información del aprendiz con credenciales enmascaradas
                aprendizInfo.innerHTML = `
                    <p><strong>Nombre:</strong> ${result.data.aprendiz.nombre_completo}</p>
                    <p><strong>Documento:</strong> ${documentoEnmascarado}</p>
                    <p><strong>Correo Electrónico:</strong> ${emailEnmascarado}</p>
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

