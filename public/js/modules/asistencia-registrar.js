/**
 * SENAttend - JavaScript para Registro de Asistencia
 * Extraído de views/asistencia/registrar.php
 */

// Variables globales
let aprendicesData = [];
let fichaSeleccionada = null;
let fechaSeleccionada = null;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    inicializarEventos();
    
    // Si hay datos precargados, mostrarlos
    // Los datos se pasan desde PHP antes de cargar este script
    if (window.aprendicesPrecargados) {
        fichaSeleccionada = window.fichaSeleccionada || null;
        fechaSeleccionada = window.fechaSeleccionada || null;
        aprendicesData = window.aprendicesPrecargados || [];
        renderizarTablaAprendices(aprendicesData);
        const contenedor = document.getElementById('contenedorAprendices');
        if (contenedor) {
            contenedor.style.display = 'block';
        }
    }
});

function inicializarEventos() {
    // Evento para cargar aprendices dinámicamente
    const btnCargar = document.getElementById('btnCargarAprendices');
    if (btnCargar) {
        btnCargar.addEventListener('click', cargarAprendicesDinamico);
    }
    
    // Eventos de controles masivos
    const btnMarcarTodosPresente = document.getElementById('btnMarcarTodosPresente');
    const btnMarcarTodosAusente = document.getElementById('btnMarcarTodosAusente');
    const btnLimpiarSeleccion = document.getElementById('btnLimpiarSeleccion');
    
    if (btnMarcarTodosPresente) {
        btnMarcarTodosPresente.addEventListener('click', () => marcarTodos('presente'));
    }
    if (btnMarcarTodosAusente) {
        btnMarcarTodosAusente.addEventListener('click', () => marcarTodos('ausente'));
    }
    if (btnLimpiarSeleccion) {
        btnLimpiarSeleccion.addEventListener('click', limpiarSeleccion);
    }
    
    // Evento de envío del formulario
    const formAsistencia = document.getElementById('formAsistencia');
    if (formAsistencia) {
        formAsistencia.addEventListener('submit', guardarAsistencia);
    }
    
    // Atajos de teclado
    document.addEventListener('keydown', manejarAtajosTeclado);
}

async function cargarAprendicesDinamico() {
    const fichaId = document.getElementById('ficha').value;
    const fecha = document.getElementById('fecha').value;
    
    if (!fichaId || !fecha) {
        mostrarAlerta('Por favor seleccione una ficha y fecha', 'warning');
        return;
    }

    const btnCargar = document.getElementById('btnCargarAprendices');
    const btnText = btnCargar.querySelector('.btn-text');
    const btnLoader = btnCargar.querySelector('.btn-loader');
    
    // Mostrar loader
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    btnCargar.disabled = true;

    try {
        const response = await fetch(`/api/asistencia/aprendices/${fichaId}?fecha=${fecha}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            aprendicesData = data.aprendices;
            fichaSeleccionada = fichaId;
            fechaSeleccionada = fecha;
            
            renderizarTablaAprendices(aprendicesData);
            document.getElementById('contenedorAprendices').style.display = 'block';
            
            // Actualizar campos hidden
            const fichaIdHidden = document.getElementById('fichaIdHidden');
            const fechaHidden = document.getElementById('fechaHidden');
            if (fichaIdHidden) fichaIdHidden.value = fichaId;
            if (fechaHidden) fechaHidden.value = fecha;
            
            mostrarAlerta(`Se cargaron ${aprendicesData.length} aprendices exitosamente`, 'success');
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error cargando aprendices:', error);
        mostrarAlerta('Error al cargar aprendices: ' + error.message, 'error');
    } finally {
        // Ocultar loader
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        btnCargar.disabled = false;
    }
}

function renderizarTablaAprendices(aprendices) {
    const tbody = document.getElementById('tablaAprendicesBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    aprendices.forEach((aprendiz, index) => {
        const row = document.createElement('tr');
        row.className = aprendiz.asistencia_id ? 'ya-registrado' : 'sin-registro';
        
        row.innerHTML = `
            <td class="col-numero">${index + 1}</td>
            <td class="col-foto">
                <div class="avatar-placeholder">
                    ${aprendiz.nombre.charAt(0)}${aprendiz.apellido.charAt(0)}
                </div>
            </td>
            <td class="col-documento">${aprendiz.documento}</td>
            <td class="col-nombre">
                <strong>${aprendiz.apellido}, ${aprendiz.nombre}</strong>
            </td>
            <td class="col-email">${aprendiz.email || 'N/A'}</td>
            <td class="col-estado">
                ${renderizarEstadoAsistencia(aprendiz)}
            </td>
            <td class="col-hora">
                ${aprendiz.asistencia_hora ? formatearHora(aprendiz.asistencia_hora) : '--'}
            </td>
            <td class="col-observaciones">
                ${renderizarObservaciones(aprendiz)}
            </td>
        `;
        
        tbody.appendChild(row);
    });

    // Agregar eventos a los controles
    agregarEventosTabla();
    actualizarContadores();
}

function renderizarEstadoAsistencia(aprendiz) {
    if (aprendiz.asistencia_id) {
        // Ya tiene registro
        const badgeClass = `badge-estado badge-${aprendiz.asistencia_estado}`;
        return `<span class="${badgeClass}">${capitalizar(aprendiz.asistencia_estado)}</span>`;
    } else {
        // Registrar nuevo
        return `
            <div class="estado-radio-moderno">
                <label class="radio-label radio-presente">
                    <input type="radio" name="asistencias[${aprendiz.id_aprendiz}]" value="presente" required>
                    <span class="radio-custom"></span>
                    <span class="radio-text">Presente</span>
                </label>
                <label class="radio-label radio-ausente">
                    <input type="radio" name="asistencias[${aprendiz.id_aprendiz}]" value="ausente">
                    <span class="radio-custom"></span>
                    <span class="radio-text">Ausente</span>
                </label>
                <label class="radio-label radio-tardanza">
                    <input type="radio" name="asistencias[${aprendiz.id_aprendiz}]" value="tardanza">
                    <span class="radio-custom"></span>
                    <span class="radio-text">Tardanza</span>
                </label>
            </div>
        `;
    }
}

function renderizarObservaciones(aprendiz) {
    if (aprendiz.asistencia_id) {
        return aprendiz.observaciones || '--';
    } else {
        return `<textarea name="observaciones[${aprendiz.id_aprendiz}]" class="observaciones-input" placeholder="Observaciones opcionales..." maxlength="255"></textarea>`;
    }
}

function agregarEventosTabla() {
    // Eventos para radio buttons
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', actualizarContadores);
    });
}

function actualizarContadores() {
    const presentes = document.querySelectorAll('input[value="presente"]:checked').length;
    const ausentes = document.querySelectorAll('input[value="ausente"]:checked').length;
    const tardanzas = document.querySelectorAll('input[value="tardanza"]:checked').length;
    const total = presentes + ausentes + tardanzas;

    const conteoPresentes = document.getElementById('conteoPresentes');
    const conteoAusentes = document.getElementById('conteoAusentes');
    const conTeoTardanzas = document.getElementById('conteoTardanzas');
    const conteoSeleccionados = document.getElementById('conteoSeleccionados');
    const btnGuardar = document.getElementById('btnGuardar');

    if (conteoPresentes) conteoPresentes.textContent = presentes;
    if (conteoAusentes) conteoAusentes.textContent = ausentes;
    if (conTeoTardanzas) conTeoTardanzas.textContent = tardanzas;
    if (conteoSeleccionados) conteoSeleccionados.textContent = total;
    if (btnGuardar) btnGuardar.disabled = total === 0;
}

function marcarTodos(estado) {
    document.querySelectorAll(`input[value="${estado}"]`).forEach(radio => {
        if (!radio.closest('tr').classList.contains('ya-registrado')) {
            radio.checked = true;
        }
    });
    actualizarContadores();
}

function limpiarSeleccion() {
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        if (!radio.closest('tr').classList.contains('ya-registrado')) {
            radio.checked = false;
        }
    });
    actualizarContadores();
}

async function guardarAsistencia(event) {
    event.preventDefault();
    
    const btnGuardar = document.getElementById('btnGuardar');
    const btnText = btnGuardar.querySelector('.btn-text');
    const btnLoader = btnGuardar.querySelector('.btn-loader');
    
    // Mostrar loader
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    btnGuardar.disabled = true;

    try {
        // Recopilar datos del formulario
        const formData = new FormData(event.target);
        
        // Validar que hay selecciones
        const radiosChecked = document.querySelectorAll('input[type="radio"]:checked');
        if (radiosChecked.length === 0) {
            throw new Error('Debe marcar al menos un aprendiz');
        }

        // Enviar formulario tradicional (por compatibilidad)
        event.target.submit();
        
    } catch (error) {
        mostrarAlerta('Error: ' + error.message, 'error');
        
        // Restaurar botón
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        btnGuardar.disabled = false;
    }
}

function manejarAtajosTeclado(event) {
    // Ctrl + P: Marcar todos presente
    if (event.ctrlKey && event.key === 'p') {
        event.preventDefault();
        marcarTodos('presente');
    }
    
    // Ctrl + A: Marcar todos ausente
    if (event.ctrlKey && event.key === 'a') {
        event.preventDefault();
        marcarTodos('ausente');
    }
    
    // Ctrl + T: Marcar todos tardanza
    if (event.ctrlKey && event.key === 't') {
        event.preventDefault();
        marcarTodos('tardanza');
    }
    
    // Ctrl + L: Limpiar selección
    if (event.ctrlKey && event.key === 'l') {
        event.preventDefault();
        limpiarSeleccion();
    }
}

// Funciones utilitarias
function mostrarAlerta(mensaje, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-temporal`;
    alertDiv.textContent = mensaje;
    
    const container = document.querySelector('.asistencia-form');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

function capitalizar(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatearHora(hora) {
    const [h, m] = hora.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${m} ${ampm}`;
}

