/**
 * JavaScript para escaneo continuo de QR del portero
 * Similar al escáner de asistencia, escanea continuamente sin interrupciones
 */

let html5QrCode = null;
let isScanning = false;
let historialIngresos = [];
let ultimoQRProcesado = null;
let tiempoUltimoProcesamiento = 0;

// Elementos del DOM
const btnIniciarScanner = document.getElementById('btnIniciarScanner');
const btnDetenerScanner = document.getElementById('btnDetenerScanner');
const scanResult = document.getElementById('scanResult');
const historialContainer = document.getElementById('historialContainer');
const estadisticasContainer = document.getElementById('estadisticasContainer');

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si Html5Qrcode está disponible
    if (typeof Html5Qrcode === 'undefined') {
        console.error('Html5Qrcode no está cargado. Asegúrate de incluir la librería.');
        return;
    }

    // Configurar botones
    if (btnIniciarScanner) {
        btnIniciarScanner.addEventListener('click', iniciarScanner);
    }
    if (btnDetenerScanner) {
        btnDetenerScanner.addEventListener('click', detenerScanner);
    }

    // Cargar historial inicial si existe
    cargarHistorialIngresos();
});

// Iniciar escáner
async function iniciarScanner() {
    try {
        html5QrCode = new Html5Qrcode("reader");
        
        const config = {
            fps: 10,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
                // Usar 70% del ancho disponible, mínimo 250px, máximo 350px
                const minSize = 250;
                const maxSize = 350;
                const size = Math.min(Math.max(viewfinderWidth * 0.7, minSize), maxSize);
                return { width: size, height: size };
            },
            aspectRatio: 1.0
        };
        
        await html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        );
        
        isScanning = true;
        if (btnIniciarScanner) btnIniciarScanner.style.display = 'none';
        if (btnDetenerScanner) btnDetenerScanner.style.display = 'inline-flex';
        
        mostrarMensaje('Escáner activo. Acerca el código QR del equipo a la cámara.', 'info');
        
    } catch (error) {
        console.error('Error iniciando escáner:', error);
        mostrarMensaje('No se pudo iniciar la cámara. Por favor verifica los permisos.', 'error');
    }
}

// Detener escáner
async function detenerScanner() {
    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
            isScanning = false;
            if (btnIniciarScanner) btnIniciarScanner.style.display = 'inline-flex';
            if (btnDetenerScanner) btnDetenerScanner.style.display = 'none';
            mostrarMensaje('Escáner detenido', 'info');
        } catch (error) {
            console.error('Error deteniendo escáner:', error);
        }
    }
}

// Callback cuando se escanea exitosamente
async function onScanSuccess(decodedText, decodedResult) {
    const ahora = Date.now();
    
    // Evitar procesar el mismo QR múltiples veces (debounce de 2 segundos)
    if (ultimoQRProcesado === decodedText && (ahora - tiempoUltimoProcesamiento) < 2000) {
        return;
    }
    
    ultimoQRProcesado = decodedText;
    tiempoUltimoProcesamiento = ahora;
    
    console.log('QR Escaneado:', decodedText);
    
    // Procesar el QR sin detener el escáner
    await procesarQR(decodedText);
}

// Callback de errores de escaneo (no críticos)
function onScanError(errorMessage) {
    // Ignorar errores continuos de lectura (es normal cuando no hay QR visible)
}

// Procesar código QR escaneado
async function procesarQR(qrData) {
    try {
        mostrarMensaje('Procesando código QR...', 'info');
        
        const response = await fetch('/api/portero/procesar-qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                qr_data: qrData,
                observaciones: ''
            })
        });
        
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            mostrarMensaje('Error: El servidor no respondió correctamente', 'error');
            return;
        }
        
        const result = await response.json();
        
        // Si la respuesta no fue exitosa, mostrar el mensaje de error
        if (!response.ok) {
            const errorMessage = result.message || `Error ${response.status}: ${response.statusText}`;
            mostrarMensaje(`<i class="fas fa-exclamation-circle"></i> ${errorMessage}`, 'error');
            console.error('Error del servidor:', result);
            return;
        }
        
        if (result.success) {
            const registro = result.data || {};
            
            // Añadir al historial
            historialIngresos.unshift({
                id: registro.id || Date.now(),
                equipo: registro.equipo || {},
                aprendiz: registro.aprendiz || {},
                tipo: registro.tipo || 'ingreso',
                fecha: registro.fecha || new Date().toLocaleDateString('es-CO'),
                hora: registro.hora || new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }),
                mensaje: result.message || 'Operación registrada correctamente'
            });
            
            const tipoTexto = registro.tipo === 'ingreso' ? 'INGRESO' : 'SALIDA';
            const nombreEquipo = registro.equipo?.marca || 'Equipo';
            const serialEquipo = registro.equipo?.numero_serial || '';
            const nombreAprendiz = registro.aprendiz?.nombre || 'Aprendiz';
            const apellidoAprendiz = registro.aprendiz?.apellido || '';
            
            mostrarMensaje(
                `<i class="fas fa-check"></i> ${tipoTexto} registrado: ${nombreEquipo} (${serialEquipo}) - ${nombreAprendiz} ${apellidoAprendiz}`,
                'success'
            );
            
            actualizarHistorial();
            actualizarEstadisticas();
            
            // Reproducir sonido de éxito
            reproducirSonidoExito();
            
        } else {
            mostrarMensaje(`<i class="fas fa-xmark"></i> Error: ${result.message || 'Error al procesar el QR'}`, 'error');
        }
        
    } catch (error) {
        console.error('Error procesando QR:', error);
        mostrarMensaje('Error de conexión al procesar el QR. Por favor intenta nuevamente.', 'error');
    }
}

// Mostrar mensaje en el resultado del escaneo
function mostrarMensaje(mensaje, tipo) {
    if (!scanResult) return;
    
    const iconos = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'info': 'fa-info-circle'
    };
    
    scanResult.innerHTML = `
        <div class="scan-result-message ${tipo}">
            <i class="fas ${iconos[tipo] || 'fa-info-circle'}"></i>
            <span>${mensaje}</span>
        </div>
    `;
    
    // Auto-ocultar después de un tiempo
    setTimeout(() => {
        if (scanResult) {
            scanResult.innerHTML = '';
        }
    }, tipo === 'success' ? 3000 : 5000);
}

// Actualizar historial de ingresos
function actualizarHistorial() {
    if (!historialContainer) return;
    
    if (historialIngresos.length === 0) {
        historialContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-qrcode"></i>
                <p>No hay registros aún. Escanea el código QR de un equipo para comenzar.</p>
            </div>
        `;
        return;
    }
    
    const html = historialIngresos.map((registro, index) => {
        const tipoClass = registro.tipo === 'ingreso' ? 'success' : 'info';
        const tipoIcon = registro.tipo === 'ingreso' ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
        
        return `
            <div class="historial-item ${tipoClass}">
                <div class="historial-info">
                    <span class="historial-numero">#${historialIngresos.length - index}</span>
                    <div class="historial-datos">
                        <strong>${registro.equipo?.marca || 'Equipo'} - ${registro.equipo?.numero_serial || 'N/A'}</strong>
                        <span class="historial-doc">${registro.aprendiz?.nombre || ''} ${registro.aprendiz?.apellido || ''}</span>
                    </div>
                </div>
                <div class="historial-estado">
                    <span class="badge badge-${tipoClass}">
                        <i class="fas ${tipoIcon}"></i> ${registro.tipo?.toUpperCase() || 'OPERACIÓN'}
                    </span>
                    <span class="historial-hora">${registro.hora || '--'}</span>
                </div>
            </div>
        `;
    }).join('');
    
    historialContainer.innerHTML = html;
}

// Actualizar estadísticas
function actualizarEstadisticas() {
    if (!estadisticasContainer) return;
    
    const total = historialIngresos.length;
    const ingresos = historialIngresos.filter(r => r.tipo === 'ingreso').length;
    const salidas = historialIngresos.filter(r => r.tipo === 'salida').length;
    
    estadisticasContainer.innerHTML = `
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total Operaciones</span>
                <span class="stat-value">${total}</span>
            </div>
            <div class="stat-item success">
                <span class="stat-label">Ingresos</span>
                <span class="stat-value">${ingresos}</span>
            </div>
            <div class="stat-item info">
                <span class="stat-label">Salidas</span>
                <span class="stat-value">${salidas}</span>
            </div>
        </div>
    `;
}

// Cargar historial de ingresos desde el servidor
async function cargarHistorialIngresos() {
    try {
        const response = await fetch('/api/portero/ingresos-activos?limit=50', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data && Array.isArray(result.data)) {
            // Convertir ingresos activos a formato de historial
            historialIngresos = result.data.map(ingreso => ({
                id: ingreso.id,
                equipo: ingreso.equipo || {},
                aprendiz: ingreso.aprendiz || {},
                tipo: ingreso.fecha_salida ? 'salida' : 'ingreso',
                fecha: ingreso.fecha_ingreso || '',
                hora: ingreso.hora_ingreso || '',
                mensaje: 'Ingreso activo'
            }));
            
            actualizarHistorial();
            actualizarEstadisticas();
        }
    } catch (error) {
        console.error('Error cargando historial:', error);
    }
}

// Reproducir sonido de éxito
function reproducirSonidoExito() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
        // Silenciar errores de audio
    }
}

// Limpiar al salir
window.addEventListener('beforeunload', () => {
    if (isScanning) {
        detenerScanner();
    }
});
