/**
 * JavaScript para el panel del portero
 */

class PorteroPanel {
    constructor() {
        this.init();
    }

    init() {
        // Auto-refresh cada 30 segundos para actualizar lista de ingresos activos
        setInterval(() => {
            this.actualizarIngresos();
        }, 30000);
    }

    async actualizarIngresos() {
        try {
            const response = await fetch('/api/portero/ingresos-activos');
            const result = await response.json();
            
            if (result.success) {
                // Aquí se podría actualizar la tabla dinámicamente
                // Por ahora solo recargamos la página si hay cambios
                console.log('Ingresos actualizados:', result.data.length);
            }
        } catch (error) {
            console.error('Error al actualizar ingresos:', error);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PorteroPanel();
});

