/* ═══════════════════════════════════════════════════════════════════════════
   CDHQ – Configuración de tema y comportamiento compartido del sitio público
   Debe cargarse DESPUÉS de cdn.tailwindcss.com y ANTES del cierre de </head>.
   ═══════════════════════════════════════════════════════════════════════════ */

/* ─── Paleta institucional para Tailwind ──────────────────────────────────── */
if (typeof tailwind !== 'undefined') {
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    /* Aqua institucional (logo CDHQ) */
                    brand: {
                        50:  '#f0fbfd', 100: '#dbf3fa', 200: '#b6e7f2', 300: '#82d5e8', 400: '#3fb5d3',
                        500: '#0e85a5', 600: '#0c7290', 700: '#0e5c75', 800: '#124b60', 900: '#143f51'
                    },
                    /* Verde esperanza (acento del logo) */
                    leaf: {
                        50:  '#eefbee', 100: '#d7f6d7', 200: '#aeeaae', 300: '#75d975', 400: '#33c433',
                        500: '#00ac00', 600: '#009300', 700: '#047604', 800: '#085d08', 900: '#094c09'
                    }
                }
            }
        }
    };
}

(function () {
    'use strict';

    /* ─── Menú móvil ──────────────────────────────────────────────────────── */
    function initMobileNav() {
        const navToggle = document.getElementById('navToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        if (!navToggle || !mobileMenu) return;

        function close() {
            mobileMenu.classList.add('hidden');
            mobileMenu.classList.remove('flex');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.innerHTML = '<i class="fas fa-bars"></i>';
        }
        navToggle.addEventListener('click', function () {
            if (mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.remove('hidden');
                mobileMenu.classList.add('flex');
                navToggle.setAttribute('aria-expanded', 'true');
                navToggle.innerHTML = '<i class="fas fa-xmark"></i>';
            } else {
                close();
            }
        });
        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', close);
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) close();
        });
    }

    /* ─── Botones magnéticos con brillo ───────────────────────────────────── */
    function initMagneticButtons() {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.magnetic-btn').forEach(function (btn) {
            const wrap = btn.closest('.magnetic-wrap') || btn;
            let raf = null;
            const strengthX = 8, strengthY = 8;

            function updateVars(mx, my, gx, gy, opacity) {
                btn.style.setProperty('--magnetic-x', mx + 'px');
                btn.style.setProperty('--magnetic-y', my + 'px');
                btn.style.setProperty('--glow-x', gx + '%');
                btn.style.setProperty('--glow-y', gy + '%');
                btn.style.setProperty('--glow-opacity', opacity);
            }

            function onMove(e) {
                if (reduceMotion || (e.pointerType && e.pointerType !== 'mouse')) return;
                const rect = btn.getBoundingClientRect();
                const offsetX = e.clientX - (rect.left + rect.width / 2);
                const offsetY = e.clientY - (rect.top + rect.height / 2);
                const mx = Math.max(-strengthX, Math.min(strengthX, offsetX * 0.14));
                const my = Math.max(-strengthY, Math.min(strengthY, offsetY * 0.18));
                const gx = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                const gy = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
                if (raf) cancelAnimationFrame(raf);
                raf = requestAnimationFrame(function () { updateVars(mx, my, gx, gy, 1); });
            }

            function onLeave() {
                if (raf) cancelAnimationFrame(raf);
                raf = requestAnimationFrame(function () { updateVars(0, 0, 50, 50, 0); });
            }

            function spawnRipple(x, y) {
                if (reduceMotion) return;
                const rect = btn.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height) * 1.7;
                const span = document.createElement('span');
                span.className = 'magnetic-ripple';
                span.style.width = size + 'px';
                span.style.height = size + 'px';
                span.style.left = (x - size / 2) + 'px';
                span.style.top = (y - size / 2) + 'px';
                btn.appendChild(span);
                span.addEventListener('animationend', function () { span.remove(); });
            }

            wrap.addEventListener('pointermove', onMove);
            wrap.addEventListener('pointerleave', onLeave);
            btn.addEventListener('pointerdown', function (e) {
                const rect = btn.getBoundingClientRect();
                spawnRipple(e.clientX - rect.left, e.clientY - rect.top);
            });
            btn.addEventListener('keydown', function (e) {
                if ((e.key === 'Enter' || e.key === ' ') && !e.repeat) {
                    const rect = btn.getBoundingClientRect();
                    spawnRipple(rect.width / 2, rect.height / 2);
                }
            });
        });
    }

    /* ─── Tarjetas con spotlight (barrido aqua → verde) ───────────────────── */
    function initGlowCards() {
        if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
        document.querySelectorAll('.glow-card').forEach(function (card) {
            let raf = null;
            card.addEventListener('pointermove', function (e) {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const xp = Math.max(0, Math.min(1, x / rect.width));
                if (raf) cancelAnimationFrame(raf);
                raf = requestAnimationFrame(function () {
                    card.style.setProperty('--mx', x + 'px');
                    card.style.setProperty('--my', y + 'px');
                    // 192 (aqua institucional) → 120 (verde esperanza)
                    card.style.setProperty('--hue', String(192 - xp * 72));
                });
            });
        });
    }

    /* ─── Acordeones ──────────────────────────────────────────────────────── */
    function initAccordion() {
        document.querySelectorAll('.acc-item').forEach(function (item) {
            const trigger = item.querySelector('.acc-trigger');
            if (!trigger) return;
            trigger.addEventListener('click', function () {
                const isOpen = item.classList.toggle('open');
                trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    }

    /* ─── Carrusel infinito de testimonios ────────────────────────────────── */
    function initCarousel() {
        const track = document.getElementById('testimonialTrack');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const dotsContainer = document.getElementById('carouselDots');
        if (!track || !prevBtn || !nextBtn || !dotsContainer) return;

        const originalSlides = Array.from(track.querySelectorAll('.carousel-slide'));
        const realCount = originalSlides.length;
        if (realCount === 0) return;

        const CLONE_COUNT = 3; // cubre el máximo de tarjetas visibles a la vez (escritorio)
        const firstOriginal = originalSlides[0];

        // Clona las últimas tarjetas al inicio y las primeras al final para un loop sin cortes
        originalSlides.slice(-CLONE_COUNT).forEach(function (slide) {
            const clone = slide.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            track.insertBefore(clone, firstOriginal);
        });
        originalSlides.slice(0, CLONE_COUNT).forEach(function (slide) {
            const clone = slide.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            track.appendChild(clone);
        });

        const allSlides = Array.from(track.querySelectorAll('.carousel-slide'));
        let slidesPerView = 1;
        let absoluteIndex = CLONE_COUNT; // apunta a la tarjeta real 0

        function getRealIndex() {
            return ((absoluteIndex - CLONE_COUNT) % realCount + realCount) % realCount;
        }
        function applyWidths() {
            const widthPercent = 100 / slidesPerView;
            allSlides.forEach(function (slide) { slide.style.flex = '0 0 ' + widthPercent + '%'; });
        }
        function render(withTransition) {
            const widthPercent = 100 / slidesPerView;
            track.style.transition = withTransition ? 'transform 0.5s ease-in-out' : 'none';
            track.style.transform = 'translateX(-' + (absoluteIndex * widthPercent) + '%)';
        }
        function updateDotsActive() {
            const realIndex = getRealIndex();
            dotsContainer.querySelectorAll('.dot').forEach(function (dot, idx) {
                dot.classList.toggle('active', idx === realIndex);
            });
        }
        function goToSlide(realIndex) {
            absoluteIndex = CLONE_COUNT + realIndex;
            render(true);
            updateDotsActive();
        }
        function createDots() {
            dotsContainer.innerHTML = '';
            for (let i = 0; i < realCount; i++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'dot';
                dot.setAttribute('aria-label', 'Ir al testimonio ' + (i + 1));
                if (i === getRealIndex()) dot.classList.add('active');
                dot.addEventListener('click', function () { goToSlide(i); });
                dotsContainer.appendChild(dot);
            }
        }
        function updateSlidesPerView() {
            const width = window.innerWidth;
            if (width >= 1024) slidesPerView = 3;
            else if (width >= 768) slidesPerView = 2;
            else slidesPerView = 1;
            applyWidths();
            render(false);
        }

        // Al llegar a la zona clonada, reposiciona sin transición para simular el loop infinito
        track.addEventListener('transitionend', function () {
            if (absoluteIndex >= CLONE_COUNT + realCount) {
                absoluteIndex -= realCount;
                render(false);
            } else if (absoluteIndex < CLONE_COUNT) {
                absoluteIndex += realCount;
                render(false);
            }
        });

        nextBtn.addEventListener('click', function () { absoluteIndex++; render(true); updateDotsActive(); });
        prevBtn.addEventListener('click', function () { absoluteIndex--; render(true); updateDotsActive(); });
        window.addEventListener('resize', updateSlidesPerView);

        createDots();
        updateSlidesPerView();
    }

    /* ─── Arranque ────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 800, once: true, offset: 80, easing: 'ease-out-quad' });
            window.addEventListener('load', function () { AOS.refresh(); });
        }
        initMobileNav();
        initMagneticButtons();
        initGlowCards();
        initAccordion();
        initCarousel();
    });
})();
