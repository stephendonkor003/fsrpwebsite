(() => {
    const body = document.body;
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navigation = document.querySelector('[data-navigation]');
    const languageTrigger = document.querySelector('[data-language-trigger]');
    const languageMenu = languageTrigger?.closest('.language-menu');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const closeNavigation = () => {
        body.classList.remove('nav-open');
        navToggle?.setAttribute('aria-expanded', 'false');
    };
    const closeLanguageMenu = () => {
        languageMenu?.classList.remove('open');
        languageTrigger?.setAttribute('aria-expanded', 'false');
    };
    navToggle?.addEventListener('click', () => {
        const open = !body.classList.contains('nav-open');
        body.classList.toggle('nav-open', open);
        navToggle.setAttribute('aria-expanded', String(open));
    });
    navigation?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeNavigation));
    languageTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = !languageMenu.classList.contains('open');
        languageMenu.classList.toggle('open', open);
        languageTrigger.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', (event) => {
        if (!languageMenu?.contains(event.target)) closeLanguageMenu();
        if (body.classList.contains('nav-open') && !navigation?.contains(event.target) && !navToggle?.contains(event.target)) closeNavigation();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const wasOpen = body.classList.contains('nav-open');
            closeNavigation();
            closeLanguageMenu();
            if (wasOpen) navToggle?.focus();
        }
    });
    window.matchMedia('(min-width: 1051px)').addEventListener('change', (event) => {
        if (event.matches) closeNavigation();
    });

    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const slides = [...carousel.querySelectorAll('[data-slide]')];
        if (!slides.length) return;
        const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
        const pauseButton = carousel.querySelector('[data-carousel-pause]');
        let current = 0;
        let paused = reduceMotion.matches;
        let inView = true;
        let hovering = false;
        let focused = false;
        let timer;

        const syncMedia = () => {
            slides.forEach((slide, index) => {
                const video = slide.querySelector('[data-hero-video]');
                if (!video) return;
                if (index === current && inView && !paused && !document.hidden) {
                    if (!video.getAttribute('src')) video.src = video.dataset.src;
                    video.play().catch(() => {});
                } else {
                    video.pause();
                }
            });
        };
        const show = (index) => {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === current;
                slide.classList.toggle('active', active);
                slide.setAttribute('aria-hidden', String(!active));
                slide.inert = !active;
            });
            dots.forEach((dot, index) => {
                const active = index === current;
                dot.classList.toggle('active', active);
                dot.setAttribute('aria-pressed', String(active));
            });
            syncMedia();
        };
        const schedule = () => {
            window.clearInterval(timer);
            if (!paused && inView && !hovering && !focused && !document.hidden && slides.length > 1) {
                timer = window.setInterval(() => show(current + 1), 9000);
            }
        };
        const updatePause = () => {
            pauseButton?.classList.toggle('paused', paused);
            pauseButton?.setAttribute('aria-label', paused ? pauseButton.dataset.playLabel : pauseButton.dataset.pauseLabel);
            pauseButton?.setAttribute('aria-pressed', String(paused));
            syncMedia();
            schedule();
        };
        carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => { show(current - 1); schedule(); });
        carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => { show(current + 1); schedule(); });
        dots.forEach((dot, index) => dot.addEventListener('click', () => { show(index); schedule(); }));
        pauseButton?.addEventListener('click', () => { paused = !paused; updatePause(); });
        carousel.addEventListener('mouseenter', () => { hovering = true; schedule(); });
        carousel.addEventListener('mouseleave', () => { hovering = false; schedule(); });
        carousel.addEventListener('focusin', () => { focused = true; schedule(); });
        carousel.addEventListener('focusout', (event) => { focused = carousel.contains(event.relatedTarget); schedule(); });
        carousel.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                event.preventDefault();
                const forward = event.key === 'ArrowRight';
                show(current + (document.documentElement.dir === 'rtl' ? (forward ? -1 : 1) : (forward ? 1 : -1)));
                schedule();
            }
        });
        document.addEventListener('visibilitychange', () => { syncMedia(); schedule(); });
        reduceMotion.addEventListener('change', (event) => { paused = event.matches; updatePause(); });
        new IntersectionObserver(([entry]) => {
            inView = entry.isIntersecting;
            syncMedia();
            schedule();
        }, { threshold: 0.1 }).observe(carousel);
        updatePause();
        show(0);
    });

    const featureVideos = [...document.querySelectorAll('[data-feature-video]')];
    featureVideos.forEach((video) => {
        video.addEventListener('play', () => {
            featureVideos.forEach((other) => { if (other !== video) other.pause(); });
        });
        new IntersectionObserver(([entry]) => {
            if (!entry.isIntersecting) video.pause();
        }, { threshold: 0.05 }).observe(video);
    });

    const countdowns = [...document.querySelectorAll('[data-countdown]')];
    if (countdowns.length) {
        const formatNumber = (value) => String(Math.max(0, value)).padStart(2, '0');
        const updateCountdowns = () => {
            const now = Date.now();

            countdowns.forEach((counter) => {
                const target = Date.parse(counter.dataset.countdownTarget || '');
                const grid = counter.querySelector('.countdown-grid');
                const finished = counter.querySelector('[data-countdown-finished]');

                if (!grid || !finished || Number.isNaN(target)) {
                    if (grid) {
                        grid.hidden = true;
                    }
                    if (finished) {
                        finished.hidden = true;
                    }
                    return;
                }

                const diff = target - now;

                if (diff <= 0) {
                    grid.hidden = true;
                    finished.hidden = false;
                    return;
                }

                const totalSeconds = Math.floor(diff / 1000);
                const days = Math.floor(totalSeconds / 86400);
                const hours = Math.floor((totalSeconds % 86400) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                grid.hidden = false;
                finished.hidden = true;

                counter.querySelector('[data-countdown-part="days"]').textContent = formatNumber(days);
                counter.querySelector('[data-countdown-part="hours"]').textContent = formatNumber(hours);
                counter.querySelector('[data-countdown-part="minutes"]').textContent = formatNumber(minutes);
                counter.querySelector('[data-countdown-part="seconds"]').textContent = formatNumber(seconds);
            });
        };

        updateCountdowns();
        window.setInterval(updateCountdowns, 1000);
    }

    document.querySelectorAll('[data-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            const previous = button.textContent;
            try {
                await navigator.clipboard.writeText(window.location.href);
                button.textContent = button.dataset.successLabel || 'Link copied';
                window.setTimeout(() => { button.textContent = previous; }, 1800);
            } catch {
                window.prompt(button.dataset.promptLabel || 'Copy this link', window.location.href);
            }
        });
    });
    document.querySelectorAll('[data-speaker-open]').forEach((button) => {
        const modal = document.getElementById(button.dataset.speakerOpen);

        button.addEventListener('click', () => modal?.showModal());
        modal?.querySelector('[data-speaker-close]')?.addEventListener('click', () => modal.close());
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) modal.close();
        });
    });
    document.querySelectorAll('[data-africa-map]').forEach((map) => {
        const countries = [...map.querySelectorAll('[data-map-country]')];
        const regionButtons = [...map.querySelectorAll('[data-region-filter]')];
        const picker = map.querySelector('[data-country-picker]');
        const selectionName = map.querySelector('[data-selection-name]');
        const selectionRegion = map.querySelector('[data-selection-region]');
        const selectionDetail = map.querySelector('[data-selection-detail]');
        const initial = { name: selectionName.textContent, region: selectionRegion.textContent, detail: selectionDetail.textContent };
        const coverage = map.querySelector('.coverage-statement').textContent.trim();
        let selectedRegion = 'all';
        let selectedCountry = '';

        const showDetails = (code = selectedCountry) => {
            const country = countries.find((item) => item.dataset.mapCountry === code);
            const regionButton = regionButtons.find((item) => item.dataset.regionFilter === selectedRegion);
            selectionName.textContent = country?.dataset.name || (selectedRegion === 'all' ? initial.name : regionButton.querySelectorAll('span')[1].textContent);
            selectionRegion.textContent = country?.dataset.regionName || initial.region;
            selectionDetail.textContent = country ? coverage : (selectedRegion === 'all' ? initial.detail : regionButton.dataset.summary);
        };
        const update = () => {
            countries.forEach((country) => {
                country.classList.toggle('muted', selectedRegion !== 'all' && country.dataset.region !== selectedRegion);
                country.classList.toggle('selected', country.dataset.mapCountry === selectedCountry);
            });
            regionButtons.forEach((button) => {
                const active = button.dataset.regionFilter === selectedRegion;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', String(active));
            });
            picker.value = selectedCountry;
            showDetails();
        };
        const chooseCountry = (code) => {
            const country = countries.find((item) => item.dataset.mapCountry === code);
            selectedCountry = country?.dataset.mapCountry || '';
            selectedRegion = country?.dataset.region || 'all';
            update();
        };
        regionButtons.forEach((button) => button.addEventListener('click', () => {
            selectedRegion = button.dataset.regionFilter;
            selectedCountry = '';
            update();
        }));
        countries.forEach((country) => {
            country.addEventListener('mouseenter', () => showDetails(country.dataset.mapCountry));
            country.addEventListener('click', () => chooseCountry(country.dataset.mapCountry));
        });
        map.querySelector('svg').addEventListener('mouseleave', () => showDetails());
        picker.addEventListener('change', () => chooseCountry(picker.value));
    });

    const backToTop = document.querySelector('[data-back-to-top]');
    const updateBackToTop = () => backToTop?.classList.toggle('visible', window.scrollY > 650);
    window.addEventListener('scroll', updateBackToTop, { passive: true });
    updateBackToTop();
    backToTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion.matches ? 'instant' : 'smooth' }));
})();
