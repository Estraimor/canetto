<footer class="footer">
    © <?= date('Y') ?> Canetto Software
</footer>

</div> <!-- cierre main -->
</div> <!-- cierre app -->

<!-- ================= LIBRERÍAS ================= -->

<!-- jQuery (SIEMPRE PRIMERO) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables Core -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<!-- DataTables Responsive -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ================= INICIALIZACIONES ================= -->



<!-- ================= LOADER ================= -->

<script>
window.addEventListener("load", function() {
    const loader = document.getElementById("loader");

    loader.style.transition = "opacity 0.6s ease";
    loader.style.opacity = "0";

    setTimeout(() => {
        loader.style.display = "none";
    }, 600);
});
</script>

</body>
</html>