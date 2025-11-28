/**
 * DASHBOARD ADMIN - JAVASCRIPT
 * Funcionalidades interactivas del dashboard administrativo
 */

(function() {
    'use strict';

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initDashboard();
    });

    /**
     * Inicializa el dashboard
     */
    function initDashboard() {
        // Agregar animaciones de entrada a las tarjetas
        animateCards();
        
        // Agregar efectos de hover mejorados
        enhanceCardHovers();
    }

    /**
     * Anima la entrada de las tarjetas
     */
    function animateCards() {
        const cards = document.querySelectorAll('.action-card-sena');
        
        cards.forEach((card, index) => {
            // Agregar delay progresivo
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    /**
     * Mejora los efectos hover de las tarjetas
     */
    function enhanceCardHovers() {
        const cards = document.querySelectorAll('.action-card-sena');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                // Agregar clase de hover activo
                this.classList.add('card-hover-active');
            });
            
            card.addEventListener('mouseleave', function() {
                // Remover clase de hover activo
                this.classList.remove('card-hover-active');
            });
        });
    }

    /**
     * Muestra un mensaje de confirmación antes de navegar
     * (Útil para acciones críticas)
     */
    function confirmNavigation(message, url) {
        if (confirm(message)) {
            window.location.href = url;
        }
    }

    // Exportar funciones si es necesario
    window.DashboardAdmin = {
        confirmNavigation: confirmNavigation
    };

})();
