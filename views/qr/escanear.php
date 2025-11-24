<?php
/**
 * Vista para Escanear Código QR - Instructores
 * Permite a los instructores escanear códigos QR y registrar asistencia
 */

// Variables para el layout
$title = 'Escanear Código QR - SENAttend';
$additionalStyles = '<link rel="stylesheet" href="/css/qr.css">';
$showHeader = true;
$currentPage = 'qr-escanear';

// Obtener usuario de sesión (pasado desde el controlador)
$user = $user ?? null;

ob_start();
?>

<div class="qr-container">
    <div class="qr-header">
        <h1>
            <i class="fas fa-camera"></i>
            Escanear Código QR
        </h1>
        <p class="subtitle">Escanea el código QR del aprendiz para registrar su asistencia</p>
    </div>

    <!-- Selector de ficha -->
    <div class="qr-card config-card">
        <h2>1. Selecciona la Ficha</h2>
        <div class="form-group">
            <label for="fichaSelect">Ficha Activa</label>
            <select id="fichaSelect" name="ficha_id" required>
                <option value="">Selecciona una ficha...</option>
                <?php foreach ($fichas as $ficha): ?>
                    <option value="<?= htmlspecialchars($ficha['id']) ?>">
                        <?= htmlspecialchars($ficha['numero_ficha']) ?> - <?= htmlspecialchars($ficha['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="info-banner">
            <i class="fas fa-circle-info"></i>
            <span>Fecha de registro: <strong><?= date('d/m/Y') ?></strong> | Hora límite tardanza: <strong>07:30 AM</strong></span>
        </div>
    </div>

    <!-- Escáner -->
    <div id="scannerCard" class="qr-card scanner-card" style="display: none;">
        <h2>2. Escanear Código QR</h2>
        <div class="scanner-container">
            <div id="reader"></div>
            <div class="scanner-controls">
                <button id="btnIniciarScanner" class="btn btn-primary">
                    <i class="fas fa-play"></i>
                    Iniciar Escáner
                </button>
                <button id="btnDetenerScanner" class="btn btn-danger" style="display: none;">
                    <i class="fas fa-stop"></i>
                    Detener
                </button>
            </div>
        </div>

        <div id="scanResult" class="scan-result"></div>
    </div>

    <!-- Historial de escaneos -->
    <div id="historialCard" class="qr-card history-card" style="display: none;">
        <h2>3. Registro de Asistencias</h2>
        <div id="historialContainer" class="historial-container">
            <!-- Se llenará con JavaScript -->
        </div>
        
        <div class="estadisticas" id="estadisticas">
            <!-- Se llenará con JavaScript -->
        </div>
    </div>
</div>

<!-- Librería html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
// Variables globales
let html5QrCode = null;
let fichaSeleccionada = null;
let historialRegistros = [];
let isScanning = false;

// Referencias DOM
const fichaSelect = document.getElementById('fichaSelect');
const scannerCard = document.getElementById('scannerCard');
const historialCard = document.getElementById('historialCard');
const btnIniciarScanner = document.getElementById('btnIniciarScanner');
const btnDetenerScanner = document.getElementById('btnDetenerScanner');
const scanResult = document.getElementById('scanResult');
const historialContainer = document.getElementById('historialContainer');
const estadisticas = document.getElementById('estadisticas');

// Evento cambio de ficha
fichaSelect.addEventListener('change', (e) => {
    fichaSeleccionada = e.target.value;
    
    if (fichaSeleccionada) {
        scannerCard.style.display = 'block';
        historialCard.style.display = 'block';
        
        // Limpiar historial al cambiar de ficha
        historialRegistros = [];
        actualizarHistorial();
        actualizarEstadisticas();
    } else {
        scannerCard.style.display = 'none';
        historialCard.style.display = 'none';
        if (isScanning) {
            detenerScanner();
        }
    }
});

// Iniciar escáner
btnIniciarScanner.addEventListener('click', async () => {
    if (!fichaSeleccionada) {
        alert('Por favor selecciona una ficha primero');
        return;
    }
    
    try {
        html5QrCode = new Html5Qrcode("reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        await html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanError
        );
        
        isScanning = true;
        btnIniciarScanner.style.display = 'none';
        btnDetenerScanner.style.display = 'inline-flex';
        
        mostrarMensaje('Escáner activo. Acerca el código QR a la cámara.', 'info');
        
    } catch (error) {
        console.error('Error iniciando escáner:', error);
        alert('No se pudo iniciar la cámara. Por favor verifica los permisos.');
    }
});

// Detener escáner
btnDetenerScanner.addEventListener('click', detenerScanner);

async function detenerScanner() {
    if (html5QrCode && isScanning) {
        try {
            await html5QrCode.stop();
            html5QrCode.clear();
            isScanning = false;
            btnIniciarScanner.style.display = 'inline-flex';
            btnDetenerScanner.style.display = 'none';
            mostrarMensaje('Escáner detenido', 'info');
        } catch (error) {
            console.error('Error deteniendo escáner:', error);
        }
    }
}

// Callback cuando se escanea exitosamente
async function onScanSuccess(decodedText, decodedResult) {
    console.log('QR Escaneado:', decodedText);
    
    // Detener temporalmente el escaneo para evitar duplicados
    if (isScanning) {
        await detenerScanner();
    }
    
    // Procesar el QR
    await procesarQR(decodedText);
}

// Callback de errores de escaneo (no críticos)
function onScanError(errorMessage) {
    // Ignorar errores continuos de lectura
}

// Procesar código QR escaneado
async function procesarQR(qrData) {
    try {
        mostrarMensaje('Procesando código QR...', 'info');
        
        const response = await fetch('/api/qr/procesar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                qr_data: qrData,
                ficha_id: fichaSeleccionada
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const registro = result.data;
            historialRegistros.unshift(registro);
            
            mostrarMensaje(
                `<i class="fas fa-check"></i> Asistencia registrada: ${registro.aprendiz.nombre} - ${registro.estado.toUpperCase()}`,
                'success'
            );
            
            actualizarHistorial();
            actualizarEstadisticas();
            
            // Reproducir sonido de éxito (opcional)
            reproducirSonidoExito();
            
            // Reactivar escáner después de 2 segundos (éxito rápido)
            setTimeout(() => {
                if (!isScanning) {
                    btnIniciarScanner.click();
                }
            }, 2000);
            
        } else {
            mostrarMensaje(`<i class="fas fa-xmark"></i> Error: ${result.message}`, 'error');
            
            // Reactivar escáner después de 4 segundos (tiempo para leer el error)
            setTimeout(() => {
                if (!isScanning) {
                    btnIniciarScanner.click();
                }
            }, 4000);
        }
        
    } catch (error) {
        console.error('Error procesando QR:', error);
        mostrarMensaje('Error al procesar el código QR. Por favor intenta nuevamente.', 'error');
        
        // Reactivar escáner después de 4 segundos (tiempo para leer el error)
        setTimeout(() => {
            if (!isScanning) {
                btnIniciarScanner.click();
            }
        }, 4000);
    }
}

// Actualizar historial
function actualizarHistorial() {
    if (historialRegistros.length === 0) {
        historialContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-qrcode"></i>
                <p>No hay registros aún. Escanea el código QR de un aprendiz para comenzar.</p>
            </div>
        `;
        return;
    }
    
    const html = historialRegistros.map((registro, index) => {
        const estadoClass = {
            'presente': 'success',
            'tardanza': 'warning',
            'ausente': 'danger'
        }[registro.estado] || 'info';
        
        return `
            <div class="historial-item ${estadoClass}">
                <div class="historial-info">
                    <span class="historial-numero">#${historialRegistros.length - index}</span>
                    <div class="historial-datos">
                        <strong>${registro.aprendiz.nombre}</strong>
                        <span class="historial-doc">Doc: ${registro.aprendiz.documento}</span>
                    </div>
                </div>
                <div class="historial-estado">
                    <span class="badge badge-${estadoClass}">${registro.estado.toUpperCase()}</span>
                    <span class="historial-hora">${registro.hora}</span>
                </div>
            </div>
        `;
    }).join('');
    
    historialContainer.innerHTML = html;
}

// Actualizar estadísticas
function actualizarEstadisticas() {
    const total = historialRegistros.length;
    const presentes = historialRegistros.filter(r => r.estado === 'presente').length;
    const tardanzas = historialRegistros.filter(r => r.estado === 'tardanza').length;
    const ausentes = historialRegistros.filter(r => r.estado === 'ausente').length;
    
    estadisticas.innerHTML = `
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total Registrados</span>
                <span class="stat-value">${total}</span>
            </div>
            <div class="stat-item success">
                <span class="stat-label">Presentes</span>
                <span class="stat-value">${presentes}</span>
            </div>
            <div class="stat-item warning">
                <span class="stat-label">Tardanzas</span>
                <span class="stat-value">${tardanzas}</span>
            </div>
            <div class="stat-item danger">
                <span class="stat-label">Ausentes</span>
                <span class="stat-value">${ausentes}</span>
            </div>
        </div>
    `;
}

// Variable para controlar el temporizador de auto-limpieza
let mensajeTimeout = null;

// Mostrar mensaje
function mostrarMensaje(mensaje, tipo = 'info') {
    const iconos = {
        success: `<i class="fas fa-check-circle"></i>`,
        error: `<i class="fas fa-xmark-circle"></i>`,
        info: `<i class="fas fa-circle-info"></i>`
    };
    
    // Limpiar temporizador anterior si existe
    if (mensajeTimeout) {
        clearTimeout(mensajeTimeout);
        mensajeTimeout = null;
    }
    
    scanResult.innerHTML = `
        <div class="alert alert-${tipo}">
            ${iconos[tipo] || iconos.info}
            ${mensaje}
        </div>
    `;
    
    // Auto-limpiar después de un tiempo según el tipo
    if (tipo === 'success') {
        // Mensajes de éxito: 4 segundos
        mensajeTimeout = setTimeout(() => {
            scanResult.innerHTML = '';
            mensajeTimeout = null;
        }, 4000);
    } else if (tipo === 'info') {
        // Mensajes informativos: 5 segundos
        mensajeTimeout = setTimeout(() => {
            scanResult.innerHTML = '';
            mensajeTimeout = null;
        }, 5000);
    } else if (tipo === 'error') {
        // Mensajes de error: 4 segundos
        mensajeTimeout = setTimeout(() => {
            scanResult.innerHTML = '';
            mensajeTimeout = null;
        }, 4000);
    }
}

// Reproducir sonido de éxito (opcional)
function reproducirSonidoExito() {
    // Crear un beep corto usando Web Audio API
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
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/base.php';
?>

