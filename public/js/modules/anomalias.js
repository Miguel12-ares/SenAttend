/**
 * Módulo de Registro de Anomalías
 * Maneja la interfaz y comunicación con la API para registrar anomalías
 * Refactorizado para usar sistema estándar de modales
 */

(function() {
    'use strict';

    // Variables globales
    let fichaId = null;
    let fecha = null;
    let aprendices = [];
    let anomaliasFicha = [];
    let tiposAnomalias = window.TIPOS_ANOMALIAS || {};

    // Elementos del DOM
    const elementos = {
        fichaSelect: document.getElementById('fichaSelect'),
        fechaSelect: document.getElementById('fechaSelect'),
        btnCargarAprendices: document.getElementById('btnCargarAprendices'),
        infoFecha: document.getElementById('infoFecha'),
        infoFechaTexto: document.getElementById('infoFechaTexto'),
        aprendicesCard: document.getElementById('aprendicesCard'),
        anomaliaFichaCard: document.getElementById('anomaliaFichaCard'),
        aprendicesTableBody: document.getElementById('aprendicesTableBody'),
        anomaliasFichaList: document.getElementById('anomaliasFichaList'),
        mensajeVacio: document.getElementById('mensajeVacio'),
        modalAnomalia: document.getElementById('modalAnomalia'),
        formAnomalia: document.getElementById('formAnomalia'),
        btnCerrarModal: document.getElementById('btnCerrarModal'),
        btnCancelarAnomalia: document.getElementById('btnCancelarAnomalia'),
        btnGuardarAnomalia: document.getElementById('btnGuardarAnomalia'),
        btnAnomaliaFicha: document.getElementById('btnAnomaliaFicha'),
        alertContainer: document.getElementById('alertContainer')
    };

    // Gestor de Modal
    const ModalManager = {
        open: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        },

        close: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    };

    // Inicialización
    function init() {
        if (!elementos.fichaSelect) {
            return;
        }

        // Establecer fecha por defecto
        if (!elementos.fechaSelect.value) {
            elementos.fechaSelect.value = new Date().toISOString().split('T')[0];
            fecha = elementos.fechaSelect.value;
        } else {
            fecha = elementos.fechaSelect.value;
        }

        // Event listeners básicos
        if (elementos.fichaSelect) {
            elementos.fichaSelect.addEventListener('change', onFichaChange);
        }
        if (elementos.fechaSelect) {
            elementos.fechaSelect.addEventListener('change', onFechaChange);
        }
        if (elementos.btnCargarAprendices) {
            elementos.btnCargarAprendices.addEventListener('click', cargarAprendices);
        }
        
        // Delegación de eventos para botones dinámicos
        // Botón de anomalía de ficha
        document.addEventListener('click', (e) => {
            if (e.target.closest('#btnAnomaliaFicha')) {
                e.preventDefault();
                abrirModalAnomaliaFicha();
            }
        });
        
        // Botones de anomalía de aprendiz (se crean dinámicamente)
        if (elementos.aprendicesTableBody) {
            elementos.aprendicesTableBody.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-anomalia-aprendiz');
                if (btn && !btn.disabled) {
                    e.preventDefault();
                    e.stopPropagation();
                    const aprendizId = parseInt(btn.getAttribute('data-aprendiz-id'));
                    const estadoAprendiz = btn.getAttribute('data-aprendiz-estado');
                    
                    // Validar que no sea presente (null se trata como ausente, por lo que sí se puede)
                    if (estadoAprendiz === 'presente') {
                        mostrarAlerta('error', 'No se pueden registrar anomalías para aprendices que están presentes');
                        return;
                    }
                    
                    const aprendiz = aprendices.find(a => a.id_aprendiz === aprendizId);
                    if (aprendiz) {
                        abrirModalAnomaliaAprendiz(aprendiz);
                    } else {
                        mostrarAlerta('error', 'No se encontró la información del aprendiz');
                    }
                }
            });
        }
        
        // Event listeners del modal
        if (elementos.btnCerrarModal) {
            elementos.btnCerrarModal.addEventListener('click', () => cerrarModal());
        }
        if (elementos.btnCancelarAnomalia) {
            elementos.btnCancelarAnomalia.addEventListener('click', () => cerrarModal());
        }
        if (elementos.btnGuardarAnomalia) {
            elementos.btnGuardarAnomalia.addEventListener('click', (e) => {
                e.preventDefault();
                guardarAnomalia();
            });
        }

        // Cerrar modal al hacer clic fuera
        if (elementos.modalAnomalia) {
            elementos.modalAnomalia.addEventListener('click', (e) => {
                if (e.target === elementos.modalAnomalia) {
                    cerrarModal();
                }
            });
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && elementos.modalAnomalia && elementos.modalAnomalia.classList.contains('active')) {
                cerrarModal();
            }
        });

        // Validar fecha inicial
        validarFecha();
        
        // Si hay ficha seleccionada, habilitar botón
        if (elementos.fichaSelect.value) {
            onFichaChange();
        }
    }

    // Validar fecha seleccionada
    function validarFecha() {
        const fechaSeleccionada = elementos.fechaSelect.value;
        if (!fechaSeleccionada) {
            if (elementos.infoFecha) {
                elementos.infoFecha.style.display = 'none';
            }
            return;
        }

        const fechaObj = new Date(fechaSeleccionada);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        fechaObj.setHours(0, 0, 0, 0);

        const diffTime = hoy - fechaObj;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (!elementos.infoFecha || !elementos.infoFechaTexto) return;

        if (diffDays < 0) {
            elementos.infoFechaTexto.textContent = 'No se pueden registrar anomalías para fechas futuras';
            elementos.infoFecha.className = 'info-banner alert-danger';
            elementos.infoFecha.style.display = 'block';
            if (elementos.btnCargarAprendices) {
                elementos.btnCargarAprendices.disabled = true;
            }
        } else if (diffDays > 3) {
            elementos.infoFechaTexto.textContent = `Han pasado ${diffDays} días. Solo se pueden registrar anomalías hasta 3 días después del registro de asistencia.`;
            elementos.infoFecha.className = 'info-banner alert-warning';
            elementos.infoFecha.style.display = 'block';
            if (elementos.btnCargarAprendices) {
                elementos.btnCargarAprendices.disabled = true;
            }
        } else {
            const diasRestantes = 3 - diffDays;
            elementos.infoFechaTexto.textContent = `Puedes registrar anomalías. Días restantes: ${diasRestantes} de 3 días permitidos.`;
            elementos.infoFecha.className = 'info-banner alert-success';
            elementos.infoFecha.style.display = 'block';
            if (elementos.btnCargarAprendices) {
                elementos.btnCargarAprendices.disabled = !elementos.fichaSelect.value;
            }
        }
    }

    function onFichaChange() {
        fichaId = elementos.fichaSelect.value;
        fecha = elementos.fechaSelect.value || new Date().toISOString().split('T')[0];
        if (!elementos.fechaSelect.value) {
            elementos.fechaSelect.value = fecha;
        }
        validarFecha();
        if (elementos.btnCargarAprendices) {
            elementos.btnCargarAprendices.disabled = !fichaId || !fecha;
        }
    }

    function onFechaChange() {
        fecha = elementos.fechaSelect.value || new Date().toISOString().split('T')[0];
        if (!elementos.fechaSelect.value) {
            elementos.fechaSelect.value = fecha;
        }
        validarFecha();
        if (elementos.btnCargarAprendices) {
            elementos.btnCargarAprendices.disabled = !fichaId || !fecha;
        }
    }

    // Cargar aprendices de la ficha
    async function cargarAprendices() {
        if (!fichaId || !fecha) {
            mostrarAlerta('error', 'Selecciona una ficha y una fecha');
            return;
        }

        try {
            if (elementos.btnCargarAprendices) {
                elementos.btnCargarAprendices.disabled = true;
                elementos.btnCargarAprendices.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            }

            const response = await fetch(`/api/asistencia/aprendices/${fichaId}?fecha=${fecha}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                let errorMessage = `Error HTTP ${response.status}: ${response.statusText}`;
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorData.error || errorMessage;
                } catch (e) {
                    // Si no se puede parsear JSON, usar el mensaje por defecto
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();
            
            if (data.success) {
                aprendices = data.data.aprendices || [];
                await renderizarAprendices();
                await cargarAnomaliasFicha();
                if (elementos.mensajeVacio) {
                    elementos.mensajeVacio.style.display = 'none';
                }
                if (elementos.aprendicesCard) {
                    elementos.aprendicesCard.style.display = 'block';
                }
                if (elementos.anomaliaFichaCard) {
                    elementos.anomaliaFichaCard.style.display = 'block';
                }
            } else {
                throw new Error(data.message || 'Error al cargar aprendices');
            }

        } catch (error) {
            mostrarAlerta('error', 'Error al cargar aprendices: ' + error.message);
        } finally {
            if (elementos.btnCargarAprendices) {
                elementos.btnCargarAprendices.disabled = false;
                elementos.btnCargarAprendices.innerHTML = '<i class="fas fa-search"></i> Cargar Aprendices';
            }
        }
    }

    // Renderizar tabla de aprendices
    async function renderizarAprendices() {
        if (!elementos.aprendicesTableBody) return;

        elementos.aprendicesTableBody.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

        if (aprendices.length === 0) {
            elementos.aprendicesTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">No hay aprendices con asistencia registrada en esta fecha</td>
                </tr>
            `;
            return;
        }

        // Cargar anomalías para todos los aprendices
        const promesas = aprendices.map(async (aprendiz) => {
            const anomaliasAprendiz = await obtenerAnomaliasAprendiz(aprendiz.id_aprendiz);
            return { aprendiz, anomalias: anomaliasAprendiz };
        });

        const resultados = await Promise.all(promesas);
        
        elementos.aprendicesTableBody.innerHTML = '';
        resultados.forEach(({ aprendiz, anomalias }) => {
            const row = crearFilaAprendiz(aprendiz, anomalias);
            elementos.aprendicesTableBody.appendChild(row);
        });
    }

    // Crear fila de aprendiz
    function crearFilaAprendiz(aprendiz, anomalias) {
        const tr = document.createElement('tr');
        
        const estadoBadge = getEstadoBadge(aprendiz.asistencia_estado);
        const anomaliasHTML = anomalias.map(a => getAnomaliaBadge(a)).join(' ');
        
        // Determinar si se puede registrar anomalía (no permitir para presentes)
        // Si no hay estado (null), se trata como ausente, por lo que SÍ se puede registrar anomalía
        const puedeRegistrarAnomalia = aprendiz.asistencia_estado !== 'presente';
        const botonDisabled = !puedeRegistrarAnomalia ? 'disabled' : '';
        const botonClass = puedeRegistrarAnomalia ? 'btn btn-sm btn-warning btn-anomalia-aprendiz' : 'btn btn-sm btn-secondary btn-anomalia-aprendiz';
        const botonTitle = puedeRegistrarAnomalia ? 'Registrar anomalía' : 'No se pueden registrar anomalías para aprendices presentes';

        tr.innerHTML = `
            <td>${aprendiz.documento}</td>
            <td>${aprendiz.nombre_completo}</td>
            <td>${estadoBadge}</td>
            <td>
                <div class="anomalias-badges">
                    ${anomaliasHTML || '<span class="text-muted">Sin anomalías</span>'}
                </div>
            </td>
            <td>
                <button class="${botonClass}" 
                        data-aprendiz-id="${aprendiz.id_aprendiz}"
                        data-aprendiz-nombre="${aprendiz.nombre_completo}"
                        data-aprendiz-estado="${aprendiz.asistencia_estado || 'sin_registro'}"
                        ${botonDisabled}
                        title="${botonTitle}">
                    <i class="fas fa-plus"></i> Anomalía
                </button>
            </td>
        `;

        return tr;
    }

    // Obtener anomalías de un aprendiz
    async function obtenerAnomaliasAprendiz(aprendizId) {
        if (!fichaId || !fecha || !aprendizId) return [];

        try {
            const response = await fetch(`/api/asistencia/anomalias?ficha_id=${fichaId}&fecha=${fecha}&aprendiz_id=${aprendizId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const result = await response.json();
                return result.success ? result.data : [];
            }
        } catch (error) {
            // Error silencioso, retornar array vacío
        }
        
        return [];
    }

    // Obtener badge de estado
    // Si no hay registro, se trata como ausente
    function getEstadoBadge(estado) {
        // Si no hay estado o es null, tratar como ausente
        if (!estado || estado === null) {
            return '<span class="badge badge-danger">Ausente</span>';
        }
        const estados = {
            'presente': '<span class="badge badge-success">Presente</span>',
            'ausente': '<span class="badge badge-danger">Ausente</span>',
            'tardanza': '<span class="badge badge-warning">Tardanza</span>'
        };
        return estados[estado] || '<span class="badge badge-danger">Ausente</span>';
    }

    // Obtener badge de anomalía
    function getAnomaliaBadge(anomalia) {
        const tipo = tiposAnomalias[anomalia.tipo_anomalia] || {};
        const color = tipo.color || '#666';
        const nombre = tipo.nombre || anomalia.tipo_anomalia;
        
        return `<span class="badge anomalia-badge" style="background-color: ${color}; color: white;">
            ${nombre}
        </span>`;
    }

    // Abrir modal para anomalía de aprendiz
    function abrirModalAnomaliaAprendiz(aprendiz) {
        if (!fichaId || !fecha) {
            mostrarAlerta('error', 'Selecciona una ficha y una fecha primero');
            return;
        }

        // Validar que no sea presente (null se trata como ausente, por lo que sí se puede registrar)
        if (aprendiz.asistencia_estado === 'presente') {
            mostrarAlerta('error', 'No se pueden registrar anomalías para aprendices que están presentes');
            return;
        }

        const idAprendizInput = document.getElementById('anomaliaIdAprendiz');
        const idFichaInput = document.getElementById('anomaliaIdFicha');
        const fechaInput = document.getElementById('anomaliaFecha');
        const tipoInput = document.getElementById('anomaliaTipo');
        const infoAprendizNombre = document.getElementById('infoAprendizNombre');
        const infoAprendiz = document.getElementById('infoAprendiz');
        const modalTitle = document.getElementById('modalTitle');

        if (!idAprendizInput || !idFichaInput || !fechaInput || !tipoInput || !modalTitle) {
            mostrarAlerta('error', 'Error: No se encontraron los elementos del formulario');
            return;
        }

        idAprendizInput.value = aprendiz.id_aprendiz;
        idFichaInput.value = fichaId;
        fechaInput.value = fecha;
        tipoInput.value = 'aprendiz';
        
        if (infoAprendizNombre) {
            infoAprendizNombre.textContent = aprendiz.nombre_completo;
        }
        if (infoAprendiz) {
            infoAprendiz.style.display = 'block';
        }
        
        modalTitle.innerHTML = '<i class="fas fa-user"></i> Registrar Anomalía - Aprendiz';
        
        // Limpiar formulario
        if (elementos.formAnomalia) {
            elementos.formAnomalia.reset();
        }
        const descripcionInput = document.getElementById('descripcionAnomalia');
        if (descripcionInput) {
            descripcionInput.value = '';
        }
        
        // Restablecer valores después de reset
        idAprendizInput.value = aprendiz.id_aprendiz;
        idFichaInput.value = fichaId;
        fechaInput.value = fecha;
        tipoInput.value = 'aprendiz';
        
        // Desmarcar radios
        const radios = document.querySelectorAll('input[name="tipo_anomalia"]');
        radios.forEach(radio => radio.checked = false);
        
        validarFechaModal();
        ModalManager.open('modalAnomalia');
    }

    // Abrir modal para anomalía de ficha
    function abrirModalAnomaliaFicha() {
        if (!fichaId || !fecha) {
            mostrarAlerta('error', 'Selecciona una ficha y una fecha primero');
            return;
        }

        const idAprendizInput = document.getElementById('anomaliaIdAprendiz');
        const idFichaInput = document.getElementById('anomaliaIdFicha');
        const fechaInput = document.getElementById('anomaliaFecha');
        const tipoInput = document.getElementById('anomaliaTipo');
        const infoAprendiz = document.getElementById('infoAprendiz');
        const modalTitle = document.getElementById('modalTitle');

        if (!idFichaInput || !fechaInput || !tipoInput || !modalTitle) {
            mostrarAlerta('error', 'Error: No se encontraron los elementos del formulario');
            return;
        }

        if (idAprendizInput) {
            idAprendizInput.value = '';
        }
        idFichaInput.value = fichaId;
        fechaInput.value = fecha;
        tipoInput.value = 'ficha';
        
        if (infoAprendiz) {
            infoAprendiz.style.display = 'none';
        }
        
        modalTitle.innerHTML = '<i class="fas fa-clipboard-list"></i> Registrar Anomalía - Ficha General';
        
        // Limpiar formulario
        if (elementos.formAnomalia) {
            elementos.formAnomalia.reset();
        }
        const descripcionInput = document.getElementById('descripcionAnomalia');
        if (descripcionInput) {
            descripcionInput.value = '';
        }
        
        // Restablecer valores después de reset
        if (idAprendizInput) {
            idAprendizInput.value = '';
        }
        idFichaInput.value = fichaId;
        fechaInput.value = fecha;
        tipoInput.value = 'ficha';
        
        // Desmarcar radios
        const radios = document.querySelectorAll('input[name="tipo_anomalia"]');
        radios.forEach(radio => radio.checked = false);
        
        validarFechaModal();
        ModalManager.open('modalAnomalia');
    }

    // Validar fecha en el modal
    function validarFechaModal() {
        const fechaSeleccionada = fecha;
        const fechaObj = new Date(fechaSeleccionada);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        fechaObj.setHours(0, 0, 0, 0);

        const diffTime = hoy - fechaObj;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        const validacionDiv = document.getElementById('validacionFecha');
        const mensajeDiv = document.getElementById('mensajeValidacionFecha');

        if (!validacionDiv || !mensajeDiv) return;

        if (diffDays < 0) {
            mensajeDiv.textContent = 'No se pueden registrar anomalías para fechas futuras';
            validacionDiv.className = 'alert alert-danger';
            validacionDiv.style.display = 'block';
            if (elementos.btnGuardarAnomalia) {
                elementos.btnGuardarAnomalia.disabled = true;
            }
        } else if (diffDays > 3) {
            mensajeDiv.textContent = `Han pasado ${diffDays} días. Solo se pueden registrar anomalías hasta 3 días después del registro de asistencia.`;
            validacionDiv.className = 'alert alert-warning';
            validacionDiv.style.display = 'block';
            if (elementos.btnGuardarAnomalia) {
                elementos.btnGuardarAnomalia.disabled = true;
            }
        } else {
            const diasRestantes = 3 - diffDays;
            mensajeDiv.textContent = `Puedes registrar anomalías. Días restantes: ${diasRestantes} de 3 días permitidos.`;
            validacionDiv.className = 'alert alert-success';
            validacionDiv.style.display = 'block';
            if (elementos.btnGuardarAnomalia) {
                elementos.btnGuardarAnomalia.disabled = false;
            }
        }
    }

    // Cerrar modal
    function cerrarModal() {
        ModalManager.close('modalAnomalia');
        if (elementos.formAnomalia) {
            elementos.formAnomalia.reset();
        }
    }

    // Guardar anomalía
    async function guardarAnomalia() {
        const tipoInput = document.getElementById('anomaliaTipo');
        if (!tipoInput) {
            mostrarAlerta('error', 'Error: No se encontró el campo tipo');
            return;
        }

        const tipo = tipoInput.value;
        const form = elementos.formAnomalia;
        
        if (!form) {
            mostrarAlerta('error', 'Error: No se encontró el formulario');
            return;
        }

        const tipoSeleccionado = form.querySelector('input[name="tipo_anomalia"]:checked');
        if (!tipoSeleccionado) {
            mostrarAlerta('error', 'Selecciona un tipo de anomalía');
            return;
        }
        
        const idFichaInput = document.getElementById('anomaliaIdFicha');
        const fechaInput = document.getElementById('anomaliaFecha');
        const descripcionInput = document.getElementById('descripcionAnomalia');

        if (!idFichaInput || !fechaInput) {
            mostrarAlerta('error', 'Error: Faltan datos requeridos');
            return;
        }

        const data = {
            id_ficha: parseInt(idFichaInput.value),
            fecha_asistencia: fechaInput.value,
            tipo_anomalia: tipoSeleccionado.value,
            descripcion: descripcionInput ? (descripcionInput.value || null) : null
        };

        if (tipo === 'aprendiz') {
            const idAprendizInput = document.getElementById('anomaliaIdAprendiz');
            if (!idAprendizInput || !idAprendizInput.value) {
                mostrarAlerta('error', 'Error: Falta el ID del aprendiz');
                return;
            }
            data.id_aprendiz = parseInt(idAprendizInput.value);
        }

        if (!data.tipo_anomalia) {
            mostrarAlerta('error', 'Selecciona un tipo de anomalía');
            return;
        }

        if (!data.id_ficha || !data.fecha_asistencia) {
            mostrarAlerta('error', 'Error: Faltan datos requeridos (ficha o fecha)');
            return;
        }

        try {
            if (elementos.btnGuardarAnomalia) {
                elementos.btnGuardarAnomalia.disabled = true;
                elementos.btnGuardarAnomalia.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }

            const endpoint = tipo === 'aprendiz' 
                ? '/api/asistencia/anomalia/aprendiz'
                : '/api/asistencia/anomalia/ficha';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                let errorMessage = `Error HTTP ${response.status}: ${response.statusText}`;
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorData.error || errorMessage;
                } catch (e) {
                    // Si no se puede parsear JSON, usar el mensaje por defecto
                }
                throw new Error(errorMessage);
            }

            const result = await response.json();

            if (result.success) {
                const mensaje = result.message || 'Anomalía registrada exitosamente';
                mostrarAlerta('success', mensaje);
                cerrarModal();
                
                // Recargar datos
                if (tipo === 'ficha') {
                    // Cuando es anomalía de ficha, recargar tanto las anomalías de ficha como los aprendices
                    // porque se registró la anomalía para todos los aprendices no presentes
                    // Recargar aprendices primero para que se actualicen las anomalías en cada fila
                    await cargarAprendices();
                    // Luego recargar anomalías de ficha
                    await cargarAnomaliasFicha();
                } else {
                    // Cuando es anomalía de aprendiz, solo recargar aprendices
                    await cargarAprendices();
                }
            } else {
                throw new Error(result.message || 'Error al registrar anomalía');
            }

        } catch (error) {
            mostrarAlerta('error', 'Error al registrar anomalía: ' + error.message);
        } finally {
            if (elementos.btnGuardarAnomalia) {
                elementos.btnGuardarAnomalia.disabled = false;
                elementos.btnGuardarAnomalia.innerHTML = '<i class="fas fa-save"></i> Registrar Anomalía';
            }
        }
    }

    // Cargar anomalías de ficha
    async function cargarAnomaliasFicha() {
        if (!fichaId || !fecha || !elementos.anomaliasFichaList) return;

        try {
            const response = await fetch(`/api/asistencia/anomalias?ficha_id=${fichaId}&fecha=${fecha}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const result = await response.json();
                anomaliasFicha = result.success ? result.data : [];
                renderizarAnomaliasFicha();
            } else {
                elementos.anomaliasFichaList.innerHTML = '<p class="text-muted">No hay anomalías de ficha registradas</p>';
            }
        } catch (error) {
            elementos.anomaliasFichaList.innerHTML = '<p class="text-muted">Error al cargar anomalías</p>';
        }
    }

    // Renderizar anomalías de ficha
    function renderizarAnomaliasFicha() {
        if (!elementos.anomaliasFichaList) return;

        if (anomaliasFicha.length === 0) {
            elementos.anomaliasFichaList.innerHTML = '<p class="text-muted">No hay anomalías de ficha registradas para esta fecha</p>';
            return;
        }

        elementos.anomaliasFichaList.innerHTML = anomaliasFicha.map(anomalia => {
            const tipo = tiposAnomalias[anomalia.tipo_anomalia] || {};
            return `
                <div class="anomalia-item">
                    <div class="anomalia-item-info">
                        <div class="anomalia-item-tipo" style="color: ${tipo.color || '#666'}">
                            <i class="fas fa-${tipo.icono || 'exclamation-triangle'}"></i>
                            ${tipo.nombre || anomalia.tipo_anomalia}
                        </div>
                        ${anomalia.descripcion ? `<div class="anomalia-item-descripcion">${anomalia.descripcion}</div>` : ''}
                        <div class="anomalia-item-fecha">
                            Registrado: ${new Date(anomalia.fecha_registro).toLocaleString('es-ES')}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Mostrar alerta
    function mostrarAlerta(tipo, mensaje) {
        if (!elementos.alertContainer) return;

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo === 'error' ? 'danger' : tipo}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            ${mensaje}
        `;
        
        elementos.alertContainer.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Inicializar cuando el DOM esté listo
    function inicializar() {
        setTimeout(() => {
            init();
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }

})();
