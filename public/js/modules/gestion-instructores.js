/**
 * SENAttend - JavaScript para Gestión de Instructores
 * Extraído de views/gestion_instructores/index.php
 */

function confirmarEliminar(id, nombre) {
    document.getElementById('instructorName').textContent = nombre;
    document.getElementById('deleteForm').action = `/gestion-instructores/${id}/eliminar`;
    document.getElementById('deleteModal').style.display = 'flex';
}

function cerrarModalEliminar() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Cerrar modal al hacer click fuera
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalEliminar();
    }
});

