export function enhanceHeroBoard(root) {
    const container = root.querySelector('[data-crossfade-video]');
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    if (container && !reducedMotion.matches) {
        const videos = [...container.querySelectorAll('video')];
        const poster = container.querySelector('img');
        let active = 0;
        let switching = false;
        let visible = true;
        let ready = false;
        const source = () => container.dataset.videoSrc.replace(/hero-ai-(light|dark)(?:-optimized)?\.mp4/g,
            `hero-ai-${document.documentElement.classList.contains('dark') ? 'dark' : 'light'}-optimized.mp4`);
        const play = async () => {
            if (!ready || !visible || document.hidden || reducedMotion.matches) return;
            const video = videos[active];
            if (!video) return;
            if (video.getAttribute('src') !== source()) video.src = source();
            try { await video.play(); } catch { /* The poster remains visible when autoplay is unavailable. */ }
        };
        videos.forEach((video, index) => {
            video.muted = true;
            video.playsInline = true;
            video.style.opacity = '0';
            video.addEventListener('playing', () => {
                video.style.opacity = '1';
                if (switching) {
                    const previous = videos[1 - index];
                    previous.style.opacity = '0';
                    previous.pause();
                    switching = false;
                }
            });
            video.addEventListener('timeupdate', async () => {
                if (index !== active || switching || !Number.isFinite(video.duration)) return;
                const other = videos[1 - index];
                if (!other) return;
                if (video.duration - video.currentTime <= 4 && other.getAttribute('src') !== source()) {
                    other.src = source();
                    other.preload = 'auto';
                }
                if (video.duration - video.currentTime <= 0.8 && other.readyState >= 2) {
                    switching = true;
                    other.currentTime = 0;
                    active = 1 - index;
                    try { await other.play(); } catch { active = index; switching = false; }
                }
            });
            video.addEventListener('ended', () => { if (index === active) { video.currentTime = 0; play(); } });
        });
        const pause = () => videos.forEach(video => video.pause());
        new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            visible ? play() : pause();
        }).observe(container);
        document.addEventListener('visibilitychange', () => document.hidden ? pause() : play());
        reducedMotion.addEventListener('change', () => {
            if (reducedMotion.matches) { pause(); videos.forEach(video => video.style.opacity = '0'); }
            else play();
        });
        window.addEventListener('findo:theme', () => {
            pause();
            videos.forEach(video => { video.style.opacity = '0'; video.removeAttribute('src'); video.load(); });
            active = 0;
            switching = false;
            play();
        });
        const start = async () => {
            try { await poster?.decode(); } catch { /* Video enhancement does not block the static page. */ }
            requestAnimationFrame(() => requestAnimationFrame(() => { ready = true; play(); }));
        };
        if (document.readyState === 'complete') start();
        else window.addEventListener('load', start, { once: true });
    }

    const carousel = root.querySelector('[aria-roledescription="carousel"]');
    const rail = carousel?.querySelector('.overflow-hidden');
    const slides = [...(carousel?.querySelectorAll('[aria-roledescription="slide"]') || [])];
    if (rail && slides.length && !reducedMotion.matches) {
        let hovering = false;
        let inView = false;
        let index = 0;
        carousel.addEventListener('mouseenter', () => hovering = true);
        carousel.addEventListener('mouseleave', () => hovering = false);
        carousel.addEventListener('focusin', () => hovering = true);
        carousel.addEventListener('focusout', () => hovering = false);
        new IntersectionObserver(([entry]) => inView = entry.isIntersecting).observe(carousel);
        setInterval(() => {
            if (hovering || !inView || document.hidden || reducedMotion.matches) return;
            const width = slides[0].getBoundingClientRect().width;
            const visibleCount = Math.max(1, Math.round(rail.clientWidth / width));
            index = index >= slides.length - visibleCount ? 0 : index + 1;
            rail.scrollTo({ left: (root.querySelector('[dir="rtl"]') ? -1 : 1) * index * width, behavior: 'smooth' });
        }, 2000);
    }
}
