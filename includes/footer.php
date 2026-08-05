    <!-- Floating WhatsApp Widget -->
    <?php if (file_exists(__DIR__ . '/whatsapp_widget.php')) include __DIR__ . '/whatsapp_widget.php'; ?>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?php echo isset($base_url) ? $base_url : ''; ?>sw.js')
                    .then(reg => console.log('PWA ServiceWorker ready:', reg.scope))
                    .catch(err => console.warn('PWA ServiceWorker error:', err));
            });
        }
    </script>
    
    <?php if (isset($custom_js)): ?>
        <?php foreach ($custom_js as $js): ?>
            <script src="<?php echo isset($base_url) ? $base_url : ''; ?>assets/js/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
