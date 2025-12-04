// Variables globales
let qrCode = null;
let aprendizData = null;

// Referencias DOM
const documentoInput = document.getElementById('documento');
const btnBuscar = document.getElementById('btnBuscar');
const resultadoBusqueda = document.getElementById('resultadoBusqueda');
const infoAprendiz = document.getElementById('infoAprendiz');
const generadorQR = document.getElementById('generadorQR');
const qrCodeContainer = document.getElementById('qrCodeContainer');
const btnDescargar = document.getElementById('btnDescargar');
const btnNuevo = document.getElementById('btnNuevo');

// Referencias del modal de error
const modalError = document.getElementById('modalError');
const modalErrorTitulo = document.getElementById('modalErrorTitulo');
const modalErrorMensaje = document.getElementById('modalErrorMensaje');
const btnCerrarModalError = document.getElementById('btnCerrarModalError');

// Referencias del modal de éxito
const modalExito = document.getElementById('modalExito');
const btnCerrarModalExito = document.getElementById('btnCerrarModalExito');
const countdown = document.getElementById('countdown');

// Variable para controlar el temporizador
let countdownInterval = null;
let autoCloseTimeout = null;

// Eventos
btnBuscar.addEventListener('click', buscarAprendiz);
documentoInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        buscarAprendiz();
    }
});

// Cerrar modales
btnCerrarModalError.addEventListener('click', () => cerrarModal('error'));
btnCerrarModalExito.addEventListener('click', () => cerrarModal('exito'));

modalError.addEventListener('click', (e) => {
    if (e.target === modalError) {
        cerrarModal('error');
    }
});

modalExito.addEventListener('click', (e) => {
    if (e.target === modalExito) {
        cerrarModal('exito');
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (modalError.style.display !== 'none') {
            cerrarModal('error');
        }
        if (modalExito.style.display !== 'none') {
            cerrarModal('exito');
        }
    }
});

// Función para buscar aprendiz
async function buscarAprendiz() {
    const documento = documentoInput.value.trim();
    
    if (!documento) {
        mostrarModalError('Documento requerido', 'Por favor ingresa tu número de documento antes de buscar.');
        documentoInput.focus();
        return;
    }
    
    // Validar que solo sean números
    if (!/^\d+$/.test(documento)) {
        mostrarModalError('Documento inválido', 'El número de documento debe contener solo números. Por favor verifica e intenta nuevamente.');
        documentoInput.focus();
        return;
    }
    
    // Validar longitud mínima
    if (documento.length < 5) {
        mostrarModalError('Documento inválido', 'El número de documento debe tener al menos 5 dígitos. Por favor verifica e intenta nuevamente.');
        documentoInput.focus();
        return;
    }

    try {
        btnBuscar.disabled = true;
        btnBuscar.innerHTML = '<span class="spinner"></span> Buscando...';
        
        // Tiempo mínimo de espera para mostrar el feedback visual (800ms)
        const minLoadTime = new Promise(resolve => setTimeout(resolve, 800));
        
        const fetchPromise = fetch(`/api/qr/buscar?documento=${encodeURIComponent(documento)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Esperar tanto la respuesta como el tiempo mínimo
        const [response] = await Promise.all([fetchPromise, minLoadTime]);
        const result = await response.json();
        
        if (result.success) {
            aprendizData = result.data;
            mostrarInformacionAprendiz(result.data);
            generarCodigoQR(result.data);
        } else {
            mostrarModalError('Aprendiz no encontrado', result.message || 'No se encontró un aprendiz con ese número de documento. Por favor verifica e intenta nuevamente.');
            ocultarSecciones();
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarModalError('Error de conexión', 'No se pudo conectar con el servidor. Por favor verifica tu conexión a internet e intenta nuevamente.');
        ocultarSecciones();
    } finally {
        btnBuscar.disabled = false;
        btnBuscar.innerHTML = `
            <i class="fas fa-magnifying-glass"></i>
            Buscar
        `;
    }
}

// Mostrar información del aprendiz
function mostrarInformacionAprendiz(data) {
    const { aprendiz, fichas } = data;
    
    const fichasHTML = fichas.map(f => 
        `<span class="badge badge-info">${f.numero_ficha} - ${f.nombre}</span>`
    ).join('');
    
    const html = `
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Documento:</span>
                <span class="info-value">${aprendiz.documento}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Nombre Completo:</span>
                <span class="info-value">${aprendiz.nombre_completo}</span>
            </div>
            ${aprendiz.email ? `
            <div class="info-item">
                <span class="info-label">Correo Electrónico:</span>
                <span class="info-value">${aprendiz.email}</span>
            </div>
            ` : ''}
            <div class="info-item full-width">
                <span class="info-label">Fichas:</span>
                <div class="info-value">${fichasHTML}</div>
            </div>
        </div>
    `;
    
    infoAprendiz.querySelector('.info-content').innerHTML = html;
    infoAprendiz.style.display = 'block';
    resultadoBusqueda.style.display = 'none';
}

// Generar código QR
function generarCodigoQR(data) {
    const { aprendiz } = data;
    
    // Datos simplificados para el QR: solo ID y fecha
    // Formato: "ID|FECHA" (ej: "123|2025-11-20")
    // Esto hace el código mucho más pequeño y fácil de escanear
    const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
    const qrData = `${aprendiz.id}|${today}`;
    
    // Limpiar contenedor
    qrCodeContainer.innerHTML = '';
    
    // Crear nuevo código QR
    qrCode = new QRCodeStyling({
        width: 300,
        height: 300,
        margin: 10,
        data: qrData,
        dotsOptions: {
            color: '#2b2b2b',
            type: 'rounded'
        },
        cornersSquareOptions: {
            type: 'extra-rounded',
            color: '#39A900'
        },
        cornersDotOptions: {
            type: 'dot',
            color: '#39A900'
        },
        backgroundOptions: {
            color: '#ffffff'
        },
        imageOptions: {
            crossOrigin: 'anonymous',
            margin: 6
        }
    });
    
    qrCode.append(qrCodeContainer);
    generadorQR.style.display = 'block';
    
    // Scroll suave al QR
    generadorQR.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Descargar QR
btnDescargar.addEventListener('click', async () => {
    if (!qrCode || !aprendizData) return;
    
    try {
        const { aprendiz } = aprendizData;
        const fileName = `QR_${aprendiz.documento}_${aprendiz.nombre.replace(/\s+/g, '_')}.png`;
        
        await qrCode.download({
            name: fileName,
            extension: 'png'
        });
        
        // Mostrar modal de éxito
        mostrarModalExito();
        
    } catch (error) {
        console.error('Error descargando QR:', error);
        mostrarModalError(
            'Error al descargar', 
            'No se pudo descargar el código QR. Por favor intenta nuevamente o toma una captura de pantalla.'
        );
    }
});

// Generar nuevo QR
btnNuevo.addEventListener('click', () => {
    documentoInput.value = '';
    ocultarSecciones();
    documentoInput.focus();
});

// Ocultar secciones
function ocultarSecciones() {
    infoAprendiz.style.display = 'none';
    generadorQR.style.display = 'none';
    resultadoBusqueda.style.display = 'none';
}

// Mostrar modal de error
function mostrarModalError(titulo, mensaje) {
    modalErrorTitulo.textContent = titulo;
    modalErrorMensaje.textContent = mensaje;
    modalError.style.display = 'flex';
    
    // Agregar animación
    setTimeout(() => {
        modalError.querySelector('.modal-container').classList.add('modal-show');
    }, 10);
}

// Cerrar modal
function cerrarModal(tipo = 'error') {
    const modal = tipo === 'error' ? modalError : modalExito;
    const container = modal.querySelector('.modal-container');
    container.classList.remove('modal-show');
    
    // Limpiar temporizadores si es modal de éxito
    if (tipo === 'exito') {
        if (countdownInterval) clearInterval(countdownInterval);
        if (autoCloseTimeout) clearTimeout(autoCloseTimeout);
    }
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Mostrar modal de éxito
function mostrarModalExito() {
    modalExito.style.display = 'flex';
    
    setTimeout(() => {
        modalExito.querySelector('.modal-container').classList.add('modal-show');
    }, 10);
    
    // Iniciar cuenta regresiva
    let seconds = 5;
    countdown.textContent = seconds;
    
    // Limpiar temporizadores anteriores si existen
    if (countdownInterval) clearInterval(countdownInterval);
    if (autoCloseTimeout) clearTimeout(autoCloseTimeout);
    
    // Actualizar cuenta regresiva cada segundo
    countdownInterval = setInterval(() => {
        seconds--;
        countdown.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(countdownInterval);
        }
    }, 1000);
    
    // Cerrar automáticamente después de 5 segundos
    autoCloseTimeout = setTimeout(() => {
        cerrarModal('exito');
    }, 5000);
}

// Auto-focus en el input
documentoInput.focus();

