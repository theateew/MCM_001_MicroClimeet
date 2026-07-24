/**
 * Website Showcase Carousel Script - MicroClimeet
 * Lightweight Vanilla JS carousel with autoplay, pause-on-hover, touch swipe & dot navigation.
 */
document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.querySelector('.glass-carousel');
    if (!carousel) return;

    const track = carousel.querySelector('.carousel-track');
    const slides = Array.from(track.children);
    const nextBtn = carousel.querySelector('.carousel-btn.next');
    const prevBtn = carousel.querySelector('.carousel-btn.prev');
    const dotsNav = carousel.querySelector('.carousel-dots');
    const dots = Array.from(dotsNav.children);

    let currentIndex = 0;
    let autoplayTimer = null;
    const intervalTime = 5000;

    // Position slides side-by-side
    const updateSlidePosition = () => {
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        
        slides.forEach((slide, index) => {
            if (index === currentIndex) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
        });

        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    };

    const goToSlide = (index) => {
        if (index < 0) {
            currentIndex = slides.length - 1;
        } else if (index >= slides.length) {
            currentIndex = 0;
        } else {
            currentIndex = index;
        }
        updateSlidePosition();
    };

    const nextSlide = () => {
        goToSlide(currentIndex + 1);
    };

    const prevSlide = () => {
        goToSlide(currentIndex - 1);
    };

    // Event Listeners
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAutoplay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAutoplay();
        });
    }

    dotsNav.addEventListener('click', (e) => {
        const targetDot = e.target.closest('.carousel-dot');
        if (!targetDot) return;

        const targetIndex = dots.indexOf(targetDot);
        if (targetIndex !== -1) {
            goToSlide(targetIndex);
            resetAutoplay();
        }
    });

    // Autoplay feature
    const startAutoplay = () => {
        stopAutoplay();
        autoplayTimer = setInterval(nextSlide, intervalTime);
    };

    const stopAutoplay = () => {
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
    };

    const resetAutoplay = () => {
        startAutoplay();
    };

    // Pause on hover
    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    // Touch Swipe Support for mobile devices
    let touchStartX = 0;
    let touchEndX = 0;

    track.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    track.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });

    const handleSwipe = () => {
        const swipeThreshold = 40;
        if (touchEndX < touchStartX - swipeThreshold) {
            nextSlide();
            resetAutoplay();
        } else if (touchEndX > touchStartX + swipeThreshold) {
            prevSlide();
            resetAutoplay();
        }
    };

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        // Check if carousel is in viewport
        const rect = carousel.getBoundingClientRect();
        const isInViewport = rect.top >= 0 && rect.bottom <= window.innerHeight;
        
        if (isInViewport) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                resetAutoplay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                resetAutoplay();
            }
        }
    });

    // Initialize position & start autoplay
    updateSlidePosition();
    startAutoplay();
});
