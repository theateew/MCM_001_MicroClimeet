<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MicroClimeet - Smart Greenhouse Monitoring System Berbasis IoT untuk Optimalisasi Budidaya Tanaman Premium secara Real-Time.">
    <meta name="keywords" content="greenhouse, smart farming, iot, monitoring, pertanian modern, microclimeet">
    <meta name="author" content="Tim WIBAWA UNS 2026">
    <title><?php echo isset($page_title) ? $page_title . " - MicroClimeet" : "MicroClimeet - Smart Greenhouse Monitoring System"; ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Core Style -->
    <link rel="stylesheet" href="<?php echo isset($base_url) ? $base_url : ''; ?>assets/css/style.css">
    <?php if (isset($custom_css)): ?>
        <?php foreach ($custom_css as $css): ?>
            <link rel="stylesheet" href="<?php echo isset($base_url) ? $base_url : ''; ?>assets/css/<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
