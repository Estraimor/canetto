        <footer class="footer">
            © <?= date('Y') ?> Canetto Software
        </footer>

    </div> <!-- cierre main -->
</div> <!-- cierre app -->


<script>
window.addEventListener("load", function() {
    const loader = document.getElementById("loader");
    setTimeout(() => {
        loader.style.display = "none";
    }, 500);
});
</script>



<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
</body>
</html>