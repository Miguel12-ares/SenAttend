/**
 * JavaScript para el Módulo de Analítica y Reportes
 * Archivo: public/js/modules/analytics.js
 */

(function () {
    'use strict';

    // Elementos del DOM
    const formGenerarReporte = document.getElementById('formGenerarReporte');
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const fichaSelect = document.getElementById('ficha_select');
    const buscarFicha = document.getElementById('buscar_ficha');
    const tipoSemanal = document.getElementById('tipo_semanal');
    const tipoMensual = document.getElementById('tipo_mensual');
    const opcionesSemanal = document.getElementById('opciones_semanal');
    const opcionesMensual = document.getElementById('opciones_mensual');
    const alertContainer = document.getElementById('alertContainer');

    // Inicialización
    document.addEventListener('DOMContentLoaded', function () {
        initEventListeners();
        initFichaSearch();
    });

    /**
     * Inicializa los event listeners
     */
    function initEventListeners() {
        // Cambio de tipo de reporte
        if (tipoSemanal) {
            tipoSemanal.addEventListener('change', handleTipoReporteChange);
        }
        if (tipoMensual) {
            tipoMensual.addEventListener('change', handleTipoReporteChange);
        }

        // Submit del formulario
        if (formGenerarReporte) {
            formGenerarReporte.addEventListener('submit', handleFormSubmit);
        }

        // Cambio de ficha
        if (fichaSelect) {
            fichaSelect.addEventListener('change', handleFichaChange);
        }
    }

    /**
     * Inicializa la búsqueda de fichas
     */
    function initFichaSearch() {
        if (!buscarFicha || !fichaSelect) return;

        buscarFicha.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const options = fichaSelect.querySelectorAll('option');

            options.forEach(function (option) {
                if (option.value === '') return; // Skip placeholder

                const numeroFicha = option.getAttribute('data-numero');
                const text = option.textContent.toLowerCase();

                if (numeroFicha && numeroFicha.toLowerCase().includes(searchTerm)) {
                    option.style.display = '';
                } else if (text.includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    }

    /**
     * Maneja el cambio de tipo de reporte
     */
    function handleTipoReporteChange() {
        const esSemanal = tipoSemanal && tipoSemanal.checked;

        if (opcionesSemanal) {
            opcionesSemanal.style.display = esSemanal ? 'block' : 'none';
        }
        if (opcionesMensual) {
            opcionesMensual.style.display = esSemanal ? 'none' : 'block';
        }
    }

    /**
     * Maneja el cambio de ficha seleccionada
     */
    function handleFichaChange() {
        // Limpiar alertas previas
        hideAlert();
    }

    /**
     * Maneja el submit del formulario
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        // Validar ficha seleccionada
        const fichaId = fichaSelect ? fichaSelect.value : '';
        if (!fichaId) {
            showAlert('Por favor selecciona una ficha.', 'error');
            return;
        }

        // Determinar tipo de reporte
        const esSemanal = tipoSemanal && tipoSemanal.checked;

        if (esSemanal) {
            generarReporteSemanal();
        } else {
            generarReporteMensual();
        }
    }

    /**
     * Genera reporte semanal
     */
    function generarReporteSemanal() {
        const formData = new FormData(formGenerarReporte);

        // Mostrar estado de carga
        setLoadingState(true);
        hideAlert();

        fetch('/analytics/generar-semanal', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                setLoadingState(false);

                if (data.success) {
                    showAlert('Reporte semanal generado correctamente. Descargando...', 'success');

                    // Descargar archivo
                    setTimeout(() => {
                        window.location.href = data.download_url;
                    }, 500);
                } else {
                    showAlert(data.message || 'Error al generar el reporte.', 'error');
                }
            })
            .catch(error => {
                setLoadingState(false);
                console.error('Error:', error);
                showAlert('Error de conexión al generar el reporte.', 'error');
            });
    }

    /**
     * Genera reporte mensual
     */
    function generarReporteMensual() {
        const formData = new FormData(formGenerarReporte);

        // Mostrar estado de carga
        setLoadingState(true);
        hideAlert();

        fetch('/analytics/generar-mensual', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                setLoadingState(false);

                if (data.success) {
                    showAlert('Reporte mensual generado correctamente. Descargando...', 'success');

                    // Descargar archivo
                    setTimeout(() => {
                        window.location.href = data.download_url;
                    }, 500);
                } else {
                    showAlert(data.message || 'Error al generar el reporte.', 'error');
                }
            })
            .catch(error => {
                setLoadingState(false);
                console.error('Error:', error);
                showAlert('Error de conexión al generar el reporte.', 'error');
            });
    }

    /**
     * Establece el estado de carga del botón
     */
    function setLoadingState(isLoading) {
        if (!btnGenerarReporte) return;

        const btnText = btnGenerarReporte.querySelector('.btn-text');
        const btnLoader = btnGenerarReporte.querySelector('.btn-loader');

        if (isLoading) {
            btnGenerarReporte.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'flex';
        } else {
            btnGenerarReporte.disabled = false;
            if (btnText) btnText.style.display = 'flex';
            if (btnLoader) btnLoader.style.display = 'none';
        }
    }

    /**
     * Muestra una alerta
     */
    function showAlert(message, type = 'info') {
        if (!alertContainer) return;

        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };

        const icon = iconMap[type] || iconMap.info;

        alertContainer.innerHTML = `
            <div class="alert alert-${type}">
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            </div>
        `;
        alertContainer.style.display = 'block';

        // Auto-ocultar después de 5 segundos para mensajes de éxito
        if (type === 'success') {
            setTimeout(hideAlert, 5000);
        }
    }

    /**
     * Oculta la alerta
     */
    function hideAlert() {
        if (!alertContainer) return;
        alertContainer.style.display = 'none';
        alertContainer.innerHTML = '';
    }

})();
