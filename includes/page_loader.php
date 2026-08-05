<?php
/**
 * MicroClimeet - Apple-Inspired Seamless Page Transition Loader
 * Pure White (#FFFFFF) Background, Thin Green Rotating Ring, Zero-Gravity Floating Leaf Logo.
 * 
 * Rules:
 *  - Instant navigation WITHIN dashboard pages (/petani/* to /petani/* or /admin/* to /admin/*)
 *  - Triggers loader when moving FROM Landing Page TO Dashboard (and vice-versa)
 *  - Handles Page Refresh and Slow Connection with smooth fade-out & 5s Safety Timeout.
 */
$loader_asset_prefix = isset($base_url) ? $base_url : '';
?>
<!-- Global Page Transition Loader Overlay -->
<div id="page-loader-overlay" class="page-loader-overlay">
    <div class="page-loader-box">
        <div class="page-loader-spinner"></div>
        <div class="page-loader-logo-wrapper">
            <img src="<?php echo $loader_asset_prefix; ?>assets/images/brand/mini_logo.png" alt="MicroClimeet" class="page-loader-logo">
        </div>
    </div>
</div>

<style>
/* ── Apple-Inspired Page Transition Loader ── */
.page-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #FFFFFF;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    visibility: visible;
    pointer-events: all;
    transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s ease;
    user-select: none;
}

.page-loader-overlay.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.page-loader-box {
    position: relative;
    width: 110px;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Thin Elegant Green Spinning Ring */
.page-loader-spinner {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2.5px solid rgba(13, 93, 49, 0.08);
    border-top-color: #16A34A;
    border-right-color: rgba(34, 197, 94, 0.4);
    animation: pageSpinnerRotate 1.1s linear infinite;
    box-sizing: border-box;
}

@keyframes pageSpinnerRotate {
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Zero-Gravity Floating Logo Suspended in Mid-Air */
.page-loader-logo-wrapper {
    position: relative;
    width: 58px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: zeroGravityFloat 2.4s ease-in-out infinite;
}

@keyframes zeroGravityFloat {
    0%, 100% {
        transform: translateY(0px) scale(1);
        filter: drop-shadow(0 8px 16px rgba(13, 93, 49, 0.15));
    }
    50% {
        transform: translateY(-8px) scale(1.04);
        filter: drop-shadow(0 16px 24px rgba(13, 93, 49, 0.25));
    }
}

.page-loader-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
</style>

<script>
(function() {
    const loader = document.getElementById('page-loader-overlay');
    if (!loader) return;

    let isHidden = false;

    // Smooth Fade Out on Page Load / Refresh
    function hideLoader() {
        if (isHidden) return;
        isHidden = true;
        setTimeout(function() {
            loader.classList.add('hidden');
        }, 120);
    }

    if (document.readyState === 'complete') {
        hideLoader();
    } else {
        window.addEventListener('load', hideLoader);
        document.addEventListener('DOMContentLoaded', hideLoader);
    }

    // Safety Timeout (5s): On slow connection or offline, automatically dismiss loader
    setTimeout(function() {
        hideLoader();
    }, 5000);

    // Handles browser Back/Forward cache (bfcache)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            hideLoader();
        }
    });

    // Intercept internal link clicks for seamless page transition loader
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        const target = link.getAttribute('target');

        // Ignore hash links, javascript:, external links, target="_blank", export download links, or download attributes
        if (!href || 
            href.startsWith('#') || 
            href.startsWith('javascript:') || 
            href.startsWith('mailto:') || 
            href.startsWith('tel:') || 
            target === '_blank' || 
            href.includes('export=') || 
            link.hasAttribute('download') || 
            link.hasAttribute('data-no-loader')) {
            return;
        }

        try {
            const currentPath = window.location.pathname;
            const destination = new URL(href, window.location.origin);
            
            const isFromDashboard = currentPath.includes('/petani/') || currentPath.includes('/admin/');
            const isToDashboard   = destination.pathname.includes('/petani/') || destination.pathname.includes('/admin/');

            // 1. WITHIN DASHBOARD NAVIGATION -> Instant (NO LOADER)
            if (isFromDashboard && isToDashboard) {
                return;
            }

            // 2. FROM LANDING PAGE TO DASHBOARD (or DASHBOARD TO LANDING PAGE) -> SHOW LOADER!
            if (destination.origin === window.location.origin) {
                if (destination.pathname !== currentPath || destination.search !== window.location.search) {
                    isHidden = false;
                    loader.classList.remove('hidden');
                }
            }
        } catch (err) {
            if (!href.startsWith('http')) {
                isHidden = false;
                loader.classList.remove('hidden');
            }
        }
    });
})();
</script>
