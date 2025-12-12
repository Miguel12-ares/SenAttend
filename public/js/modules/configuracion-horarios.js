/**
 * SENAttend - JavaScript para Configuración de Horarios
 * Extraído de views/configuracion/horarios.php
 */

// Validación del formulario
(function() {
    'use strict';
    
    const form = document.getElementById('formHorarios');
    if (!form) return;
    
    form.addEventListener('submit', function(event) {
        // Validar que hora_inicio < hora_fin
        let valido = true;
        const turnos = ['mañana', 'tarde', 'noche'];
        
        turnos.forEach(turno => {
            const inicioInput = document.getElementById(turno + '_inicio');
            const finInput = document.getElementById(turno + '_fin');
            const limiteInput = document.getElementById(turno + '_limite');
            
            if (!inicioInput || !finInput || !limiteInput) return;
            
            const inicio = inicioInput.value;
            const fin = finInput.value;
            const limite = limiteInput.value;
            
            if (inicio >= fin) {
                alert(`Error en turno ${turno.charAt(0).toUpperCase() + turno.slice(1)}: La hora de inicio debe ser menor que la hora de fin`);
                valido = false;
            }
            
            if (limite < inicio || limite > fin) {
                alert(`Error en turno ${turno.charAt(0).toUpperCase() + turno.slice(1)}: La hora límite debe estar entre la hora de inicio y fin`);
                valido = false;
            }
        });
        
        if (!form.checkValidity() || !valido) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
})();

// Confirmación antes de guardar
const btnGuardar = document.getElementById('btnGuardar');
if (btnGuardar) {
    btnGuardar.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de que desea actualizar la configuración de horarios? Los cambios se aplicarán inmediatamente.')) {
            e.preventDefault();
        }
    });
}

