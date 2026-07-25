<?php if (isset($show_footer) && $show_footer === true): ?>
<footer class="mt-auto py-4 bg-slate-900 text-white border-top border-slate-800" style="background-color: #0f172a;">
    <div class="container text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <i class="fa-solid fa-hand-holding-heart text-primary fs-4"></i>
                    <span class="fw-bold fs-5">SIPENSO Dinas Sosial</span>
                </div>
                <p class="text-secondary small mb-0 mt-1">Sistem Informasi Pengaduan Masyarakat Terpadu Dinas Sosial & Layanan Kesejahteraan</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="text-secondary small mb-0">&copy; <?= date('Y'); ?> Dinas Sosial. All rights reserved.</p>
                <small class="text-muted" style="font-size: 0.75rem;">Dikembangkan dengan PHP &amp; MySQL (phpMyAdmin Ready)</small>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
