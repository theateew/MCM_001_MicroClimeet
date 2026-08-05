<?php
/**
 * MicroClimeet - Ultra-Premium 3D Zero-Gravity Leaf Splash Screen
 * Plays only once per user session when opening the Landing Page.
 */
?>
<!-- Ultra-Premium 3D Leaf Splash Screen Overlay -->
<div id="splash-overlay" class="splash-overlay" style="display:none;">
    <canvas id="splash-canvas" class="splash-canvas"></canvas>
    
    <div class="splash-content" id="splash-content">
        <div class="splash-logo-wrapper" id="splash-logo-wrapper">
            <div class="splash-logo-glow"></div>
            <img src="assets/images/brand/mini_logo.png" alt="MicroClimeet Logo" class="splash-logo-img">
        </div>
        <h1 class="splash-title">MicroClimeet</h1>
        <p class="splash-subtitle">Smart Greenhouse Monitoring System</p>

        <div class="splash-loader-bar">
            <div class="splash-loader-progress"></div>
        </div>
    </div>
    
    <button class="splash-skip-btn" id="splash-skip-btn" onclick="dismissSplashScreen()">Lewati ✕</button>
</div>

<style>
/* ── Ultra-Premium Splash Screen Styling ── */
.splash-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 99999;
    background: radial-gradient(circle at 50% 45%, #FFFFFF 0%, #F8FAFC 70%, #F1F5F9 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.8s ease;
    user-select: none;
}

.splash-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.splash-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    transform: translateY(14px);
    opacity: 0;
    transition: opacity 0.9s cubic-bezier(0.16, 1, 0.3, 1), transform 0.9s cubic-bezier(0.16, 1, 0.3, 1);
}

.splash-content.visible {
    opacity: 1;
    transform: translateY(0);
}

.splash-logo-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    transform: scale(0.5);
    opacity: 0;
    transition: transform 1.1s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.8s ease;
}

.splash-logo-wrapper.active {
    transform: scale(1);
    opacity: 1;
}

.splash-logo-glow {
    position: absolute;
    inset: -20px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(74, 222, 128, 0.5) 0%, rgba(34, 197, 94, 0.2) 50%, transparent 75%);
    filter: blur(14px);
    animation: splashGlowPulse 2.5s infinite ease-in-out;
}

@keyframes splashGlowPulse {
    0%, 100% { transform: scale(0.9); opacity: 0.6; }
    50% { transform: scale(1.2); opacity: 1; }
}

.splash-logo-img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    filter: drop-shadow(0 14px 28px rgba(13, 93, 49, 0.25));
}

.splash-title {
    font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
    font-size: 2.3rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin: 0 0 6px 0;
    background: linear-gradient(135deg, #0D5D31 0%, #16A34A 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.splash-subtitle {
    font-size: 0.95rem;
    font-weight: 600;
    color: #64748B;
    letter-spacing: 0.5px;
    margin: 0 0 26px 0;
}

.splash-loader-bar {
    width: 150px;
    height: 4px;
    background: rgba(226, 232, 240, 0.8);
    border-radius: 99px;
    overflow: hidden;
}

.splash-loader-progress {
    width: 0%;
    height: 100%;
    background: linear-gradient(90deg, #4ADE80 0%, #16A34A 100%);
    border-radius: 99px;
    transition: width 3.2s cubic-bezier(0.1, 0.7, 0.1, 1);
}

.splash-skip-btn {
    position: absolute;
    bottom: 28px;
    right: 28px;
    z-index: 10;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(226, 232, 240, 0.85);
    color: #64748B;
    padding: 8px 18px;
    border-radius: 99px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    backdrop-filter: blur(8px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease;
}

.splash-skip-btn:hover {
    background: #0D5D31;
    color: #FFFFFF;
    border-color: #0D5D31;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(13, 93, 49, 0.25);
}
</style>

<script>
(function() {
    // Run ONLY ONCE per user session on Landing Page
    if (sessionStorage.getItem('microclimeet_splash_seen')) {
        return;
    }

    const overlay = document.getElementById('splash-overlay');
    if (!overlay) return;

    // Show overlay for first-time session user
    overlay.style.display = 'flex';
    sessionStorage.setItem('microclimeet_splash_seen', 'true');

    // Canvas 3D Zero-Gravity Leaf Physics Engine
    const canvas = document.getElementById('splash-canvas');
    const ctx = canvas.getContext('2d');

    let width = canvas.width = window.innerWidth;
    let height = canvas.height = window.innerHeight;

    window.addEventListener('resize', () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });

    const leafCount = 70;
    const leaves = [];
    const centerX = width / 2;
    const centerY = height / 2 - 25;

    let startTime = Date.now();
    let isConverging = false;
    let animFrameId = null;

    // Leaf Palette (Fresh Green Eco-Tech Tones)
    const leafColors = [
        { main: '#4ADE80', dark: '#16A34A' },
        { main: '#22C55E', dark: '#15803D' },
        { main: '#86EFAC', dark: '#22C55E' },
        { main: '#15803D', dark: '#166534' }
    ];

    class Leaf {
        constructor() {
            this.reset();
        }

        reset() {
            // Spawn around screen edges in 3D zero-gravity space
            const angle = Math.random() * Math.PI * 2;
            const dist = 320 + Math.random() * Math.min(width, height) * 0.65;
            
            this.x = centerX + Math.cos(angle) * dist;
            this.y = centerY + Math.sin(angle) * dist;
            this.z = Math.random() * 600 - 300; // Depth Z
            
            this.size = 14 + Math.random() * 18;
            this.color = leafColors[Math.floor(Math.random() * leafColors.length)];
            
            // Rotation & Zero-Gravity Float Physics
            this.rotX = Math.random() * Math.PI * 2;
            this.rotY = Math.random() * Math.PI * 2;
            this.rotZ = Math.random() * Math.PI * 2;
            
            this.vRotX = (Math.random() - 0.5) * 0.035;
            this.vRotY = (Math.random() - 0.5) * 0.035;
            this.vRotZ = (Math.random() - 0.5) * 0.025;
            
            this.vx = (Math.random() - 0.5) * 0.9;
            this.vy = (Math.random() - 0.5) * 0.9;
            
            this.swirlSpeed = 0.015 + Math.random() * 0.02;
            this.swirlAngle = Math.random() * Math.PI * 2;
            this.opacity = 0.85 + Math.random() * 0.15;
        }

        update(elapsed) {
            this.rotX += this.vRotX;
            this.rotY += this.vRotY;
            this.rotZ += this.vRotZ;

            if (elapsed > 1.1) {
                // Convergence phase: Gracefully spiral inward toward center logo
                const dx = centerX - this.x;
                const dy = centerY - this.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                this.swirlAngle += this.swirlSpeed * 1.6;
                const spiralX = Math.cos(this.swirlAngle) * (dist * 0.35);
                const spiralY = Math.sin(this.swirlAngle) * (dist * 0.35);

                this.x += (dx * 0.085) + spiralX * 0.05;
                this.y += (dy * 0.085) + spiralY * 0.05;

                if (dist < 35) {
                    this.opacity = Math.max(0, this.opacity - 0.08);
                }
            } else {
                // Zero-gravity swirl weightless drift
                this.swirlAngle += this.swirlSpeed;
                this.x += Math.cos(this.swirlAngle) * 0.7 + this.vx;
                this.y += Math.sin(this.swirlAngle) * 0.7 + this.vy;
            }
        }

        draw(ctx) {
            if (this.opacity <= 0) return;

            ctx.save();
            ctx.translate(this.x, this.y);

            // 3D Projection scale based on Z
            const scale = Math.max(0.2, (1000 + this.z) / 1000);
            ctx.scale(scale * Math.cos(this.rotY), scale * Math.sin(this.rotX));
            ctx.rotate(this.rotZ);

            ctx.globalAlpha = this.opacity;

            // Draw organic 3D leaf shape
            ctx.beginPath();
            ctx.moveTo(0, -this.size);
            ctx.bezierCurveTo(this.size * 0.7, -this.size * 0.4, this.size * 0.8, this.size * 0.4, 0, this.size);
            ctx.bezierCurveTo(-this.size * 0.8, this.size * 0.4, -this.size * 0.7, -this.size * 0.4, 0, -this.size);
            ctx.closePath();

            // Leaf gradient fill
            const grad = ctx.createLinearGradient(0, -this.size, 0, this.size);
            grad.addColorStop(0, this.color.main);
            grad.addColorStop(1, this.color.dark);
            ctx.fillStyle = grad;
            ctx.fill();

            // Leaf center vein line
            ctx.beginPath();
            ctx.moveTo(0, -this.size * 0.8);
            ctx.lineTo(0, this.size * 0.8);
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.45)';
            ctx.lineWidth = 1.2;
            ctx.stroke();

            ctx.restore();
        }
    }

    // Populate Leaves
    for (let i = 0; i < leafCount; i++) {
        leaves.push(new Leaf());
    }

    // Animation Loop (60fps)
    function animate() {
        ctx.clearRect(0, 0, width, height);

        const elapsed = (Date.now() - startTime) / 1000;

        leaves.forEach(leaf => {
            leaf.update(elapsed);
            leaf.draw(ctx);
        });

        // Trigger Logo & Text Reveal at t = 1.5s
        if (elapsed >= 1.5 && !isConverging) {
            isConverging = true;
            document.getElementById('splash-logo-wrapper').classList.add('active');
            document.getElementById('splash-content').classList.add('visible');
            document.querySelector('.splash-loader-progress').style.width = '100%';
        }

        // Auto Dismiss at t = 3.6s
        if (elapsed < 3.6) {
            animFrameId = requestAnimationFrame(animate);
        } else {
            dismissSplashScreen();
        }
    }

    animFrameId = requestAnimationFrame(animate);

    // Global Dismiss Function
    window.dismissSplashScreen = function() {
        if (animFrameId) cancelAnimationFrame(animFrameId);
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 800);
    };
})();
</script>
