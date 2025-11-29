// Módulo Gestión de Reportes - Lógica frontend con AJAX

document.addEventListener('DOMContentLoaded', () => {
    const fichaSelect = document.getElementById('ficha_select');
    const buscarFichaInput = document.getElementById('buscar_ficha');
    const fechaInput = document.getElementById('fecha_reporte');
    const btnGenerar = document.getElementById('btnGenerarReporte');
    const alertContainer = document.getElementById('alertContainer');

    if (!btnGenerar || !fichaSelect) {
        return;
    }

    function showAlert(message, type = 'success') {
        if (!alertContainer) return;
        alertContainer.innerHTML = '';
        alertContainer.style.display = 'block';

        const div = document.createElement('div');
        div.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
        div.textContent = message;
        alertContainer.appendChild(div);

        setTimeout(() => {
            alertContainer.style.display = 'none';
        }, 6000);
    }

    // Habilitar botón cuando haya ficha seleccionada
    fichaSelect.addEventListener('change', () => {
        btnGenerar.disabled = !fichaSelect.value;
    });

    // Filtro por número de ficha
    if (buscarFichaInput) {
        buscarFichaInput.addEventListener('input', () => {
            const termino = buscarFichaInput.value.toLowerCase();
            const options = fichaSelect.querySelectorAll('option');

            options.forEach(option => {
                if (!option.value) {
                    // Mantener opción placeholder
                    option.hidden = false;
                    return;
                }

                const numero = (option.getAttribute('data-numero') || '').toLowerCase();
                const texto = option.textContent.toLowerCase();
                const coincide = numero.includes(termino) || texto.includes(termino);
                option.hidden = !coincide;
            });

            // Si la opción seleccionada queda oculta, limpiar selección
            const selectedOption = fichaSelect.selectedOptions[0];
            if (selectedOption && selectedOption.hidden) {
                fichaSelect.value = '';
                btnGenerar.disabled = true;
            }
        });
    }

    btnGenerar.addEventListener('click', async () => {
        const fichaId = fichaSelect.value;
        const fecha = fechaInput ? fechaInput.value : '';

        if (!fichaId) {
            showAlert('Debes seleccionar una ficha.', 'error');
            return;
        }

        const form = document.getElementById('formGenerarReporte');
        if (!form) return;

        const formData = new FormData(form);

        btnGenerar.disabled = true;
        const btnText = btnGenerar.querySelector('.btn-text');
        const btnLoader = btnGenerar.querySelector('.btn-loader');
        if (btnText && btnLoader) {
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline-flex';
        }

        try {
            const resp = await fetch('/gestion-reportes/generar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
            });

            const data = await resp.json();

            if (!data.success) {
                showAlert(data.message || 'Error al generar el reporte.', 'error');
            } else {
                showAlert(data.message || 'Reporte generado correctamente.', 'success');

                if (data.download_url) {
                    // Forzar descarga automática
                    const a = document.createElement('a');
                    a.href = data.download_url;
                    a.download = data.file_name || '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }

                // Opcional: refrescar la página para actualizar historial
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } catch (e) {
            console.error(e);
            showAlert('Ocurrió un error inesperado al generar el reporte.', 'error');
        } finally {
            if (btnText && btnLoader) {
                btnText.style.display = 'inline-flex';
                btnLoader.style.display = 'none';
            }
            btnGenerar.disabled = !fichaSelect.value;
        }
    });
});


