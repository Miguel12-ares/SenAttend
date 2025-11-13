/**
 * SENAttend - JavaScript principal
 */

(function() {
    'use strict';

    // Inicialización cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('SENAttend - Sistema de Asistencia SENA inicializado');

        // Cerrar alertas automáticamente después de 5 segundos
        autoCloseAlerts();

        // Confirmación de logout
        setupLogoutConfirmation();

        // Validación de formularios
        setupFormValidation();
    });

    /**
     * Cierra alertas automáticamente
     */
    function autoCloseAlerts() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 5000);
        });
    }

    /**
     * Confirma logout antes de cerrar sesión
     */
    function setupLogoutConfirmation() {
        // Ya no se requiere confirmación - logout directo
        // Los enlaces /auth/logout funcionan sin confirmación
        console.log('Logout directo habilitado');
    }

    /**
     * Validación básica de formularios
     */
    function setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const inputs = form.querySelectorAll('input[required]');
                let isValid = true;
                
                inputs.forEach(function(input) {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.borderColor = 'var(--color-danger)';
                    } else {
                        input.style.borderColor = '';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Por favor complete todos los campos requeridos');
                }
            });
        });
    }

    // Utilidades globales
    window.SENAttend = {
        /**
         * Muestra un mensaje de confirmación
         */
        confirm: function(message) {
            return window.confirm(message);
        },

        /**
         * Muestra un mensaje de alerta
         */
        alert: function(message) {
            window.alert(message);
        },

        /**
         * Redirige a una URL
         */
        redirect: function(url) {
            window.location.href = url;
        }
    };

})();

