    </main>

    <footer class="bg-light py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="footer-brand mb-2">Your Career Launchpad</div>
                    <div class="text-muted small">Connecting talented students with amazing opportunities.</div>
                </div>
                <div class="col-md-6 d-flex flex-column align-items-md-end align-items-center">
                    <a href="<?php echo BASE_URL; ?>/feedback.php" class="btn btn-outline-primary mb-2">Feedback</a>
                    <div class="footer-social">
                        <a href="https://www.linkedin.com/" target="_blank" rel="noopener" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="https://twitter.com/" target="_blank" rel="noopener" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://facebook.com/" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="https://instagram.com/" target="_blank" rel="noopener" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide flash messages (but not persistent modal warnings)
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    // Only auto-close alerts that are not the persistent resume warning
                    if (!alert.id || alert.id !== 'resume-warning') {
                        var bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html> 