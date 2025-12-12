// Event listener para botón "Generar Otro QR"
document.addEventListener('DOMContentLoaded', function() {
    const btnGenerarOtro = document.getElementById('btnGenerarOtro');
    if (btnGenerarOtro) {
        btnGenerarOtro.addEventListener('click', function() {
            location.reload();
        });
    }
});

