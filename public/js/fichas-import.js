/**
 * Manejo de Importación CSV para Fichas
 * Funcionalidades: Modal, validación, importación con progreso
 */

class FichasImport {
    constructor() {
        this.modal = document.getElementById('importModal');
        this.form = document.getElementById('importForm');
        this.fileInput = document.getElementById('csv_file');
        this.fileInfo = document.getElementById('fileInfo');
        
        this.init();
    }
    
    init() {
        if (!this.modal || !this.form) return;
        
        // Eventos de archivo
        if (this.fileInput) {
            this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }
        
        // Drag and drop
        this.setupDragDrop();
    }
    
    setupDragDrop() {
        const uploadArea = document.querySelector('.file-upload-area');
        if (!uploadArea) return;
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.fileInput.files = files;
                this.handleFileSelect({ target: { files } });
            }
        });
    }
    
    handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validar extensión
        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.showError('El archivo debe ser un CSV');
            this.clearFile();
            return;
        }
        
        // Mostrar información del archivo
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = this.formatFileSize(file.size);
        this.fileInfo.style.display = 'flex';
    }
    
    async validarArchivo() {
        const formData = new FormData(this.form);
        
        if (!formData.get('csv_file')?.name) {
            this.showError('Seleccione un archivo CSV');
            return;
        }
        
        this.showLoading('Validando archivo...');
        
        try {
            const response = await fetch('/api/fichas/validar-csv', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            // Ocultar loading ANTES de mostrar modales
            this.hideLoading();
            
            if (result.valid) {
                if (result.tiene_errores) {
                    const errores = result.errores.slice(0, 10).join('<br>');
                    this.showWarning(`Archivo válido pero con ${result.errores.length} advertencias`);
                    
                    // Pequeño delay para que se oculte el loading
                    setTimeout(async () => {
                        await this.showErrorModal(
                            'Advertencias de Validación',
                            `<div style="text-align: left; max-height: 300px; overflow-y: auto;">${errores}</div>`
                        );
                    }, 100);
                } else {
                    this.showSuccess(`✓ Archivo válido: ${result.fichas_validas} fichas listas para importar`);
                }
            } else {
                const error = result.errors?.join(', ') || 'Error de validación';
                this.showError(error);
            }
            
        } catch (error) {
            this.hideLoading();
            console.error('Error validación:', error);
            this.showError('Error al validar el archivo');
        }
    }
    
    async importarCSV() {
        const formData = new FormData(this.form);
        
        if (!formData.get('csv_file')?.name) {
            this.showError('Seleccione un archivo CSV');
            return;
        }
        
        const confirmado = await this.showConfirm(
            'Confirmar Importación',
            '¿Desea importar las fichas desde el archivo CSV?'
        );
        
        if (!confirmado) return;
        
        this.showLoading('Importando fichas...');
        
        try {
            const response = await fetch('/api/fichas/importar', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message);
                this.cerrarModal();
                
                // Recargar después de 1.5 segundos
                setTimeout(() => {
                    if (window.fichasSearch) {
                        window.fichasSearch.performSearch();
                    } else {
                        window.location.reload();
                    }
                }, 1500);
            } else {
                const error = result.errors?.join(', ') || 'Error al importar';
                this.showError(error);
            }
            
        } catch (error) {
            console.error('Error importación:', error);
            this.showError('Error durante la importación');
        } finally {
            this.hideLoading();
        }
    }
    
    abrirModal() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    cerrarModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
            this.form.reset();
            this.clearFile();
        }
    }
    
    clearFile() {
        if (this.fileInput) this.fileInput.value = '';
        if (this.fileInfo) this.fileInfo.style.display = 'none';
    }
    
    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    showLoading(message) {
        if (typeof Loading !== 'undefined' && Loading.show) {
            Loading.show(message);
        } else {
            // Fallback simple
            const loading = document.createElement('div');
            loading.id = 'simple-loading';
            loading.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                color: white;
                font-size: 18px;
            `;
            loading.innerHTML = `<div>🔄 ${message}</div>`;
            document.body.appendChild(loading);
        }
    }
    
    hideLoading() {
        if (typeof Loading !== 'undefined' && Loading.hide) {
            Loading.hide();
        } else {
            // Fallback simple
            const loading = document.getElementById('simple-loading');
            if (loading) {
                loading.remove();
            }
        }
    }
    
    showSuccess(message) {
        if (typeof Notification !== 'undefined' && Notification.success) {
            Notification.success(message);
        } else {
            alert(message);
        }
    }
    
    showError(message) {
        if (typeof Notification !== 'undefined' && Notification.error) {
            Notification.error(message);
        } else {
            alert(message);
        }
    }
    
    showWarning(message) {
        if (typeof Notification !== 'undefined' && Notification.warning) {
            Notification.warning(message);
        } else {
            alert(message);
        }
    }
    
    async showConfirm(title, message) {
        if (typeof Confirm !== 'undefined' && Confirm.show) {
            return await Confirm.show(title, message, {
                confirmText: 'Importar',
                confirmClass: 'btn-primary'
            });
        } else {
            return confirm(message);
        }
    }
    
    async showErrorModal(title, content) {
        if (typeof Confirm !== 'undefined' && Confirm.show) {
            return await Confirm.show(title, content, {
                confirmText: 'Entendido',
                confirmClass: 'btn-info'
            });
        } else {
            // Fallback con modal simple
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 400px;
                    overflow-y: auto;
                ">
                    <h3>${title}</h3>
                    <div>${content}</div>
                    <div style="text-align: right; margin-top: 15px;">
                        <button onclick="this.closest('div').parentElement.remove()" 
                                style="background: #39A900; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            Entendido
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            return new Promise(resolve => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.remove();
                        resolve(true);
                    }
                });
            });
        }
    }
}

// Funciones globales para mantener compatibilidad
function abrirModalImportar() {
    if (window.fichasImport) {
        window.fichasImport.abrirModal();
    }
}

function cerrarModalImportar() {
    if (window.fichasImport) {
        window.fichasImport.cerrarModal();
    }
}

function validarArchivoFichas() {
    if (window.fichasImport) {
        window.fichasImport.validarArchivo();
    }
}

function importarCSV() {
    if (window.fichasImport) {
        window.fichasImport.importarCSV();
    }
}

function clearFile() {
    if (window.fichasImport) {
        window.fichasImport.clearFile();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.fichasImport = new FichasImport();
});

// Exportar para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FichasImport;
}
