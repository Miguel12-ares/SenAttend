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

        // Menú hamburguesa
        setupMobileMenu();

        // Detectar scroll en tablas
        setupTableScrollDetection();
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

    /**
     * Configura el menú hamburguesa para móviles
     */
    function setupMobileMenu() {
        // Menú principal (autenticado)
        const menuToggle = document.getElementById('menuToggle');
        const mainNav = document.getElementById('mainNav');
        
        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', function() {
                menuToggle.classList.toggle('active');
                mainNav.classList.toggle('active');
            });

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 767) {
                    if (!menuToggle.contains(e.target) && !mainNav.contains(e.target)) {
                        menuToggle.classList.remove('active');
                        mainNav.classList.remove('active');
                    }
                }
            });

            // Cerrar menú al cambiar de tamaño de ventana
            window.addEventListener('resize', function() {
                if (window.innerWidth > 767) {
                    menuToggle.classList.remove('active');
                    mainNav.classList.remove('active');
                }
            });
        }

        // Menú público (no autenticado)
        const menuTogglePublic = document.getElementById('menuTogglePublic');
        const mainNavPublic = document.getElementById('mainNavPublic');
        
        if (menuTogglePublic && mainNavPublic) {
            menuTogglePublic.addEventListener('click', function() {
                menuTogglePublic.classList.toggle('active');
                mainNavPublic.classList.toggle('active');
            });

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 767) {
                    if (!menuTogglePublic.contains(e.target) && !mainNavPublic.contains(e.target)) {
                        menuTogglePublic.classList.remove('active');
                        mainNavPublic.classList.remove('active');
                    }
                }
            });

            // Cerrar menú al cambiar de tamaño de ventana
            window.addEventListener('resize', function() {
                if (window.innerWidth > 767) {
                    menuTogglePublic.classList.remove('active');
                    mainNavPublic.classList.remove('active');
                }
            });

            // Cerrar menú al hacer clic en un enlace
            const navLinks = mainNavPublic.querySelectorAll('a');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 767) {
                            menuTogglePublic.classList.remove('active');
                            mainNavPublic.classList.remove('active');
                        }
                });
            });
        }
    }

    /**
     * Detecta scroll horizontal en tablas y añade indicador visual
     */
    function setupTableScrollDetection() {
        const tableWrappers = document.querySelectorAll('.table-wrapper');
        
        tableWrappers.forEach(function(wrapper) {
            function checkScroll() {
                const isScrollable = wrapper.scrollWidth > wrapper.clientWidth;
                if (isScrollable) {
                    wrapper.classList.add('scrollable');
                } else {
                    wrapper.classList.remove('scrollable');
                }
            }

            checkScroll();
            wrapper.addEventListener('scroll', checkScroll);
            window.addEventListener('resize', checkScroll);
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

