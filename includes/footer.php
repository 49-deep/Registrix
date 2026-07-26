<?php
/**
 * includes/footer.php
 * Shared closing tags + Bootstrap JS.
 */
?>

<!-- ══════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════ -->
<footer class="rgx-footer mt-auto">
    <div class="container">
        <div class="row align-items-center py-4">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <span class="rgx-footer-brand">
                    <i class="bi bi-journal-bookmark-fill me-1"></i>Registrix
                </span>
                <span class="rgx-footer-sep mx-2">·</span>
                <span class="rgx-footer-tagline">Live Student Registry &amp; Search System</span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-muted">Academic Record Management &copy; <?= date('Y') ?></small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
