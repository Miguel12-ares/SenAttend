/**
 * Buscador Simple y Funcional
 * Funciona tanto manual como automático
 */

class SimpleSearch {
    constructor(formSelector, inputSelector) {
        this.form = document.querySelector(formSelector);
        this.input = document.querySelector(inputSelector);
        this.searchTimeout = null;
        
        this.init();
    }
    
    init() {
        if (!this.form || !this.input) return;
        
        // Búsqueda automática con delay
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            const value = e.target.value.trim();
            
            // Solo buscar si tiene contenido o está vacío
            if (value.length >= 2 || value.length === 0) {
                this.searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 600);
            }
        });
        
        // Búsqueda manual con Enter
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(this.searchTimeout);
                this.form.submit();
            }
        });
        
        // Búsqueda manual con botón
        const submitBtn = this.form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                clearTimeout(this.searchTimeout);
            });
        }
    }
}

// Inicializar buscadores cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Buscador de fichas
    if (document.querySelector('.search-form')) {
        new SimpleSearch('.search-form', '#searchInput');
    }
    
    // Buscador de aprendices
    if (document.querySelector('#filterForm')) {
        new SimpleSearch('#filterForm', '#search');
    }
});

// Función para filtros automáticos
function setupAutoFilters() {
    // Cambio automático en selects
    document.querySelectorAll('select[name="estado"], select[name="ficha"]').forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
}

// Inicializar filtros automáticos
document.addEventListener('DOMContentLoaded', setupAutoFilters);
