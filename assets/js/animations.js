/**
 * Scroll Reveal Animation Script - MicroClimeet (Agrovia-Style)
 * Uses IntersectionObserver for performant scroll-triggered animations.
 */
document.addEventListener('DOMContentLoaded', () => {

    // Scroll Reveal with IntersectionObserver
    const revealElements = document.querySelectorAll('.reveal');
    
    if (revealElements.length === 0) return;

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                // Also trigger stagger children if present
                if (entry.target.querySelector('.reveal-stagger')) {
                    entry.target.querySelector('.reveal-stagger').classList.add('revealed');
                }
                // Unobserve after revealing (one-time animation)
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => {
        revealObserver.observe(el);
    });

    // Also observe standalone stagger containers
    const staggerElements = document.querySelectorAll('.reveal-stagger');
    const staggerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                staggerObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -30px 0px'
    });

    staggerElements.forEach(el => {
        staggerObserver.observe(el);
    });

    // Smooth navbar background on scroll
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        let lastScrollY = 0;
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            if (scrollY > 50) {
                navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.08)';
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            } else {
                navbar.style.boxShadow = '0 4px 24px rgba(0, 0, 0, 0.06)';
                navbar.style.background = 'rgba(255, 255, 255, 0.88)';
            }
            lastScrollY = scrollY;
        }, { passive: true });
    }

    // Active nav link highlighting on scroll
    const sections = document.querySelectorAll('section[id], header[id]');
    const navLinks = document.querySelectorAll('.nav-links a');
    
    if (sections.length > 0 && navLinks.length > 0) {
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${id}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, {
            threshold: 0.3,
            rootMargin: '-80px 0px -50% 0px'
        });

        sections.forEach(section => {
            navObserver.observe(section);
        });
    }
});
