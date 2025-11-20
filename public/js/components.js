/**
 * SENAttend - Componentes JavaScript Reutilizables
 * Fase 2 - Sistema de modales, AJAX y feedback visual
 */

// ==============================================
// SISTEMA DE MODALES REUTILIZABLE
// ==============================================

class Modal {
    constructor(id) {
        this.id = id;
        this.modal = document.getElementById(id);
        this.init();
    }

    init() {
        if (!this.modal) return;

        // Cerrar al hacer clic en el overlay
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Cerrar con tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    open() {
        if (this.modal) {
            this.modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    close() {
        if (this.modal) {
            this.modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    isOpen() {
        return this.modal && this.modal.classList.contains('active');
    }

    setContent(html) {
        const content = this.modal.querySelector('.modal-body');
        if (content) {
            content.innerHTML = html;
        }
    }

    setTitle(title) {
        const titleElement = this.modal.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }
}

// ==============================================
// SISTEMA DE NOTIFICACIONES / FEEDBACK
// ==============================================

class Notification {
    static show(message, type = 'info', duration = 5000) {
        const container = this.getOrCreateContainer();
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${this.getIcon(type)}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        container.appendChild(notification);

        // Auto-cerrar
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, duration);

        return notification;
    }

    static success(message, duration) {
        return this.show(message, 'success', duration);
    }

    static error(message, duration) {
        return this.show(message, 'error', duration);
    }

    static warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    static info(message, duration) {
        return this.show(message, 'info', duration);
    }

    static getIcon(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }

    static getOrCreateContainer() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }
}

// ==============================================
// SISTEMA DE AJAX REQUESTS
// ==============================================

class API {
    static async request(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        const config = { ...defaults, ...options };

        // Si hay body y no es FormData, convertir a JSON
        if (config.body && !(config.body instanceof FormData)) {
            config.body = JSON.stringify(config.body);
        }

        // Si es FormData, quitar Content-Type para que el navegador lo configure
        if (config.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.errors?.[0] || 'Error en la petición');
            }

            return { success: true, data, status: response.status };
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message };
        }
    }

    static get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(fullUrl, { method: 'GET' });
    }

    static post(url, data) {
        return this.request(url, { method: 'POST', body: data });
    }

    static put(url, data) {
        return this.request(url, { method: 'PUT', body: data });
    }

    static delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
}

// ==============================================
// COMPONENTE DE CONFIRMACIÓN
// ==============================================

class Confirm {
    static async show(title, message, options = {}) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal active';
            modal.innerHTML = `
                <div class="modal-content">
                    <h2 class="modal-title">${title}</h2>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-secondary" data-action="cancel">
                            ${options.cancelText || 'Cancelar'}
                        </button>
                        <button class="btn ${options.confirmClass || 'btn-primary'}" data-action="confirm">
                            ${options.confirmText || 'Confirmar'}
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                if (action) {
                    modal.remove();
                    resolve(action === 'confirm');
                } else if (e.target === modal) {
                    modal.remove();
                    resolve(false);
                }
            });
        });
    }
}

// ==============================================
// COMPONENTE DE LOADING
// ==============================================

class Loading {
    static show(message = 'Cargando...') {
        this.hide(); // Asegurar que no haya múltiples
        
        const loader = document.createElement('div');
        loader.id = 'global-loader';
        loader.className = 'loader-overlay';
        loader.innerHTML = `
            <div class="loader-content">
                <div class="spinner"></div>
                <p>${message}</p>
            </div>
        `;

        document.body.appendChild(loader);
        return loader;
    }

    static hide() {
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.remove();
        }
    }
}

// ==============================================
// COMPONENTE DE BÚSQUEDA DINÁMICA
// ==============================================

class SearchBox {
    constructor(inputId, callback, debounceTime = 500) {
        this.input = document.getElementById(inputId);
        this.callback = callback;
        this.debounceTime = debounceTime;
        this.timeout = null;

        if (this.input) {
            this.init();
        }
    }

    init() {
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.callback(e.target.value);
            }, this.debounceTime);
        });
    }
}

// ==============================================
// UTILIDADES DE VALIDACIÓN
// ==============================================

class Validator {
    static validateDocumento(documento) {
        const regex = /^[0-9]{6,20}$/;
        return regex.test(documento);
    }

    static validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    static validateFicha(numero) {
        const regex = /^[A-Z0-9]{4,20}$/i;
        return regex.test(numero);
    }

    static isEmpty(value) {
        return !value || value.trim() === '';
    }

    static minLength(value, min) {
        return value.length >= min;
    }

    static maxLength(value, max) {
        return value.length <= max;
    }
}

// ==============================================
// COMPONENTE DE CSV UPLOADER
// ==============================================

class CSVUploader {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.options = {
            onValidate: options.onValidate || null,
            onSuccess: options.onSuccess || null,
            onError: options.onError || null,
            validateUrl: options.validateUrl || null,
            uploadUrl: options.uploadUrl || null
        };

        if (this.form) {
            this.init();
        }
    }

    init() {
        const fileInput = this.form.querySelector('input[type="file"]');
        
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileSelect(e.target.files[0]);
            });
        }

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleUpload();
        });
    }

    handleFileSelect(file) {
        if (!file) return;

        // Validar extensión
        if (!file.name.endsWith('.csv')) {
            Notification.error('El archivo debe ser CSV');
            return;
        }

        // Validar tamaño (5MB max)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            Notification.error('El archivo no debe superar 5MB');
            return;
        }

        // Pre-validar si hay URL configurada
        if (this.options.validateUrl) {
            this.validateFile(file);
        }
    }

    async validateFile(file) {
        Loading.show('Validando archivo...');

        const formData = new FormData();
        formData.append('csv_file', file);

        const result = await API.post(this.options.validateUrl, formData);
        
        Loading.hide();

        if (result.success && result.data.valid) {
            if (result.data.tiene_errores) {
                const errores = result.data.errores.slice(0, 5).join('\n');
                Notification.warning(`Archivo válido pero con advertencias:\n${errores}`);
            } else {
                Notification.success(`Archivo válido: ${result.data.aprendices_validos} registros`);
            }

            if (this.options.onValidate) {
                this.options.onValidate(result.data);
            }
        } else {
            const error = result.error || result.data?.errors?.join(', ') || 'Error de validación';
            Notification.error(error);
        }
    }

    async handleUpload() {
        if (!this.options.uploadUrl) {
            console.error('No se configuró uploadUrl');
            return;
        }

        Loading.show('Importando datos...');

        const formData = new FormData(this.form);
        const result = await API.post(this.options.uploadUrl, formData);

        Loading.hide();

        if (result.success && result.data.success) {
            Notification.success(result.data.message);
            
            if (this.options.onSuccess) {
                this.options.onSuccess(result.data);
            } else {
                // Recargar página por defecto
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            const error = result.error || result.data?.errors?.join(', ') || 'Error al importar';
            Notification.error(error);

            if (this.options.onError) {
                this.options.onError(result);
            }
        }
    }
}

// ==============================================
// EXPORTAR PARA USO GLOBAL
// ==============================================

window.Modal = Modal;
window.Notification = Notification;
window.API = API;
window.Confirm = Confirm;
window.Loading = Loading;
window.SearchBox = SearchBox;
window.Validator = Validator;
window.CSVUploader = CSVUploader;

