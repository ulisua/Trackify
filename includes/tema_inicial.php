<script>
    // Aplica el tema guardado antes de pintar la página (evita el destello de tema claro)
    (function() {
        let savedTheme = null;
        try { savedTheme = localStorage.getItem('theme'); } catch (e) {}
        document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
    })();
</script>
