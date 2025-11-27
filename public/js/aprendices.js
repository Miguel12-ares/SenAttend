/**
 * SENAttend - JavaScript para Gestión de Aprendices
 * Funcionalidades: modales, validaciones, confirmaciones
 */

(function() {
    'use strict';

    /**
     * Gestión de modales
     */
    const ModalManager = {
        open: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        },

        close: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        },

        init: function() {
            // Cerrar modal al hacer clic fuera
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('active');
                        this.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            });

            // Cerrar modal con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal').forEach(modal => {
                        if (modal.style.display === 'flex' || modal.classList.contains('active')) {
                            modal.classList.remove('active');
                            modal.style.display = 'none';
                            document.body.style.overflow = '';
                        }
                    });
                }
            });
        }
    };

    /**
     * Confirmación de eliminación de aprendiz
     */
    function confirmarEliminarAprendiz(id, nombre) {
        const modal = document.getElementById('deleteModal');
        const form = document.getElementById('deleteForm');
        const nameElement = document.getElementById('aprendizName');

        if (modal && form && nameElement) {
            nameElement.textContent = nombre;
            form.action = '/aprendices/' + id + '/eliminar';
            ModalManager.open('deleteModal');
        }
    }

    /**
     * Cerrar modal de eliminación
     */
    function cerrarModalEliminar() {
        ModalManager.close('deleteModal');
    }

    /**
     * Abrir modal de importación
     */
    function abrirModalImportar() {
        ModalManager.open('importModal');
    }

    /**
     * Cerrar modal de importación
     */
    function cerrarModalImportar() {
        ModalManager.close('importModal');
        const form = document.getElementById('importForm');
        if (form) {
            form.reset();
        }
        const fileInfo = document.getElementById('fileInfo');
        if (fileInfo) {
            fileInfo.style.display = 'none';
        }
    }

    /**
     * Gestión de archivos CSV
     */
    const FileManager = {
        init: function() {
            const fileInput = document.getElementById('csv_file');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');

            if (fileInput && fileInfo && fileName && fileSize) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        fileName.textContent = file.name;
                        fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
                        fileInfo.style.display = 'flex';
                    } else {
                        fileInfo.style.display = 'none';
                    }
                });
            }
        },

        clear: function() {
            const fileInput = document.getElementById('csv_file');
            const fileInfo = document.getElementById('fileInfo');
            
            if (fileInput) {
                fileInput.value = '';
            }
            if (fileInfo) {
                fileInfo.style.display = 'none';
            }
        }
    };

    /**
     * Validación de formularios
     */
    function setupFormValidation() {
        const forms = document.querySelectorAll('form[id$="Form"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Mostrar mensajes de validación
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }

    /**
     * Inicialización
     */
    function init() {
        ModalManager.init();
        FileManager.init();
        setupFormValidation();

        // Exponer funciones globales
        window.confirmarEliminarAprendiz = confirmarEliminarAprendiz;
        window.cerrarModalEliminar = cerrarModalEliminar;
        window.abrirModalImportar = abrirModalImportar;
        window.cerrarModalImportar = cerrarModalImportar;
        window.clearFile = FileManager.clear;
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

