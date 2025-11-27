/**
 * Manejo de Importación CSV para Aprendices
 * Funcionalidades: Modal, validación, importación con progreso
 */

class AprendicesImport {
    constructor() {
        this.setupFileHandling();
        this.setupValidation();
    }
    
    setupFileHandling() {
        // Manejo de archivo seleccionado
        const fileInput = document.getElementById('csv_file');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    document.getElementById('fileName').textContent = file.name;
                    document.getElementById('fileSize').textContent = formatFileSize(file.size);
                    document.getElementById('fileInfo').style.display = 'flex';
                }
            });
        }
        
        // Drag and drop
        const uploadArea = document.querySelector('.file-upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById('csv_file').files = files;
                    const event = new Event('change');
                    document.getElementById('csv_file').dispatchEvent(event);
                }
            });
        }
    }
    
    setupValidation() {
        // Funciones globales para validación e importación
        window.validarArchivo = async function() {
            const form = document.getElementById('importForm');
            const formData = new FormData(form);

            if (!formData.get('csv_file')?.name) {
                showNotification('error', 'Seleccione un archivo CSV');
                return;
            }

            showLoading('Validando archivo...');

            try {
                const result = await fetchAPI('/api/aprendices/validar-csv', formData);
                
                hideLoading();

                if (result.success && result.data.valid) {
                    if (result.data.tiene_errores) {
                        const errores = result.data.errores.slice(0, 10).join('<br>');
                        showNotification('warning', `Archivo válido pero con ${result.data.errores.length} advertencias`);
                        
                        // Mostrar modal con errores
                        await showConfirmModal(
                            'Advertencias de Validación',
                            `<div style="text-align: left; max-height: 300px; overflow-y: auto;">${errores}</div>`,
                            'Entendido'
                        );
                    } else {
                        showNotification('success', `✓ Archivo válido: ${result.data.aprendices_validos} aprendices listos para importar`);
                    }
                } else {
                    const error = result.error || result.data?.errors?.join(', ') || 'Error de validación';
                    showNotification('error', error);
                }
            } catch (error) {
                hideLoading();
                showNotification('error', 'Error al validar el archivo');
            }
        };

        window.importarCSV = async function() {
            const form = document.getElementById('importForm');
            const formData = new FormData(form);

            if (!formData.get('csv_file')?.name) {
                showNotification('error', 'Seleccione un archivo CSV');
                return;
            }

            if (!formData.get('ficha_id')) {
                showNotification('error', 'Seleccione una ficha');
                return;
            }

            const confirmado = await showConfirmModal(
                'Confirmar Importación',
                '¿Desea importar los aprendices desde el archivo CSV?',
                'Importar'
            );

            if (!confirmado) return;

            showLoading('Importando aprendices...');

            try {
                const result = await fetchAPI('/api/aprendices/importar', formData);
                
                hideLoading();

                if (result.success) {
                    showNotification('success', result.message || 'Importación completada exitosamente');
                    cerrarModalImportar();
                    
                    // Recargar búsqueda si existe, sino recargar página
                    setTimeout(() => {
                        if (window.aprendicesSearch) {
                            window.aprendicesSearch.performSearch();
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    const error = result.error || result.errors?.join(', ') || 'Error al importar';
                    showNotification('error', error);
                }
            } catch (error) {
                hideLoading();
                showNotification('error', 'Error durante la importación');
            }
        };

        window.clearFile = function() {
            document.getElementById('csv_file').value = '';
            document.getElementById('fileInfo').style.display = 'none';
        };
    }
}

// Funciones auxiliares
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function showLoading(message) {
    if (typeof Loading !== 'undefined' && Loading.show) {
        Loading.show(message);
    }
}

function hideLoading() {
    if (typeof Loading !== 'undefined' && Loading.hide) {
        Loading.hide();
    }
}

function showNotification(type, message) {
    if (typeof Notification !== 'undefined' && Notification[type]) {
        Notification[type](message);
    } else {
        alert(message);
    }
}

async function showConfirmModal(title, message, confirmText = 'Confirmar') {
    if (typeof Confirm !== 'undefined' && Confirm.show) {
        return await Confirm.show(title, message, {
            confirmText: confirmText,
            confirmClass: 'btn-primary'
        });
    } else {
        return confirm(message.replace(/<[^>]*>/g, ''));
    }
}

async function fetchAPI(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        body: formData
    });
    return await response.json();
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new AprendicesImport();
});

// Exportar para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AprendicesImport;
}
