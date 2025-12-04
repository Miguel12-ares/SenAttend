// Event listener para botones de volver sin URL
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-back[data-back="true"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            window.history.back();
        });
    });
});

