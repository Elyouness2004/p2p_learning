<?php
// includes/footer.php

$localBootstrapJs = file_exists(__DIR__ . '/../bootstrap/bootstrap.bundle.min.js');
$bootstrapJs = $localBootstrapJs
    ? BASE_URL . '/bootstrap/bootstrap.bundle.min.js'
    : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';

// Close sidebar layout wrappers if sidebar was used
if (!empty($use_sidebar)): ?>
    </main><!-- /#page-content -->
  </div><!-- /#main-wrapper -->
</div><!-- /#app-layout -->
<?php endif; ?>

<script src="<?= $bootstrapJs ?>"></script>

<?php if (!empty($load_chartjs)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/js/main.js"></script>

<?php
if (!empty($page_scripts) && is_array($page_scripts)) {
    foreach ($page_scripts as $script) {
        echo '<script src="' . BASE_URL . e($script) . '"></script>' . PHP_EOL;
    }
}
?>

</body>
</html>
