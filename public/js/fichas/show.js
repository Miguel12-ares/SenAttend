// Obtener el ID de la ficha desde el contexto
const actionButtons = document.querySelector('.action-buttons');
const fichaId = actionButtons?.dataset.fichaId;

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Botón cambiar estado
    const btnCambiarEstado = document.querySelector('[data-action="cambiar-estado"]');
    if (btnCambiarEstado) {
        btnCambiarEstado.addEventListener('click', function() {
            const nuevoEstado = this.dataset.estado;
            cambiarEstado(nuevoEstado);
        });
    }
    
    // Botón eliminar
    const btnEliminar = document.querySelector('[data-action="eliminar"]');
    if (btnEliminar) {
        btnEliminar.addEventListener('click', eliminarFicha);
    }
});

async function cambiarEstado(nuevoEstado) {
    const confirmar = await Confirm.show(
        'Cambiar Estado',
        `¿Está seguro de cambiar el estado a "${nuevoEstado}"?`
    );
    
    if (!confirmar) return;
    
    Loading.show('Cambiando estado...');
    
    try {
        const result = await API.post(`/api/fichas/${fichaId}/estado`, {
            estado: nuevoEstado
        });
        
        if (result.success) {
            Notification.success('Estado actualizado correctamente');
            setTimeout(() => location.reload(), 1000);
        } else {
            Notification.error(result.errors?.[0] || 'Error al cambiar estado');
        }
    } catch (error) {
        Notification.error('Error de conexión');
    } finally {
        Loading.hide();
    }
}

async function eliminarFicha() {
    const confirmar = await Confirm.show(
        'Eliminar Ficha',
        '¿Está seguro de eliminar esta ficha? Esta acción no se puede deshacer.'
    );
    
    if (!confirmar) return;
    
    Loading.show('Eliminando ficha...');
    
    try {
        const result = await API.delete(`/api/fichas/${fichaId}`);
        
        if (result.success) {
            Notification.success('Ficha eliminada correctamente');
            setTimeout(() => window.location.href = '/fichas', 1000);
        } else {
            Notification.error(result.errors?.[0] || 'Error al eliminar ficha');
        }
    } catch (error) {
        Notification.error('Error de conexión');
    } finally {
        Loading.hide();
    }
}

