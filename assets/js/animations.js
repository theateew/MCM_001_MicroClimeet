/**
 * MicroClimeet - animations.js
 * Handles: Scroll Reveal, Navbar scroll state, Active nav highlighting, Mobile Dropdown Toggle
 */
document.addEventListener('DOMContentLoaded', () => {

    /* ========================================================
       1. SCROLL REVEAL (IntersectionObserver)
       ======================================================== */
    const revealElements = document.querySelectorAll('.reveal');

    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    const stagger = entry.target.querySelector('.reveal-stagger');
                    if (stagger) stagger.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        revealElements.forEach(el => revealObserver.observe(el));
    }

    // Standalone stagger containers
    const staggerElements = document.querySelectorAll('.reveal-stagger');
    if (staggerElements.length > 0) {
        const staggerObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    staggerObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

        staggerElements.forEach(el => staggerObserver.observe(el));
    }

    /* ========================================================
       2. NAVBAR SCROLL STATE
       ======================================================== */
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        }, { passive: true });
    }

    /* ========================================================
       3. ACTIVE NAV LINK HIGHLIGHTING ON SCROLL
       Updates both desktop (.nav-links a) and mobile (.mobile-nav-links a)
       ======================================================== */
    const sections = document.querySelectorAll('section[id], header[id]');
    const allNavLinks = document.querySelectorAll('.nav-links a, .mobile-nav-links a');

    if (sections.length > 0 && allNavLinks.length > 0) {
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    allNavLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${id}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, { threshold: 0.3, rootMargin: '-80px 0px -50% 0px' });

        sections.forEach(section => navObserver.observe(section));
    }

    /* ========================================================
       4. MOBILE HAMBURGER DROPDOWN TOGGLE
       - Toggle button is CENTERED in navbar (inside .nav-center)
       - Smooth slide-down + fade animation via CSS
       - Icon swaps between menu ↔ X
       - Closes on link click or outside click
       ======================================================== */
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileDropdown = document.getElementById('mobileDropdown');
    const iconMenu = mobileToggle ? mobileToggle.querySelector('.icon-menu') : null;
    const iconClose = mobileToggle ? mobileToggle.querySelector('.icon-close') : null;

    /**
     * Opens the mobile dropdown menu
     */
    function openMobileMenu() {
        if (!mobileDropdown || !mobileToggle) return;
        mobileDropdown.classList.add('open');
        mobileToggle.classList.add('active');
        mobileToggle.setAttribute('aria-expanded', 'true');
        mobileDropdown.setAttribute('aria-hidden', 'false');
        if (iconMenu) iconMenu.style.display = 'none';
        if (iconClose) iconClose.style.display = '';
    }

    /**
     * Closes the mobile dropdown menu
     */
    function closeMobileMenu() {
        if (!mobileDropdown || !mobileToggle) return;
        mobileDropdown.classList.remove('open');
        mobileToggle.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
        mobileDropdown.setAttribute('aria-hidden', 'true');
        if (iconMenu) iconMenu.style.display = '';
        if (iconClose) iconClose.style.display = 'none';
    }

    if (mobileToggle && mobileDropdown) {
        // Toggle on hamburger button click
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (mobileDropdown.classList.contains('open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        // Close when any mobile nav link is tapped
        mobileDropdown.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                closeMobileMenu();
            });
        });

        // Close when clicking anywhere outside the nav-center area
        document.addEventListener('click', (e) => {
            const navCenter = document.querySelector('.nav-center');
            if (navCenter && !navCenter.contains(e.target)) {
                closeMobileMenu();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMobileMenu();
        });
    }

});
