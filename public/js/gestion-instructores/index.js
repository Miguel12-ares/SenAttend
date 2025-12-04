// Event listeners para botones de eliminar
document.addEventListener('DOMContentLoaded', function() {
    const btnCancelarEliminar = document.getElementById('btnCancelarEliminar');
    const deleteModal = document.getElementById('deleteModal');
    
    // Agregar event listeners a todos los botones de eliminar
    document.querySelectorAll('.btn-eliminar-instructor').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.instructorId;
            const nombre = this.dataset.instructorNombre;
            confirmarEliminar(id, nombre);
        });
    });
    
    // Event listener para cancelar
    if (btnCancelarEliminar) {
        btnCancelarEliminar.addEventListener('click', cerrarModalEliminar);
    }
    
    // Cerrar modal al hacer click fuera
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEliminar();
            }
        });
    }
});

function confirmarEliminar(id, nombre) {
    document.getElementById('instructorName').textContent = nombre;
    document.getElementById('deleteForm').action = `/gestion-instructores/${id}/eliminar`;
    document.getElementById('deleteModal').style.display = 'flex';
}

function cerrarModalEliminar() {
    document.getElementById('deleteModal').style.display = 'none';
}

