/**
 * JavaScript para el módulo de Gestión de Asignaciones Instructor-Ficha
 * SENAttend - Sistema de Asistencia SENA
 */

// ========================================
// VARIABLES GLOBALES
// ========================================
let instructorActual = null;
let fichaActual = null;
let fichasOriginales = [];
let instructoresOriginales = [];

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    inicializarTabs();
    inicializarBuscadores();
    cargarContadoresInstructoresPorFicha();
});

// ========================================
// GESTIÓN DE TABS
// ========================================
function inicializarTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remover clases activas
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Agregar clase activa al tab seleccionado
            this.classList.add('active');
            document.getElementById(`tab-${targetTab}`).classList.add('active');
        });
    });

    // Interceptar clicks en paginación para preservar la pestaña activa
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const activeBtn = document.querySelector('.tab-button.active');
            const activeTab = activeBtn ? activeBtn.getAttribute('data-tab') : null;
            if (activeTab) {
                e.preventDefault();
                const url = new URL(this.href, window.location.origin);
                url.searchParams.set('tab', activeTab);
                window.location.href = url.toString();
            }
        });
    });
}

// ========================================
// BUSCADORES
// ========================================
function inicializarBuscadores() {
    // Buscador de instructores
    const buscarInstructor = document.getElementById('buscarInstructor');
    if (buscarInstructor) {
        buscarInstructor.addEventListener('input', function() {
            filtrarTabla('tablaInstructores', this.value);
        });
    }
    
    // Buscador de fichas
    const buscarFicha = document.getElementById('buscarFicha');
    if (buscarFicha) {
        buscarFicha.addEventListener('input', function() {
            filtrarFichas(this.value);
        });
    }

    const quickSearch = document.getElementById('quickFichaSearch');
    if (quickSearch) {
        quickSearch.addEventListener('input', function() {
            filtrarQuickFichas(this.value);
        });
    }
}

function filtrarTabla(tablaId, termino) {
    const tabla = document.getElementById(tablaId);
    const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    const terminoLower = termino.toLowerCase();
    
    Array.from(filas).forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(terminoLower) ? '' : 'none';
    });
}

function filtrarFichas(termino) {
    const fichas = document.querySelectorAll('.ficha-card');
    const terminoLower = termino.toLowerCase();
    
    fichas.forEach(ficha => {
        const texto = ficha.textContent.toLowerCase();
        ficha.style.display = texto.includes(terminoLower) ? '' : 'none';
    });
}

// ========================================
// MODAL DE ASIGNACIÓN PARA INSTRUCTOR
// ========================================
function abrirModalAsignacion(instructorId, instructorNombre) {
    instructorActual = instructorId;
    document.getElementById('modalInstructorId').value = instructorId;
    document.getElementById('modalTitulo').textContent = `Gestionar Fichas - ${instructorNombre}`;
    
    // Cargar fichas del instructor
    cargarFichasInstructor(instructorId);
    
    // Mostrar modal
    const modal = document.getElementById('modalAsignacion');
    modal.classList.remove('closing');
    modal.classList.add('show');
}

function cerrarModal() {
    const modal = document.getElementById('modalAsignacion');
    // Añadir estado de cierre para reproducir animación y mantener overlay visible
    modal.classList.add('closing');
    modal.classList.remove('show');
    // Esperar a que se complete la animación (slideOutScale: 280ms)
    setTimeout(() => {
        modal.classList.remove('closing');
        // Limpiar datos después de ocultar completamente
        instructorActual = null;
        document.getElementById('modalFichasDisponibles').innerHTML = '';
        document.getElementById('modalFichasAsignadas').innerHTML = '';
    }, 320);
}

async function cargarFichasInstructor(instructorId) {
    try {
        // Mostrar loading
        document.getElementById('modalFichasDisponibles').innerHTML = '<option>Cargando...</option>';
        document.getElementById('modalFichasAsignadas').innerHTML = '<option>Cargando...</option>';
        
        // Cargar fichas disponibles
        const responseDisponibles = await fetch(`/api/instructor-fichas/fichas-disponibles/${instructorId}`);
        const dataDisponibles = await responseDisponibles.json();
        if (!dataDisponibles.success) {
            throw new Error(dataDisponibles.error || 'No fue posible obtener las fichas disponibles');
        }
        
        // Cargar fichas asignadas (del instructor actual)
        const responseAsignadas = await fetch(`/api/instructor-fichas/instructor/${instructorId}/fichas`);
        const dataAsignadas = await responseAsignadas.json();
        if (!dataAsignadas.success) {
            throw new Error(dataAsignadas.error || 'No fue posible obtener las fichas del instructor');
        }
        
        // Llenar selects
        llenarSelectFichas('modalFichasDisponibles', dataDisponibles.fichas || []);
        llenarSelectFichas('modalFichasAsignadas', dataAsignadas.fichas || []);
        
        // Guardar estado original
        fichasOriginales = (dataAsignadas.fichas || []).map(f => f.id);
        
    } catch (error) {
        console.error('Error cargando fichas:', error);
        mostrarNotificacion('Error al cargar las fichas', 'error');
    }
}

function llenarSelectFichas(selectId, fichas) {
    const select = document.getElementById(selectId);
    select.innerHTML = '';
    
    if (fichas.length === 0) {
        select.innerHTML = '<option disabled>No hay fichas disponibles</option>';
        return;
    }
    
    fichas.forEach(ficha => {
        const option = document.createElement('option');
        option.value = ficha.id;
        option.textContent = `${ficha.numero_ficha} - ${ficha.nombre}`;
        select.appendChild(option);
    });
}

function agregarFichas() {
    const disponibles = document.getElementById('modalFichasDisponibles');
    const asignadas = document.getElementById('modalFichasAsignadas');
    
    // Obtener opciones seleccionadas
    const seleccionadas = Array.from(disponibles.selectedOptions);
    
    // Mover opciones
    seleccionadas.forEach(option => {
        asignadas.appendChild(option);
    });
}

function quitarFichas() {
    const disponibles = document.getElementById('modalFichasDisponibles');
    const asignadas = document.getElementById('modalFichasAsignadas');
    
    // Obtener opciones seleccionadas
    const seleccionadas = Array.from(asignadas.selectedOptions);
    
    // Mover opciones
    seleccionadas.forEach(option => {
        disponibles.appendChild(option);
    });
}

async function guardarAsignaciones() {
    if (!instructorActual) {
        mostrarNotificacion('Error: No se ha seleccionado un instructor', 'error');
        return;
    }
    
    const asignadas = document.getElementById('modalFichasAsignadas');
    const fichasIds = Array.from(asignadas.options).map(option => parseInt(option.value));
    
    try {
        // Mostrar loading
        mostrarLoading(true);
        
        const response = await fetch('/api/instructor-fichas/sincronizar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                instructor_id: instructorActual,
                ficha_ids: fichasIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('Asignaciones guardadas correctamente', 'success');
            cerrarModal();
            // Recargar la página para actualizar los datos
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarNotificacion(data.error || 'Error al guardar las asignaciones', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la solicitud', 'error');
    } finally {
        mostrarLoading(false);
    }
}

// ========================================
// MODAL DE ASIGNACIÓN PARA FICHA
// ========================================
function abrirModalAsignacionFicha(fichaId, fichaNumero) {
    fichaActual = fichaId;
    document.getElementById('modalFichaId').value = fichaId;
    document.getElementById('modalTituloFicha').textContent = `Asignar Instructores - Ficha ${fichaNumero}`;
    
    // Cargar instructores
    cargarInstructoresFicha(fichaId);
    
    // Mostrar modal
    const modal = document.getElementById('modalAsignacionFicha');
    modal.classList.remove('closing');
    modal.classList.add('show');
}

function cerrarModalFicha() {
    const modal = document.getElementById('modalAsignacionFicha');
    modal.classList.add('closing');
    modal.classList.remove('show');
    // Esperar a que se complete la animación (slideOutScale: 280ms)
    setTimeout(() => {
        modal.classList.remove('closing');
        // Limpiar datos después de ocultar completamente
        fichaActual = null;
        document.querySelector('.instructores-list').innerHTML = '';
    }, 320);
}

async function cargarInstructoresFicha(fichaId) {
    try {
        const container = document.querySelector('.instructores-list');
        container.innerHTML = '<p>Cargando instructores...</p>';
        
        // Cargar todos los instructores
        const responseInstructores = await fetch('/api/instructores');
        const instructores = await responseInstructores.json();
        if (!instructores.success) {
            throw new Error(instructores.error || 'No fue posible listar los instructores');
        }
        
        // Cargar instructores asignados a esta ficha
        const responseAsignados = await fetch(`/api/instructor-fichas/ficha/${fichaId}/instructores`);
        const asignados = await responseAsignados.json();
        if (!asignados.success) {
            throw new Error(asignados.error || 'No fue posible obtener los instructores asignados');
        }
        
        const asignadosIds = (asignados.instructores || []).map(i => i.id);
        instructoresOriginales = [...asignadosIds];
        
        // Crear lista de checkboxes
        let html = '';
        (instructores.data || []).forEach(instructor => {
            const isChecked = asignadosIds.includes(instructor.id) ? 'checked' : '';
            html += `
                <div class="instructor-item">
                    <input type="checkbox" 
                           id="inst-${instructor.id}" 
                           name="instructores[]" 
                           value="${instructor.id}" 
                           ${isChecked}>
                    <div class="instructor-info">
                        <strong>${instructor.nombre}</strong>
                        <small>${instructor.email}</small>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<p>No hay instructores disponibles</p>';
        
    } catch (error) {
        console.error('Error cargando instructores:', error);
        mostrarNotificacion('Error al cargar los instructores', 'error');
    }
}

async function guardarInstructoresFicha() {
    if (!fichaActual) {
        mostrarNotificacion('Error: No se ha seleccionado una ficha', 'error');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.instructores-list input[type="checkbox"]:checked');
    const instructorIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    try {
        mostrarLoading(true);
        
        const response = await fetch('/api/instructor-fichas/asignar-instructores', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ficha_id: fichaActual,
                instructor_ids: instructorIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('Instructores asignados correctamente', 'success');
            cerrarModalFicha();
            cargarContadoresInstructoresPorFicha();
        } else {
            mostrarNotificacion(data.error || 'Error al asignar instructores', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la solicitud', 'error');
    } finally {
        mostrarLoading(false);
    }
}

// ========================================
// ASIGNACIÓN RÁPIDA
// ========================================
function limpiarAsignacionRapida() {
    document.getElementById('quickInstructor').value = '';
    const checkboxes = document.querySelectorAll('input[name="quickFichas[]"]');
    checkboxes.forEach(cb => cb.checked = false);
}

async function realizarAsignacionRapida() {
    const instructorId = document.getElementById('quickInstructor').value;
    const checkboxes = document.querySelectorAll('input[name="quickFichas[]"]:checked');
    const fichaIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (!instructorId) {
        mostrarNotificacion('Por favor seleccione un instructor', 'warning');
        return;
    }
    
    if (fichaIds.length === 0) {
        mostrarNotificacion('Por favor seleccione al menos una ficha', 'warning');
        return;
    }
    
    try {
        mostrarLoading(true);
        
        const response = await fetch('/api/instructor-fichas/asignar-fichas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                instructor_id: parseInt(instructorId),
                ficha_ids: fichaIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const resultado = data.resultado;
            let mensaje = `Asignación completada: ${resultado.exitosos} fichas asignadas`;
            if (resultado.duplicados > 0) {
                mensaje += `, ${resultado.duplicados} ya estaban asignadas`;
            }
            if (resultado.errores > 0) {
                mensaje += `, ${resultado.errores} errores`;
            }
            
            mostrarNotificacion(mensaje, 'success');
            limpiarAsignacionRapida();
            
            // Recargar la página después de un momento
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            mostrarNotificacion(data.error || 'Error al realizar la asignación', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error al procesar la solicitud', 'error');
    } finally {
        mostrarLoading(false);
    }
}

// ========================================
// UTILIDADES
// ========================================
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo === 'error' ? 'error' : tipo === 'warning' ? 'warning' : 'success'}`;
    notificacion.style.position = 'fixed';
    notificacion.style.top = '20px';
    notificacion.style.right = '20px';
    notificacion.style.zIndex = '9999';
    notificacion.style.minWidth = '300px';
    notificacion.style.animation = 'slideIn 0.3s ease';
    
    // Icono según el tipo
    let icono = '';
    switch(tipo) {
        case 'success':
            icono = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            icono = '<i class="fas fa-exclamation-circle"></i>';
            break;
        case 'warning':
            icono = '<i class="fas fa-exclamation-triangle"></i>';
            break;
        default:
            icono = '<i class="fas fa-info-circle"></i>';
    }
    
    notificacion.innerHTML = `${icono} ${mensaje}`;
    
    // Agregar al body
    document.body.appendChild(notificacion);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notificacion);
        }, 300);
    }, 5000);
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        // Crear overlay de loading
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        overlay.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center;">
                <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #39A900; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                <p style="margin-top: 1rem; color: #333;">Procesando...</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
    } else {
        // Remover overlay
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            document.body.removeChild(overlay);
        }
    }
}

// Cargar contadores de instructores por ficha
async function cargarContadoresInstructoresPorFicha() {
    const fichaCards = document.querySelectorAll('.ficha-card');
    
    for (const card of fichaCards) {
        const fichaId = card.querySelector('button').getAttribute('onclick').match(/\d+/)[0];
        const contador = card.querySelector(`#instructores-ficha-${fichaId}`);
        
        if (contador) {
            try {
                const response = await fetch(`/api/instructor-fichas/ficha/${fichaId}/instructores`);
                const data = await response.json();
                const count = data.instructores ? data.instructores.length : 0;
                contador.textContent = `${count} instructor${count !== 1 ? 'es' : ''} asignado${count !== 1 ? 's' : ''}`;
            } catch (error) {
                contador.textContent = 'Error al cargar';
            }
        }
    }
}

function filtrarQuickFichas(termino) {
    const items = document.querySelectorAll('#quickFichaList .checkbox-item');
    const search = termino.trim().toLowerCase();

    items.forEach(item => {
        const input = item.querySelector('input[type="checkbox"]');
        const numero = input?.dataset.numero?.toLowerCase() || '';
        item.style.display = numero.includes(search) ? '' : 'none';
    });
}

// CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalFicha();
    }
});

// Cerrar modales al hacer clic fuera
document.getElementById('modalAsignacion').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

document.getElementById('modalAsignacionFicha').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalFicha();
    }
});
