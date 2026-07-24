    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    
    <?php if (isset($custom_js)): ?>
        <?php foreach ($custom_js as $js): ?>
            <script src="<?php echo isset($base_url) ? $base_url : ''; ?>assets/js/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
